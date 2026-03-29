<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KioscoSeeder extends Seeder
{
    public function run()
    {
        // 1. Catálogo de Etapas
        $etapas = [
            ['codigo' => 'TURNO_GENERADO', 'nombre' => 'Turno Generado'],
            ['codigo' => 'FOTO_CAPTURADA', 'nombre' => 'Fotografía Capturada'],
            ['codigo' => 'FIRMA_REGISTRADA', 'nombre' => 'Firma Registrada'],
            ['codigo' => 'HUELLA_CAPTURADA', 'nombre' => 'Huella Capturada'],
            ['codigo' => 'COMPLETADO',       'nombre' => 'Trámite Completado'],
        ];

        foreach ($etapas as $e) {
            $exists = $this->db->table('cat_etapas')->where('codigo', $e['codigo'])->get()->getRowArray();
            if (!$exists) {
                $e['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('cat_etapas')->insert($e);
            }
        }

        // 2. Catálogo de Estatus
        $estatus = [
            ['codigo' => 'EN_ESPERA', 'nombre' => 'En Espera'],
            ['codigo' => 'EN_PROCESO', 'nombre' => 'En Proceso'],
            ['codigo' => 'FINALIZADO', 'nombre' => 'Finalizado'],
            ['codigo' => 'CANCELADO',  'nombre' => 'Cancelado'],
        ];

        foreach ($estatus as $s) {
            $exists = $this->db->table('cat_estatus_turno')->where('codigo', $s['codigo'])->get()->getRowArray();
            if (!$exists) {
                $s['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('cat_estatus_turno')->insert($s);
            }
        }

        // 3. Datos de prueba: Alumnos y Turnos
        $alumnos = [
            [
                'numero_control'  => '20160001',
                'nombre_completo' => 'JUAN PEREZ LOPEZ',
                'carrera_nombre'  => 'INGENIERIA EN SISTEMAS COMPUTACIONALES',
            ],
            [
                'numero_control'  => '20160002',
                'nombre_completo' => 'MARIA GARCIA HERNANDEZ',
                'carrera_nombre'  => 'INGENIERIA INDUSTRIAL',
            ],
        ];

        $etapaTurnoId = $this->db->table('cat_etapas')->where('codigo', 'TURNO_GENERADO')->get()->getRowArray()['id_etapa'];
        $estatusEsperaId = $this->db->table('cat_estatus_turno')->where('codigo', 'EN_ESPERA')->get()->getRowArray()['id_estatus'];

        foreach ($alumnos as $a) {
            $exists = $this->db->table('alumnos')->where('numero_control', $a['numero_control'])->get()->getRowArray();
            if (!$exists) {
                $a['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('alumnos')->insert($a);
                $alumnoId = $this->db->insertID();

                // Generar un turno para este alumno
                $this->db->table('turnos')->insert([
                    'alumno_id'        => $alumnoId,
                    'folio'            => 'T-' . str_pad($alumnoId, 4, '0', STR_PAD_LEFT),
                    'etapa_actual_id'  => $etapaTurnoId,
                    'estatus_turno_id' => $estatusEsperaId,
                    'es_activo'        => 1,
                    'fecha_expira'     => date('Y-m-d 23:59:59'),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
