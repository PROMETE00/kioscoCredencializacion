<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

/**
 * Model for managing administrative users.
 */
class UserAdminModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id_user';
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
        return $this->select('users.id_user, users.username, users.full_name, users.email, users.is_active, users.created_at, roles.code as role_code, roles.name as role_name')
            ->join('roles', 'roles.id_role = users.role_id', 'left')
            ->orderBy('users.id_user', 'DESC')
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
