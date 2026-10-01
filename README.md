# BookWorld — Library Management System

A full-stack public library management system built with **Core PHP, MySQLi (prepared statements), MySQL, Bootstrap 5, vanilla JavaScript, SweetAlert2, and Chart.js.**

## Setup (XAMPP)

1. Copy the `BookWorld` folder into `C:\xampp\htdocs\` (Windows) or `/opt/lampp/htdocs/` (Linux).
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), create nothing manually — just go to **Import**, choose `database/bookworld.sql`, and run it. This creates the `bookworld` database, all 19 tables, and sample data.
4. Visit `http://localhost/BookWorld/` — the landing page should load.

If your MySQL root user has a password, update `config/database.php` accordingly.

## Demo accounts (seeded by `bookworld.sql`)

| Role | Email | Password |
|---|---|---|
| Admin | admin@bookworld.com | Admin@123 |
| Librarian | riya.librarian@bookworld.com | Lib@123 |
| Librarian | karan.librarian@bookworld.com | Lib@123 |
| Member | aarav@example.com | Member@123 |
| Member | priya@example.com | Member@123 |
| Member | rohan@example.com | Member@123 |

**Change these passwords before deploying anywhere beyond a local demo.**

## What's implemented

- **Public site**: landing page, live-search catalog browsing, membership plans — all before login.
- **Auth**: registration (members only), login (auto-detects role across 3 tables), forgot/reset password (token-based, 1-hour expiry), CSRF protection, session timeout, brute-force lockout after 5 failed attempts.
- **Admin**: dashboard with live charts, full book CRUD, inventory + restocking, librarian CRUD, member management (deactivate), donation oversight, reports, activity log viewer.
- **Librarian**: dashboard, book CRUD, issue/reject requests (with subscription-limit + availability checks), returns (auto fine calculation, auto reservation fulfillment), inventory (read-only), donation approval (auto-adds to catalog), member list (read-only).
- **Member**: dashboard, browse/search/reserve/request-issue, my issued books + return requests + fine payment, reservations, book donation, book purchase (simulated payment), subscription plans (Bronze/Silver/Gold), profile + password change, feedback.
- **Automation**: 14-day due dates, ₹50/day overdue fines calculated automatically on return, 30-day forfeiture (fine + book price + subscription cancellation), automatic reservation fulfillment on return, low-stock notifications to admin/librarian, new arrivals section, simple demand-prediction signal (see `cron/check_low_stock.php`).
- **Scheduled tasks** (`/cron`): run these via Windows Task Scheduler / cron for full automation, or trigger manually for a demo:
  - `php cron/calculate_fines.php` — flags overdue issues, auto-forfeits books over 30 days late.
  - `php cron/check_low_stock.php` — raises low-stock alerts + demand signal.
  - `php cron/check_overdue_subscriptions.php` — expires subscriptions past their end date.

## Notes on design decisions

- **"Delete" on books/members is a soft delete** (status flips to inactive) rather than a hard `DELETE`, since issue/fine/payment history references them via foreign keys — this keeps reports accurate.
- **Payments are fully simulated** (no real payment gateway), per the "Core PHP / no external paid APIs" constraint — every payment instantly succeeds and generates a transaction ID for the receipt trail.
- **Password reset** works without a configured mail server: in local/demo environments the reset link is shown directly in the success message so you can test the flow end-to-end without SMTP.
- Four tables were added beyond your original 15 — `categories`, `subscription_plans`, `notifications`, and `password_resets` — because the requested features (genre filtering, meaningfully different Gold/Silver/Bronze plans, persistent low-stock/donation alerts, and forgot-password) all needed somewhere to live. See the SQL comments for details.
