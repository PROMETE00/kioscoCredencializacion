<?php

namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use App\Models\AlumnoModel;
use App\Models\TurnoModel;

class TurnoPublicController extends BaseController
{
    protected string $viewBase = 'public';

    public function nuevo()
    {
        return view($this->viewBase . '/generar_turno', $this->baseViewData());
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

        $turnoModel = new TurnoModel();

        $token     = $this->makeToken();
        $tokenHash = hash('sha256', $token);

        $ahora  = date('Y-m-d H:i:s');
        $expira = date('Y-m-d 23:59:59');

        $db = \Config\Database::connect();
        $db->transStart();

        $turnoId = $turnoModel->insert([
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
        ], true);

        $folio = 'A-' . str_pad((string) $turnoId, 6, '0', STR_PAD_LEFT);

        $turnoModel->update($turnoId, [
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

        $url = base_url('t/' . $token);

        return view($this->viewBase . '/turno_qr', [
            'folio'  => $folio,
            'nombre' => $alumnoDb['nombre_completo'],
            'url'    => $url,
            'expira' => $expira,
        ]);
    }

    public function estado(string $token)
    {
        $token = trim($token);

        if ($token === '') {
            return redirect()->to(base_url('turno'));
        }

        $hash = hash('sha256', $token);

        $db = \Config\Database::connect();

        $row = $db->table('turnos t')
            ->select('
                t.id_turno,
                t.folio,
                t.es_activo,
                t.fecha_expira,
                t.llamado_at,
                t.created_at,
                e.nombre AS etapa,
                s.nombre AS estatus
            ')
            ->join('cat_etapas e', 'e.id_etapa = t.etapa_actual_id', 'left')
            ->join('cat_estatus_turno s', 's.id_estatus = t.estatus_turno_id', 'left')
            ->where('t.qr_token_hash', $hash)
            ->orderBy('t.id_turno', 'DESC')
            ->get()
            ->getRowArray();

        if (!$row) {
            return view($this->viewBase . '/turno_estado', [
                'notFound' => true,
            ]);
        }

        return view($this->viewBase . '/turno_estado', [
            'notFound' => false,
            'turno'    => $row,
        ]);
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
        $turnoModel = new TurnoModel();
        $ahora = date('Y-m-d H:i:s');

        $turno = $turnoModel
            ->where('alumno_id', $alumnoId)
            ->where('es_activo', 1)
            ->where('fecha_expira >=', $ahora)
            ->orderBy('id_turno', 'DESC')
            ->first();

        return $turno ?: null;
    }

    private function obtenerCatalogosIniciales(): ?array
    {
        $db = \Config\Database::connect();

        $etapa = $db->table('cat_etapas')
            ->orderBy('orden', 'ASC')
            ->orderBy('id_etapa', 'ASC')
            ->get()
            ->getRowArray();

        $estatus = $db->table('cat_estatus_turno')
            ->orderBy('id_estatus', 'ASC')
            ->get()
            ->getRowArray();

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
            'identificador'  => $identificador,
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
        ];
    }

    private function makeToken(): string
    {
        $raw = random_bytes(24);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}