<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Services\UserService;
use RuntimeException;

class UserController extends BaseController
{
    protected UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index()
    {
        return view('admin/users/index', [
            'title'      => 'Users',
            'activeMenu' => 'users',
            'userName'   => session('auth.full_name') ?? session('auth.username') ?? 'User',
            'users'      => $this->userService->listUsers(),
        ]);
    }

    public function create()
    {
        return view('admin/users/create', [
            'title'      => 'Create User',
            'activeMenu' => 'users',
            'userName'   => session('auth.full_name') ?? session('auth.username') ?? 'User',
            'roles'      => $this->userService->listRoles(),
        ]);
    }

    public function store()
    {
        $data = [
            'username'         => trim((string)$this->request->getPost('username')),
            'full_name'        => trim((string)$this->request->getPost('full_name')),
            'email'            => trim((string)$this->request->getPost('email')),
            'role_id'          => (int)$this->request->getPost('role_id'),
            'is_active'        => (int)($this->request->getPost('is_active') ?? 1),
            'password'         => (string)$this->request->getPost('password'),
            'password_confirm' => (string)$this->request->getPost('password2'),
        ];

        try {
            $this->userService->createUser($data);
            return redirect()->to(site_url('admin/users'))->with('ok', 'User created successfully.');
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
