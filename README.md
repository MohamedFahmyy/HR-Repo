# Enterprise HR & Payroll System
## Native PHP OOP + Custom MVC + MySQL + ZKTeco Biometric Integration

A standalone, production-oriented Enterprise Human Resources Management System (HRMS) & Payroll Engine with native ZKTeco biometric device integration, built with **Pure Native PHP 8.4+ (OOP)**, a lightweight custom MVC framework, and MySQL.

> **Zero Full-Stack Frameworks**: Built without Laravel, Symfony, CodeIgniter, or CakePHP. Powered by native PSR-4 autoloading, custom routing, middleware pipelines, service layers, and PDO prepared statements.

---

### ✨ Core Features & Capabilities

1. **Lightweight Custom MVC Framework**:
   - Single front-controller (`public/index.php`).
   - Route dispatcher supporting HTTP verbs (`GET`, `POST`, `PUT`, `DELETE`), parameter capture (`{id}`), route groups, and nested middleware.
   - Central `Request`, `Response`, `Session`, `View`, `Auth`, `Validator`, `Csrf`, and `ErrorHandler` components.
   - 404, 403, and 500 error pages with clean CLI error formatting in console mode.

2. **Enterprise Authentication & Granular RBAC**:
   - Secure password hashing using native `password_hash()` (Argon2id/Bcrypt).
   - Session fixation protection with automatic `session_regenerate_id()`.
   - Granular Role-Based Access Control (`super-admin`, `hr-manager`, `attendance-manager`, `payroll-manager`, `employee`).
   - Audit trail tracking user modifications with IP address and user-agent logging.

3. **ZKTeco Biometric Terminal Integration**:
   - **Direct Socket TCP (Port 4370)**: Binary socket protocol implementation supporting handshake commands (`CMD_CONNECT`, `CMD_EXIT`, `CMD_ATTLOG_RRQ`), 16-bit checksums, and 40-byte/14-byte attendance packet unpacking.
   - **ADMS / iClock HTTP Push Listener**: High-throughput HTTP endpoint (`/api/zkteco/iclock/cdata`) parsing tab-delimited terminal uploads in real time.
   - **Mock Hardware Simulator**: Deterministic hardware emulation adapter for continuous integration and offline staging.
   - **Separation of Raw vs Calculated Events**: Raw punches are preserved permanently with idempotent deduplication constraints.

4. **Deterministic Attendance & Shift Engine**:
   - Multi-shift scheduling (Fixed, Flexible, Evening, Overnight crossing midnight, Ramadan schedule).
   - Policy-based calculation with configurable grace periods (e.g., 15 minutes).
   - Explainable calculation of lateness, early departures, worked hours, and overtime hours.
   - Missing checkout detection and manual adjustment request workflows.

5. **Transactional Leave Management**:
   - Balance ledger (`leave_balance_transactions`) tracking opening balances, accruals, deductions, and reversals.
   - Overdraw prevention throwing exceptions on insufficient balances.
   - Self-service leave request submission and multi-level approval workflows.

6. **Comprehensive Payroll Engine**:
   - Salary formula: `Net Pay = Basic + Housing + Transport + Other Allowances + Overtime Pay - (Absence + Lateness + Unpaid Leave)`.
   - Lateness deduction tiers based on company policy.
   - **Immutable Payroll Runs**: Locked payroll runs cannot be modified or recalculated without explicit authorized unlock.
   - Automated payslip generation with cryptographically secure access tokens.

7. **Bilingual User Interface (Arabic RTL First)**:
   - Modern, executive dashboard design using Bootstrap 5 RTL with English toggle.
   - Key operational widgets, real-time KPI cards, and Chart.js visualizations.
   - Employee Self-Service (ESS) portal for attendance review, leave requests, and payslip viewing.

---

### 📁 Project Architecture

```
hr-management-system/
├── app/
│   ├── Controllers/             # Native HTTP controllers
│   ├── Core/                    # Lightweight custom MVC core
│   │   ├── Application.php      # Application bootstrapper
│   │   ├── Router.php           # Regex route dispatcher & groups
│   │   ├── Database.php         # PDO prepared statements & transactions
│   │   ├── Request.php          # HTTP request abstraction
│   │   ├── Response.php         # HTTP response & JSON helpers
│   │   ├── Controller.php       # Base controller with validation
│   │   ├── Model.php            # Base active-record / query builder
│   │   ├── View.php             # PHP layout view renderer
│   │   ├── Session.php          # Secure session manager
│   │   ├── Auth.php             # Authentication & RBAC checker
│   │   ├── Validator.php        # Server-side input validator
│   │   ├── Csrf.php             # CSRF token generator & verifier
│   │   └── ErrorHandler.php     # Global exception & error handler
│   ├── Helpers/                 # Global helper functions & translation
│   ├── Integrations/            # Hardware integration layer
│   │   └── ZKTeco/
│   │       ├── Contracts/       # Device adapter interfaces
│   │       ├── DTOs/            # Typed punch data objects
│   │       ├── Adapters/        # Direct TCP, ADMS Push & Mock adapters
│   │       └── Services/        # Device sync & event orchestrator
│   ├── Middleware/              # Authentication, Guest, Role, CSRF
│   ├── Models/                  # Database entity models
│   ├── Services/                # Business logic services
│   └── Views/                   # Clean PHP view templates & layouts
├── bin/                         # Executable CLI scripts
│   ├── migrate.php              # Database migration runner
│   ├── seed.php                 # Database seeder
│   ├── sync-attendance.php      # Biometric device sync CLI
│   └── calculate-attendance.php # Daily attendance calculation CLI
├── config/                      # Application & database configurations
├── database/                    # Schema migrations & seeders
├── docs/                        # Technical manuals & guides
├── public/                      # Web-accessible document root
│   ├── assets/                  # CSS, JS, Fonts & Icons
│   └── index.php                # Front controller
├── routes/                      # Route definitions (web.php)
└── tests/                       # Automated PHPUnit test suite
```

---

### 🚀 Quick Start & Installation

#### 1. Requirements
- PHP 8.3+ or 8.4+ with extensions: `pdo_mysql`, `sockets`, `mbstring`, `json`, `openssl`.
- MySQL 8.0+ or MariaDB 10.4+.
- Web server: Apache (with `mod_rewrite`) or Nginx.

#### 2. Environment Setup
Clone the repository and copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Configure your database credentials in `.env`:
```ini
APP_NAME="Enterprise HR & Payroll System"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/test-folder/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hr_payroll_system
DB_USERNAME=root
DB_PASSWORD=
```

#### 3. Run Database Migrations & Seeders
Execute the native CLI scripts:
```bash
# Run schema migrations
php bin/migrate.php

# Seed initial roles, permissions, admin user, shifts, devices, and sample records
php bin/seed.php
```

#### 4. Configure Web Server Document Root
Point your web server's VirtualHost document root directly to the `public/` directory:
- **Apache**: Ensure `AllowOverride All` is set so `public/.htaccess` routes requests through `public/index.php`.
- **Nginx configuration**:
  ```nginx
  server {
      listen 80;
      server_name hr.local;
      root /path/to/project/public;
      index index.php;

      location / {
          try_files $uri $uri/ /index.php?$query_string;
      }

      location ~ \.php$ {
          fastcgi_pass 127.0.0.1:9000;
          fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
          include fastcgi_params;
      }
  }
  ```

---

### 🔑 Default Credentials

| Role | Username / Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Super Administrator** | `admin@enterprise.local` | `Admin@123456` | Complete system control |
| **HR Manager** | `hr.manager@enterprise.local` | `Emp@123456` | Employee, shifts, leaves, recruitment |
| **Payroll Manager** | `payroll@enterprise.local` | `Emp@123456` | Payroll calculation, review & locking |
| **Employee (ESS Portal)** | `fahad.dev@enterprise.local` | `Emp@123456` | Personal attendance, leaves, payslips |

---

### 🧪 Automated Testing

The project includes unit and feature test suites built on **PHPUnit 13+**:

```bash
php vendor/bin/phpunit
```

#### Test Suite Coverage:
- **ZKTeco Protocol Tests**: Direct TCP packet headers, ADMS push parsing, and mock simulator integrity.
- **Attendance Calculation Tests**: Grace periods, lateness minutes, overnight shifts, missing checkout handling, and absence detection.
- **Payroll Calculation Tests**: Standard gross/net formula, overtime compensation, lateness deductions, and immutable lock protection.
- **Leave Ledger Tests**: Transactional ledger deduction, balance overdraw prevention, and cancellation reversal.
- **Security & RBAC Tests**: Password hashing verification, role authorization, CSRF token validation, and SQL injection resistance via PDO prepared statements.

---

### ⏱️ Background Task Scheduling

To run automated device synchronization and midnight attendance processing, schedule the CLI commands:

```bash
# Synchronize biometric devices (run every 10 minutes)
php bin/sync-attendance.php

# Calculate daily attendance (run daily at 23:55)
php bin/calculate-attendance.php
```

Refer to [docs/cron-scheduler.md](file:///d:/laragon/www/test-folder/docs/cron-scheduler.md) for full Linux Crontab and Windows Task Scheduler setup guides.

---

### 📖 Documentation

- [ZKTeco Biometric Integration Manual](file:///d:/laragon/www/test-folder/docs/zkteco-integration.md)
- [Background Scheduler & Cron Guide](file:///d:/laragon/www/test-folder/docs/cron-scheduler.md)
