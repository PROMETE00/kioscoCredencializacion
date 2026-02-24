<?php

namespace App\Controllers;

use App\Models\UserAdminModel;
use App\Models\RoleModel;

class AdminUsersController extends BaseController
{
    // 🔒 más adelante aquí pones check ADMIN
    private function allowForNow(): void
    {
        // Fase 2: descomenta para restringir a ADMIN
        /*
        $auth = session()->get('auth');
        if (($auth['rol_codigo'] ?? '') !== 'ADMIN') {
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
            'userName'   => session('auth.nombre') ?? session('auth.usuario') ?? 'Usuario',
            'users'      => $users,
        ]);
    }

    public function create()
    {
        $this->allowForNow();

        $roles = (new RoleModel())->listAll();
        return view('admin/users/create', [
            'title'      => 'Crear usuario',
            'activeMenu' => 'dashboard',
            'userName'   => session('auth.nombre') ?? session('auth.usuario') ?? 'Usuario',
            'roles'      => $roles,
        ]);
    }

    public function store()
    {
        $this->allowForNow();

        $usuario  = trim((string)$this->request->getPost('usuario'));
        $nombre   = trim((string)$this->request->getPost('nombre'));
        $email    = trim((string)$this->request->getPost('email'));
        $rolId    = (int)$this->request->getPost('rol_id');
        $activo   = (int)($this->request->getPost('activo') ?? 1);

        $password = (string)$this->request->getPost('password');
        $pass2    = (string)$this->request->getPost('password2');

        // Validaciones mínimas
        if ($usuario === '' || $nombre === '' || $rolId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Usuario, nombre y rol son obligatorios.');
        }
        if (strlen($password) < 6) {
            return redirect()->back()->withInput()->with('error', 'La contraseña debe tener mínimo 6 caracteres.');
        }
        if ($password !== $pass2) {
            return redirect()->back()->withInput()->with('error', 'Las contraseñas no coinciden.');
        }

        $um = new UserAdminModel();
        if ($um->findByUsuario($usuario)) {
            return redirect()->back()->withInput()->with('error', 'Ese usuario ya existe.');
        }

        // ✅ Aquí se genera el hash AUTOMÁTICO
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $um->insert([
            'usuario'       => $usuario,
            'nombre'        => $nombre,
            'email'         => ($email !== '' ? $email : null),
            'password_hash' => $hash,
            'rol_id'        => $rolId,
            'activo'        => $activo ? 1 : 0,
        ]);

        return redirect()->to(site_url('admin/usuarios'))->with('ok', 'Usuario creado.');
    }
}