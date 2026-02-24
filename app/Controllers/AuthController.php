<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        // Si ya hay sesión, manda al dashboard
        if (session()->get('auth')) {
            return redirect()->to(site_url('admin'));
        }

        return view('auth/login_kiosco', [
            'title' => 'Acceso Kiosco',
        ]);
    }

    public function attempt()
    {
        $usuario  = trim((string)$this->request->getPost('usuario'));
        $password = (string)$this->request->getPost('password');

        if ($usuario === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Completa usuario y contraseña.');
        }

        $model = new UserModel();
        $user  = $model->findActiveByUsuario($usuario);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Credenciales incorrectas.');
        }

        session()->regenerate(true);

        // Guarda lo mínimo necesario
        session()->set('auth', [
            'id'      => (int)$user['id'],
            'usuario' => $user['usuario'],
            'nombre'  => $user['nombre'],
            'rol_id'  => (int)$user['rol_id'],
        ]);

        return redirect()->to(site_url('admin'));
    }

    public function logout()
    {
        session()->remove('auth');
        session()->destroy();
        return redirect()->to(site_url('/'));
    }
}