<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// 1. PUBLIC PORTAL (Turnos)
$routes->group('', ['namespace' => 'App\Modules\PublicPortal\Controllers'], static function ($routes) {
    // New Self-Service Flow (English)
    $routes->group('self-service', static function ($routes) {
        $routes->get('/', 'SelfServiceController::index');
        $routes->post('identify', 'SelfServiceController::identify');
        $routes->get('confirm', 'SelfServiceController::confirm');
        $routes->get('signature', 'SelfServiceController::signature');
        $routes->post('signature', 'SelfServiceController::saveSignature');
        $routes->get('fingerprint', 'SelfServiceController::fingerprint');
        $routes->get('photo', 'SelfServiceController::photo');
        $routes->post('photo', 'SelfServiceController::savePhoto');
        $routes->get('success', 'SelfServiceController::success');
    });

    $routes->get('/', 'TicketController::index');
    $routes->get('turno', 'TicketController::index');
    $routes->get('turnos/general', 'TicketController::overview');
    $routes->post('turno/buscar', 'TicketController::searchStudent');
    $routes->post('turno/generar', 'TicketController::generateTicket'); // Corregido: generate -> generateTicket
    $routes->post('turno/firma', 'TicketController::savePublicSignature');
    $routes->post('turno/foto', 'TicketController::savePublicPhoto');
    $routes->get('foto', 'TicketController::photo');
    $routes->post('turno/Fingerprint', 'FingerprintController::registerChallenge');
    $routes->post('fingerprint/registerChallenge',  'FingerprintController::registerChallenge');
    $routes->post('fingerprint/Verifyregister',  'FingerprintController::Verifyregister');
    $routes->post('fingerprint/authChallenge',      'FingerprintController::authChallenge');
    $routes->post('fingerprint/Verifyauth',      'FingerprintController::Verifyauth');
    $routes->post('fingerprint/existFingerprint',   'FingerprintController::existFingerprint');
    $routes->get('t/(:segment)', 'TicketController::status/$1');
    $routes->get('t/(:segment)/json', 'TicketController::statusJson/$1');
    $routes->get('turno/pdf/(:segment)', 'TicketController::downloadPdf/$1');

    // Rutas para Huella / WebAuthn (Autoservicio)
    $routes->get('huella', 'FingerprintController::index');
    $routes->post('huella/tiene-huella', 'FingerprintController::existFingerprint');
    $routes->post('huella/registro-challenge', 'FingerprintController::registerChallenge');
    $routes->post('huella/registro-verificar', 'FingerprintController::Verifyregister');
    $routes->post('huella/auth-challenge', 'FingerprintController::authChallenge');
    $routes->post('huella/auth-verificar', 'FingerprintController::Verifyauth');
    $routes->post('huella/finalizar', 'FingerprintController::finishFlow');
    $routes->post('huella/guardar', 'TicketController::savePublicSignature'); // Reutilizamos la lógica de guardado de firma
    
});

// 2. AUTHENTICATION
$routes->group('', ['namespace' => 'App\Modules\Auth\Controllers'], static function ($routes) {
    $routes->get('login', 'LoginController::index');
    $routes->post('login', 'LoginController::attempt'); // Corregido: login -> attempt
    $routes->get('logout', 'LoginController::logout');
    $routes->addRedirect('admin/login', 'login');
});

// 3. CAPTURE STATIONS (Protected by AuthFilter)
$routes->group('stations', ['namespace' => 'App\Modules\Stations\Controllers', 'filter' => 'auth'], static function ($routes) {
    
    // Redirect root to photo
    $routes->get('/', 'PhotoController::index');

    // Photo
    $routes->get('photo', 'PhotoController::index');
    $routes->get('photo/(:num)', 'PhotoController::index/$1');
    $routes->get('photo/queue', 'PhotoController::queue');
    $routes->post('photo/save', 'PhotoController::save');

    // Signature
    $routes->get('signature', 'SignatureController::index');
    $routes->get('signature/(:num)', 'SignatureController::index/$1');
    $routes->get('signature/queue', 'SignatureController::queue');
    $routes->get('signature/student', 'SignatureController::student');
    $routes->post('signature/save', 'SignatureController::save');

    // Fingerprint
    $routes->get('fingerprint', 'FingerprintController::index');
    $routes->get('fingerprint/(:num)', 'FingerprintController::index/$1');
    $routes->get('fingerprint/queue', 'FingerprintController::queue');
    $routes->get('fingerprint/student', 'FingerprintController::student');
    $routes->post('fingerprint/save', 'FingerprintController::save');
});

// 4. ADMIN DASHBOARD (Protected by AuthFilter)
$routes->group('admin', ['namespace' => 'App\Modules\Admin\Controllers', 'filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('dashboard/worklist', 'DashboardController::getWorklist');
    $routes->post('dashboard/status', 'DashboardController::updateStatus');
    $routes->post('dashboard/biometric/clear', 'DashboardController::clearBiometric');
    $routes->post('dashboard/delivery', 'DashboardController::recordDelivery');

    // Users
    $routes->get('users', 'UserController::index');
    $routes->get('users/create', 'UserController::create');
    $routes->post('users', 'UserController::store');

    // Legacy redirects for compatibility during transition
    $routes->get('usuarios', 'UserController::index');
    $routes->get('usuarios/create', 'UserController::create');
    $routes->get('dashboard/alumnos', 'DashboardController::getWorklist');
});
