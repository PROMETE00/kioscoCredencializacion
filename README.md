# Kiosco Credencializacion

Aplicacion web en `CodeIgniter 4` para gestionar un flujo de credencializacion estudiantil con turnos y estaciones de captura.

## Objetivo del sistema

El proyecto implementa un kiosco con tres capas funcionales:

- Area publica para que el alumno busque su registro y genere un turno.
- Estaciones internas para captura de fotografia, firma y huella.
- Panel administrativo para dashboard y gestion de usuarios.

## Stack tecnico

- PHP `^8.2`
- CodeIgniter `^4.7`
- PHPUnit `^10.5`

## Modulos principales

### 1. Turnos publicos

Rutas principales en `app/Config/Routes.php`:

- `GET /` y `GET /turno`
- `POST /turno/buscar`
- `POST /turno/generar`
- `GET /t/{token}`

Punto de entrada: `app/Controllers/Publico/TurnoPublicController.php`

Responsabilidad:

- Buscar alumno por `numero_control` o `numero_ficha`.
- Generar un turno activo con token QR.
- Consultar el estado del turno mediante un enlace publico.

### 2. Autenticacion y acceso interno

Archivos clave:

- `app/Controllers/AuthController.php`
- `app/Filters/AuthFilter.php`
- `app/Views/auth/login_kiosco.php`

Responsabilidad:

- Iniciar y cerrar sesion.
- Restringir acceso al panel administrativo y estaciones de captura.

### 3. Estacion de fotografia

Archivos clave:

- `app/Controllers/CameraController.php`
- `app/Models/CaptureQueueModel.php`
- `app/Views/camera/capture_queue.php`

Responsabilidad:

- Mostrar la cola de captura.
- Guardar imagenes en `public/uploads/photos/`.
- Avanzar el flujo del turno tras la captura.

### 4. Estaciones de firma y huella

Archivos clave:

- `app/Controllers/FirmaController.php`
- `app/Controllers/HuellaController.php`
- `app/Models/FirmaModel.php`
- `app/Models/HuellaModel.php`

Estado actual:

- Los controladores ya exponen el flujo esperado.
- Los modelos siguen con metodos placeholder y requieren implementacion real.

### 5. Administracion

Archivos clave:

- `app/Controllers/DashboardController.php`
- `app/Controllers/AdminUsersController.php`
- `app/Models/DashboardModel.php`
- `app/Models/UserModel.php`

Responsabilidad:

- Dashboard de trabajo y KPIs.
- Alta y consulta de usuarios internos.

## Estructura relevante del repositorio

```text
app/
  Config/          Configuracion de framework y rutas
  Controllers/     Entradas HTTP del sistema
  Filters/         Control de acceso
  Models/          Acceso a datos y logica de flujo
  Views/           Vistas por modulo
  Database/Seeds/  Datos base de autenticacion
public/
  index.php        Punto de entrada web
tests/             Pruebas base de CodeIgniter
```

## Riesgos tecnicos detectados

- `README.md` estaba desalineado con el sistema real.
- `app/Views/captura/` y `app/Views/capture/` mezclan nombres en espanol e ingles.
- `app/Database/Migrations/` no contiene migraciones reales del dominio.
- `app/Models/DashboardModel.php` depende de `vw_dashboard_worklist` sin documentacion del esquema.
- `app/Models/FirmaModel.php` y `app/Models/HuellaModel.php` siguen como placeholders.

## Setup rapido (Recomendado con Docker)

El proyecto está preparado para ejecutarse fácilmente usando Docker y Docker Compose, incluyendo la base de datos y un servidor web.

1. **Clona el repositorio** y entra al directorio.
2. **Crea el archivo de entorno**: Copia el archivo de ejemplo para que CodeIgniter tome las variables.
   ```bash
   cp .env.example .env
   ```
3. **Levanta los contenedores**:
   ```bash
   docker compose up -d
   ```
4. **Instala dependencias y ejecuta migraciones/seeders**:
   Ejecuta estos comandos dentro del contenedor de la aplicación:
   ```bash
   docker compose exec app composer install
   docker compose exec app php spark migrate
   docker compose exec app php spark db:seed AuthSeeder
   ```

El sistema estará disponible en `http://localhost:8080/`.

**Credenciales por defecto:**
- Al ejecutar el `AuthSeeder`, se crean usuarios para cada estación y un administrador.
- El usuario administrador es `admin` y la contraseña por defecto es `admin123` (configurable en el archivo `.env` mediante `SEED_ADMIN_PASSWORD`). **¡Cambia estas contraseñas en producción!**

---

## Setup manual (Sin Docker)

1. Crea el archivo `.env` a partir de `.env.example` y ajusta la conexión a tu base de datos local.
2. Instala dependencias con:

```bash
composer install
```

3. Levanta el servidor local de CodeIgniter:

```bash
php spark serve
```

4. Corre pruebas disponibles:

```bash
php vendor/bin/phpunit --testdox
```

## Seeds disponibles

El proyecto incluye `app/Database/Seeds/AuthSeeder.php`, que prepara:

- Roles base: `ADMIN`, `SUPERVISOR`, `EST_FOTO`, `EST_FIRMA`, `EST_HUELLA`, `EST_IMPRIME`
- Usuarios iniciales para cada estacion

## Proximos pasos recomendados

- Crear migraciones reales para tablas, catalogos y vistas requeridas.
- Normalizar nombres de carpetas y vistas de captura.
- Implementar la logica real en `FirmaModel` y `HuellaModel`.
- Agregar pruebas del flujo de turnos y estaciones.
