<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthSeeder extends Seeder
{
    public function run()
    {
        // 1) Roles
        $roles = [
            ['codigo'=>'ADMIN',       'nombre'=>'Administrador'],
            ['codigo'=>'SUPERVISOR',  'nombre'=>'Supervisor'],
            ['codigo'=>'EST_FOTO',    'nombre'=>'Estación: Captura de Foto'],
            ['codigo'=>'EST_FIRMA',   'nombre'=>'Estación: Captura de Firma'],
            ['codigo'=>'EST_HUELLA',  'nombre'=>'Estación: Captura de Huella'],
            ['codigo'=>'EST_IMPRIME', 'nombre'=>'Estación: Impresión'],
        ];

        // Inserta roles (ignora si ya existen)
        foreach ($roles as $r) {
            $exists = $this->db->table('roles')->where('codigo', $r['codigo'])->get()->getRowArray();
            if (!$exists) $this->db->table('roles')->insert($r);
        }

        // Helper: obtener rol_id por codigo
        $getRoleId = function(string $codigo){
            $row = $this->db->table('roles')->where('codigo', $codigo)->get()->getRowArray();
            return (int)($row['id'] ?? 0);
        };

        // 2) Usuarios base (passwords de ejemplo; cámbialas luego)
        $users = [
            ['usuario'=>'admin',    'nombre'=>'Administrador',      'email'=>null, 'pass'=>'Admin.2026!',   'rol'=>'ADMIN'],
            ['usuario'=>'super',    'nombre'=>'Supervisor',         'email'=>null, 'pass'=>'Super.2026!',   'rol'=>'SUPERVISOR'],
            ['usuario'=>'foto',     'nombre'=>'Estación Foto',      'email'=>null, 'pass'=>'Foto.2026!',    'rol'=>'EST_FOTO'],
            ['usuario'=>'firma',    'nombre'=>'Estación Firma',     'email'=>null, 'pass'=>'Firma.2026!',   'rol'=>'EST_FIRMA'],
            ['usuario'=>'huella',   'nombre'=>'Estación Huella',    'email'=>null, 'pass'=>'Huella.2026!',  'rol'=>'EST_HUELLA'],
            ['usuario'=>'imprimir', 'nombre'=>'Estación Impresión', 'email'=>null, 'pass'=>'Imprime.2026!', 'rol'=>'EST_IMPRIME'],
        ];

        foreach ($users as $u) {
            $exists = $this->db->table('usuarios')->where('usuario', $u['usuario'])->get()->getRowArray();
            if ($exists) continue;

            $this->db->table('usuarios')->insert([
                'usuario'       => $u['usuario'],
                'nombre'        => $u['nombre'],
                'email'         => $u['email'],
                'password_hash' => password_hash($u['pass'], PASSWORD_DEFAULT),
                'rol_id'        => $getRoleId($u['rol']),
                'activo'        => 1,
            ]);
        }
    }
}