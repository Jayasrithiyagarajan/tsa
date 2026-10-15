<?php

final class RequestService
{
    /**
     * Record a client request from any channel.
     * @param array $data  company_name, contact_name, email, phone, location, po_no, preferred_date, description, client_id?
     * @param array $items [['inspection_type_id'=>, 'equipment_description'=>, 'equipment_no'=>, 'quantity'=>, 'notes'=>], …]
     */
    public static function create(array $data, array $items, string $channel): int
    {
        return DB::tx(function () use ($data, $items, $channel) {
            $clientId = $data['client_id'] ?? null;
            if (!$clientId && !empty($data['email'])) {
                // auto-match a registered client by email so the admin does not have to
                $clientId = DB::value('SELECT id FROM clients WHERE email = ? LIMIT 1', [strtolower($data['email'])]);
            }
            $id = DB::insert('service_requests', [
                'request_no'     => Numbering::request(),
                'channel'        => $channel,
                'client_id'      => $clientId ?: null,
                'company_name'   => $data['company_name'],
                'contact_name'   => $data['contact_name'],
                'email'          => nullable(strtolower($data['email'] ?? '')),
                'phone'          => nullable($data['phone'] ?? null),
                'location'       => nullable($data['location'] ?? null),
                'po_no'          => nullable($data['po_no'] ?? null),
                'preferred_date' => nullable($data['preferred_date'] ?? null),
                'description'    => nullable($data['description'] ?? null),
                'received_by'    => in_array($channel, ['email', 'phone'], true) ? Auth::id() : null,
                'submitted_by'   => $channel === 'portal' ? Auth::id() : null,
            ]);
            foreach ($items as $it) {
                if (trim($it['equipment_description'] ?? '') === '') {
                    continue;
                }
                DB::insert('service_request_items', [
                    'request_id'            => $id,
                    'inspection_type_id'    => nullable($it['inspection_type_id'] ?? null),
                    'equipment_description' => mb_substr(trim($it['equipment_description']), 0, 190),
                    'equipment_no'          => nullable($it['equipment_no'] ?? null),
                    'quantity'              => max(1, min(500, (int) ($it['quantity'] ?? 1))),
                    'notes'                 => nullable($it['notes'] ?? null),
                ]);
            }
            $no = DB::value('SELECT request_no FROM service_requests WHERE id = ?', [$id]);
            Audit::log('request.created', 'service_request', $id, ['channel' => $channel, 'request_no' => $no]);
            Notify::role('admin', "New inspection request $no", $data['company_name'] . ' via ' . Workflow::label($channel), 'admin/requests/' . $id);
            return $id;
        });
    }

    public static function changeStatus(int $id, string $to, ?string $note = null): void
    {
        $r = DB::one('SELECT * FROM service_requests WHERE id = ?', [$id]);
        if (!$r) throw new DomainException('Request not found.');
        if ($to === 'converted') throw new DomainException('Use "Convert to Job" to convert a request.');
        Workflow::assert('request', $r['status'], $to);
        DB::update('service_requests', ['status' => $to, 'status_note' => nullable($note)], 'id = ?', [$id]);
        Audit::log('request.status', 'service_request', $id, ['from' => $r['status'], 'to' => $to, 'note' => $note]);
        if ($r['submitted_by']) {
            Notify::user((int) $r['submitted_by'], "Request {$r['request_no']} is " . Workflow::label($to), $note, 'portal/requests');
        }
    }

    /**
     * Convert a request into a Job. If the requester is not a registered client yet, a client record is
     * created from the request details. Each request item × quantity becomes one sub-job.
     */
    public static function convert(int $id, array $job, array $subJobs, ?array $newClient = null): int
    {
        return DB::tx(function () use ($id, $job, $subJobs, $newClient) {
            $r = DB::one('SELECT * FROM service_requests WHERE id = ? FOR UPDATE', [$id]);
            if (!$r) throw new DomainException('Request not found.');
            Workflow::assert('request', $r['status'], 'converted');

            $clientId = (int) ($job['client_id'] ?? 0);
            if (!$clientId) {
                if (!$newClient || trim($newClient['code'] ?? '') === '') {
                    throw new DomainException('Select an existing client or enter a client code to register a new one.');
                }
                $clientId = ClientService::create([
                    'code'           => $newClient['code'],
                    'name'           => $r['company_name'],
                    'contact_person' => $r['contact_name'],
                    'email'          => $r['email'],
                    'phone'          => $r['phone'],
                    'city'           => $newClient['city'] ?? null,
                ]);
            }
            $jobId = JobService::create(array_merge($job, [
                'client_id'  => $clientId,
                'request_id' => $id,
            ]), $subJobs);

            DB::update('service_requests', [
                'status' => 'converted', 'client_id' => $clientId, 'converted_job_id' => $jobId,
            ], 'id = ?', [$id]);
            Audit::log('request.converted', 'service_request', $id, ['job_id' => $jobId]);
            if ($r['submitted_by']) {
                $jobNo = DB::value('SELECT job_no FROM jobs WHERE id = ?', [$jobId]);
                Notify::user((int) $r['submitted_by'], "Request {$r['request_no']} accepted", "Job $jobNo has been opened.", 'portal/jobs/' . $jobId);
            }
            return $jobId;
        });
    }
}

final class ClientService
{
    public static function create(array $d): int
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $d['code']));
        if ($code === '') throw new DomainException('Client code is required.');
        if (DB::value('SELECT id FROM clients WHERE code = ?', [$code])) {
            throw new DomainException("Client code $code is already used.");
        }
        $id = DB::insert('clients', [
            'code'           => $code,
            'name'           => $d['name'],
            'contact_person' => nullable($d['contact_person'] ?? null),
            'email'          => nullable(strtolower($d['email'] ?? '')),
            'phone'          => nullable($d['phone'] ?? null),
            'address'        => nullable($d['address'] ?? null),
            'city'           => nullable($d['city'] ?? null),
            'vat_no'         => nullable($d['vat_no'] ?? null),
        ]);
        Audit::log('client.created', 'client', $id, ['code' => $code]);
        return $id;
    }
}
