# ITEC Logistics MVC

ITEC Logistics MVC is a PHP-based logistics management system for planning, monitoring, and controlling transport operations. It gives an organization one workspace for vehicles, drivers, trips, delivery tracking, maintenance, fuel, inventory, procurement, expenses, reports, and role-based access.

The project is built as a separate lightweight MVC application and does not modify the existing Xode application.

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
- Session-based authentication and logout.
- Role-based access control for dashboards and modules.
- Role-specific dashboards with operational metrics.
- Sidebar navigation that changes based on the logged-in role.
- CRUD-style screens for logistics records.
- Searchable and filterable module tables.
- View, create, edit, toggle status, and delete records.
- Delete confirmation with a required reason.
- CSV export for reports.
- Light/dark theme switcher from the dashboard layout.
- MySQL/MariaDB schema for logistics data.
- Idempotent seed file that can be run multiple times safely.

## User Roles

The system includes these roles:

- Super Admin: full access to every module.
- Logistics Manager: transport, fleet, delivery, warehouse, procurement, expense, fuel, maintenance, and reports access.
- Fleet Manager: vehicles, drivers, maintenance, fuel, and reports access.
- Warehouse Manager: warehouse, procurement, transport requests, and reports access.
- Driver: assigned trips and deliveries access.
- Finance: fuel, expenses, procurement, and reports access.
- Management: dashboard and reports access.

## Demo Login

Open the home page and use the login panel. Selecting a role fills the matching demo email automatically.

All demo accounts use this password:

```text
password
```

Demo accounts:

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
- audit_logs

The seed file includes realistic starter data for all main tables and is idempotent. You can run it again without duplicating the seeded records.

## Important Current Data Note

The database schema and seed data are ready. The current module screens still use session-backed demo records from `app/Models/LogisticsData.php` for fast UI interaction. This means create, edit, delete, and status toggle actions update the current browser session. The database layer is prepared for the next step, where each module can be connected to real MySQL repositories.

## Tech Stack

- PHP MVC structure
- MySQL/MariaDB
- PDO database foundation
- Bootstrap-based admin UI assets
- jQuery DataTables
- Select2
- XAMPP-friendly local setup

## Project Structure

- `public/index.php` - front controller
- `routes/web.php` - application route table
- `bootstrap.php` - configuration, session, role helpers, and autoloading
- `app/Controllers/` - controller classes
- `app/Models/` - data and database model classes
- `app/Views/` - page templates and shared layouts
- `config/config.php` - app and database configuration
- `database/schema.sql` - database schema
- `database/seed.sql` - starter data
- `public/assets/` - CSS, JavaScript, images, fonts, and UI assets

## Run Locally

Through XAMPP, open:

```text
http://localhost/logistics-mvc/public/
```

Or run PHP's built-in server from the project root:

```text
php -S localhost:8080 -t public
```

On this XAMPP installation, use the XAMPP PHP executable if the global `php` command points somewhere else:

```text
C:\xampp\php\php.exe -S localhost:8091 -t public
```

Then open:

```text
http://127.0.0.1:8091/?route=home
```

## Database Setup

Default local database settings:

- Host: `127.0.0.1`
- Database: `logistics_mvc`
- User: `root`
- Password: empty

Initialize the database:

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

## Main Routes

- `?route=home` - public home and login page
- `?route=login` - login POST endpoint
- `?route=logout` - logout endpoint
- `?route=dashboard` - role-specific dashboard
- `?route=vehicles` - vehicle fleet module
- `?route=drivers` - driver module
- `?route=requests` - transport requests module
- `?route=trips` - trips module
- `?route=deliveries` - deliveries module
- `?route=maintenance` - maintenance module
- `?route=fuel` - fuel module
- `?route=expenses` - expenses module
- `?route=warehouse` - warehouse and inventory module
- `?route=procurement` - procurement module
- `?route=reports` - reports module
- `?route=users` - users and permissions module

## Suggested Next Improvements

- Connect each module to MySQL repositories instead of session-backed demo records.
- Add real password verification against the `users` table.
- Add migrations for schema changes.
- Add server-side validation for module forms.
- Add file upload handling for delivery proofs, signatures, receipts, and documents.
- Add audit-log persistence for create, update, status change, delete, login, and export actions.
- Add automated tests for role access, login, CRUD actions, and report export.
