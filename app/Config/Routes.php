<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// =======================
// Público: Login
// =======================
$routes->get('/', 'AuthController::login');
$routes->post('login', 'AuthController::attempt');
$routes->get('logout', 'AuthController::logout');

// =======================
// Protegido: TODO el sistema
// =======================
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

    // Debug (si lo quieres temporalmente)
    $routes->get('debug/db', 'DebugController::db');
});