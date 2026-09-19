# LMS - Logistics Management System

LMS is a PHP-based logistics management system for planning, monitoring, and controlling transport operations. It gives an organization one workspace for vehicles, drivers, trips, delivery tracking, maintenance, fuel, inventory, procurement, expenses, reports, and role-based access.

The project is built as a separate lightweight MVC application and does not modify the existing Xode application.

The current version is fully database-backed: authentication reads real users from MySQL, module actions save to the database, audit activity is persisted, uploads are stored on disk, migrations are available for existing installations, and smoke tests cover the main workflow.

## What The System Does

The system helps logistics teams answer daily operational questions:

- Which vehicles are available, on trip, under maintenance, or inactive?
- Which drivers are available and whose licenses need attention?
- Which transport requests are pending, approved, assigned, rejected, or cancelled?
- Which trips and deliveries are currently loading, in transit, delivered, or failed?
- Which vehicles need maintenance and what work orders are open?
- How much fuel is being purchased and by which vehicles?
- Which stock items are low, available, or out of stock?
- Which procurement requests and suppliers are active?
- Which expenses are linked to trips, vehicles, repairs, fuel, or allowances?
- Which reports can management, finance, fleet, warehouse, and operations teams review?

The goal is to make logistics activity visible, controlled, and accountable from one dashboard.

## Main Features

- Public home page with system overview and login panel.
- Demo login by role with automatic email selection.
- Password verification against the seeded `users` table.
- Session-based authentication and logout after database login.
- Role-based access control for dashboards and modules.
- Role-specific dashboards with operational metrics.
- Sidebar navigation that changes based on the logged-in role.
- Database-backed CRUD-style screens for logistics records.
- Searchable and filterable module tables.
- View, create, edit, toggle status, and delete records.
- Delete confirmation with a required reason.
- Server-side validation for required fields, option values, dates, numbers, and upload types.
- File upload handling for delivery proof files, signatures, and fuel receipts.
- DB-backed operational notifications for role/user updates.
- Persistent audit logs for login, failed login, logout, create, update, delete, status change, and report export actions.
- CSV export for reports.
- Light/dark theme switcher from the dashboard layout.
- MySQL/MariaDB schema for logistics data.
- Migration runner for existing database updates.
- Idempotent seed file that can be run multiple times safely.
- Automated smoke test for database import, login data, role access, CRUD persistence, reports, and audit logging.

## User Roles

The system includes these roles:

- Super Admin: full access to every module.
- Logistics Manager: transport, fleet, delivery, warehouse, procurement, expense, fuel, maintenance, and reports access.
- Fleet Manager: vehicles, drivers, maintenance, fuel, and reports access.
- Warehouse Manager: warehouse, procurement, transport requests, and reports access.
- Driver: assigned trips and deliveries access.
- Finance: fuel, expenses, procurement, and reports access.
- Management: dashboard and reports access.

## Seeded Database Login

Open the home page and use the login panel. The role list, email autofill, and account list come from active records in the `users` table joined to `roles`.

All seeded login users use this password:

```text
password
```

Seeded active users:

| Role | Email |
| --- | --- |
| Super Admin | admin@itec.rw |
| Logistics Manager | aline@itec.rw |
| Fleet Manager | eric@itec.rw |
| Warehouse Manager | nadine@itec.rw |
| Driver | samuel@itec.rw |
| Finance | emmanuel@itec.rw |
| Management | jeanpierre@itec.rw |

## Modules

### Dashboard

Shows role-specific metrics, recent trips and deliveries, and operational attention items such as vehicles due for service, licenses expiring soon, and fuel review reminders.

### Vehicles

Tracks fleet vehicles, plate numbers, vehicle types, assigned drivers, availability status, mileage, and next service dates.

### Drivers

Tracks driver information, phone numbers, license numbers, license expiry dates, availability, and assignment status.

### Transport Requests

Handles internal transport demand. Requests include requester, route, required date, priority, status, and notes.

### Trips

Tracks planned and active transport movement, including trip references, pickup location, destination, vehicle, driver, departure time, arrival time, and status.

### Deliveries

Tracks proof-of-delivery workflow, delivery codes, linked trips, recipients, destinations, delivery status, proof files, signatures, and delivered time.

### Maintenance

Tracks vehicle service work orders, providers, priorities, estimated costs, due dates, completion dates, and maintenance status.

### Fuel

Tracks fuel purchases by vehicle, station, litres, unit price, mileage, purchase time, and receipt file.

### Expenses

Tracks logistics costs such as fuel, tolls, repairs, allowances, parking, insurance, and trip or vehicle related expenses.

### Warehouse And Inventory

Tracks warehouses, stock items, SKU, quantity on hand, minimum level, unit cost, and stock status.

### Procurement

Tracks suppliers and purchase requests, including descriptions, request amounts, requested-by users, supplier links, and purchase status.

### Reports

Provides report rows such as vehicle utilization, fuel consumption, delivery performance, maintenance cost, driver performance, inventory movement, trip profitability, open requests, and expense summaries. Reports can be exported as CSV.

### Users And Permissions

Shows system user and role information for access control workflows.

## Database

The database schema is in:

```text
database/schema.sql
```

The seed data is in:

```text
database/seed.sql
```

The schema creates these tables:

- vehicles
- drivers
- trips
- expenses
- roles
- users
- transport_requests
- deliveries
- fuel_records
- maintenance_orders
- warehouses
- inventory_items
- suppliers
- purchase_requests
- reports
- notifications
- audit_logs

The seed file includes realistic starter data for all main tables and is idempotent. You can run it again without duplicating the seeded records.

## Current Data Implementation

Module records are read from and written to MySQL through `app/Models/LogisticsData.php`. Login uses `app/Models/UserRepository.php` and verifies `users.password_hash`. Role/user updates are loaded from `notifications` through `app/Models/Notification.php`. Audit actions are stored in `audit_logs` through `app/Models/AuditLog.php`. Uploaded delivery and fuel files are saved under `storage/uploads/`.

## Tech Stack

- PHP MVC structure
- MySQL/MariaDB
- PDO database foundation
- Bootstrap-based admin UI assets
- jQuery DataTables
- Select2
- XAMPP-friendly local setup
- Plain PHP smoke tests

## Project Structure

- `public/index.php` - front controller
- `routers/web.php` - web (HTML) route table
- `routers/api.php` - JSON API route table, reached as `/api/<endpoint>`
- `app/Core/Router.php` - route registration, login/role checks and dispatch
- `bootstrap.php` - configuration, session, role helpers, and autoloading
- `app/Controllers/` - controller classes
- `app/Models/` - data and database model classes
- `app/Views/` - page templates and shared layouts
- `config/config.php` - app and database configuration
- `database/schema.sql` - database schema
- `database/seed.sql` - starter data
- `database/migrations/` - incremental schema updates for existing databases
- `scripts/migrate.php` - migration runner
- `tests/smoke.php` - automated smoke test
- `tests/migrate_fresh.php` - verifies one-command setup on a brand-new database
- `tests/seed_integrity.php` - full seed, role, notification, and workflow integrity test
- `storage/uploads/` - runtime upload location for proofs, signatures, and receipts
- `public/assets/` - CSS, JavaScript, images, fonts, and UI assets

## Run Locally

Through XAMPP, open:

```text
http://localhost/itec_logistic/
```

Or run PHP's built-in server from the project root:

```text
php -S localhost:8080 -t public public/index.php
```

On this XAMPP installation, use the XAMPP PHP executable if the global `php` command points somewhere else:

```text
C:\xampp\php\php.exe -S localhost:8091 -t public public/index.php
```

Then open:

```text
http://127.0.0.1:8091/
```

## Database Setup

Default local database settings:

- Host: `127.0.0.1`
- Database: `logistics_mvc`
- User: `root`
- Password: empty

Initialize or update the database with one command:

```text
C:\xampp\php\php.exe scripts\migrate.php
```

The migration script creates the database if it does not exist, imports `database/schema.sql` when the database is empty, applies every file in `database/migrations/`, and loads `database/seed.sql`. The seed is idempotent, so running the command again refreshes the starter data without duplicating seeded records.

Manual setup is still possible:

```text
C:\xampp\mysql\bin\mysql.exe -u root < database/schema.sql
C:\xampp\mysql\bin\mysql.exe -u root logistics_mvc < database/seed.sql
```

If your MySQL user has a password, add `-p`:

```text
C:\xampp\mysql\bin\mysql.exe -u root -p < database/schema.sql
C:\xampp\mysql\bin\mysql.exe -u root -p logistics_mvc < database/seed.sql
```

Environment variables can override database settings:

- `LOGISTICS_DB_HOST`
- `LOGISTICS_DB_NAME`
- `LOGISTICS_DB_USER`
- `LOGISTICS_DB_PASS`

See `.env.example` for the expected values.

## Tests

Run the smoke test from the project root:

```text
C:\xampp\php\php.exe tests\smoke.php
```

The test creates a temporary database, imports the schema and seed data, verifies the seeded login password, checks role module access, performs a vehicle create/status workflow, confirms report data, writes an audit log, and drops the temporary database when finished.

Run the fresh migration test to prove a new machine can set up everything with only `scripts/migrate.php`:

```text
C:\xampp\php\php.exe tests\migrate_fresh.php
```

Run the full seed integrity test when you want to verify every seeded workflow:

```text
C:\xampp\php\php.exe tests\seed_integrity.php
```

That test imports the schema, runs the seed twice to prove idempotency, verifies expected counts for every seeded table, checks every seeded user/password/role permission, confirms every role receives notifications, loads each allowed module, creates a request-to-trip-to-delivery expense workflow, and confirms audit updates are written.

## Main Routes

- `/` - public home and login page
- `/login` - login POST endpoint
- `/logout` - logout endpoint
- `/dashboard` - role-specific dashboard
- `/vehicles` - vehicle fleet module
- `/drivers` - driver module
- `/requests` - transport requests module
- `/trips` - trips module
- `/deliveries` - deliveries module
- `/maintenance` - maintenance module
- `/fuel` - fuel module
- `/expenses` - expenses module
- `/warehouse` - warehouse and inventory module
- `/procurement` - procurement module
- `/reports` - reports module (`/reports?report_type=Fuel%20consumption` filters, `/reports/export` downloads CSV)
- `/users` - users and permissions module
- `/api/health`, `/api/me` - JSON endpoints

Every module also has these sub-routes, where `{id}` is the record's first-column value (plate number, reference code, name):

- `/{module}/create` - create form (GET) and save (POST)
- `/{module}/{id}` - record details
- `/{module}/{id}/edit` - edit form (GET) and save (POST)
- `/{module}/{id}/toggle` - change status (POST)
- `/{module}/{id}/delete` - delete with reason (POST)

Under Apache the root `.htaccess` forwards every request to `public/`, so `mod_rewrite` and `AllowOverride All` are required. The base path comes from `app.base_url` in `config/config.php` (`/itec_logistic`); PHP's built-in server ignores it.

## Completed Improvement Checklist

These items are implemented in the current codebase:

- Each module is connected to MySQL-backed data operations.
- Login verifies passwords against the `users` table.
- Schema changes are captured in `database/migrations/`.
- Module forms have server-side validation.
- Delivery and fuel upload fields are handled by the application.
- Notifications are seeded and loaded from the database for every role.
- Audit logs are persisted for authentication, module changes, status changes, deletion, and report export.
- Automated smoke tests cover login data, role access, CRUD persistence, reports, and audit logging.

## Optional Future Enhancements

- Add CSRF tokens to all POST forms.
- Add pagination and server-side DataTables processing for very large datasets.
- Add password reset and user password-change screens.
- Add stricter file download permissions for uploaded proofs and receipts.
- Add deployment notes for shared hosting or a production web server.
