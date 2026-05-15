<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Controllers\BaseController;
use App\Modules\PublicPortal\Libraries\TicketPdfGenerator;
use App\Modules\PublicPortal\Services\TicketTrackingService;
use App\Services\TicketService;
use RuntimeException;

class TicketController extends BaseController
{
    protected TicketService $ticketService;
    protected string $viewBase = 'public';

    public function __construct()
    {
        $this->ticketService = new TicketService();
    }

    public function index()
    {
        return view($this->viewBase . '/autoservicio_inicio', array_merge(
            $this->baseViewData(),
            ['vistaGeneral' => $this->trackingService()->getOverview()]
        ));
    }

    public function overview()
    {
        return view($this->viewBase . '/turnos_general', [
            'vistaGeneral' => $this->trackingService()->getOverview(),
        ]);
    }

    public function searchStudent()
    {
        $identifier = trim((string) $this->request->getPost('identificador'));

        if (!$this->validate(['identificador' => 'required|min_length[4]|max_length[20]'])) {
            return view($this->viewBase . '/autoservicio_inicio', array_merge(
                $this->baseViewData(),
                ['error' => 'Escribe tu No. de control o No. de ficha.']
            ));
        }

        $studentDb = $this->ticketService->findStudentByIdentifier($identifier);

        if (!$studentDb) {
            return view($this->viewBase . '/autoservicio_inicio', array_merge(
                $this->baseViewData(),
                ['consultaRealizada' => true, 'alumnoEncontrado' => false, 'identificador' => $identifier]
            ));
        }

        $this->ticketService->deactivateExpiredTickets((int) $studentDb['id']);
        $currentTicket = $this->ticketService->findActiveTicketByStudent((int) $studentDb['id']);

        return view($this->viewBase . '/autoservicio_inicio', array_merge(
            $this->baseViewData(),
            [
                'consultaRealizada' => true,
                'alumnoEncontrado'  => true,
                'identificador'     => $identifier,
                'alumno'            => $this->mapStudentToView($studentDb, $identifier),
                'turnoExistente'    => $currentTicket !== null,
                'turnoActual'       => $currentTicket ? $this->mapTicketForView($currentTicket) : null,
            ]
        ));
    }

    public function generateTicket()
    {
        $lastRequest = session()->get('last_ticket_request_time') ?? 0;
        if (time() - $lastRequest < 3) {
            return view($this->viewBase . '/autoservicio_inicio', array_merge(
                $this->baseViewData(),
                ['error' => 'Por favor, espera unos segundos antes de intentar nuevamente.']
            ));
        }
        session()->set('last_ticket_request_time', time());

        $identifier = trim((string) $this->request->getPost('identificador'));

        if (!$this->validate(['identificador' => 'required|min_length[4]|max_length[20]'])) {
            return view($this->viewBase . '/autoservicio_inicio', array_merge(
                $this->baseViewData(),
                ['error' => 'Identificador inválido.']
            ));
        }

        $studentDb = $this->ticketService->findStudentByIdentifier($identifier);

        if (!$studentDb) {
            return view($this->viewBase . '/autoservicio_inicio', array_merge(
                $this->baseViewData(),
                ['consultaRealizada' => true, 'alumnoEncontrado' => false, 'identificador' => $identifier, 'error' => 'No se encontró el alumno con ese dato.']
            ));
        }

        $this->ticketService->deactivateExpiredTickets((int) $studentDb['id']);
        $existingTicket = $this->ticketService->findActiveTicketByStudent((int) $studentDb['id']);

        if ($existingTicket) {
            session()->set('pending_signature', [
                'student_id' => (int) $studentDb['id'],
                'ticket_id'  => (int) $existingTicket['id'],
                'turno'      => $this->mapTicketForView($existingTicket),
            ]);

            return view($this->viewBase . '/autoservicio_firma', [
                'turno'     => $this->mapTicketForView($existingTicket),
                'alumno'    => $this->mapStudentToView($studentDb, $identifier),
                'studentId' => (int) $studentDb['id'],
                'ticketId'  => (int) $existingTicket['id'],
            ]);
        }

        $catalogs = $this->ticketService->getInitialCatalogs();
        if (!$catalogs) {
            return view($this->viewBase . '/autoservicio_inicio', array_merge(
                $this->baseViewData(),
                ['consultaRealizada' => true, 'alumnoEncontrado' => true, 'identificador' => $identifier, 'alumno' => $this->mapStudentToView($studentDb, $identifier), 'error' => 'No se encontraron registros iniciales.']
            ));
        }

        $ticket = $this->ticketService->createTicket((int) $studentDb['id']);
        if (!$ticket) {
            return view($this->viewBase . '/autoservicio_inicio', array_merge(
                $this->baseViewData(),
                ['consultaRealizada' => true, 'alumnoEncontrado' => true, 'identificador' => $identifier, 'alumno' => $this->mapStudentToView($studentDb, $identifier), 'error' => 'No se pudo generar el turno.']
            ));
        }

        $token = $this->makeToken();
        $enrichedTicket = $this->trackingService()->getById((int) $ticket['id'], $token);

        $viewTicket = $enrichedTicket ? $this->mapTicketForView($enrichedTicket) : $this->mapTicketForView($ticket);

        session()->set('pending_signature', [
            'student_id' => (int) $studentDb['id'],
            'ticket_id'  => (int) $ticket['id'],
            'turno'      => $viewTicket,
        ]);

        return view($this->viewBase . '/autoservicio_firma', [
            'turno'     => $viewTicket,
            'alumno'    => $this->mapStudentToView($studentDb, $identifier),
            'studentId' => (int) $studentDb['id'],
            'ticketId'  => (int) $ticket['id'],
        ]);
    }

    public function savePublicSignature()
    {
        $pending = session()->get('pending_signature');
        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        $studentId    = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId     = (int) ($this->request->getPost('turno_id') ?? 0);
        $signatureB64 = (string) ($this->request->getPost('firma_png') ?? '');

        if ($studentId !== (int) $pending['student_id'] || $ticketId !== (int) $pending['ticket_id']) {
            return redirect()->to(base_url('turno'));
        }

        if ($signatureB64 !== '' && str_starts_with($signatureB64, 'data:image/')) {
            try {
                $this->ticketService->savePublicSignature($studentId, $ticketId, $signatureB64);
            } catch (RuntimeException $e) {
                log_message('error', 'Public signature save failed: ' . $e->getMessage());
            }
        }

        session()->set('pending_huella', [
            'student_id' => $studentId,
            'ticket_id'  => $ticketId,
            'turno'      => $pending['turno'],
        ]);
        session()->remove('pending_signature');

        return redirect()->to(base_url('huella'));
    }

    public function photo()
    {
        $pending = session()->get('pending_photo');
        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        return view($this->viewBase . '/autoservicio_foto', [
            'turno'     => $pending['turno'],
            'alumno'    => ['nombre' => $pending['turno']['nombre_completo'], 'identificador' => $pending['turno']['identificador']],
            'studentId' => $pending['student_id'],
            'ticketId'  => $pending['ticket_id'],
        ]);
    }

    public function savePublicPhoto()
    {
        $pending = session()->get('pending_photo');
        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        $studentId = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId  = (int) ($this->request->getPost('turno_id') ?? 0);
        $photoB64  = (string) ($this->request->getPost('foto_png') ?? '');

        if ($studentId !== (int) $pending['student_id'] || $ticketId !== (int) $pending['ticket_id']) {
            return redirect()->to(base_url('turno'));
        }

        if ($photoB64 !== '' && str_starts_with($photoB64, 'data:image/')) {
            try {
                $this->ticketService->savePublicPhoto($studentId, $ticketId, $photoB64);
            } catch (RuntimeException $e) {
                log_message('error', 'Public photo save failed: ' . $e->getMessage());
            }
        }

        session()->remove('pending_photo');
        session()->setFlashdata('ok', '¡Trámite completado! Tus biométricos han sido registrados exitosamente.');
        return redirect()->to(base_url('turno'));
    }

    public function status(string $token)
    {
        $token = trim($token);
        if ($token === '') {
            return redirect()->to(base_url('ticket'));
        }

        $ticket = $this->trackingService()->getByToken($token);

        return view($this->viewBase . '/turno_estado', [
            'notFound' => $ticket === null,
            'turno'    => $ticket ? $this->mapTicketForView($ticket) : null,
        ]);
    }

    public function statusJson(string $token)
    {
        $token = trim($token);
        if ($token === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'message' => 'Token de seguimiento inválido.']);
        }

        $ticket = $this->trackingService()->getByToken($token);
        if ($ticket === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Turno no encontrado.']);
        }

        return $this->response->setJSON(['ok' => true, 'turno' => $this->mapTicketForView($ticket)]);
    }

    public function downloadPdf(string $token)
    {
        $token = trim($token);
        if ($token === '') {
            return redirect()->to(base_url('ticket'));
        }

        $ticket = $this->trackingService()->getByToken($token);
        if ($ticket === null) {
            return $this->response->setStatusCode(404)->setBody('No se encontró el turno solicitado.');
        }

        try {
            $pdf = (new TicketPdfGenerator())->generate($this->mapTicketForView($ticket));
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(500)->setBody($e->getMessage());
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['contents']);
    }

    private function mapStudentToView(array $studentDb, string $identifier): array
    {
        return [
            'id_alumno'      => (int) $studentDb['id'],
            'identificador'  => $studentDb['control_number'] ?: ($studentDb['registration_number'] ?: $identifier),
            'numero_control' => $studentDb['control_number'] ?? null,
            'numero_ficha'   => $studentDb['registration_number'] ?? null,
            'nombre'         => $studentDb['full_name'] ?? 'N/A',
            'carrera'        => $studentDb['major_name'] ?: ($studentDb['major_code'] ?? 'N/A'),
            'campus'         => 'Instituto Tecnológico de Oaxaca',
        ];
    }

    private function mapTicketForView(array $ticket): array
    {
        return array_merge($ticket, [
            'id_turno'               => $ticket['id'] ?? null,
            'nombre_completo'        => $ticket['full_name'] ?? ($ticket['nombre_completo'] ?? 'N/A'),
            'identificador'          => $ticket['identifier'] ?? ($ticket['identificador'] ?? 'N/A'),
            'carrera'                => $ticket['major'] ?? ($ticket['carrera'] ?? 'N/A'),
            'etapa'                  => $ticket['stage_name'] ?? ($ticket['etapa'] ?? 'N/A'),
            'estatus'                => $ticket['status_name'] ?? ($ticket['estatus'] ?? 'N/A'),
            'fecha_generacion_texto' => $ticket['created_at_text'] ?? null,
            'fecha_expira_texto'     => $ticket['expires_at_text'] ?? null,
            'llamado_at_texto'       => $ticket['called_at_text'] ?? null,
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
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    private function trackingService(): TicketTrackingService
    {
        return new TicketTrackingService();
    }
}
