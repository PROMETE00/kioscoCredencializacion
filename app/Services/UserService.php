<?php

namespace App\Services;

use App\Modules\Auth\Models\UserModel;
use App\Models\RoleModel;
use Config\Schema;
use RuntimeException;

class UserService extends BaseService
{
    protected Schema $schema;
    protected UserModel $userModel;
    protected RoleModel $roleModel;

    public function __construct()
    {
        parent::__construct();
        $this->schema = new Schema();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    /**
     * Lists all users with their roles.
     */
    public function listUsers(): array
    {
        return $this->userModel->listWithRoles();
    }

    /**
     * Lists all available roles.
     */
    public function listRoles(): array
    {
        return $this->roleModel->listAll();
    }

    /**
     * Creates a new user.
     */
    public function createUser(array $data): int
    {
        if (empty($data['username']) || empty($data['full_name']) || empty($data['role_id'])) {
            throw new RuntimeException('Username, full name and role are required.');
        }

        if ($this->userModel->findByUsername($data['username'])) {
            throw new RuntimeException('Username already exists.');
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            throw new RuntimeException('Password must be at least 6 characters long.');
        }

        if ($data['password'] !== ($data['password_confirm'] ?? '')) {
            throw new RuntimeException('Passwords do not match.');
        }

        $userId = $this->userModel->insert([
            'username'      => $data['username'],
            'full_name'     => $data['full_name'],
            'email'         => $data['email'] ?? null,
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role_id'       => $data['role_id'],
            'is_active'     => $data['is_active'] ?? 1,
        ]);

        if (!$userId) {
            throw new RuntimeException('Could not create user.');
        }

        return (int) $userId;
    }
}
