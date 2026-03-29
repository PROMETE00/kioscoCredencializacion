<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthSeeder extends Seeder
{
    public function run()
    {
        // 1) Roles
        $roles = [
            ['code' => 'ADMIN',       'name' => 'Administrador'],
            ['code' => 'SUPERVISOR',  'name' => 'Supervisor'],
            ['code' => 'EST_PHOTO',   'name' => 'Estación: Captura de Foto'],
            ['code' => 'EST_SIGN',    'name' => 'Estación: Captura de Firma'],
            ['code' => 'EST_FINGER',  'name' => 'Estación: Captura de Huella'],
            ['code' => 'EST_PRINT',   'name' => 'Estación: Impresión'],
        ];

        foreach ($roles as $r) {
            $exists = $this->db->table('roles')->where('code', $r['code'])->get()->getRowArray();
            if (!$exists) {
                $r['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('roles')->insert($r);
            }
        }

        $getRoleId = function (string $code) {
            $row = $this->db->table('roles')->where('code', $code)->get()->getRowArray();
            return (int) ($row['id_role'] ?? 0);
        };

        // 2) Default Users
        $defaultPassword = getenv('SEED_DEFAULT_PASSWORD') ?: 'changeme';
        $adminPassword   = getenv('SEED_ADMIN_PASSWORD') ?: $defaultPassword;

        $users = [
            ['username' => 'admin',    'full_name' => 'Administrador',      'email' => null, 'pass' => $adminPassword,   'role' => 'ADMIN'],
            ['username' => 'super',    'full_name' => 'Supervisor',         'email' => null, 'pass' => $defaultPassword, 'role' => 'SUPERVISOR'],
            ['username' => 'photo',    'full_name' => 'Estación Foto',      'email' => null, 'pass' => $defaultPassword, 'role' => 'EST_PHOTO'],
            ['username' => 'signature','full_name' => 'Estación Firma',     'email' => null, 'pass' => $defaultPassword, 'role' => 'EST_SIGN'],
            ['username' => 'finger',   'full_name' => 'Estación Huella',    'email' => null, 'pass' => $defaultPassword, 'role' => 'EST_FINGER'],
            ['username' => 'print',    'full_name' => 'Estación Impresión', 'email' => null, 'pass' => $defaultPassword, 'role' => 'EST_PRINT'],
        ];

        foreach ($users as $u) {
            $exists = $this->db->table('users')->where('username', $u['username'])->get()->getRowArray();
            if ($exists) continue;

            $this->db->table('users')->insert([
                'username'      => $u['username'],
                'full_name'     => $u['full_name'],
                'email'         => $u['email'],
                'password_hash' => password_hash($u['pass'], PASSWORD_DEFAULT),
                'role_id'       => $getRoleId($u['role']),
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
