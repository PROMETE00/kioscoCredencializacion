<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

/**
 * Model for managing administrative users.
 */
class UserAdminModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'username', 'full_name', 'email', 'password_hash', 'role_id', 'is_active'
    ];

    /**
     * Lists all users with their associated role information.
     */
    public function listWithRoles(): array
    {
        return $this->select('users.id, users.username as usuario, users.full_name as nombre, users.email, users.is_active as activo, users.created_at, roles.code as rol_codigo, roles.name as rol_nombre')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->orderBy('users.id', 'DESC')
            ->findAll();
    }

    /**
     * Finds a user by their username.
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }
}
