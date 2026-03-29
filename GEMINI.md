# Proyecto: Kiosco de Credencialización (Discere)

Este documento proporciona el contexto, los estándares y la hoja de ruta para el desarrollo del proyecto Kiosco de Credencialización.

## 📝 Descripción General
El sistema facilita el trámite de credenciales universitarias mediante un flujo dividido en:
1.  **Zona Pública:** Búsqueda de alumnos y generación de turnos (Token QR).
2.  **Estaciones de Captura:** Fotografía, Firma y Huella dactilar.
3.  **Panel Administrativo:** Gestión de usuarios internos (operadores) y visualización de métricas/KPIs.

## 🛠️ Stack Tecnológico
-   **Framework:** CodeIgniter 4.7 (PHP 8.x)
-   **Base de Datos:** MySQL (con fuerte dependencia actual en vistas como `vw_dashboard_worklist`).
-   **Frontend:** Vanilla CSS (Discere Theme) y JavaScript nativo.
-   **Testing:** PHPUnit 10.5.
-   **Gestión de Dependencias:** Composer.

## 📂 Estructura del Proyecto (Estado Actual)
-   `app/Controllers/`: Lógica de flujo (incluye `FirmaController` funcional).
-   `app/Models/`: Acceso a datos (`FirmaModel` ahora es funcional con DB).
-   `app/Database/Migrations/`: Migración inicial `20260328000001_CreateKioscoTables.php` que define el esquema completo.
-   `app/Database/Seeds/`: `KioscoSeeder.php` para poblar catálogos y datos de prueba.
-   `app/Views/`: Plantillas divididas en `admin`, `auth`, `camera`, `captura`, `public`.
-   `public/uploads/`: Almacenamiento de biometría (`photos/`, `firmas/`).

## 🚀 Plan de Reestructuración (Objetivos)
-   **Módulo de Firma:** Completamente funcional. Captura desde Canvas, guarda imagen física y registra en DB con historial de eventos.
-   **Modularización:** Mover la lógica a `app/Modules/` (e.g., `TurnoPublico`, `Auth`, `EstacionFoto`).
-   **Capas:** Implementar **Servicios** para lógica de negocio y **Repositorios** para persistencia, manteniendo controladores "delgados".
-   **API-First:** Preparar el sistema para ser consumido por apps móviles o nuevos clientes.
-   **Migraciones:** Eliminar la dependencia de vistas manuales creando migraciones de base de datos (`php spark make:migration`).

## 📏 Estándares de Ingeniería
-   **Idioma:**
    -   **Código:** Todo el código (clases, métodos, variables, bases de datos) debe estar en **Inglés**.
    -   **Comentarios/Documentación:** Pueden ser en **Español** para facilitar la comprensión del equipo local.
-   **Convenciones de Naming:**
    -   Clases: `PascalCase`.
    -   Métodos/Variables: `camelCase`.
    -   Base de datos (tablas/columnas): `snake_case`.
-   **Estilo:** Seguir **PSR-12**. Evitar lógica compleja en las vistas.
-   **Seguridad:** Protección CSRF activa, validación estricta de entradas y gestión de secretos vía `.env`.

## 🛠️ Comandos Comunes
-   **Servidor local:** `php spark serve`
-   **Ejecutar pruebas:** `php vendor/bin/phpunit --testdox`
-   **Crear migración:** `php spark make:migration [Nombre]`
-   **Limpiar caché:** `php spark cache:clear`

## 🚩 Riesgos y Pendientes Actuales
-   Implementar lógica real en `FirmaModel` y `HuellaModel`.
-   Normalizar rutas y carpetas que mezclan idiomas (e.g., `captura` vs `capture`).
-   Documentar y versionar la vista `vw_dashboard_worklist`.
