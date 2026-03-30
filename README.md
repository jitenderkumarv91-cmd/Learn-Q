# ScholarGrid E-Learning Website

ScholarGrid is a PHP and MySQL e-learning portal with written courses, student authentication, dashboard tracking, course tests, encrypted personal data storage, activity logging, and an admin panel.

## Features

- Homepage with navbar, course search, three-column course card layout, and footer links.
- Student signup, login, logout, session-based access control, and profile management.
- Dedicated written course page for every course in the catalog.
- 20-question test flow per course with selectable difficulty, live answer feedback, and locked difficulty after test start.
- Student dashboard showing scores, recent course activity, and log history.
- Admin panel for students, questions, logs, and contact messages.
- Database encryption for personal fields and PBKDF2 hashing for passwords.
- `logs` table for action tracking, timestamps, page references, and duration values.

## Course Catalog

- HTML
- CSS
- JavaScript
- Python
- Machine Learning
- DSA
- C
- C++
- Ethical Hacking
- Linux
- Django
- MySQL
- MongoDB

## Runtime Requirements

- PHP 8.1 or newer with `pdo_mysql` and `openssl`
- MySQL 8.0 or newer
- A local web server or `php -S`

## Setup

1. Create the database by importing [`database/schema.sql`](./database/schema.sql).
2. Seed the platform data by importing [`database/seed.sql`](./database/seed.sql).
3. Update MySQL credentials in [`config/database.php`](./config/database.php).
4. If you host the project in a subfolder, set `app_url` in [`config/app.php`](./config/app.php).
5. Start the server from the project root:
   - `php -S localhost:8000`
6. Open `http://localhost:8000/index.php`.

## Seeded Admin Login

- Email: `admin@scholargrid.local`
- Password: `Admin@12345`

## Security Notes

- Student and admin personal fields are encrypted before being stored in MySQL.
- Passwords use PBKDF2-SHA256 with a per-user salt.
- Forms use CSRF tokens.
- Course, dashboard, and admin routes are protected with session checks.

## Important Configuration Note

The current `app_key` and `lookup_salt` values in [`config/app.php`](./config/app.php) are fixed so the seeded admin record works immediately. Change both values before production and reseed any encrypted records that depend on them.

## Validation Note

This workspace did not expose `php` or `mysql` on `PATH`, so the project was built and checked statically but not executed in this environment.
# Learn-Q
