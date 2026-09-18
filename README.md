# ITEC Logistics MVC

A separate logistics management project built with a small PHP MVC structure. The existing Xode application is not modified.

## Structure

- `public/index.php` - front controller
- `routes/web.php` - route table
- `app/Controllers/` - request orchestration
- `app/Models/` - database access
- `app/Views/` - page templates and shared layout
- `config/config.php` - environment-based database settings
- `database/schema.sql` - initial logistics schema
- `database/seed.sql` - realistic starter data for local development
- `app/Core/Database.php` - lazy PDO connection with prepared statements
- `app/Models/BaseModel.php` - reusable database model foundation
- `public/assets/` - frontend template assets and logistics styles

## Run locally

Open `http://localhost/logistics-mvc/public/` through XAMPP, or run:

```text
php -S localhost:8080 -t public
```

On this XAMPP installation, use `C:\xampp\php\php.exe` if the command-line `php` points to another PHP build:

```text
C:\xampp\php\php.exe -S localhost:8091 -t public
```

Set `LOGISTICS_DB_HOST`, `LOGISTICS_DB_NAME`, `LOGISTICS_DB_USER`, and `LOGISTICS_DB_PASS` before connecting the models to MySQL. Copy `.env.example` as a reference for the required variables.

Initialize the backend with MySQL:

```text
mysql -u root -p < database/schema.sql
mysql -u root -p logistics_mvc < database/seed.sql
```

The local database was initialized with the XAMPP MariaDB service. The default local connection is `127.0.0.1`, database `logistics_mvc`, user `root`, and an empty password; change these through the `LOGISTICS_DB_*` environment variables when needed.

The current UI keeps session-backed demo data available until database repositories are connected module by module. The PDO and schema foundations are ready for that transition and do not alter the existing Xode project.

## Planned modules

Vehicles, drivers, trip requests, deliveries, fuel, warehouse, procurement, maintenance, expenses, reports, and role-based access.
