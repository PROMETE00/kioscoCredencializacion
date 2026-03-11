<?php

namespace App\Services;

class TurnoSeguimientoService
{
    private const CAMPUS = 'Instituto Tecnológico de Oaxaca';
    private const ESTATUS_FINALES = ['vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'];
    private const ESTATUS_EN_PROCESO = ['EN_PROCESO'];

    public function __construct(
        private readonly int $tiempoBaseSegundos = 240,
        private readonly int $bloqueExtensionSegundos = 30
    ) {
    }

    public function obtenerPorToken(string $token): ?array
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $row = $this->turnoBaseBuilder()
            ->where('t.qr_token_hash', hash('sha256', $token))
            ->orderBy('t.id_turno', 'DESC')
            ->get()
            ->getRowArray();

        return $row ? $this->enriquecerTurno($row, $token) : null;
    }

    public function obtenerPorId(int $turnoId, ?string $token = null): ?array
    {
        $row = $this->turnoBaseBuilder()
            ->where('t.id_turno', $turnoId)
            ->get()
            ->getRowArray();

        return $row ? $this->enriquecerTurno($row, $token) : null;
    }

    public function construirQrUrl(string $url): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($url);
    }

    public function obtenerVistaGeneral(): array
    {
        $cola = $this->obtenerColaActiva();
        $turnoActual = $this->resolverTurnoActual($cola);
        $items = [];

        foreach ($cola as $index => $item) {
            $turno = array_merge($item, $this->calcularSeguimiento($item));
            $turno['posicion_general'] = $index + 1;
            $items[] = $turno;
        }

        $turnoActualResumen = null;

        if ($turnoActual !== null) {
            $turnoActualResumen = array_merge($turnoActual, $this->calcularSeguimiento($turnoActual));
        }

        return [
            'actualizado_en' => date('d/m/Y H:i'),
            'turno_actual'   => $turnoActualResumen,
            'total_turnos'   => count($items),
            'en_atencion'    => $turnoActualResumen ? 1 : 0,
            'en_espera'      => max(0, count($items) - ($turnoActualResumen ? 1 : 0)),
            'items'          => $items,
        ];
    }

    private function enriquecerTurno(array $turno, ?string $token = null): array
    {
        $turno['identificador'] = $turno['numero_control'] ?: ($turno['numero_ficha'] ?: 'N/A');
        $turno['carrera'] = $turno['carrera_nombre'] ?: ($turno['carrera_clave'] ?: 'N/A');
        $turno['campus'] = self::CAMPUS;
        $turno['token'] = $token;
        $turno['seguimiento_url'] = $token ? base_url('t/' . $token) : null;
        $turno['pdf_url'] = $token ? base_url('turno/pdf/' . $token) : null;
        $turno['seguimiento_endpoint'] = $token ? base_url('turno/seguimiento/' . $token) : null;
        $turno['qr_url'] = $token ? $this->construirQrUrl($turno['seguimiento_url']) : null;
        $turno['fecha_generacion_texto'] = $this->formatearFecha($turno['created_at'] ?? null);
        $turno['fecha_expira_texto'] = $this->formatearFecha($turno['fecha_expira'] ?? null);
        $turno['llamado_at_texto'] = $this->formatearFecha($turno['llamado_at'] ?? null);

        return array_merge($turno, $this->calcularSeguimiento($turno));
    }

    private function calcularSeguimiento(array $turno): array
    {
        $estaFinalizado = $this->esTurnoConcluido($turno);
        $cola = $this->obtenerColaActiva();
        $colaIds = array_column($cola, 'id_turno');
        $indiceTurno = array_search((int) $turno['id_turno'], $colaIds, true);
        $turnoActual = $this->resolverTurnoActual($cola);
        $indiceActual = $turnoActual ? array_search((int) $turnoActual['id_turno'], $colaIds, true) : false;
        $esTurnoActual = $turnoActual !== null && (int) $turnoActual['id_turno'] === (int) $turno['id_turno'];

        $turnosAntes = 0;
        $etaSegundos = 0;

        if (!$estaFinalizado && $indiceTurno !== false) {
            if ($esTurnoActual) {
                $turnosAntes = 0;
                $etaSegundos = 0;
            } elseif ($turnoActual && $indiceActual !== false && $indiceTurno > $indiceActual) {
                $turnosAntes = max(0, $indiceTurno - $indiceActual - 1);
                $etaSegundos = $this->tiempoRestanteTurnoActual($turnoActual) + ($turnosAntes * $this->tiempoBaseSegundos);
            } else {
                $turnosAntes = max(0, (int) $indiceTurno);
                $etaSegundos = $turnosAntes * $this->tiempoBaseSegundos;
            }
        }

        return [
            'esta_finalizado'         => $estaFinalizado,
            'badge_class'             => $this->resolverBadgeClass($estaFinalizado, $esTurnoActual),
            'mensaje_progreso'        => $this->resolverMensajeProgreso($estaFinalizado, $esTurnoActual, $turnosAntes),
            'eta_segundos'            => $etaSegundos,
            'eta_texto'               => $this->formatearDuracion($etaSegundos, $estaFinalizado, $esTurnoActual),
            'turnos_antes'            => $turnosAntes,
            'turno_actual_folio'      => $turnoActual['folio'] ?? null,
            'turno_actual_etapa'      => $turnoActual['etapa'] ?? null,
            'turno_actual_llamado_at' => $this->formatearFecha($turnoActual['llamado_at'] ?? null),
            'es_turno_actual'         => $esTurnoActual,
        ];
    }

    private function turnoBaseBuilder()
    {
        return \Config\Database::connect()
            ->table('turnos t')
            ->select('
                t.id_turno,
                t.folio,
                t.alumno_id,
                t.es_activo,
                t.fecha_expira,
                t.qr_token_hash,
                t.llamado_at,
                t.created_at,
                t.updated_at,
                a.numero_control,
                a.numero_ficha,
                a.nombre_completo,
                a.carrera_clave,
                a.carrera_nombre,
                e.id_etapa,
                e.codigo AS etapa_codigo,
                e.nombre AS etapa,
                e.orden AS etapa_orden,
                e.es_terminal AS etapa_es_terminal,
                s.id_estatus,
                s.codigo AS estatus_codigo,
                s.nombre AS estatus
            ')
            ->join('alumnos a', 'a.id_alumno = t.alumno_id', 'left')
            ->join('cat_etapas e', 'e.id_etapa = t.etapa_actual_id', 'left')
            ->join('cat_estatus_turno s', 's.id_estatus = t.estatus_turno_id', 'left');
    }

    private function obtenerColaActiva(): array
    {
        $ahora = date('Y-m-d H:i:s');

        return $this->turnoBaseBuilder()
            ->where('t.es_activo', 1)
            ->where('t.fecha_expira >=', $ahora)
            ->groupStart()
                ->where('e.es_terminal', 0)
                ->orWhere('e.es_terminal IS NULL', null, false)
            ->groupEnd()
            ->groupStart()
                ->where('s.codigo IS NULL', null, false)
                ->orWhereNotIn('s.codigo', self::ESTATUS_FINALES)
            ->groupEnd()
            ->orderBy('t.created_at', 'ASC')
            ->orderBy('t.id_turno', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function resolverTurnoActual(array $cola): ?array
    {
        $candidatos = array_values(array_filter(
            $cola,
            static fn (array $item): bool => in_array($item['estatus_codigo'] ?? '', self::ESTATUS_EN_PROCESO, true)
                || !empty($item['llamado_at'])
        ));

        if ($candidatos === []) {
            return null;
        }

        usort(
            $candidatos,
            static function (array $a, array $b): int {
                $fechaA = strtotime($a['llamado_at'] ?: $a['created_at']);
                $fechaB = strtotime($b['llamado_at'] ?: $b['created_at']);

                return [$fechaA, (int) $a['id_turno']] <=> [$fechaB, (int) $b['id_turno']];
            }
        );

        return $candidatos[0];
    }

    private function tiempoRestanteTurnoActual(array $turnoActual): int
    {
        $inicio = $turnoActual['llamado_at'] ?: $turnoActual['updated_at'] ?: $turnoActual['created_at'];
        $transcurrido = max(0, time() - strtotime((string) $inicio));

        if ($transcurrido < $this->tiempoBaseSegundos) {
            return $this->tiempoBaseSegundos - $transcurrido;
        }

        $exceso = $transcurrido - $this->tiempoBaseSegundos;
        $restanteBloque = $this->bloqueExtensionSegundos - ($exceso % $this->bloqueExtensionSegundos);

        return $restanteBloque === 0 ? $this->bloqueExtensionSegundos : $restanteBloque;
    }

    private function esTurnoConcluido(array $turno): bool
    {
        if ((int) ($turno['es_activo'] ?? 0) !== 1) {
            return true;
        }

        if (!empty($turno['fecha_expira']) && strtotime((string) $turno['fecha_expira']) < time()) {
            return true;
        }

        if ((int) ($turno['etapa_es_terminal'] ?? 0) === 1) {
            return true;
        }

        return in_array((string) ($turno['estatus_codigo'] ?? ''), self::ESTATUS_FINALES, true);
    }

    private function resolverBadgeClass(bool $estaFinalizado, bool $esTurnoActual): string
    {
        if ($estaFinalizado) {
            return 'pt-badge--finished';
        }

        if ($esTurnoActual) {
            return 'pt-badge--serving';
        }

        return 'pt-badge--waiting';
    }

    private function resolverMensajeProgreso(bool $estaFinalizado, bool $esTurnoActual, int $turnosAntes): string
    {
        if ($estaFinalizado) {
            return 'Tu proceso ha finalizado';
        }

        if ($esTurnoActual) {
            return 'Tu turno está siendo atendido';
        }

        if ($turnosAntes <= 1) {
            return 'Tu turno está próximo a ser atendido';
        }

        return 'Tu turno está en espera';
    }

    private function formatearDuracion(int $segundos, bool $estaFinalizado, bool $esTurnoActual): string
    {
        if ($estaFinalizado) {
            return 'Proceso concluido';
        }

        if ($esTurnoActual) {
            return 'En atención';
        }

        if ($segundos <= 0) {
            return 'Menos de 1 min';
        }

        $minutos = intdiv($segundos, 60);
        $restoSegundos = $segundos % 60;

        if ($minutos <= 0) {
            return $restoSegundos . ' s';
        }

        if ($restoSegundos === 0) {
            return $minutos . ' min';
        }

        return $minutos . ' min ' . $restoSegundos . ' s';
    }

    private function formatearFecha(?string $fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }

        return date('d/m/Y H:i', strtotime($fecha));
    }
}
