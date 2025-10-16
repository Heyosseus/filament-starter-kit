# **Crocoshop Admin**

It is a simple admin panel built with PHP, Laravel, and Filament.

# **Prerequisites**

-   PHP 8.1+
-   Laravel 11.x
-   FilamentPHP 3.x
-   Composer 2.5+
-   MySQL 8.0+

# **Getting Started**

## Quick Setup (Recommended)

1. Clone the repository
2. Run `composer install` to install the dependencies
3. Copy the environment file: `cp .env.example .env` (or `copy .env.example .env` on Windows)
4. Configure your database in `.env` file
5. Run `php artisan key:generate` to generate the application key
6. Run `php artisan init` to initialize the application (migrations, Shield setup, permissions, and super admin)
7. Run `php artisan serve` to start the server
8. Access the admin panel at `http://localhost:8000/admin`

## Manual Setup

If you prefer to set up manually, follow these steps:

1. Clone the repository
2. Run `composer install` to install the dependencies
3. Copy the environment file: `cp .env.example .env` (or `copy .env.example .env` on Windows)
4. Configure your database in `.env` file
5. Run `php artisan key:generate` to generate the application key
6. Run `php artisan migrate` to create the database tables
7. Run `php artisan shield:install --panel=admin --minimal` to set up Shield
8. Run `php artisan shield:generate --all` to generate permissions
9. Run `php artisan shield:super-admin` to create a super admin user
10. Run `php artisan serve` to start the server
