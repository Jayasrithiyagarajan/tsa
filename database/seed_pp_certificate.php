<?php
// F-7.5.1-CL 11 Petroleum Products Transportation Tanks as a certificate template (layout 'pp').
// Returns a builder: it takes the CL 11 form definition (sections + items, text verbatim) and turns every form row into a Yes / No / N/A field
// ('section' / 'item' keep the form text used by documents/certificate_pp.php and by the checklist-style rows of the inspector form).
return function (array $chk): array {
    $head = [
        ['key' => 'report_no', 'label' => 'Report No.', 'source' => 'cert.certificate_no'],
        ['key' => 'report_date', 'label' => 'Report Date', 'source' => 'cert.issue_date'],
        ['key' => 'client', 'label' => 'Client Name', 'source' => 'client.name'],
        ['key' => 'site', 'label' => 'Address of the site', 'source' => 'job.location'],
        ['key' => 'inspection_date', 'label' => 'Date of inspection', 'source' => 'cert.inspection_date'],
        ['key' => 'odometer', 'label' => 'Odometer', 'type' => 'text', 'required' => false],
        ['key' => 'specs', 'label' => 'Specifications', 'source' => 'type.standard_ref'],
        ['key' => 'vin', 'label' => 'Vin Number', 'source' => 'equipment.equipment_no'],
        ['key' => 'eq_desc', 'label' => 'Vehicle Type (Description)', 'source' => 'equipment.description'],
        ['key' => 'model', 'label' => 'Model', 'source' => 'equipment.model'],
        ['key' => 'color', 'label' => 'Color', 'type' => 'text', 'required' => false],
        ['key' => 'origin', 'label' => 'Country Made', 'type' => 'text', 'required' => false],
        ['key' => 'brand', 'label' => 'Manufacturer Name (Brand)', 'source' => 'equipment.manufacturer'],
        ['key' => 'year', 'label' => 'Year of manufacture', 'source' => 'equipment.year_built'],
    ];
    $items = [];
    $n = 0;
    foreach ($chk['sections'] as $section => $rows) {
        foreach ($rows as $row) {
            $text = is_array($row) ? (string) $row['label'] : (string) $row;
            $items[] = [
                'key' => sprintf('i%03d', ++$n),
                'label' => mb_strimwidth(trim(preg_replace('/\s*\R>>\s*/u', ' ', $text)), 0, 60, '…'),
                'type' => 'select', 'options' => ['Yes', 'No', 'N/A'], 'required' => true,
                'section' => $section, 'item' => $text,
            ];
        }
    }
    return [
        'code' => 'F-7.5.1-CL 11', 'title' => 'Petroleum Products Transportation Tanks Inspection Report', 'doc_ref' => 'F-7.5.1-CL 11',
        'revision' => '02', 'revision_date' => '09/Feb./23', 'layout' => 'pp', 'inspector_label' => 'Inspector Name:',
        'default_statement' => '',
        'fields' => array_merge($head, $items),
        'notes' => '',
    ];
};
