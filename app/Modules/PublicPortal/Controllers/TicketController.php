<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Controllers\BaseController;
use App\Modules\PublicPortal\Libraries\TicketPdfGenerator;
use App\Modules\PublicPortal\Models\StudentModel;
use App\Modules\PublicPortal\Models\TicketModel;
use App\Modules\PublicPortal\Services\TicketTrackingService;
use RuntimeException;

/**
 * Controlador para el portal público de generación y seguimiento de turnos.
 */
class TicketController extends BaseController
{
    protected string $viewBase = 'public';

    /**
     * Muestra la pantalla para generar un nuevo turno.
     */
    public function index()
    {
        return view($this->viewBase . '/generar_turno', array_merge(
            $this->baseViewData(),
            [
                'vistaGeneral' => $this->trackingService()->getOverview(),
            ]
        ));
    }

    /**
     * Muestra la vista general de turnos en espera.
     */
    public function overview()
    {
        return view($this->viewBase . '/turnos_general', [
            'vistaGeneral' => $this->trackingService()->getOverview(),
        ]);
    }

    /**
     * Busca un alumno por su identificador (No. Control o No. Ficha).
     */
    public function searchStudent()
    {
        $identifier = trim((string) $this->request->getPost('identificador'));

        $rules = [
            'identificador' => 'required|min_length[4]|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'error' => 'Escribe tu No. de control o No. de ficha.',
                ]
            ));
        }

        $studentDb = $this->searchStudentByIdentifier($identifier);

        if (!$studentDb) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => false,
                    'identificador'     => $identifier,
                ]
            ));
        }

        $this->deactivateExpiredTickets((int) $studentDb['id']);

        $currentTicket = $this->searchActiveTicketByStudent((int) $studentDb['id']);

        return view($this->viewBase . '/generar_turno', array_merge(
            $this->baseViewData(),
            [
                'consultaRealizada' => true,
                'alumnoEncontrado'  => true,
                'identificador'     => $identifier,
                'alumno'            => $this->mapStudentToView($studentDb, $identifier),
                'turnoExistente'    => $currentTicket !== null,
                'turnoActual'       => $currentTicket,
            ]
        ));
    }

    /**
     * Genera un nuevo ticket de turno para el alumno.
     */
    public function generateTicket()
    {
        // Rate limiting básico (prevenir doble clic o spam automatizado)
        $lastRequest = session()->get('last_ticket_request_time') ?? 0;
        if (time() - $lastRequest < 3) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                ['error' => 'Por favor, espera unos segundos antes de intentar nuevamente.']
            ));
        }
        session()->set('last_ticket_request_time', time());

        $identifier = trim((string) $this->request->getPost('identificador'));

        $rules = [
            'identificador' => 'required|min_length[4]|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'error' => 'Identificador inválido.',
                ]
            ));
        }

        $studentDb = $this->searchStudentByIdentifier($identifier);

        if (!$studentDb) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => false,
                    'identificador'     => $identifier,
                    'error'             => 'No se encontró el alumno con ese dato.',
                ]
            ));
        }

        $this->deactivateExpiredTickets((int) $studentDb['id']);

        $existingTicket = $this->searchActiveTicketByStudent((int) $studentDb['id']);

        if ($existingTicket) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identifier,
                    'alumno'            => $this->mapStudentToView($studentDb, $identifier),
                    'turnoExistente'    => true,
                    'turnoActual'       => $existingTicket,
                ]
            ));
        }

        $catalogs = $this->getInitialCatalogs();

        if (!$catalogs) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identifier,
                    'alumno'            => $this->mapStudentToView($studentDb, $identifier),
                    'error'             => 'No se encontraron registros iniciales en cat_stages o cat_ticket_status.',
                ]
            ));
        }

        $token     = $this->makeToken();
        $tokenHash = hash('sha256', $token);

        $now     = date('Y-m-d H:i:s');
        $expires = date('Y-m-d 23:59:59');

        $db = \Config\Database::connect();
        $db->transStart();

        // Debug logging
        log_message('info', 'Insertando ticket con datos: ' . json_encode([
            'student_id' => (int) $studentDb['id'],
            'status_id' => (int) $catalogs['status_id'], 
            'stage_id' => (int) $catalogs['stage_id']
        ]));

        $insertResult = $db->table('tickets')->insert([
            'folio'         => 'PEND',
            'student_id'    => (int) $studentDb['id'],
            'status_id'     => (int) $catalogs['status_id'],
            'stage_id'      => (int) $catalogs['stage_id'],
            'is_active'     => 1,
            'expires_at'    => $expires,
            'qr_token_hash' => $tokenHash,
            'called_at'     => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $ticketId = (int) $db->insertID();
        log_message('info', 'Insert result: ' . ($insertResult ? 'true' : 'false') . ', Insert ID: ' . $ticketId);

        if ($ticketId <= 0) {
            log_message('error', 'Ticket ID is ' . $ticketId . ', rolling back transaction');
            $db->transRollback();

            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identifier,
                    'alumno'            => $this->mapStudentToView($studentDb, $identifier),
                    'error'             => 'No se pudo crear el registro del turno. Intenta de nuevo.',
                ]
            ));
        }

        $folio = $this->buildFolio((int) $ticketId);

        $db->table('tickets')
            ->where('id', $ticketId)
            ->update([
                'folio'      => $folio,
                'updated_at' => $now,
            ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identifier,
                    'alumno'            => $this->mapStudentToView($studentDb, $identifier),
                    'error'             => 'No se pudo generar el turno. Intenta de nuevo.',
                ]
            ));
        }

        $ticket = $this->trackingService()->getById((int) $ticketId, $token);

        if ($ticket === null) {
            $ticket = [
                'folio'                  => $folio,
                'full_name'              => $studentDb['full_name'],
                'identifier'             => $this->resolveStudentIdentifier($studentDb),
                'major'                  => $studentDb['major_name'] ?? ($studentDb['major_code'] ?? 'N/A'),
                'campus'                 => 'Instituto Tecnológico de Oaxaca',
                'status_name'            => 'Activo',
                'stage_name'             => 'Turno generado',
                'created_at_text'        => date('d/m/Y H:i', strtotime($now)),
                'expires_at_text'        => date('d/m/Y H:i', strtotime($expires)),
                'tracking_url'           => base_url('t/' . $token),
                'pdf_url'                => base_url('ticket/pdf/' . $token),
                'qr_url'                 => $this->trackingService()->buildQrUrl(base_url('t/' . $token)),
            ];
        }

        // Mapear para la vista (que espera nombres en español)
        $viewTicket = $this->mapTicketForView($ticket);

        // Store ticket data in session for the signature step
        session()->set('pending_signature', [
            'student_id' => (int) $studentDb['id'],
            'ticket_id'  => $ticketId,
            'turno'      => $viewTicket,
        ]);

        // Show signature capture page instead of QR
        return view($this->viewBase . '/captura_firma_publica', [
            'turno'     => $viewTicket,
            'alumno'    => $this->mapStudentToView($studentDb, $identifier),
            'studentId' => (int) $studentDb['id'],
            'ticketId'  => $ticketId,
        ]);
    }

    /**
     * Saves a signature from the public kiosk flow (no auth required).
     * Expects: firma_png (base64 data URL), alumno_id, turno_id via POST.
     */
    public function savePublicSignature()
    {
        $pending = session()->get('pending_signature');

        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        $studentId    = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId     = (int) ($this->request->getPost('turno_id') ?? 0);
        $signatureB64 = (string) ($this->request->getPost('firma_png') ?? '');

        // Validate that IDs match what's in session (prevent tampering)
        if ($studentId !== (int) $pending['student_id'] || $ticketId !== (int) $pending['ticket_id']) {
            return redirect()->to(base_url('turno'));
        }

        // If signature data is provided, save it
        if ($signatureB64 !== '' && str_starts_with($signatureB64, 'data:image/')) {
            try {
                $this->saveSignatureFile($studentId, $ticketId, $signatureB64);
            } catch (\RuntimeException $e) {
                log_message('error', 'Public signature save failed: ' . $e->getMessage());
                // Continue to QR even if signature fails — don't block the flow
            }
        }

        $viewTicket = $pending['turno'];
        session()->remove('pending_signature');

        return view($this->viewBase . '/turno_qr', [
            'turno' => $viewTicket,
        ]);
    }

    /**
     * Directly saves a signature file without queue validation.
     * Used by the public kiosk flow.
     */
    private function saveSignatureFile(int $studentId, int $ticketId, string $dataUrl): array
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            throw new \RuntimeException('El formato de la firma es inválido.');
        }

        $mime   = strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw new \RuntimeException('No se pudo decodificar la firma.');
        }

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            default      => 'png',
        };

        $relativePath = 'uploads/firmas/signature_' . $studentId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $absolutePath = FCPATH . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio de firmas.');
        }

        if (file_put_contents($absolutePath, $binary) === false) {
            throw new \RuntimeException('No se pudo guardar el archivo de firma.');
        }

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        $db->transStart();

        // Insert file record
        $db->table('files')->insert([
            'type'       => 'signature',
            'path'       => $relativePath,
            'sha256'     => hash('sha256', $binary),
            'mime'       => $mime,
            'size_bytes' => filesize($absolutePath),
            'created_at' => $now,
        ]);

        $fileId = (int) $db->insertID();

        // Update student's signature reference
        $db->table('students')
            ->where('id', $studentId)
            ->update([
                'signature_file_id' => $fileId,
                'updated_at'        => $now,
            ]);

        // Try to advance the ticket stage
        $currentTicket = $db->table('tickets')
            ->select('stage_id, status_id')
            ->where('id', $ticketId)
            ->get()
            ->getRowArray();

        $nextStageId = null;
        foreach (['SIGNATURE_CAPTURED', 'signature_saved', 'SIGNATURE_REGISTERED', 'FIRMA_REGISTRADA'] as $code) {
            $row = $db->table('cat_stages')->select('id')->where('code', $code)->get(1)->getRowArray();
            if ($row) { $nextStageId = (int) $row['id']; break; }
        }

        $ticketUpdate = ['updated_at' => $now];
        if ($nextStageId !== null) {
            $ticketUpdate['stage_id'] = $nextStageId;
        }

        $db->table('tickets')
            ->where('id', $ticketId)
            ->update($ticketUpdate);

        // Log event
        $db->table('ticket_events')->insert([
            'ticket_id'          => $ticketId,
            'event_type'         => 'signature_saved',
            'previous_stage_id'  => $currentTicket['stage_id'] ?? null,
            'new_stage_id'       => $nextStageId,
            'previous_status_id' => $currentTicket['status_id'] ?? null,
            'new_status_id'      => $currentTicket['status_id'] ?? null,
            'user_id'            => null, // Public kiosk, no auth user
            'details_json'       => json_encode(['file_id' => $fileId, 'source' => 'public_kiosk'], JSON_UNESCAPED_UNICODE),
            'created_at'         => $now,
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            @unlink($absolutePath);
            throw new \RuntimeException('Error al guardar la firma en la base de datos.');
        }

        return ['file_id' => $fileId, 'url' => base_url($relativePath)];
    }

    /**
     * Muestra el estado actual de un turno mediante su token.
     */
    public function status(string $token)
    {
        $token = trim($token);

        if ($token === '') {
            return redirect()->to(base_url('ticket'));
        }

        $ticket = $this->trackingService()->getByToken($token);

        if ($ticket === null) {
            return view($this->viewBase . '/turno_estado', [
                'notFound' => true,
            ]);
        }

        return view($this->viewBase . '/turno_estado', [
            'notFound' => false,
            'turno'    => $this->mapTicketForView($ticket),
        ]);
    }

    /**
     * Retorna el estado del turno en formato JSON.
     */
    public function statusJson(string $token)
    {
        $token = trim($token);

        if ($token === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'ok'      => false,
                    'message' => 'Token de seguimiento inválido.',
                ]);
        }

        $ticket = $this->trackingService()->getByToken($token);

        if ($ticket === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'ok'      => false,
                    'message' => 'Turno no encontrado.',
                ]);
        }

        return $this->response->setJSON([
            'ok'    => true,
            'turno' => $this->mapTicketForView($ticket),
        ]);
    }

    /**
     * Genera y descarga el PDF del turno.
     */
    public function downloadPdf(string $token)
    {
        $token = trim($token);

        if ($token === '') {
            return redirect()->to(base_url('ticket'));
        }

        $ticket = $this->trackingService()->getByToken($token);

        if ($ticket === null) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('No se encontró el turno solicitado.');
        }

        try {
            // El generador espera los campos que tiene el ticket enriquecido
            $pdf = (new TicketPdfGenerator())->generate($this->mapTicketForView($ticket));
        } catch (RuntimeException $e) {
            return $this->response
                ->setStatusCode(500)
                ->setBody($e->getMessage());
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['contents']);
    }

    private function searchStudentByIdentifier(string $identifier): ?array
    {
        $studentModel = new StudentModel();

        $student = $studentModel
            ->groupStart()
                ->where('control_number', $identifier)
                ->orWhere('registration_number', $identifier)
            ->groupEnd()
            ->first();

        return $student ?: null;
    }

    private function searchActiveTicketByStudent(int $studentId): ?array
    {
        $now = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();

        $ticket = $db->table('tickets t')
            ->select('
                t.id,
                t.folio,
                t.created_at,
                t.expires_at,
                e.name AS stage_name,
                s.name AS status_name
            ')
            ->join('cat_stages e', 'e.id = t.stage_id', 'left')
            ->join('cat_ticket_status s', 's.id = t.status_id', 'left')
            ->where('t.student_id', $studentId)
            ->where('t.is_active', 1)
            ->where('t.expires_at >=', $now)
            ->groupStart()
                ->where('s.code IS NULL', null, false)
                ->orWhereNotIn('s.code', ['EXPIRED', 'CANCELLED', 'FINISHED', 'COMPLETED', 'REJECTED', 'vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'])
            ->groupEnd()
            ->orderBy('t.id', 'DESC')
            ->get()
            ->getRowArray();

        if ($ticket) {
            return $this->mapTicketForView($ticket);
        }

        return null;
    }

    private function getInitialCatalogs(): ?array
    {
        $db = \Config\Database::connect();

        $stage = $db->table('cat_stages')
            ->where('code', 'TICKET_GENERATED')
            ->orWhere('code', 'turno_generado')
            ->get()
            ->getRowArray();

        if (!$stage) {
            $stage = $db->table('cat_stages')
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        }

        $status = $db->table('cat_ticket_status')
            ->where('code', 'WAITING')
            ->orWhere('code', 'EN_ESPERA')
            ->get()
            ->getRowArray();

        if (!$status) {
            $status = $db->table('cat_ticket_status')
                ->groupStart()
                    ->where('code', 'WAITING')
                    ->orWhere('code', 'IN_PROGRESS')
                ->groupEnd()
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        }

        if (!$stage || !$status) {
            return null;
        }

        return [
            'stage_id'  => (int) $stage['id'],
            'status_id' => (int) $status['id'],
        ];
    }

    private function mapStudentToView(array $studentDb, string $identifier): array
    {
        return [
            'id_alumno'      => (int) $studentDb['id'],
            'identificador'  => $this->resolveStudentIdentifier($studentDb) ?: $identifier,
            'numero_control' => $studentDb['control_number'] ?? null,
            'numero_ficha'   => $studentDb['registration_number'] ?? null,
            'nombre'         => $studentDb['full_name'] ?? 'N/A',
            'carrera'        => !empty($studentDb['major_name'])
                ? $studentDb['major_name']
                : ($studentDb['major_code'] ?? 'N/A'),
            'campus'         => 'Instituto Tecnológico de Oaxaca',
        ];
    }

    private function mapTicketForView(array $ticket): array
    {
        // Esta función mapea las claves en inglés a las que esperan las vistas actuales en español
        return array_merge($ticket, [
            'id_turno'               => $ticket['id'] ?? null,
            'nombre_completo'        => $ticket['full_name'] ?? ($ticket['nombre_completo'] ?? 'N/A'),
            'identificador'          => $ticket['identifier'] ?? ($ticket['identificador'] ?? 'N/A'),
            'carrera'                => $ticket['major'] ?? ($ticket['carrera'] ?? 'N/A'),
            'etapa'                  => $ticket['stage_name'] ?? ($ticket['etapa'] ?? 'N/A'),
            'estatus'                => $ticket['status_name'] ?? ($ticket['estatus'] ?? 'N/A'),
            'fecha_generacion_texto' => $ticket['created_at_text'] ?? ($ticket['fecha_generacion_texto'] ?? null),
            'fecha_expira_texto'     => $ticket['expires_at_text'] ?? ($ticket['fecha_expira_texto'] ?? null),
            'llamado_at_texto'       => $ticket['called_at_text'] ?? ($ticket['llamado_at_texto'] ?? null),
            'pdf_url'                => $ticket['pdf_url'] ?? null,
            'qr_url'                 => $ticket['qr_url'] ?? null,
            'seguimiento_url'        => $ticket['tracking_url'] ?? null,
            'mensaje_progreso'       => $ticket['progress_message'] ?? null,
            'eta_texto'              => $ticket['eta_text'] ?? null,
            'turnos_antes'           => $ticket['tickets_before'] ?? 0,
            'turno_actual_folio'     => $ticket['current_ticket_folio'] ?? null,
            'turno_actual_etapa'     => $ticket['current_ticket_stage'] ?? null,
        ]);
    }

    private function baseViewData(): array
    {
        return [
            'error'             => null,
            'ok'                => null,
            'consultaRealizada' => false,
            'alumnoEncontrado'  => false,
            'identificador'     => '',
            'alumno'            => null,
            'turnoExistente'    => false,
            'turnoActual'       => null,
            'vistaGeneral'      => null,
        ];
    }

    private function makeToken(): string
    {
        $raw = random_bytes(24);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function buildFolio(int $ticketId): string
    {
        $db = \Config\Database::connect();
        
        // Intentar primero el folio basado en ID
        $basefolioID = 'FOL-' . str_pad((string) $ticketId, 8, '0', STR_PAD_LEFT);
        
        // Verificar si ya existe
        $exists = $db->table('tickets')
            ->where('folio', $basefolioID)
            ->where('id !=', $ticketId)
            ->countAllResults();
        
        if ($exists == 0) {
            return $basefolioID;
        }
        
        // Si existe, generar uno basado en timestamp + ticketID
        $timestamp = date('ymdHis');
        return 'FOL-' . $timestamp . '-' . str_pad((string) $ticketId, 4, '0', STR_PAD_LEFT);
    }

    private function resolveStudentIdentifier(array $studentDb): string
    {
        if (!empty($studentDb['control_number'])) {
            return (string) $studentDb['control_number'];
        }

        return (string) ($studentDb['registration_number'] ?? '');
    }

    private function trackingService(): TicketTrackingService
    {
        return new TicketTrackingService();
    }

    private function deactivateExpiredTickets(int $studentId): void
    {
        $now = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();

        $db->query(
            'UPDATE tickets t
             LEFT JOIN cat_ticket_status s ON s.id = t.status_id
             LEFT JOIN cat_stages e ON e.id = t.stage_id
             SET t.is_active = NULL, t.updated_at = ?
             WHERE t.student_id = ?
               AND t.is_active = 1
               AND (
                    t.expires_at < ?
                    OR s.code IN ("EXPIRED", "CANCELLED", "FINISHED", "COMPLETED", "REJECTED", "vencido", "cancelado", "finalizado", "COMPLETADO", "RECHAZADO")
                    OR e.is_terminal = 1
               )',
            [$now, $studentId, $now]
        );
    }
}
