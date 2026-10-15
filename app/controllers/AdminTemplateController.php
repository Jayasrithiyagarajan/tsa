<?php

final class AdminTemplateController extends Controller
{
    public function index(): void
    {
        $types = DB::all("SELECT t.*,
              (SELECT id FROM checklist_templates c WHERE c.inspection_type_id = t.id AND c.is_active = 1 ORDER BY version DESC LIMIT 1) cl_id,
              (SELECT CONCAT(code, ' · v', version) FROM checklist_templates c WHERE c.inspection_type_id = t.id AND c.is_active = 1 ORDER BY version DESC LIMIT 1) cl_label,
              (SELECT COUNT(*) FROM checklist_template_items i JOIN checklist_templates c ON c.id = i.template_id WHERE c.inspection_type_id = t.id AND c.is_active = 1) cl_items,
              (SELECT id FROM certificate_templates c WHERE c.inspection_type_id = t.id AND c.is_active = 1 ORDER BY version DESC LIMIT 1) ct_id,
              (SELECT CONCAT(title, ' · v', version) FROM certificate_templates c WHERE c.inspection_type_id = t.id AND c.is_active = 1 ORDER BY version DESC LIMIT 1) ct_label,
              (SELECT COUNT(*) FROM sub_jobs s WHERE s.inspection_type_id = t.id) used
            FROM inspection_types t ORDER BY t.category, t.name");
        $this->view('admin/templates/index', ['types' => $types, 'title' => 'Templates', 'crumbs' => ['Templates' => null]]);
    }

    public function saveType(): void
    {
        $errors = validate($_POST, ['code' => 'required|max:20', 'name' => 'required|max:120', 'category' => 'required|max:60', 'validity_months' => 'required|int']);
        if ($errors) $this->failValidation($errors);
        $this->attempt(function () {
            $id = (int) input('id');
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) input('code')));
            if (DB::value('SELECT id FROM inspection_types WHERE code = ? AND id <> ?', [$code, $id])) throw new DomainException("Code $code is already used.");
            $data = [
                'code' => $code, 'name' => input('name'), 'category' => input('category'),
                'procedure_ref' => nullable(input('procedure_ref')), 'standard_ref' => nullable(input('standard_ref')),
                'validity_months' => max(1, min(60, (int) input('validity_months'))),
                'requires_checklist' => (int) !empty($_POST['requires_checklist']), 'requires_certificate' => (int) !empty($_POST['requires_certificate']),
                'requires_sticker' => (int) !empty($_POST['requires_sticker']), 'status' => input('status') === 'inactive' ? 'inactive' : 'active',
            ];
            if ($id) { DB::update('inspection_types', $data, 'id = ?', [$id]); Audit::log('type.updated', 'inspection_type', $id); }
            else { $id = DB::insert('inspection_types', $data); Audit::log('type.created', 'inspection_type', $id); }
        }, 'Inspection type saved.');
    }

    /** {id} is the inspection type id; edits the active template (or creates the first one). */
    public function checklist(string $id): void
    {
        $type = DB::one('SELECT * FROM inspection_types WHERE id = ?', [(int) $id]);
        if (!$type) $this->notFound();
        $tpl = InspectionService::activeChecklistTemplate((int) $id);
        $items = $tpl ? DB::all('SELECT * FROM checklist_template_items WHERE template_id = ? ORDER BY sort_order', [$tpl['id']]) : [];
        $versions = DB::all('SELECT t.*, (SELECT COUNT(*) FROM checklists c WHERE c.template_id = t.id) used FROM checklist_templates t WHERE inspection_type_id = ? ORDER BY version DESC', [(int) $id]);
        $this->view('admin/templates/checklist', compact('type', 'tpl', 'items', 'versions') + [
            'title' => 'Checklist template', 'crumbs' => ['Templates' => 'admin/templates', $type['name'] => null, 'Checklist' => null],
        ]);
    }

    public function saveChecklist(string $id): void
    {
        $this->attempt(function () use ($id) {
            $sections = [];
            foreach ((array) ($_POST['items'] ?? []) as $it) {
                $sec = trim($it['section'] ?? '') ?: 'General Requirements';
                $sections[$sec][] = ['label' => $it['label'] ?? '', 'mandatory' => !empty($it['mandatory'])];
            }
            $tpl = InspectionService::activeChecklistTemplate((int) $id);
            $newId = TemplateService::saveChecklist($tpl['id'] ?? null, (int) $id, $_POST, $sections);
            $versioned = $tpl && $newId !== (int) $tpl['id'];
            flash('success', $versioned ? 'Template was in use — saved as a new version. Existing checklists keep the previous version.' : 'Checklist template saved.');
        });
    }

    public function certificate(string $id): void
    {
        $type = DB::one('SELECT * FROM inspection_types WHERE id = ?', [(int) $id]);
        if (!$type) $this->notFound();
        $tpl = InspectionService::activeCertificateTemplate((int) $id);
        $versions = DB::all('SELECT t.id, t.version, t.title, t.created_at, t.is_active, (SELECT COUNT(*) FROM certificates c WHERE c.template_id = t.id) used FROM certificate_templates t WHERE inspection_type_id = ? ORDER BY version DESC', [(int) $id]);
        $this->view('admin/templates/certificate', compact('type', 'tpl', 'versions') + [
            'fields' => $tpl ? json_decode($tpl['fields'], true) : [],
            'title' => 'Certificate template', 'crumbs' => ['Templates' => 'admin/templates', $type['name'] => null, 'Certificate' => null],
        ]);
    }

    public function saveCertificate(string $id): void
    {
        $this->attempt(function () use ($id) {
            $tpl = InspectionService::activeCertificateTemplate((int) $id);
            $newId = TemplateService::saveCertificate($tpl['id'] ?? null, (int) $id, $_POST, array_values((array) ($_POST['fields'] ?? [])));
            $versioned = $tpl && $newId !== (int) $tpl['id'];
            flash('success', $versioned ? 'Template was in use — saved as a new version. Issued certificates keep the previous layout.' : 'Certificate template saved.');
        });
    }

    /** Render a template with sample data in the real document layout. */
    public function preview(string $kind, string $id): void
    {
        $sample = [
            'id' => 0, 'job_id' => 0, 'sub_job_no' => 'SAMPLE', 'client_name' => 'Sample Client Co.', 'location' => 'Site location', 'po_no' => null,
            'procedure_ref' => '', 'standard_ref' => '', 'eq_description' => 'EQUIPMENT', 'equipment_no' => 'EQ-01', 'eq_serial_no' => 'SN-0001',
            'manufacturer' => 'Manufacturer', 'eq_model' => 'Model', 'eq_capacity' => '5 Ton', 'eq_year' => '2024', 'inspector_id' => null,
        ];
        if ($kind === 'checklist') {
            $tpl = DB::one('SELECT * FROM checklist_templates WHERE id = ?', [(int) $id]);
            if (!$tpl) $this->notFound();
            $items = DB::all('SELECT *, NULL result, NULL remarks FROM checklist_template_items WHERE template_id = ? ORDER BY sort_order', [$tpl['id']]);
            $html = View::partial('documents/checklist', ['m' => [
                'sj' => $sample, 'template' => $tpl, 'items' => $items, 'certificate_no' => 'YYYY-CCCCC-JJJJ-SSS',
                'checklist' => ['serial_no' => '', 'status' => 'draft', 'inspection_date' => null], 'inspector' => ['name' => 'Inspector Name', 'signature' => null],
            ], 'toolbar' => empty($_GET['pdf']) ? ['back' => url('admin/templates/checklist/' . $tpl['inspection_type_id']), 'pdf' => '?pdf=1', 'status' => 'Template preview'] : null]);
            empty($_GET['pdf']) ? print($html) : DocumentService::pdf($html, $tpl['doc_ref'] . ' blank.pdf', false);
            return;
        }
        $tpl = DB::one('SELECT * FROM certificate_templates WHERE id = ?', [(int) $id]);
        if (!$tpl) $this->notFound();
        $type = DB::one('SELECT * FROM inspection_types WHERE id = ?', [$tpl['inspection_type_id']]);
        $sample['procedure_ref'] = $type['procedure_ref'];
        $sample['standard_ref'] = $type['standard_ref'];
        $cert = ['certificate_no' => 'YYYY-CCCCC-JJJJ-SSS', 'issue_date' => date('Y-m-d'), 'inspection_date' => date('Y-m-d'),
            'next_inspection_date' => InspectionService::nextDate(date('Y-m-d'), (int) $type['validity_months']), 'result' => 'accepted',
            'result_statement' => $tpl['default_statement'], 'remarks' => '', 'status' => 'draft'];
        $fields = [];
        foreach (json_decode($tpl['fields'], true) ?: [] as $f) {
            $f['value'] = !empty($f['source']) ? CertificateService::fieldValue($f, $sample, $cert, []) : '‹' . $f['label'] . '›';
            $fields[] = $f;
        }
        $html = View::partial('documents/certificate', ['m' => [
            'sj' => $sample, 'cert' => $cert, 'template' => $tpl, 'fields' => $fields,
            'notes' => array_values(array_filter(array_map('trim', explode("\n", (string) $tpl['notes'])))),
            'options' => explode(',', $tpl['result_options']), 'inspector' => ['name' => 'Inspector Name', 'qualification' => 'Level II', 'signature' => null],
            'reviewer' => null, 'photos' => [], 'issued' => false, 'qr' => DocumentService::qr(abs_url('verify/SAMPLE')), 'verify_url' => '',
        ], 'toolbar' => empty($_GET['pdf']) ? ['back' => url('admin/templates/certificate/' . $tpl['inspection_type_id']), 'pdf' => '?pdf=1', 'status' => 'Template preview'] : null]);
        empty($_GET['pdf']) ? print($html) : DocumentService::pdf($html, $tpl['doc_ref'] . ' sample.pdf', false);
    }
}
