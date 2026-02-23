<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('captura', 'CameraController::index');
$routes->post('captura/guardar', 'CameraController::save');

$routes->get('captura/firma', 'FirmaController::index');
$routes->get('captura/firma/(:num)', 'FirmaController::index/$1');

$routes->get('captura/firma/cola', 'FirmaController::cola');
$routes->post('captura/firma/guardar', 'FirmaController::guardar');

$routes->get('/admin', 'DashboardController::index');
$routes->get('/admin/dashboard', 'DashboardController::index');