# Tripistry — Travel Package Booking Platform

COS221 Practical Assignment 5 — Task 5: Web-Based Application

## Stack
- **Backend:** PHP 8.2 with PDO (SQL injection prevention via prepared statements)
- **Frontend:** HTML5, CSS3 (custom design system), vanilla JavaScript
- **Database:** MySQL 8.0 (MariaDB compatible)
- **Server:** Apache via Docker

## Setup

### 1. Configure your database password
Edit `config.php` and set `DB_PASS` to match your `docker-compose.yml` `MYSQL_ROOT_PASSWORD`.

### 2. Place your DB dump
Copy your `.sql` dump file (rename it `mydb.sql`) to the project root. Docker will auto-import it on first run.

### 3. Start with Docker
```bash
docker-compose up -d
```
The app will be at: **http://localhost:8080**

> **Note:** On first start, the PHP container needs to install the PDO MySQL extension.
> This takes ~30 seconds. If you get a DB error, wait and refresh.

### 4. If NOT using Docker
Point your Apache/Nginx document root at this folder and configure a MySQL DB. Update `config.php` accordingly.

## Project Structure
```
tripistry/
├── config.php              # DB credentials & constants
├── index.php               # Login page
├── register.php            # Registration (traveller & agency)
├── logout.php
├── includes/
│   ├── db.php              # PDO connection (singleton)
│   ├── auth.php            # Session auth, login, register, CSRF
│   ├── header.php          # Navbar + <head>
│   └── footer.php          # Scripts + </body>
├── traveler/
│   ├── browse.php          # Browse & filter packages
│   ├── package.php         # Package detail, booking, reviews
│   └── bookings.php        # My trips / booking management
├── agency/
│   ├── dashboard.php       # Stats, package list, CRUD
│   ├── package-form.php    # Create/edit package
│   └── manage-items.php    # Add flights/hotels/restaurants/attractions
├── assets/
│   ├── css/style.css       # Design system (CSS variables, components)
│   └── js/app.js           # Tabs, modals, star ratings, price calc
└── docker-compose.yml
```

## Features (Task 5)

### Traveller
- ✅ Register/login with session management
- ✅ Browse packages with filter (destination, price range, agency, status)
- ✅ Sort by date, price (asc/desc), rating, duration
- ✅ Package detail: pricing, itinerary, items, agency info, reviews
- ✅ Book packages (with live price calculator)
- ✅ Cancel bookings
- ✅ Leave star ratings + written reviews (after booking)
- ✅ View booking history

### Travel Agency
- ✅ Register/login with separate interface
- ✅ Agency dashboard with stats (revenue, bookings, ratings)
- ✅ Create/edit/delete travel packages
- ✅ Manage package status (active/inactive/fully_booked/cancelled)
- ✅ Add destinations to packages
- ✅ Upload itinerary days
- ✅ Add/remove flights, accommodation, restaurants, attractions
- ✅ Configure group trips (capacity management)
- ✅ View agency-level reviews

### Security
- ✅ PDO prepared statements (SQL injection prevention)
- ✅ CSRF tokens on all forms
- ✅ Password hashing with `password_hash()` (bcrypt)
- ✅ Session regeneration on login
- ✅ User type separation (traveller cannot access agency pages and vice versa)
- ✅ Ownership checks (agencies can only modify their own packages)
- ✅ `htmlspecialchars()` on all output (XSS prevention)

## Test Accounts (from DB dump)
| Type | Email | Password |
|------|-------|----------|
| Traveller | alice@email.com | *(set via `password_hash`)* |
| Agency | info@sunsettravel.co.za | *(set via `password_hash`)* |

> The dump uses placeholder hashes. Run this to create proper test passwords:
> ```sql
> UPDATE traveler SET password_hash = '$2y$10$YourHashHere' WHERE email = 'alice@email.com';
> ```
> Or register new accounts via the UI.
