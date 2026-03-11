<?php

namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use App\Libraries\TurnoPdfGenerator;
use App\Models\AlumnoModel;
use App\Models\TurnoModel;
use App\Services\TurnoSeguimientoService;
use RuntimeException;

class TurnoPublicController extends BaseController
{
    protected string $viewBase = 'public';

    public function nuevo()
    {
        return view($this->viewBase . '/generar_turno', array_merge(
            $this->baseViewData(),
            [
                'vistaGeneral' => $this->seguimientoService()->obtenerVistaGeneral(),
            ]
        ));
    }

    public function general()
    {
        return view($this->viewBase . '/turnos_general', [
            'vistaGeneral' => $this->seguimientoService()->obtenerVistaGeneral(),
        ]);
    }

    public function buscarAlumno()
    {
        $identificador = trim((string) $this->request->getPost('identificador'));

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

        $alumnoDb = $this->buscarAlumnoPorIdentificador($identificador);

        if (!$alumnoDb) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => false,
                    'identificador'     => $identificador,
                ]
            ));
        }

        $this->desactivarTurnosNoVigentes((int) $alumnoDb['id_alumno']);

        $turnoActual = $this->buscarTurnoActivoPorAlumno((int) $alumnoDb['id_alumno']);

        return view($this->viewBase . '/generar_turno', array_merge(
            $this->baseViewData(),
            [
                'consultaRealizada' => true,
                'alumnoEncontrado'  => true,
                'identificador'     => $identificador,
                'alumno'            => $this->mapearAlumnoParaVista($alumnoDb, $identificador),
                'turnoExistente'    => $turnoActual !== null,
                'turnoActual'       => $turnoActual,
            ]
        ));
    }

    public function generarTurno()
    {
        $identificador = trim((string) $this->request->getPost('identificador'));

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

        $alumnoDb = $this->buscarAlumnoPorIdentificador($identificador);

        if (!$alumnoDb) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => false,
                    'identificador'     => $identificador,
                    'error'             => 'No se encontró el alumno con ese dato.',
                ]
            ));
        }

        $this->desactivarTurnosNoVigentes((int) $alumnoDb['id_alumno']);

        $turnoExistente = $this->buscarTurnoActivoPorAlumno((int) $alumnoDb['id_alumno']);

        if ($turnoExistente) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identificador,
                    'alumno'            => $this->mapearAlumnoParaVista($alumnoDb, $identificador),
                    'turnoExistente'    => true,
                    'turnoActual'       => $turnoExistente,
                ]
            ));
        }

        $catalogos = $this->obtenerCatalogosIniciales();

        if (!$catalogos) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identificador,
                    'alumno'            => $this->mapearAlumnoParaVista($alumnoDb, $identificador),
                    'error'             => 'No se encontraron registros iniciales en cat_etapas o cat_estatus_turno.',
                ]
            ));
        }

        $token     = $this->makeToken();
        $tokenHash = hash('sha256', $token);

        $ahora  = date('Y-m-d H:i:s');
        $expira = date('Y-m-d 23:59:59');

        $db = \Config\Database::connect();
        $db->transStart();

        $db->table('turnos')->insert([
            'folio'            => 'PEND',
            'alumno_id'        => (int) $alumnoDb['id_alumno'],
            'estatus_turno_id' => (int) $catalogos['estatus_id'],
            'etapa_actual_id'  => (int) $catalogos['etapa_id'],
            'es_activo'        => 1,
            'fecha_expira'     => $expira,
            'qr_token_hash'    => $tokenHash,
            'llamado_at'       => null,
            'created_at'       => $ahora,
            'updated_at'       => $ahora,
        ]);

        $turnoId = (int) $db->insertID();

        if ($turnoId <= 0) {
            $db->transRollback();

            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identificador,
                    'alumno'            => $this->mapearAlumnoParaVista($alumnoDb, $identificador),
                    'error'             => 'No se pudo crear el registro del turno. Intenta de nuevo.',
                ]
            ));
        }

        $folio = $this->buildFolio((int) $turnoId);

        $db->table('turnos')
            ->where('id_turno', $turnoId)
            ->update([
                'folio'      => $folio,
                'updated_at' => $ahora,
            ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            return view($this->viewBase . '/generar_turno', array_merge(
                $this->baseViewData(),
                [
                    'consultaRealizada' => true,
                    'alumnoEncontrado'  => true,
                    'identificador'     => $identificador,
                    'alumno'            => $this->mapearAlumnoParaVista($alumnoDb, $identificador),
                    'error'             => 'No se pudo generar el turno. Intenta de nuevo.',
                ]
            ));
        }

        $turno = $this->seguimientoService()->obtenerPorId((int) $turnoId, $token);

        if ($turno === null) {
            $turno = [
                'folio'                 => $folio,
                'nombre_completo'       => $alumnoDb['nombre_completo'],
                'identificador'         => $this->resolverIdentificadorAlumno($alumnoDb),
                'carrera'               => $alumnoDb['carrera_nombre'] ?? ($alumnoDb['carrera_clave'] ?? 'N/A'),
                'campus'                => 'Instituto Tecnológico de Oaxaca',
                'estatus'               => 'Activo',
                'etapa'                 => 'Turno generado',
                'fecha_generacion_texto'=> date('d/m/Y H:i', strtotime($ahora)),
                'fecha_expira_texto'    => date('d/m/Y H:i', strtotime($expira)),
                'seguimiento_url'       => base_url('t/' . $token),
                'pdf_url'               => base_url('turno/pdf/' . $token),
                'qr_url'                => $this->seguimientoService()->construirQrUrl(base_url('t/' . $token)),
            ];
        }

        return view($this->viewBase . '/turno_qr', [
            'turno' => $turno,
        ]);
    }

    public function estado(string $token)
    {
        $token = trim($token);

        if ($token === '') {
            return redirect()->to(base_url('turno'));
        }

        $turno = $this->seguimientoService()->obtenerPorToken($token);

        if ($turno === null) {
            return view($this->viewBase . '/turno_estado', [
                'notFound' => true,
            ]);
        }

        return view($this->viewBase . '/turno_estado', [
            'notFound' => false,
            'turno'    => $turno,
        ]);
    }

    public function estadoJson(string $token)
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

        $turno = $this->seguimientoService()->obtenerPorToken($token);

        if ($turno === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'ok'      => false,
                    'message' => 'Turno no encontrado.',
                ]);
        }

        return $this->response->setJSON([
            'ok'    => true,
            'turno' => $turno,
        ]);
    }

    public function descargarPdf(string $token)
    {
        $token = trim($token);

        if ($token === '') {
            return redirect()->to(base_url('turno'));
        }

        $turno = $this->seguimientoService()->obtenerPorToken($token);

        if ($turno === null) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('No se encontró el turno solicitado.');
        }

        try {
            $pdf = (new TurnoPdfGenerator())->generar($turno);
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

    private function buscarAlumnoPorIdentificador(string $identificador): ?array
    {
        $alumnoModel = new AlumnoModel();

        $alumno = $alumnoModel
            ->groupStart()
                ->where('numero_control', $identificador)
                ->orWhere('numero_ficha', $identificador)
            ->groupEnd()
            ->first();

        return $alumno ?: null;
    }

    private function buscarTurnoActivoPorAlumno(int $alumnoId): ?array
    {
        $ahora = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();

        $turno = $db->table('turnos t')
            ->select('
                t.id_turno,
                t.folio,
                t.created_at,
                t.fecha_expira,
                e.nombre AS etapa,
                s.nombre AS estatus
            ')
            ->join('cat_etapas e', 'e.id_etapa = t.etapa_actual_id', 'left')
            ->join('cat_estatus_turno s', 's.id_estatus = t.estatus_turno_id', 'left')
            ->where('t.alumno_id', $alumnoId)
            ->where('t.es_activo', 1)
            ->where('t.fecha_expira >=', $ahora)
            ->groupStart()
                ->where('s.codigo IS NULL', null, false)
                ->orWhereNotIn('s.codigo', ['vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'])
            ->groupEnd()
            ->orderBy('t.id_turno', 'DESC')
            ->get()
            ->getRowArray();

        return $turno ?: null;
    }

    private function obtenerCatalogosIniciales(): ?array
    {
        $db = \Config\Database::connect();

        $etapa = $db->table('cat_etapas')
            ->where('codigo', 'turno_generado')
            ->get()
            ->getRowArray();

        if (!$etapa) {
            $etapa = $db->table('cat_etapas')
                ->orderBy('orden', 'ASC')
                ->orderBy('id_etapa', 'ASC')
                ->get()
                ->getRowArray();
        }

        $estatus = $db->table('cat_estatus_turno')
            ->where('codigo', 'activo')
            ->get()
            ->getRowArray();

        if (!$estatus) {
            $estatus = $db->table('cat_estatus_turno')
                ->groupStart()
                    ->where('codigo', 'activo')
                    ->orWhere('codigo', 'EN_COLA')
                ->groupEnd()
                ->orderBy('id_estatus', 'ASC')
                ->get()
                ->getRowArray();
        }

        if (!$etapa || !$estatus) {
            return null;
        }

        return [
            'etapa_id'   => (int) $etapa['id_etapa'],
            'estatus_id' => (int) $estatus['id_estatus'],
        ];
    }

    private function mapearAlumnoParaVista(array $alumnoDb, string $identificador): array
    {
        return [
            'id_alumno'      => (int) $alumnoDb['id_alumno'],
            'identificador'  => $this->resolverIdentificadorAlumno($alumnoDb) ?: $identificador,
            'numero_control' => $alumnoDb['numero_control'] ?? null,
            'numero_ficha'   => $alumnoDb['numero_ficha'] ?? null,
            'nombre'         => $alumnoDb['nombre_completo'] ?? 'N/A',
            'carrera'        => !empty($alumnoDb['carrera_nombre'])
                ? $alumnoDb['carrera_nombre']
                : ($alumnoDb['carrera_clave'] ?? 'N/A'),

            // Tu tabla alumnos no tiene columna campus.
            // Aquí lo dejamos fijo porque tu sistema es de un solo campus.
            'campus'         => 'Instituto Tecnológico de Oaxaca',
        ];
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

    private function buildFolio(int $turnoId): string
    {
        return 'FOL-' . str_pad((string) $turnoId, 8, '0', STR_PAD_LEFT);
    }

    private function resolverIdentificadorAlumno(array $alumnoDb): string
    {
        if (!empty($alumnoDb['numero_control'])) {
            return (string) $alumnoDb['numero_control'];
        }

        return (string) ($alumnoDb['numero_ficha'] ?? '');
    }

    private function seguimientoService(): TurnoSeguimientoService
    {
        return new TurnoSeguimientoService();
    }

    private function desactivarTurnosNoVigentes(int $alumnoId): void
    {
        $ahora = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();

        $db->query(
            'UPDATE turnos t
             LEFT JOIN cat_estatus_turno s ON s.id_estatus = t.estatus_turno_id
             LEFT JOIN cat_etapas e ON e.id_etapa = t.etapa_actual_id
             SET t.es_activo = NULL, t.updated_at = ?
             WHERE t.alumno_id = ?
               AND t.es_activo = 1
               AND (
                    t.fecha_expira < ?
                    OR s.codigo IN ("vencido", "cancelado", "finalizado", "COMPLETADO", "RECHAZADO")
                    OR e.es_terminal = 1
               )',
            [$ahora, $alumnoId, $ahora]
        );
    }
}
