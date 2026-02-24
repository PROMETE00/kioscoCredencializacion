<?php

namespace App\Models;

use CodeIgniter\Model;

class UserAdminModel extends Model
{
    protected $table      = 'usuarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'usuario', 'nombre', 'email', 'password_hash', 'rol_id', 'activo'
    ];

    public function listWithRoles(): array
    {
        return $this->select('usuarios.id, usuarios.usuario, usuarios.nombre, usuarios.email, usuarios.activo, usuarios.created_at, roles.codigo as rol_codigo, roles.nombre as rol_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->orderBy('usuarios.id', 'DESC')
            ->findAll();
    }

    public function findByUsuario(string $usuario): ?array
    {
        return $this->where('usuario', $usuario)->first();
    }
}