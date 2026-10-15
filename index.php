<?php
require __DIR__ . '/app/bootstrap.php';

$r = new Router();
$A = ['admin'];
$I = ['inspector'];
$Q = ['qc'];
$C = ['customer'];
$STAFF = ['admin', 'qc'];

// ---------------------------------------------------------------- public website channel
$r->get('/',                      [PublicController::class, 'home']);
$r->get('/request',               [PublicController::class, 'requestForm']);
$r->post('/request',              [PublicController::class, 'requestSubmit']);
$r->get('/request/thanks/{no}',   [PublicController::class, 'requestThanks']);
$r->get('/verify',                [PublicController::class, 'verify']);
$r->get('/verify/card/{token}',   [PublicController::class, 'verifyCard']);
$r->get('/verify/{token}',        [PublicController::class, 'verifyToken']);
$r->get('/login',                 [AuthController::class, 'form']);
$r->post('/login',                [AuthController::class, 'login']);
$r->post('/logout',               [AuthController::class, 'logout'], 'auth');

// ---------------------------------------------------------------- shared (signed-in)
$r->get('/profile',                         [ProfileController::class, 'show'], 'auth');
$r->post('/profile',                        [ProfileController::class, 'update'], 'auth');
$r->post('/profile/password',               [ProfileController::class, 'password'], 'auth');
$r->post('/profile/signature',              [ProfileController::class, 'signature'], 'auth');
$r->get('/notifications',                   [ProfileController::class, 'notifications'], 'auth');
$r->get('/notifications/{id}',              [ProfileController::class, 'openNotification'], 'auth');
$r->post('/notifications/read-all',         [ProfileController::class, 'readAll'], 'auth');
$r->get('/documents/checklist/{id}',        [DocumentController::class, 'checklist'], 'auth');
$r->get('/documents/certificate/{id}',      [DocumentController::class, 'certificate'], 'auth');
$r->get('/documents/sticker/{id}',          [DocumentController::class, 'sticker'], ['admin', 'inspector', 'qc']);
$r->get('/documents/attachment/{id}',       [DocumentController::class, 'attachment'], 'auth');
$r->get('/id-photo/{id}',                   [AdminCardController::class, 'userPhoto'], ['admin', 'inspector', 'qc']);
$r->get('/documents/signature/{id}',        [DocumentController::class, 'signature'], 'auth');

// ---------------------------------------------------------------- admin portal
$r->get('/admin',                           [AdminDashboardController::class, 'index'], $A);
$r->get('/admin/search',                    [AdminDashboardController::class, 'search'], $A);
$r->get('/admin/kpi',                       [KpiController::class, 'admin'], $A);
$r->post('/admin/kpi/targets',              [KpiController::class, 'targets'], $A);
$r->get('/admin/kpi/inspector/{id}',        [KpiController::class, 'adminInspector'], $A);
$r->get('/admin/kpi/qc/{id}',               [KpiController::class, 'adminQc'], $A);
$r->get('/admin/requests',                  [AdminRequestController::class, 'index'], $A);
$r->get('/admin/requests/new',              [AdminRequestController::class, 'create'], $A);
$r->post('/admin/requests',                 [AdminRequestController::class, 'store'], $A);
$r->get('/admin/requests/{id}',             [AdminRequestController::class, 'show'], $A);
$r->post('/admin/requests/{id}/status',     [AdminRequestController::class, 'status'], $A);
$r->get('/admin/requests/{id}/convert',     [AdminRequestController::class, 'convertForm'], $A);
$r->post('/admin/requests/{id}/convert',    [AdminRequestController::class, 'convert'], $A);
$r->get('/admin/jobs',                      [AdminJobController::class, 'index'], $A);
$r->get('/admin/jobs/new',                  [AdminJobController::class, 'create'], $A);
$r->post('/admin/jobs',                     [AdminJobController::class, 'store'], $A);
$r->get('/admin/assign',                    [AdminJobController::class, 'board'], $A);
$r->get('/admin/jobs/{id}',                 [AdminJobController::class, 'show'], $A);
$r->post('/admin/jobs/{id}/update',         [AdminJobController::class, 'update'], $A);
$r->post('/admin/jobs/{id}/assign',         [AdminJobController::class, 'assign'], $A);
$r->post('/admin/jobs/{id}/subjobs',        [AdminJobController::class, 'addSubJob'], $A);
$r->post('/admin/jobs/{id}/cancel',         [AdminJobController::class, 'cancel'], $A);
$r->post('/admin/subjobs/{id}/unassign',    [AdminJobController::class, 'unassign'], $A);
$r->post('/admin/subjobs/{id}/cancel',      [AdminJobController::class, 'cancelSubJob'], $A);
$r->post('/admin/subjobs/{id}/override',    [AdminJobController::class, 'override'], $A);
$r->get('/admin/clients',                   [AdminClientController::class, 'index'], $A);
$r->get('/admin/clients/new',               [AdminClientController::class, 'create'], $A);
$r->post('/admin/clients',                  [AdminClientController::class, 'store'], $A);
$r->get('/admin/clients/{id}',              [AdminClientController::class, 'show'], $A);
$r->post('/admin/clients/{id}',             [AdminClientController::class, 'update'], $A);
$r->get('/admin/equipment',                 [AdminClientController::class, 'equipment'], $A);
$r->post('/admin/equipment',                [AdminClientController::class, 'saveEquipment'], $A);
$r->get('/admin/users',                     [AdminUserController::class, 'index'], $A);
$r->get('/admin/users/new',                 [AdminUserController::class, 'create'], $A);
$r->post('/admin/users',                    [AdminUserController::class, 'store'], $A);
$r->get('/admin/users/{id}',                [AdminUserController::class, 'edit'], $A);
$r->post('/admin/users/{id}',               [AdminUserController::class, 'update'], $A);
$r->get('/admin/templates',                 [AdminTemplateController::class, 'index'], $A);
$r->post('/admin/types',                    [AdminTemplateController::class, 'saveType'], $A);
$r->get('/admin/templates/checklist/{id}',  [AdminTemplateController::class, 'checklist'], $A);
$r->post('/admin/templates/checklist/{id}', [AdminTemplateController::class, 'saveChecklist'], $A);
$r->get('/admin/templates/certificate/{id}',  [AdminTemplateController::class, 'certificate'], $A);
$r->post('/admin/templates/certificate/{id}', [AdminTemplateController::class, 'saveCertificate'], $A);
$r->get('/admin/templates/preview/{kind}/{id}', [AdminTemplateController::class, 'preview'], $A);
$r->get('/admin/certificates',              [AdminDocumentController::class, 'certificates'], $A);
$r->post('/admin/certificates/{id}/revoke', [AdminDocumentController::class, 'revoke'], $A);
$r->get('/admin/stickers',                  [AdminDocumentController::class, 'stickers'], $A);
$r->post('/admin/stickers',                 [AdminDocumentController::class, 'addStickers'], $A);
$r->post('/admin/stickers/{id}/void',       [AdminDocumentController::class, 'voidSticker'], $A);
$r->get('/admin/cards',                     [AdminCardController::class, 'index'], $A);
$r->get('/admin/cards/new',                 [AdminCardController::class, 'create'], $A);
$r->post('/admin/cards',                    [AdminCardController::class, 'store'], $A);
$r->post('/admin/cards/settings',           [AdminCardController::class, 'settings'], $A);
$r->get('/admin/cards/validate',            [AdminCardController::class, 'validate'], $A);
$r->get('/admin/cards/{id}',                [AdminCardController::class, 'edit'], $A);
$r->post('/admin/cards/{id}',               [AdminCardController::class, 'update'], $A);
$r->post('/admin/cards/{id}/revoke',        [AdminCardController::class, 'revoke'], $A);
$r->post('/admin/cards/{id}/handover',      [AdminCardController::class, 'handover'], $A);
$r->get('/admin/cards/{id}/print',          [AdminCardController::class, 'print'], $A);
$r->get('/admin/cards/{id}/photo',          [AdminCardController::class, 'photo'], $A);
$r->get('/admin/audit',                     [AdminDocumentController::class, 'audit'], $A);
$r->get('/admin/settings',                  [AdminDocumentController::class, 'settings'], $A);
$r->post('/admin/settings',                 [AdminDocumentController::class, 'saveSettings'], $A);

// ---------------------------------------------------------------- inspector portal
$r->get('/inspector',                          [InspectorController::class, 'dashboard'], $I);
$r->get('/inspector/jobs',                     [InspectorController::class, 'jobs'], $I);
$r->get('/inspector/jobs/{id}',                [InspectorController::class, 'job'], $I);
$r->get('/inspector/subjobs/{id}',             [InspectorController::class, 'subJob'], ['inspector', 'admin']);
$r->post('/inspector/subjobs/{id}/start',      [InspectorController::class, 'start'], ['inspector', 'admin']);
$r->post('/inspector/subjobs/{id}/checklist',  [InspectorController::class, 'saveChecklist'], ['inspector', 'admin']);
$r->post('/inspector/subjobs/{id}/certificate',[InspectorController::class, 'saveCertificate'], ['inspector', 'admin']);
$r->post('/inspector/subjobs/{id}/sticker',    [InspectorController::class, 'sticker'], ['inspector', 'admin']);
$r->post('/inspector/subjobs/{id}/upload',     [InspectorController::class, 'upload'], ['inspector', 'admin']);
$r->post('/inspector/attachments/{id}/delete', [InspectorController::class, 'deleteAttachment'], ['inspector', 'admin']);
$r->post('/inspector/subjobs/{id}/submit',     [InspectorController::class, 'submit'], ['inspector', 'admin']);
$r->get('/inspector/history',                  [InspectorController::class, 'history'], $I);
$r->get('/inspector/card',                     [InspectorController::class, 'card'], $I);
$r->post('/inspector/card/photo',              [InspectorController::class, 'cardPhoto'], $I);
$r->get('/inspector/kpi',                      [KpiController::class, 'inspector'], $I);

// ---------------------------------------------------------------- QC portal
$r->get('/qc',                         [QcController::class, 'dashboard'], $Q);
$r->get('/qc/queue',                   [QcController::class, 'queue'], $Q);
$r->get('/qc/review/{id}',             [QcController::class, 'review'], ['qc', 'admin']);
$r->post('/qc/review/{id}',            [QcController::class, 'decide'], ['qc', 'admin']);
$r->get('/qc/history',                 [QcController::class, 'history'], $Q);
$r->get('/qc/certificates',            [QcController::class, 'certificates'], $Q);
$r->get('/qc/kpi',                     [KpiController::class, 'qc'], $Q);

// ---------------------------------------------------------------- customer portal
$r->get('/portal',                     [CustomerController::class, 'dashboard'], $C);
$r->get('/portal/requests',            [CustomerController::class, 'requests'], $C);
$r->get('/portal/requests/new',        [CustomerController::class, 'newRequest'], $C);
$r->post('/portal/requests',           [CustomerController::class, 'storeRequest'], $C);
$r->get('/portal/jobs',                [CustomerController::class, 'jobs'], $C);
$r->get('/portal/jobs/{id}',           [CustomerController::class, 'job'], $C);
$r->get('/portal/certificates',        [CustomerController::class, 'certificates'], $C);
$r->get('/portal/equipment',           [CustomerController::class, 'equipment'], $C);

// ---------------------------------------------------------------- JSON API (session auth + X-CSRF-Token)
$r->get('/api/v1/me',                              [ApiController::class, 'me'], 'auth');
$r->get('/api/v1/dashboard',                       [ApiController::class, 'dashboard'], 'auth');
$r->get('/api/v1/jobs',                            [ApiController::class, 'jobs'], ['admin', 'inspector', 'qc']);
$r->get('/api/v1/jobs/{id}',                       [ApiController::class, 'job'], ['admin', 'inspector', 'qc']);
$r->get('/api/v1/inspectors',                      [ApiController::class, 'inspectors'], $A);
$r->get('/api/v1/clients/{id}/equipment',          [ApiController::class, 'clientEquipment'], $A);
$r->post('/api/v1/subjobs/{id}/checklist',         [ApiController::class, 'saveChecklist'], ['inspector', 'admin']);
$r->post('/api/v1/subjobs/{id}/certificate',       [ApiController::class, 'saveCertificate'], ['inspector', 'admin']);
$r->get('/api/v1/subjobs/{id}/validation',         [ApiController::class, 'validation'], ['inspector', 'admin', 'qc']);
$r->get('/api/v1/notifications',                   [ApiController::class, 'notifications'], 'auth');
$r->get('/api/v1/verify/{certificateNo}',          [ApiController::class, 'verify']);

try {
    $r->dispatch($_SERVER['REQUEST_METHOD'], request_path());
} catch (Throwable $e) {
    error_log((string) $e);
    if (is_ajax()) {
        json_out(['error' => $e instanceof DomainException ? $e->getMessage() : 'Server error'], $e instanceof DomainException ? 422 : 500);
    }
    http_response_code(500);
    View::render('errors/500', ['title' => 'Something went wrong', 'error' => config('env') === 'local' ? $e : null], 'auth');
}
