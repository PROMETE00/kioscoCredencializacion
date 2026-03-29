<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// 1. PUBLIC PORTAL (Turnos)
$routes->group('', ['namespace' => 'App\Modules\PublicPortal\Controllers'], static function ($routes) {
    $routes->get('/', 'TicketController::index');
    $routes->get('turno', 'TicketController::index');
    $routes->post('turno/buscar', 'TicketController::searchStudent');
    $routes->post('turno/generar', 'TicketController::generate');

    $routes->get('t/(:segment)', 'TicketController::status/$1');
    $routes->get('t/(:segment)/json', 'TicketController::statusJson/$1');
    $routes->get('turno/pdf/(:segment)', 'TicketController::downloadPdf/$1');
});

// 2. AUTHENTICATION
$routes->group('', ['namespace' => 'App\Modules\Auth\Controllers'], static function ($routes) {
    $routes->get('login', 'LoginController::index');
    $routes->post('login', 'LoginController::login');
    $routes->get('logout', 'LoginController::logout');
});

// 3. CAPTURE STATIONS (Protected by AuthFilter)
$routes->group('captura', ['namespace' => 'App\Modules\Stations\Controllers', 'filter' => 'auth'], static function ($routes) {
    
    // Fotografía
    $routes->get('foto', 'CameraController::index');
    $routes->get('foto/(:num)', 'CameraController::index/$1');
    $routes->get('foto/cola', 'CameraController::queue');
    $routes->post('foto/guardar', 'CameraController::save');

    // Firma
    $routes->get('firma', 'FirmaController::index');
    $routes->get('firma/(:num)', 'FirmaController::index/$1');
    $routes->get('firma/cola', 'FirmaController::cola');
    $routes->get('firma/alumno', 'FirmaController::alumno');
    $routes->post('firma/guardar', 'FirmaController::guardar');

    // Huella
    $routes->get('huella', 'HuellaController::index');
    $routes->get('huella/(:num)', 'HuellaController::index/$1');
    $routes->get('huella/cola', 'HuellaController::cola');
    $routes->get('huella/alumno', 'HuellaController::alumno');
    $routes->post('huella/guardar', 'HuellaController::guardar');
});

// 4. ADMIN DASHBOARD (Protected by AuthFilter)
$routes->group('admin', ['namespace' => 'App\Modules\Admin\Controllers', 'filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('dashboard/alumnos', 'DashboardController::students');
    $routes->post('dashboard/estatus', 'DashboardController::changeStatus');
    $routes->post('dashboard/biometrico/eliminar', 'DashboardController::clearBiometric');

    // Usuarios
    $routes->get('usuarios', 'AdminUsersController::index');
    $routes->get('usuarios/create', 'AdminUsersController::create');
    $routes->post('usuarios', 'AdminUsersController::store');
});
