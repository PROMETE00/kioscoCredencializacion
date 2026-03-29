<?php

namespace App\Modules\PublicPortal\Services;

/**
 * Servicio para el seguimiento de tickets por parte del público.
 */
class TicketTrackingService
{
    private const CAMPUS = 'Instituto Tecnológico de Oaxaca';
    private const FINAL_STATUSES = ['EXPIRED', 'CANCELLED', 'FINISHED', 'COMPLETED', 'REJECTED', 'vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'];
    private const IN_PROGRESS_STATUSES = ['IN_PROGRESS', 'EN_PROCESO'];

    public function __construct(
        private readonly int $baseTimeSeconds = 240,
        private readonly int $extensionBlockSeconds = 30
    ) {
    }

    public function getByToken(string $token): ?array
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $row = $this->ticketBaseBuilder()
            ->where('t.qr_token_hash', hash('sha256', $token))
            ->orderBy('t.id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ? $this->enrichTicket($row, $token) : null;
    }

    public function getById(int $ticketId, ?string $token = null): ?array
    {
        $row = $this->ticketBaseBuilder()
            ->where('t.id', $ticketId)
            ->get()
            ->getRowArray();

        return $row ? $this->enrichTicket($row, $token) : null;
    }

    public function buildQrUrl(string $url): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($url);
    }

    public function getOverview(): array
    {
        $queue = $this->getActiveQueue();
        $currentTicket = $this->resolveCurrentTicket($queue);
        $items = [];

        foreach ($queue as $index => $item) {
            $ticket = array_merge($item, $this->calculateTracking($item));
            $ticket['general_position'] = $index + 1;
            $items[] = $ticket;
        }

        $currentTicketSummary = null;

        if ($currentTicket !== null) {
            $currentTicketSummary = array_merge($currentTicket, $this->calculateTracking($currentTicket));
        }

        return [
            'updated_at'     => date('d/m/Y H:i'),
            'current_ticket' => $currentTicketSummary,
            'total_tickets'  => count($items),
            'being_served'   => $currentTicketSummary ? 1 : 0,
            'waiting'        => max(0, count($items) - ($currentTicketSummary ? 1 : 0)),
            'items'          => $items,
        ];
    }

    private function enrichTicket(array $ticket, ?string $token = null): array
    {
        $ticket['identifier'] = $ticket['control_number'] ?: ($ticket['registration_number'] ?: 'N/A');
        $ticket['major'] = $ticket['major_name'] ?: ($ticket['major_code'] ?: 'N/A');
        $ticket['campus'] = self::CAMPUS;
        $ticket['token'] = $token;
        $ticket['tracking_url'] = $token ? base_url('t/' . $token) : null;
        $ticket['pdf_url'] = $token ? base_url('ticket/pdf/' . $token) : null;
        $ticket['tracking_endpoint'] = $token ? base_url('ticket/status/' . $token) : null;
        $ticket['qr_url'] = $token ? $this->buildQrUrl($ticket['tracking_url']) : null;
        $ticket['created_at_text'] = $this->formatDate($ticket['created_at'] ?? null);
        $ticket['expires_at_text'] = $this->formatDate($ticket['expires_at'] ?? null);
        $ticket['called_at_text'] = $this->formatDate($ticket['called_at'] ?? null);

        return array_merge($ticket, $this->calculateTracking($ticket));
    }

    private function calculateTracking(array $ticket): array
    {
        $isFinished = $this->isTicketFinished($ticket);
        $queue = $this->getActiveQueue();
        $queueIds = array_column($queue, 'id');
        $ticketIndex = array_search((int) $ticket['id'], $queueIds, true);
        $currentTicket = $this->resolveCurrentTicket($queue);
        $currentIndex = $currentTicket ? array_search((int) $currentTicket['id'], $queueIds, true) : false;
        $isCurrentTicket = $currentTicket !== null && (int) $currentTicket['id'] === (int) $ticket['id'];

        $ticketsBefore = 0;
        $etaSeconds = 0;

        if (!$isFinished && $ticketIndex !== false) {
            if ($isCurrentTicket) {
                $ticketsBefore = 0;
                $etaSeconds = 0;
            } elseif ($currentTicket && $currentIndex !== false && $ticketIndex > $currentIndex) {
                $ticketsBefore = max(0, $ticketIndex - $currentIndex - 1);
                $etaSeconds = $this->remainingTimeCurrentTicket($currentTicket) + ($ticketsBefore * $this->baseTimeSeconds);
            } else {
                $ticketsBefore = max(0, (int) $ticketIndex);
                $etaSeconds = $ticketsBefore * $this->baseTimeSeconds;
            }
        }

        return [
            'is_finished'          => $isFinished,
            'badge_class'          => $this->resolveBadgeClass($isFinished, $isCurrentTicket),
            'progress_message'     => $this->resolveProgressMessage($isFinished, $isCurrentTicket, $ticketsBefore),
            'eta_seconds'          => $etaSeconds,
            'eta_text'             => $this->formatDuration($etaSeconds, $isFinished, $isCurrentTicket),
            'tickets_before'       => $ticketsBefore,
            'current_ticket_folio' => $currentTicket['folio'] ?? null,
            'current_ticket_stage' => $currentTicket['stage_name'] ?? null,
            'current_called_at'    => $this->formatDate($currentTicket['called_at'] ?? null),
            'is_current_ticket'    => $isCurrentTicket,
        ];
    }

    private function ticketBaseBuilder()
    {
        return \Config\Database::connect()
            ->table('tickets t')
            ->select('
                t.id,
                t.folio,
                t.student_id,
                t.is_active,
                t.expires_at,
                t.qr_token_hash,
                t.called_at,
                t.created_at,
                t.updated_at,
                a.control_number,
                a.registration_number,
                a.full_name,
                a.major_code,
                a.major_name,
                e.id AS stage_id,
                e.code AS stage_code,
                e.name AS stage_name,
                e.sort_order AS stage_sort_order,
                e.is_terminal AS stage_is_terminal,
                s.id AS status_id,
                s.code AS status_code,
                s.name AS status_name
            ')
            ->join('students a', 'a.id = t.student_id', 'left')
            ->join('cat_stages e', 'e.id = t.stage_id', 'left')
            ->join('cat_ticket_status s', 's.id = t.status_id', 'left');
    }

    private function getActiveQueue(): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->ticketBaseBuilder()
            ->where('t.is_active', 1)
            ->where('t.expires_at >=', $now)
            ->groupStart()
                ->where('e.is_terminal', 0)
                ->orWhere('e.is_terminal IS NULL', null, false)
            ->groupEnd()
            ->groupStart()
                ->where('s.code IS NULL', null, false)
                ->orWhereNotIn('s.code', self::FINAL_STATUSES)
            ->groupEnd()
            ->orderBy('t.created_at', 'ASC')
            ->orderBy('t.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function resolveCurrentTicket(array $queue): ?array
    {
        $candidates = array_values(array_filter(
            $queue,
            static fn (array $item): bool => in_array($item['status_code'] ?? '', self::IN_PROGRESS_STATUSES, true)
                || !empty($item['called_at'])
        ));

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static function (array $a, array $b): int {
                $dateA = strtotime($a['called_at'] ?: $a['created_at']);
                $dateB = strtotime($b['called_at'] ?: $b['created_at']);

                return [$dateA, (int) $a['id']] <=> [$dateB, (int) $b['id']];
            }
        );

        return $candidates[0];
    }

    private function remainingTimeCurrentTicket(array $currentTicket): int
    {
        $start = $currentTicket['called_at'] ?: $currentTicket['updated_at'] ?: $currentTicket['created_at'];
        $elapsed = max(0, time() - strtotime((string) $start));

        if ($elapsed < $this->baseTimeSeconds) {
            return $this->baseTimeSeconds - $elapsed;
        }

        $excess = $elapsed - $this->baseTimeSeconds;
        $blockRemaining = $this->extensionBlockSeconds - ($excess % $this->extensionBlockSeconds);

        return $blockRemaining === 0 ? $this->extensionBlockSeconds : $blockRemaining;
    }

    private function isTicketFinished(array $ticket): bool
    {
        if ((int) ($ticket['is_active'] ?? 0) !== 1) {
            return true;
        }

        if (!empty($ticket['expires_at']) && strtotime((string) $ticket['expires_at']) < time()) {
            return true;
        }

        if ((int) ($ticket['stage_is_terminal'] ?? 0) === 1) {
            return true;
        }

        return in_array((string) ($ticket['status_code'] ?? ''), self::FINAL_STATUSES, true);
    }

    private function resolveBadgeClass(bool $isFinished, bool $isCurrentTicket): string
    {
        if ($isFinished) {
            return 'pt-badge--finished';
        }

        if ($isCurrentTicket) {
            return 'pt-badge--serving';
        }

        return 'pt-badge--waiting';
    }

    private function resolveProgressMessage(bool $isFinished, bool $isCurrentTicket, int $ticketsBefore): string
    {
        if ($isFinished) {
            return 'Tu proceso ha finalizado';
        }

        if ($isCurrentTicket) {
            return 'Tu turno está siendo atendido';
        }

        if ($ticketsBefore <= 1) {
            return 'Tu turno está próximo a ser atendido';
        }

        return 'Tu turno está en espera';
    }

    private function formatDuration(int $seconds, bool $isFinished, bool $isCurrentTicket): string
    {
        if ($isFinished) {
            return 'Proceso concluido';
        }

        if ($isCurrentTicket) {
            return 'En atención';
        }

        if ($seconds <= 0) {
            return 'Menos de 1 min';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes <= 0) {
            return $remainingSeconds . ' s';
        }

        if ($remainingSeconds === 0) {
            return $minutes . ' min';
        }

        return $minutes . ' min ' . $remainingSeconds . ' s';
    }

    private function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        return date('d/m/Y H:i', strtotime($date));
    }
}
