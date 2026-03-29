# Architecture & Modules (HMVC)

El Kiosco de Credencialización está construido sobre CodeIgniter 4 utilizando una adaptación del patrón **HMVC** (Hierarchical Model-View-Controller) orientada a dominios.

El objetivo de esta arquitectura es tener alta cohesión (todo lo relacionado a un tema vive junto) y bajo acoplamiento (los módulos no dependen fuertemente entre sí).

## 1. Estructura HMVC
Todo el código de la aplicación se agrupa en `app/Modules/`. Cada módulo representa una pieza funcional autónoma del sistema.

Ejemplo de estructura de un módulo:
```text
app/Modules/
└── Auth/
    ├── Controllers/    # Puntos de entrada HTTP (LoginController)
    ├── Models/         # Interacción directa con BD / Repositorios
    ├── Services/       # Reglas de negocio (AuthService)
    └── Views/          # Plantillas HTML exclusivas del módulo
```

## 2. Los 4 Módulos Principales
1. **PublicPortal:** Maneja la interacción con los estudiantes (generación de turnos, consulta de estatus y QR).
2. **Auth:** Controla la sesión de los operadores de las estaciones y administradores.
3. **Stations (Estaciones):** Agrupa la lógica de captura biométrica (Fotografía, Firma, Huella). Comparte lógica de cola de espera.
4. **Admin:** Panel de control, estadísticas (Dashboard) y gestión de cuentas de usuario interno.

## 3. Patrones de Diseño Utilizados

### A. Capa de Servicios (Service Layer)
- **¿Qué es?** Clases que contienen la "lógica de negocio".
- **¿Por qué?** Evita que los controladores se llenen de código condicional (Fat Controllers) y permite que la misma lógica se reutilice (por ejemplo, desde un controlador web y desde un comando CLI).
- **Ejemplo:** `TicketGenerationService` es el único responsable de decidir cómo se genera un turno y validar si un alumno ya tiene uno.

### B. Capa de Repositorio (Repository Pattern / Models)
- **¿Qué es?** Los `Models` de CodeIgniter actúan como repositorios. Son el único lugar donde se escribe código SQL (Query Builder).
- **¿Por qué?** Oculta la complejidad de la base de datos a los controladores y servicios. Si la base de datos cambia, solo se modifica el modelo.

### C. Inyección de Dependencias Ligera
Se recomienda instanciar los servicios dentro del constructor del controlador o pasarlos como dependencias para facilitar las pruebas unitarias (Testing).

## 4. Flujo de una Petición (Request Flow)
1. **Ruta:** Define el endpoint y apunta a un Controlador de un Módulo.
2. **Controlador:** Recibe el Request, extrae parámetros y se los pasa al Servicio.
3. **Servicio:** Ejecuta la lógica, llama a uno o varios Modelos (BD).
4. **Modelo:** Retorna los datos al Servicio.
5. **Servicio:** Procesa o formatea los datos y los devuelve al Controlador.
6. **Controlador:** Envía la Respuesta (Vista renderizada o JSON) al cliente.