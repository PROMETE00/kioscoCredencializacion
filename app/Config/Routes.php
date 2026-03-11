<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/* =======================
   PÚBLICO (ALUMNOS)
   ======================= */
$routes->get('/', 'Publico\TurnoPublicController::nuevo');
$routes->get('turno', 'Publico\TurnoPublicController::nuevo');
$routes->get('turnos/general', 'Publico\TurnoPublicController::general');
$routes->post('turno/buscar', 'Publico\TurnoPublicController::buscarAlumno');
$routes->post('turno/generar', 'Publico\TurnoPublicController::generarTurno');
$routes->get('turno/pdf/(:segment)', 'Publico\TurnoPublicController::descargarPdf/$1');
$routes->get('turno/seguimiento/(:segment)', 'Publico\TurnoPublicController::estadoJson/$1');
$routes->get('t/(:segment)', 'Publico\TurnoPublicController::estado/$1');

/* =======================
   ADMIN: LOGIN / LOGOUT
   ======================= */
//  agrega un GET para mostrar el formulario de login admin
$routes->get('admin/login', 'AuthController::login');
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
   $routes->get('admin/dashboard/alumnos', 'DashboardController::alumnos');
   $routes->post('admin/dashboard/estatus', 'DashboardController::cambiarEstatus');
   $routes->post('admin/dashboard/biometrico/eliminar', 'DashboardController::borrarBiometrico');

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
    $routes->get('captura/firma/alumno', 'FirmaController::alumno');
    $routes->post('captura/firma/guardar', 'FirmaController::guardar');

    // Huella
   $routes->get('captura/huella', 'HuellaController::index');
   $routes->get('captura/huella/(:num)', 'HuellaController::index/$1');
   $routes->get('captura/huella/cola', 'HuellaController::cola');
   $routes->get('captura/huella/alumno', 'HuellaController::alumno');
   $routes->post('captura/huella/guardar', 'HuellaController::guardar');

   $routes->get('debug/db', 'DebugController::db');
});
