<?php

namespace App\Modules\Auth\Models;

use CodeIgniter\Model;

/**
 * Model for handling internal users (operators, admins).
 */
class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id_user';
    protected $returnType = 'array';

    protected $allowedFields = [
        'username',
        'full_name',
        'email',
        'password_hash',
        'role_id',
        'is_active',
        'created_at',
        'updated_at'
    ];

    /**
     * Finds an active user by their username, including role information.
     * 
     * @param string $username
     * @return array|null
     */
    public function findActiveByUsername(string $username): ?array
    {
        return $this->select('users.*, roles.code as role_code')
                    ->join('roles', 'roles.id_role = users.role_id', 'left')
                    ->where('username', $username)
                    ->where('is_active', 1)
                    ->first();
    }
}
