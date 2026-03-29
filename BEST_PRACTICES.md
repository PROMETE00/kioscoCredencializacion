# Coding Standards & Best Practices

Este documento define las reglas de escritura de código para el proyecto Kiosco de Credencialización. Todos los desarrolladores deben adherirse a estas normas para mantener un código limpio, escalable y profesional.

## 1. Regla de Idiomas (Language Policy)
- **Código en Inglés:** Todas las clases, métodos, variables, nombres de archivos, carpetas y esquemas de base de datos DEBEN estar estrictamente en inglés. (Ej: `StudentController` en lugar de `AlumnoController`, `save()` en lugar de `guardar()`).
- **Comentarios en Español:** Toda la documentación, comentarios en línea (inline comments) y bloques PHPDoc DEBEN escribirse en español para facilitar el entendimiento al equipo local.
- **Interfaz de Usuario (UI) en Español:** Todo texto visible para el usuario final (botones, alertas, etiquetas) se mantiene en español.

## 2. Convenciones de Nombres (Naming Conventions)
- **Clases e Interfaces:** `PascalCase` (Ej. `TicketService`).
- **Métodos y Variables:** `camelCase` (Ej. `generateTicket()`, `$studentName`).
- **Base de Datos (Tablas y Columnas):** `snake_case` (Ej. `control_number`, `cat_stages`).
- **Constantes:** `UPPER_SNAKE_CASE` (Ej. `MAX_RETRY_ATTEMPTS`).

## 3. Estilo de Código PHP (PSR-12)
Este proyecto adopta el estándar **PSR-12**.
- Usa 4 espacios para la indentación (no tabs).
- Las llaves `{` de clases y métodos van en la siguiente línea.
- Las llaves `{` de estructuras de control (`if`, `for`, `while`) van en la misma línea.
- Declara explícitamente la visibilidad (`public`, `protected`, `private`) de todos los métodos y propiedades.
- Usa tipado estricto siempre que sea posible (Type Hinting y Return Types en PHP 8+).

## 4. Principio DRY (Don't Repeat Yourself)
- Nunca copies y pegues lógica compleja.
- Si un bloque de código (como la construcción de una consulta para obtener una cola de trabajo) se usa en más de un controlador o modelo, DEBE ser extraído a un **Servicio** (Service) o un **Repositorio** (Repository) compartido.

## 5. Controladores Delgados (Thin Controllers)
- El Controlador solo debe encargarse de recibir la petición HTTP, validar los parámetros de entrada y retornar una respuesta (Vista o JSON).
- **TODA la lógica de negocio** (cálculos, reglas, flujos complejos) debe delegarse a una clase **Service**.

## 6. Seguridad
- Evita exponer IDs secuenciales directamente en URLs públicas. Usa tokens (hash/UUID).
- Mantén siempre activa la protección CSRF (`csrf_hash()`) en formularios y peticiones AJAX.
- **Nunca** incluyas contraseñas, tokens o secretos en el código fuente. Utiliza siempre variables de entorno (`getenv()`).
