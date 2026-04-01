<?php

namespace App\Modules\Auth\Controllers;

use App\Controllers\BaseController;
use App\Modules\Auth\Models\UserModel;

/**
 * Controller for handling user login and logout.
 */
class LoginController extends BaseController
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function index()
    {
        // Si ya hay sesión activa, redirige al dashboard.
        if (session()->get('auth')) {
            return redirect()->to(site_url('admin'));
        }

        return view('auth/login_kiosco', [
            'title' => 'Acceso Kiosco',
        ]);
    }

    /**
     * Intenta autenticar las credenciales del usuario.
     */
    public function attempt()
    {
        $username = trim((string)$this->request->getPost('usuario'));
        $password = (string)$this->request->getPost('password');

        if ($username === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Completa usuario y contraseña.');
        }

        $model = new UserModel();
        $user  = $model->findActiveByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Credenciales incorrectas.');
        }

        session()->regenerate(true);

        // Se guarda el mínimo de información necesaria en sesión.
        session()->set('auth', [
            'id'        => (int)$user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role_id'   => (int)$user['role_id'],
            'role_code' => $user['role_code'] ?? null,
        ]);

        return redirect()->to(site_url('admin'));
    }

    /**
     * Finaliza la sesión del usuario.
     */
    public function logout()
    {
        session()->remove('auth');
        session()->destroy();
        return redirect()->to(site_url('/'));
    }
}
