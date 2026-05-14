# Auditoría Técnica Completa - Kiosco Credencialización

**Fecha:** 2026-05-14
**Commit:** `50fc77a`
**Estado del sistema:** Contenedores activos, DB poblada, app respondiendo 200 OK

---

## Estado del Sistema

| Componente | Estado | Detalle |
|---|---|---|
| kiosco_app | ✅ Running | PHP 8.2 + Apache, puerto 3001 |
| kiosco_db | ✅ Running | MySQL 8.0, puerto 3307 |
| App HTTP | ✅ 200 OK | http://localhost:3001/ |
| Migraciones | ✅ 2/2 aplicadas | 10 tablas creadas |
| Seeders | ✅ 3/3 ejecutados | Datos de prueba completos |

### Datos en Base de Datos

| Tabla | Registros |
|---|---|
| roles | 6 |
| users | 7 |
| cat_stages | 5 |
| cat_ticket_status | 4 |
| students | 100 |
| tickets | 71 |
| ticket_events | 28 |
| files | 5 |
| huellas_credenciales | 0 |

---

## A) Código Muerto o Huérfano

### 🔴 Archivos con referencias rotas

| Archivo | Problema |
|---|---|
| `app/Services/UserService.php:5` | Importa `UserAdminModel` que **no existe** (fue eliminado). Debería usar `App\Modules\Auth\Models\UserModel` |

### ⚠️ Modelos legacy sin uso activo

| Archivo | Estado | Detalle |
|---|---|---|
| `app/Modules/PublicPortal/Models/TicketModel.php` | ⚠️ No se usa directamente | El código usa `TicketRepository` + `TicketTrackingService` en su lugar |
| `app/Modules/PublicPortal/Models/StudentModel.php` | ⚠️ Solo usado en TicketController | El nuevo flujo usa `StudentRepository` |
| `app/Modules/PublicPortal/Models/StageModel.php` | 🔴 Sin referencias | No encontrado en ningún controller/service |
| `app/Modules/PublicPortal/Models/TicketStatusModel.php` | 🔴 Sin referencias | No encontrado en ningún controller/service |
| `app/Models/RoleModel.php` | ✅ Usado | Usado por `UserService` |

### ⚠️ Vistas legacy que coexisten con las nuevas

El sistema tiene **dos flujos paralelos**:

**Flujo antiguo (vistas en español):**
- `app/Views/public/autoservicio_inicio.php`
- `app/Views/public/autoservicio_firma.php`
- `app/Views/public/autoservicio_foto.php`
- `app/Views/public/autoservicio_huella.php`
- `app/Views/public/turno_estado.php`
- `app/Views/public/turnos_general.php`
- `app/Views/public/turno_pdf.php`
- `app/Views/public/keyboardComponent.php`

**Flujo nuevo (vistas en inglés):**
- `app/Views/public/self_service/index.php`
- `app/Views/public/self_service/confirm.php`
- `app/Views/public/self_service/signature.php`
- `app/Views/public/self_service/fingerprint.php`
- `app/Views/public/self_service/photo.php`
- `app/Views/public/self_service/success.php`

Ambos flujos están activos en Routes.php. El flujo antiguo es usado por `TicketController` y `FingerprintController` (public portal). El nuevo flujo es usado por `SelfServiceController`.

### ⚠️ Partials legacy

| Directorio | Archivos | Usado por |
|---|---|---|
| `partials/capture/` | 3 archivos | Posiblemente las vistas de stations |
| `partials/firma/` | 3 archivos | Flujo antiguo de firma |
| `partials/huella/` | 2 archivos | Flujo antiguo de huella |

---

## B) Consistencia del Schema

### ✅ Lo que coincide correctamente

| Schema.php | Migración | BD Real | Estado |
|---|---|---|---|
| `students` | `students` | `students` | ✅ |
| `tickets` | `tickets` | `tickets` | ✅ |
| `ticket_events` | `ticket_events` | `ticket_events` | ✅ |
| `files` | `files` | `files` | ✅ |
| `roles` | `roles` | `roles` | ✅ |
| `users` | `users` | `users` | ✅ |
| `cat_stages` | `cat_stages` | `cat_stages` | ✅ |
| `cat_ticket_status` | `cat_ticket_status` | `cat_ticket_status` | ✅ |

### ⚠️ Discrepancias encontradas

| Ubicación | Problema | Impacto |
|---|---|---|
| `BiometricService.php:112` | Usa DB query directo en lugar de TicketRepository | Rompe abstracción |
| `TicketController.php:178-227` | Queries directos a `tickets`, `cat_stages`, `cat_ticket_status` | Lógica de negocio en controller |
| `TicketController.php:674-703` | Query directo con JOINs complejos | Debería estar en Repository |
| `TicketTrackingService.php:139-168` | Queries directos sin usar Schema config | No usa abstracción de schema |
| `QueueService.php:27-73` | Queries directos con hard-coded table names | No usa Schema config |
| `FingerprintController.php:161` | Tabla `huellas_credenciales` hard-coded | ✅ Ya migrada |
| `TicketController.php:145` | Referencia a `id_turno` que no existe en tickets | Bug potencial (fallback a `id`) |

### 🔴 Campos en código que no existen en Schema.php fields mapping

Ya fueron agregados en esta auditoría:
- `students.major_code` ✅ Agregado
- `tickets.qr_token_hash` ✅ Agregado
- `tickets.called_at` ✅ Agregado
- `tickets.expires_at` ✅ Agregado

---

## C) Rutas Legacy en Español

### Rutas con segmentos en español

| Ruta Actual | Método | Controller | Equivalente en Inglés |
|---|---|---|---|
| `GET /turno` | GET | TicketController::index | `/ticket` |
| `GET /turnos/general` | GET | TicketController::overview | `/tickets/overview` |
| `POST /turno/buscar` | POST | TicketController::searchStudent | `/tickets/search` |
| `POST /turno/generar` | POST | TicketController::generateTicket | `/tickets/generate` |
| `POST /turno/firma` | POST | TicketController::savePublicSignature | `/tickets/signature` |
| `POST /turno/foto` | POST | TicketController::savePublicPhoto | `/tickets/photo` |
| `GET /foto` | GET | TicketController::photo | `/ticket/photo` |
| `GET /t/(:segment)` | GET | TicketController::status | `/ticket/status/:token` |
| `GET /t/(:segment)/json` | GET | TicketController::statusJson | `/ticket/status/:token/json` |
| `GET /turno/pdf/(:segment)` | GET | TicketController::downloadPdf | `/ticket/pdf/:token` |
| `GET /huella` | GET | FingerprintController::index | `/fingerprint` |
| `POST /huella/tiene-huella` | POST | FingerprintController::existFingerprint | `/fingerprint/check` |
| `POST /huella/registro-challenge` | POST | FingerprintController::registerChallenge | `/fingerprint/register/challenge` |
| `POST /huella/registro-verificar` | POST | FingerprintController::Verifyregister | `/fingerprint/register/verify` |
| `POST /huella/auth-challenge` | POST | FingerprintController::authChallenge | `/fingerprint/auth/challenge` |
| `POST /huella/auth-verificar` | POST | FingerprintController::Verifyauth | `/fingerprint/auth/verify` |
| `POST /huella/finalizar` | POST | FingerprintController::finishFlow | `/fingerprint/finish` |
| `POST /huella/guardar` | POST | TicketController::savePublicSignature | `/fingerprint/save` |
| `GET /admin/usuarios` | GET | UserController::index | `/admin/users` (ya existe) |
| `GET /admin/usuarios/create` | GET | UserController::create | `/admin/users/create` (ya existe) |
| `GET /admin/dashboard/alumnos` | GET | DashboardController::getWorklist | `/admin/worklist` |

### ✅ Ya existen equivalentes en inglés

| Ruta Inglés | Ruta Español |
|---|---|
| `/self-service/*` | N/A (nuevo flujo) |
| `/stations/photo`, `/stations/signature`, `/stations/fingerprint` | N/A |
| `/admin/users` | `/admin/usuarios` |
| `/admin/dashboard` | N/A |

---

## D) Controladores Gordos

### 🔴 TicketController (866 líneas)

| Método | Líneas | Problema |
|---|---|---|
| `generateTicket()` | ~185 | Lógica de negocio completa: búsqueda, validación, creación de ticket, generación de folio, manejo de transacciones |
| `savePublicSignature()` | ~37 | Mezcla validación de sesión con guardado de archivos |
| `savePublicPhoto()` | ~25 | Similar al anterior |
| `searchActiveTicketByStudent()` | ~30 | Query complejo con JOINs debería estar en Repository |
| `getInitialCatalogs()` | ~40 | Queries a catálogos debería estar en Repository |
| `savePhotoFile()` | ~85 | Lógica completa de guardado de archivo + DB + events |
| `saveSignatureFile()` | ~100 | Similar al anterior |
| `deactivateExpiredTickets()` | ~15 | Raw SQL query |

**Recomendación:** Extraer toda la lógica de negocio a `TicketService` (ya existe pero está subutilizado). El controller debería solo manejar request/response.

### ⚠️ FingerprintController (public portal) - 272 líneas

| Método | Líneas | Problema |
|---|---|---|
| `registerChallenge()` | ~25 | WebAuthn logic directo en controller |
| `Verifyregister()` | ~40 | DB directo a tabla `huellas_credenciales` |
| `authChallenge()` | ~30 | DB directo |
| `Verifyauth()` | ~50 | DB directo + WebAuthn verification |
| `saveFinalSignature()` | ~28 | File saving + DB directo |

**Recomendación:** Crear `WebAuthnService` para encapsular toda la lógica de huella digital.

### ✅ Controladores bien estructurados

| Controlador | Líneas | Estado |
|---|---|---|
| `PhotoController` | 89 | ✅ Delega a BiometricService y QueueService |
| `SignatureController` | 114 | ✅ Delega a BiometricService y QueueService |
| `FingerprintController` (stations) | 114 | ✅ Delega a BiometricService y QueueService |
| `SelfServiceController` | 132 | ✅ Delega a TicketService y BiometricService |
| `DashboardController` | 135 | ✅ Delega a AdminService |
| `UserController` | 57 | ✅ Delega a UserService |
| `LoginController` | 70 | ✅ Simple, apropiado para auth |

---

## E) Seguridad Básica

### ✅ Lo que está bien

| Check | Resultado |
|---|---|
| `.env` accesible via HTTP | ✅ Retorna 404 |
| Debug files en public/ | ✅ Eliminados (debug.php, info.php, diagnostic.php, test.php) |
| CSRF protection | ✅ Habilitado en `.env.production` |
| Password hashing | ✅ Usa `PASSWORD_DEFAULT` (bcrypt) |
| `writable/` protegido | ✅ `.htaccess` con `Require all denied` |
| Session regeneration | ✅ `session.regenerateDestroy = true` |
| HTTPS forzado | ✅ `app.forceGlobalSecureRequests = true` en producción |

### 🔴 Riesgos reales

| Riesgo | Ubicación | Severidad | Detalle |
|---|---|---|---|
| **Uploads ejecutables** | `public/uploads/` | 🔴 ALTA | No existía `.htaccess` (creado en esta auditoría). Sin él, un archivo `.php` subido sería ejecutable |
| **Directorios de uploads no protegidos** | `uploads/firmas/`, `uploads/huellas/` | 🔴 ALTA | Se crean dinámicamente sin `.htaccess` heredado |
| **Raw SQL en controllers** | `TicketController:851-864` | ⚠️ MEDIA | Query directo sin prepared parameters para `studentId` |
| **WebAuthn userId como string** | `FingerprintController:113` | ⚠️ MEDIA | `$alumnoId` se usa como userId pero es string, WebAuthn espera bytes |
| **Credenciales en docker-compose** | `docker-compose.yml` | ⚠️ MEDIA | Passwords hardcodeados (`kiosco_secret`, `root_secret`) |
| **Encryption key vacía** | `.env.production:71` | ⚠️ MEDIA | `encryption.key` está vacío, sessions no se pueden cifrar |
| **CSP habilitado pero sin config** | `.env.production:25` | ⚠️ BAJA | `app.CSPEnabled = true` pero no hay política configurada |
| **No hay rate limiting** | `TicketController` | ⚠️ BAJA | Solo 3 segundos entre requests, fácil de bypass |

---

## Plan de Acción Priorizado

### 1. 🔴 CRÍTICO: Arreglar UserService - UserAdminModel no existe
**Impacto:** La creación de usuarios desde el admin dashboard falla con error 500.
**Acción:** Cambiar la importación en `UserService.php` de `App\Modules\Admin\Models\UserAdminModel` a `App\Modules\Auth\Models\UserModel` y ajustar los métodos accordingly.

### 2. 🔴 CRÍTICO: Proteger directorios de uploads dinámicos
**Impacto:** `uploads/firmas/` y `uploads/huellas/` se crean sin protección contra ejecución de PHP.
**Acción:** Agregar lógica en `BiometricService` y `TicketController` para copiar un `.htaccess` restrictivo al crear directorios nuevos, o configurar Apache para negar ejecución en todo `uploads/`.

### 3. 🔴 CRÍTICO: Generar encryption key para producción
**Impacto:** Sessions no se pueden cifrar correctamente.
**Acción:** Ejecutar `php spark key:generate` y actualizar `.env.production`.

### 4. ⚠️ IMPORTANTE: Refactorizar TicketController
**Impacto:** 866 líneas de código con lógica de negocio mezclada con presentación.
**Acción:** Mover `savePhotoFile`, `saveSignatureFile`, `searchActiveTicketByStudent`, `getInitialCatalogs`, `deactivateExpiredTickets` a `TicketService`. El controller debe quedar en <200 líneas.

### 5. ⚠️ IMPORTANTE: Crear WebAuthnService
**Impacto:** Lógica de WebAuthn dispersa en controller con queries directos a DB.
**Acción:** Extraer toda la lógica de `FingerprintController` (public) a un `WebAuthnService` que maneje challenges, verificación y almacenamiento de credenciales.

---

## Resumen Final

| Categoría | ✅ Bien | ⚠️ Deuda menor | 🔴 Riesgo |
|---|---|---|---|
| Arquitectura Service-Repository | Services bien diseñados | Repositories simples | TicketController ignora la arquitectura |
| Base de datos | Schema consistente, FKs | Campos faltantes en Schema.php | Tabla huellas_credenciales faltaba |
| Seguridad | CSRF, HTTPS, passwords | Rate limiting débil | Uploads ejecutables, encryption key vacía |
| Código muerto | Archivos debug eliminados | Modelos no usados, vistas duplicadas | UserAdminModel importado pero no existe |
| Rutas | self-service en inglés | 20+ rutas en español | Mezcla de convenciones |
