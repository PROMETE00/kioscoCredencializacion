<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('captura', 'CameraController::index');
$routes->post('captura/guardar', 'CameraController::save');
