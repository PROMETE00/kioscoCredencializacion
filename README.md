# Kiosco Credencializacion - Base de conocimiento unica

Este README es el **archivo unico de conocimiento** del repositorio.  
La documentacion dispersa y archivos legacy fueron retirados para concentrar arquitectura, estado tecnico y pendientes aqui.

## 1) Contexto del sistema

Aplicacion web en **CodeIgniter 4.7 (PHP 8.x)** para flujo de credencializacion:

1. Portal publico de turnos.
2. Estaciones internas de captura (foto, firma, huella).
3. Panel administrativo.

## 2) Estructura actual valida

```text
app/
  Modules/
    PublicPortal/
    Auth/
    Stations/
    Admin/
  Config/
  Database/
  Views/
public/
  assets/
  uploads/
tests/
```

## 3) Limpieza aplicada

Se eliminaron archivos legacy/no productivos:

- Documentacion duplicada/dispersa (`ARCHITECTURE.md`, `BEST_PRACTICES.md`, `GEMINI.md`, `README-TUNNEL.md`, `FIX-TUNNEL.md`, `SOLUCION-502.md`, `audit_results.md`, `DocumentacionOFICIAL/*`).
- Scripts de automatizacion legacy no ligados al runtime (`configure-tunnel.sh`, `diagnose.sh`, `test-tunnel.sh`).
- Endpoints/archivos de debug expuestos en `public/` (`debug.php`, `diagnostic.php`, `info.php`, `test.php`, `favicon.ico.backup`).
- Codigo PHP legacy sin referencias (`app/Controllers/DebugController.php`, `app/Libraries/TurnoPdfGenerator.php`, `app/Services/TurnoSeguimientoService.php`).
- Vistas parciales/layout sin uso y vista rota (`app/Views/layouts/capture_layout.php`, `app/Views/partials/captura_subheader.php`, `app/Views/partials/capture/head_mediapipe.php`, `app/Views/public/turno_generado.php`).
- Assets no enlazados (`public/assets/css/kiosco-login.css`, `public/assets/css/turno-generado.css`, `public/assets/js/capture.js`).

## 4) Pantallas y assets activos

### Vistas activas por flujo

- Publico: `public/autoservicio_inicio`, `public/autoservicio_firma`, `public/autoservicio_huella`, `public/autoservicio_foto`, `public/turno_estado`, `public/turnos_general`, `public/turno_pdf`.
- Captura interna: `camera/capture_queue`, `captura/firma`, `capture/huella`.
- Admin/Auth: `admin/dashboard`, `admin/users/index`, `admin/users/create`, `auth/login_kiosco`.

### CSS/JS activos

- CSS: `discere-theme.css`, `user-menu.css`, `user-dropdown.css`, `capture-queue.css`, `capture-common.css`, `capture-layout.css`, `firma.css`, `huella.css`, `public-turno.css`, `dashboard.css`, `admin-users.css`, `admin-user-create.css`, `kiosco-login-v2.css`, `floating-lines.css`.
- JS: `capture_queue.js`, `firma-ui.js`, `huella-ui.js`, `seguimiento.js`, `dashboard.js`, `floating-lines.js`.

## 5) Inventario de codigo no ingles (fuera de comentarios)

Se detecto mezcla de naming en identificadores, vistas, rutas y mensajes.  
**Solo comentarios/documentacion deberian quedar en espanol**, por lo que esto es deuda tecnica activa:

- Controladores con nombres/metodos/params en espanol:
  - `app/Modules/Stations/Controllers/FirmaController.php`
  - `app/Modules/Stations/Controllers/HuellaController.php`
  - `app/Modules/PublicPortal/Controllers/TicketController.php`
  - `app/Modules/PublicPortal/Controllers/FingerprintController.php`
  - `app/Modules/Admin/Controllers/AdminUsersController.php`
- Modelos y servicios con campos mixtos:
  - `app/Modules/Stations/Models/CaptureQueueModel.php`
  - `app/Modules/Stations/Models/FirmaModel.php`
  - `app/Modules/Stations/Models/HuellaModel.php`
  - `app/Modules/PublicPortal/Services/TicketTrackingService.php`
- Vistas y rutas en espanol:
  - `app/Views/captura/*`, `app/Views/public/autoservicio_*`, `app/Config/Routes.php` (segmentos `captura`, `turno`, `huella`, `usuarios`).
- JavaScript con estado/labels en espanol:
  - `public/assets/js/capture_queue.js`
  - `public/assets/js/firma-ui.js`
  - `public/assets/js/huella-ui.js`
  - `public/assets/js/seguimiento.js`

## 6) Propuesta de estructura objetivo (sin espagueti)

### Convencion objetivo

- Codigo: ingles al 100% (clases, metodos, variables, nombres de vista, rutas internas y codigos de catalogo).
- Espanol: solo comentarios/documentacion y textos UI si negocio lo requiere.

### Reorganizacion propuesta

```text
app/Modules/
  PublicPortal/
    Controllers/TicketController.php
    Services/TicketService.php
    Services/TicketTrackingService.php
    Repositories/TicketRepository.php
    Views/
      ticket/index.php
      ticket/signature.php
      ticket/fingerprint.php
      ticket/photo.php
      ticket/status.php
  Stations/
    Controllers/
      PhotoCaptureController.php
      SignatureCaptureController.php
      FingerprintCaptureController.php
    Services/
      PhotoCaptureService.php
      SignatureCaptureService.php
      FingerprintCaptureService.php
    Repositories/
      CaptureQueueRepository.php
```

### Plan de migracion sugerido

1. Estandarizar nombres de rutas y vistas nuevas en ingles, manteniendo redirects desde rutas legacy.
2. Extraer logica de controladores grandes (especialmente `TicketController`) a servicios/repositorios.
3. Renombrar DTO/keys de respuesta a ingles y mantener adapter temporal para vistas legacy.
4. Eliminar definitivamente aliases en espanol cuando frontend y DB queden migrados.

## 7) Comandos operativos

### Docker

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php spark migrate
docker compose exec app php spark db:seed AuthSeeder
```

### Local

```bash
composer install
php spark serve
php vendor/bin/phpunit --testdox
```
