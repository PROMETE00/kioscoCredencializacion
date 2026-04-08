<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// 1. PUBLIC PORTAL (Turnos)
$routes->group('', ['namespace' => 'App\Modules\PublicPortal\Controllers'], static function ($routes) {
    $routes->get('/', 'TicketController::index');
    $routes->get('turno', 'TicketController::index');
    $routes->get('turnos/general', 'TicketController::overview');
    $routes->post('turno/buscar', 'TicketController::searchStudent');
    $routes->post('turno/generar', 'TicketController::generateTicket'); // Corregido: generate -> generateTicket

    $routes->get('t/(:segment)', 'TicketController::status/$1');
    $routes->get('t/(:segment)/json', 'TicketController::statusJson/$1');
    $routes->get('turno/pdf/(:segment)', 'TicketController::downloadPdf/$1');
});

// 2. AUTHENTICATION
$routes->group('', ['namespace' => 'App\Modules\Auth\Controllers'], static function ($routes) {
    $routes->get('login', 'LoginController::index');
    $routes->post('login', 'LoginController::attempt'); // Corregido: login -> attempt
    $routes->get('logout', 'LoginController::logout');
    $routes->addRedirect('admin/login', 'login');
});

// 3. CAPTURE STATIONS (Protected by AuthFilter)
$routes->group('captura', ['namespace' => 'App\Modules\Stations\Controllers', 'filter' => 'auth'], static function ($routes) {
    
    // Redirigir la raíz del grupo a fotografía
    $routes->get('/', 'CameraController::index');

    // Fotografía
    $routes->get('foto', 'CameraController::index');
    $routes->get('foto/(:num)', 'CameraController::index/$1');
    $routes->get('foto/cola', 'CameraController::queue');
    $routes->post('foto/guardar', 'CameraController::save');

    // Firma
    $routes->get('firma', 'FirmaController::index');
    $routes->get('firma/(:num)', 'FirmaController::index/$1');
    $routes->get('firma/cola', 'FirmaController::queue'); // Unificado a queue
    $routes->get('firma/alumno', 'FirmaController::student'); // Unificado a student
    $routes->post('firma/guardar', 'FirmaController::save'); // Unificado a save

    // Huella
    $routes->get('huella', 'HuellaController::index');
    $routes->get('huella/(:num)', 'HuellaController::index/$1');
    $routes->get('huella/cola', 'HuellaController::queue'); // Unificado a queue
    $routes->get('huella/alumno', 'HuellaController::student'); // Unificado a student
    $routes->post('huella/guardar', 'HuellaController::save'); // Unificado a save
});

// 4. ADMIN DASHBOARD (Protected by AuthFilter)
$routes->group('admin', ['namespace' => 'App\Modules\Admin\Controllers', 'filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('dashboard/alumnos', 'DashboardController::students');
    $routes->post('dashboard/estatus', 'DashboardController::changeStatus');
    $routes->post('dashboard/biometrico/eliminar', 'DashboardController::clearBiometric');

    // Usuarios
    $routes->get('usuarios', 'AdminUsersController::index');
    $routes->get('usuarios/create', 'AdminUsersController::create');
    $routes->post('usuarios', 'AdminUsersController::store');
});
