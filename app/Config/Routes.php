<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/* =======================
   PÚBLICO (ALUMNOS)
   ======================= */
$routes->get('/', 'Publico\TurnoPublicController::nuevo');

$routes->group('', ['namespace' => 'App\Controllers\Publico'], static function($routes){
    $routes->get('turno', 'TurnoPublicController::nuevo');
    $routes->post('turno', 'TurnoPublicController::crear');
    $routes->get('t/(:segment)', 'TurnoPublicController::estado/$1');
});

/* =======================
   ADMIN: LOGIN / LOGOUT
   ======================= */
//  agrega un GET para mostrar el formulario de login admin
$routes->get('admin/login', 'AuthController::login');     // <-- crea este método si no existe
$routes->post('admin/login', 'AuthController::attempt');
$routes->get('admin/logout', 'AuthController::logout');

// (opcional) compatibilidad con tu POST viejo
$routes->post('login', 'AuthController::attempt');
$routes->get('logout', 'AuthController::logout');

/* =======================
   PROTEGIDO: TODO el sistema
   ======================= */
$routes->group('', ['filter' => 'auth'], function($routes) {

    // Dashboard
    $routes->get('admin', 'DashboardController::index');
    $routes->get('admin/dashboard', 'DashboardController::index');

    // Usuarios (admin panel)
    $routes->get('admin/usuarios', 'AdminUsersController::index');
    $routes->get('admin/usuarios/create', 'AdminUsersController::create');
    $routes->post('admin/usuarios', 'AdminUsersController::store');

    // Foto
    $routes->get('captura', 'CameraController::index');
    $routes->post('captura/guardar', 'CameraController::save');

    // Firma
    $routes->get('captura/firma', 'FirmaController::index');
    $routes->get('captura/firma/(:num)', 'FirmaController::index/$1');
    $routes->get('captura/firma/cola', 'FirmaController::cola');
    $routes->post('captura/firma/guardar', 'FirmaController::guardar');

    // Huella
    $routes->get('captura/huella', 'HuellaController::index');
    $routes->get('captura/huella/(:num)', 'HuellaController::index/$1');
    $routes->get('captura/huella/cola', 'HuellaController::cola');
    $routes->get('captura/huella/alumno', 'HuellaController::alumno');
    $routes->post('captura/huella/guardar', 'HuellaController::guardar');

    $routes->get('debug/db', 'DebugController::db');
});