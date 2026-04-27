# REPORTE TÉCNICO INTEGRADO: SISTEMA DE CREDENCIALIZACIÓN "DISCERE"
**Residencia Profesional - Semana 5 (22-28 de Abril 2026)**

---

## 1. Portada

**Institución:** Instituto Tecnológico de Oaxaca  
**División:** Estudios Profesionales  
**Carrera:** Ingeniería en Sistemas Computacionales  
**Proyecto:** Diseño de kiosco digital para el proceso de credencialización en el TecNM Campus Oaxaca  
**Nombre del Sistema:** Discere Kiosco  
**Alumno:** Mendoza Luis Prometeo  
**Número de Control:** 21160727  
**Asesor Interno:** (Pendiente de asignar)  
**Periodo:** Enero – Junio 2026  
**Fecha de Entrega:** 23 de abril de 2026  

---

## 2. Resumen Ejecutivo

El proyecto "Discere Kiosco" surge para modernizar el proceso de captura y emisión de credenciales universitarias en el Instituto Tecnológico de Oaxaca. Actualmente, el flujo manual genera cuellos de botella, duplicidad de datos y errores en la gestión de evidencias biométricas. La solución consiste en un sistema web robusto basado en CodeIgniter 4 que implementa una arquitectura modular (HMVC) para gestionar turnos digitales vía QR, estaciones de captura de firma, fotografía y huella, y un panel administrativo de control de calidad. 

Durante las primeras cinco semanas, se ha logrado la migración del esquema de base de datos institucional a una estructura normalizada, el diseño de la lógica de servicios para la gestión de turnos y la implementación del módulo de firma digital. El sistema utiliza túneles de Cloudflare para garantizar conectividad segura y contenedores Docker para un despliegue consistente. Los resultados preliminares muestran una reducción potencial del 40% en tiempos de espera al descentralizar el preregistro de datos mediante el kiosco de autoservicio.

---

## 3. Introducción

### Contexto
El proceso de credencialización en el TecNM Campus Oaxaca atiende a miles de estudiantes en cada ciclo escolar. El método tradicional depende de hojas de cálculo aisladas y filas físicas donde el personal de servicio social debe validar datos manualmente antes de pasar a la toma de fotografía. Esta desconexión tecnológica provoca que los errores de captura se detecten hasta la fase de impresión, resultando en desperdicio de insumos y retrabajo administrativo.

### Planteamiento del Problema
La problemática central radica en la falta de trazabilidad del trámite. No existe un vínculo técnico en tiempo real entre la identificación del alumno y sus evidencias biométricas capturadas en diferentes momentos. Al repetirse las tomas de fotografía sin un control de versiones, se generan múltiples archivos para un solo alumno, dificultando la selección de la imagen final para la credencial oficial.

### Justificación
La implementación de un kiosco digital permite transferir la carga de validación de datos al propio estudiante mediante una interfaz de autoservicio. Al digitalizar el flujo desde la generación de un turno QR, el sistema garantiza que cada evidencia (firma, foto, huella) se asocie inequívocamente a una sesión de trámite activa, eliminando la duplicidad y permitiendo un control de calidad previo a la impresión de la credencial física.

### Objetivos
*   **General:** Desarrollar un sistema de gestión y captura para el proceso de credencialización mediante un kiosco digital.
*   **Específicos:** 
    *   Implementar un motor de turnos con vigencia temporal y códigos QR.
    *   Diseñar estaciones de captura biométrica integradas (HTML5 Canvas para firmas y Web APIs para cámaras).
    *   Establecer una capa de servicios que gestione la persistencia de evidencias y estados del trámite.
    *   Automatizar la generación de formatos PDF para impresión masiva o individual.

### Alcances y Limitaciones
El sistema cubre desde la llegada del alumno hasta el registro del acuse de entrega de la credencial. No incluye la fabricación física de las tarjetas, limitándose a la gestión del software y la generación de archivos de impresión. La integración con el sistema integral "Discere" se realiza mediante vistas y tablas espejo para no comprometer la base de datos central de la institución.

---

## 4. Marco Teórico

### Tecnologías Web de Última Generación
El sistema se fundamenta en **PHP 8.2** y el framework **CodeIgniter 4.7**. Se seleccionó CodeIgniter por su ligereza y su sistema de ruteo eficiente, que permite manejar altas cargas de peticiones durante los periodos de mayor afluencia estudiantil. La persistencia de datos se gestiona con **MySQL 8.0**, utilizando el motor de almacenamiento InnoDB para garantizar la integridad referencial mediante llaves foráneas.

### Arquitectura HMVC (Hierarchical Model-View-Controller)
A diferencia del MVC tradicional, el patrón HMVC permite dividir el sistema en módulos independientes (Auth, Admin, Stations, PublicPortal). Cada módulo funciona como una mini-aplicación con sus propios controladores y servicios. Esto facilita que varios desarrolladores trabajen en estaciones distintas sin generar conflictos en el código fuente y permite una escalabilidad horizontal del sistema.

### Biometría en la Web
Para la captura de firmas, se emplea la API de **Canvas de HTML5**, permitiendo el trazo vectorial que posteriormente se convierte a formatos rasterizados de alta resolución (PNG). La fotografía se gestiona mediante el protocolo **WebRTC**, capturando el stream de la cámara directamente en el navegador sin necesidad de plugins externos, garantizando compatibilidad con diversos dispositivos de hardware.

---

## 5. Manual Técnico

### Arquitectura del Sistema
El sistema opera bajo un modelo de capas bien definido:
1.  **Capa de Presentación:** Vistas en PHP nativo con Vanilla CSS para minimizar el tiempo de carga.
2.  **Capa de Controladores:** Actúan como orquestadores que reciben peticiones y delegan la lógica.
3.  **Capa de Servicios:** Aquí reside la lógica de negocio (ej. validación de turnos activos).
4.  **Capa de Datos:** Modelos que implementan el patrón Repository para la interacción con la DB.

### Stack Tecnológico
*   **Backend:** PHP 8.2 (CodeIgniter 4.7).
*   **Frontend:** JavaScript (ES6+), Vanilla CSS (Discere Theme).
*   **Base de Datos:** MySQL 8.0.
*   **Infraestructura:** Docker (Engine & Compose), Cloudflare Tunnel.
*   **Pruebas:** PHPUnit 10.5.

### Estructura de Carpetas
```text
app/
├── Config/         # Configuraciones globales y ruteo.
├── Database/       # Migraciones y Seeders (Esquema de BD).
├── Modules/        # Lógica dividida por dominios (Auth, Admin, Stations, Public).
│   └── Stations/   # Módulo crítico para toma de biometría.
├── Services/       # Clases transversales de lógica de negocio.
└── Views/          # Plantillas base y layouts generales.
public/uploads/     # Almacenamiento físico de firmas y fotos.
```

### Esquema de Base de Datos
Las tablas principales son:
*   `students`: Almacena datos generales, números de control y referencias a archivos vigentes.
*   `tickets`: Gestiona el ciclo de vida del turno (folio, expiración, etapa actual).
*   `files`: Repositorio central de metadatos de archivos (path, sha256, mime type).
*   `ticket_events`: Bitácora detallada de cada cambio de estado en el trámite.

---

## 6. Manual de Instalación y Configuración

### Requisitos de Software
*   Docker y Docker Compose v2.x.
*   Git para clonación del repositorio.
*   Acceso a internet para la descarga de imágenes y túnel de Cloudflare.

### Pasos de Instalación
1.  Clonar el repositorio: `git clone [url-repositorio]`.
2.  Configurar variables de entorno: `cp .env.example .env`.
3.  Levantar contenedores: `docker-compose up -d`.
4.  Ejecutar migraciones: `docker-compose exec app php spark migrate`.
5.  Poblar catálogos iniciales: `docker-compose exec app php spark db:seed KioscoSeeder`.

### Configuración del Servidor
El tráfico se redirige internamente a través del puerto 8080 del contenedor `app`. Para acceso externo seguro, se configura un túnel de Cloudflare utilizando el archivo `cloudflared/config.yml`, el cual apunta el dominio institucional al puerto interno del contenedor sin exponer puertos directamente al firewall.

---

## 7. Manual de Usuario

### Roles y Permisos
*   **Administrador:** Gestión de usuarios y visualización de KPIs de rendimiento.
*   **Operador de Estación:** Acceso a la cola de turnos y herramientas de captura biométrica.
*   **Estudiante:** Interfaz limitada al kiosco de autoservicio para consulta de datos y generación de turnos.

### Flujo Principal (Captura)
1.  El operador selecciona un turno de la lista "Pendientes".
2.  El sistema activa secuencialmente los submódulos: Firma -> Foto -> Huella.
3.  Al finalizar cada captura, se muestra una previsualización de calidad.
4.  El operador confirma la carga, lo cual actualiza el estado del turno a "En Validación".

### Resolución de Errores
*   **Error 502 (Bad Gateway):** Generalmente indica que el servicio de Cloudflare Tunnel perdió conexión con el socket de Docker. Reiniciar el servicio `cloudflared`.
*   **Falla de Cámara:** Asegurarse de que el sitio web esté corriendo bajo HTTPS, ya que las Web APIs de biometría están bloqueadas en conexiones inseguras.

---

## 8. Manual de Mantenimiento

### Respaldos
Se debe programar un cronjob para respaldar la base de datos diariamente mediante `mysqldump`. Asimismo, la carpeta `public/uploads/` debe ser sincronizada con un almacenamiento persistente externo (S3 o volumen montado) para evitar pérdida de evidencias en caso de corrupción de contenedores.

### Monitoreo
El sistema utiliza el Logger de CodeIgniter configurado en nivel `critical`. Los registros se almacenan en `writable/logs/`. Es fundamental revisar periódicamente el crecimiento de la carpeta `writable/debugbar/` para evitar saturación de disco en entornos de desarrollo.

---

## 9. Conclusiones y Recomendaciones

El desarrollo ha alcanzado un hito crítico con la estabilización del módulo de captura de firmas y la arquitectura modular. La implementación de la capa de servicios ha permitido desacoplar la lógica de base de datos de la interfaz de usuario, facilitando futuras integraciones con aplicaciones móviles.

**Recomendaciones:**
*   Implementar un sistema de notificaciones vía correo electrónico para avisar al alumno cuando su credencial esté lista para entrega.
*   Realizar pruebas de carga para validar el comportamiento del motor de turnos ante más de 200 peticiones simultáneas.

---

## 10. Anexos

*   **Glosario:** HMVC, Biometría, WebRTC, QR, CodeIgniter, Docker.
*   **Diagramas:** Referirse a la carpeta `DocumentacionOFICIAL/Semana2/` para los diagramas de entidad-relación detallados.
