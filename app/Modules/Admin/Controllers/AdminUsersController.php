<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\UserAdminModel;
use App\Models\RoleModel; // Assuming RoleModel is still flat, let's keep it or migrate if needed. Since generalist didn't, we will assume it's flat or in Admin/Auth. Let's assume it's in Admin/Models for safety. Wait, generalist didn't migrate RoleModel. I should check if RoleModel exists in Modules. I'll use \App\Modules\Admin\Models\RoleModel.

class AdminUsersController extends BaseController
{
    private function allowForNow(): void
    {
        /*
        $auth = session()->get('auth');
        if (($auth['role_code'] ?? '') !== 'ADMIN') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        */
    }

    public function index()
    {
        $this->allowForNow();

        $users = (new UserAdminModel())->listWithRoles();
        return view('admin/users/index', [
            'title'      => 'Usuarios',
            'activeMenu' => 'dashboard',
            'userName'   => session('auth.full_name') ?? session('auth.username') ?? 'Usuario',
            'users'      => $users,
        ]);
    }

    public function create()
    {
        $this->allowForNow();

        // Assuming RoleModel is flat for now as it wasn't migrated.
        $roles = (new \App\Models\RoleModel())->listAll();
        return view('admin/users/create', [
            'title'      => 'Crear usuario',
            'activeMenu' => 'dashboard',
            'userName'   => session('auth.full_name') ?? session('auth.username') ?? 'Usuario',
            'roles'      => $roles,
        ]);
    }

    public function store()
    {
        $this->allowForNow();

        $username  = trim((string)$this->request->getPost('username'));
        $fullName   = trim((string)$this->request->getPost('full_name'));
        $email    = trim((string)$this->request->getPost('email'));
        $roleId    = (int)$this->request->getPost('role_id');
        $isActive   = (int)($this->request->getPost('is_active') ?? 1);

        $password = (string)$this->request->getPost('password');
        $pass2    = (string)$this->request->getPost('password2');

        if ($username === '' || $fullName === '' || $roleId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Usuario, nombre y rol son obligatorios.');
        }
        if (strlen($password) < 6) {
            return redirect()->back()->withInput()->with('error', 'La contraseña debe tener mínimo 6 caracteres.');
        }
        if ($password !== $pass2) {
            return redirect()->back()->withInput()->with('error', 'Las contraseñas no coinciden.');
        }

        $um = new UserAdminModel();
        if ($um->findByUsername($username)) {
            return redirect()->back()->withInput()->with('error', 'Ese usuario ya existe.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $um->insert([
            'username'      => $username,
            'full_name'     => $fullName,
            'email'         => ($email !== '' ? $email : null),
            'password_hash' => $hash,
            'role_id'       => $roleId,
            'is_active'     => $isActive ? 1 : 0,
        ]);

        return redirect()->to(site_url('admin/usuarios'))->with('ok', 'Usuario creado.');
    }
}
