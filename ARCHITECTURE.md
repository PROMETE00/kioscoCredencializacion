# Architecture: Kiosco Credencialización (Refactored)

## 1. Overview
The system follows a **Service-Repository** pattern, separating data access from business logic. All internal code (classes, methods, variables) is standardized to **English**.

## 2. Layers

### Data Access Layer (Repositories)
- Location: `app/Repositories/`
- Responsibilities: Direct database interaction using the `Schema` configuration for table/field mapping.
- Key Repositories: `StudentRepository`, `TicketRepository`, `FileRepository`.

### Business Logic Layer (Services)
- Location: `app/Services/`
- Responsibilities: Encapsulating complex workflows, validation, and multi-repository transactions.
- Key Services:
  - `BiometricService`: Handles image processing (Signature, Photo, Fingerprint), secure storage, and ticket stage advancement.
  - `TicketService`: Manages ticket creation and lifecycle.
  - `AdminService`: Provides KPIs, worklists, and handles manual overrides (biometric clearing, delivery recording).
  - `UserService`: Manages administrative user accounts.

### Presentation Layer (Modules & Controllers)
- Location: `app/Modules/`
- Responsibilities: Handling HTTP requests, session management, and rendering views.
- Key Modules:
  - `PublicPortal`: Contains the `SelfServiceController` for the student flow.
  - `Stations`: Contains controllers for internal capture stations (Photo, Signature, Fingerprint).
  - `Admin`: Contains the `DashboardController` and `UserController`.

## 3. Database Abstraction
The `app/Config/Schema.php` file defines the mapping between logical entities and physical database tables/columns. This allows the system to be easily integrated with different database schemas by updating a single configuration file.

## 4. Tracking and Auditing
All significant actions are logged in the `ticket_events` table with detailed JSON metadata, including:
- Who performed the action (Admin ID or Self-Service).
- Previous and new states (Stage/Status).
- Failure reasons and manual override details.

## 5. Security
- WebAuthn for biometric fingerprint verification.
- CSRF protection enabled on all forms.
- Secure session management using database handlers.
