<div align="center">

<img src="assets/img/logo.svg" alt="AgriRent logo" width="90">

# 🚜 AgriRent

### Agricultural Equipment Rental and Management System

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-MVC-2ea44f)
![XAMPP](https://img.shields.io/badge/Server-XAMPP-FB7A24?logo=xampp&logoColor=white)
![PWA](https://img.shields.io/badge/Offline-PWA-5A0FC8?logo=pwa&logoColor=white)
![Course](https://img.shields.io/badge/Course-Software%20Engineering-blue)

**A group project for the Software Engineering course at AIUB (Spring 2025-26)**

[About](#-about-the-project) •
[Features](#-features) •
[Tech Stack](#%EF%B8%8F-technologies-used) •
[Installation](#-installation) •
[Project Structure](#%EF%B8%8F-project-structure) •
[Team](#-academic-information)

</div>

---

## 📖 About The Project

Tractors, harvesters and other machines raise farm productivity, but most small and medium-scale farmers
cannot afford to buy them. They depend on informal rental deals that are slow, unreliable and hard to track.
Equipment owners, on the other side, struggle to manage availability, bookings, delivery and maintenance.

**AgriRent** is a centralized web platform where **farmers** browse and rent equipment, **employees** handle
deliveries, maintenance and verification, and **administrators** supervise the whole system. It makes renting
faster, more transparent and easier to manage.

### 🎯 Project Objectives

- Give farmers one place to find, book and pay for equipment for a set period
- Support three roles: **Farmer, Employee, Admin**
- Track every rental from booking → payment → delivery → return inspection
- Add smart features: equipment matching by crop, seasonal pricing, emergency requests
- Work in **English and Bangla**, and keep working **offline** in low-network areas

### 🔄 Software Process Model

We followed the **RAD (Rapid Application Development) Model**. The system was split into modules (Authentication, Equipment
Management, Booking & Payment, Support & Smart Features, Admin & Maintenance), and each increment went
through analysis, design, coding and testing before the next one was added.

---

## ✨ Features

### 👨‍🌾 Farmer
- Register, verify email, log in (remember-me, optional **two-factor login**, "Continue with Google")
- Upload NID / land documents for **account verification**
- **Browse equipment** with search and category, price and availability filters; see details, gallery and reviews
- **Book equipment** with a live price quote, date-conflict and stock checks
- **Pay** with bKash, Nagad, card or cash on delivery (simulated gateway), apply coupons, download invoices
- Request a **rental extension** or **cancellation**
- **Smart equipment matching** by crop, land size and season
- **Emergency equipment requests** matched by distance
- Wishlist, reviews and ratings, complaints / support tickets, chat with employees
- Rental history with **PDF / CSV export**, notifications, profile and account deletion

### 🧑‍💼 Employee
- Approve or reject **farmer verification** documents
- Handle rental requests and **delivery tracking** (team assignment, status timeline)
- **Schedule maintenance**, monitor **equipment health** (usage hours, service intervals, alerts)
- **Inspect returned equipment**
- Reply to complaints and farmer chats, mark cash payments as paid

### 🛡️ Admin
- Role-based dashboards, **analytics with charts**, PDF / CSV reports, data integrity check
- Manage **users, equipment and categories**
- **Seasonal / dynamic pricing** (harvest increase, off-season discount, minimum price) with a price-change log
- Payment tracking and export, coupon management
- System settings: general, security, pricing, notifications, payments, account rules, system logs

### 🌐 Platform
- **English / বাংলা** switch on every page
- **Offline mode** (installable PWA): cached pages, bookings and complaints are queued and synced when back online
- AJAX forms that also work without JavaScript

### 🔐 Security
- PDO **prepared statements** everywhere; passwords hashed with `password_hash()`
- **CSRF token** on every form and AJAX request
- `HttpOnly` + `SameSite=Lax` session cookie, idle timeout, session ID regenerated on login
- Account **lockout** after failed logins and **suspicious-login alerts** (new IP or device)
- OTP codes expire after **120 seconds**
- Role checks in every controller action; farmers see only their own data
- Upload checks (size + real MIME type); verification documents are never publicly reachable
- `.htaccess` blocks `app/`, `config/`, `database/`, `storage/` and stops scripts from running in `uploads/`

---

## 🛠️ Technologies Used

| Component | Technology |
|---|---|
| Backend | PHP 8 (custom MVC, no framework, no Composer packages) |
| Database | MySQL / MariaDB (PDO, prepared statements) |
| Frontend | HTML5, CSS3, vanilla JavaScript (AJAX), SVG charts |
| Offline | Web App Manifest + Service Worker |
| Server | Apache via XAMPP |
| Design | Figma |
| Project management | Trello, Gantt chart, Git & GitHub (feature branches + pull requests) |

---

## 📋 Requirements

- **XAMPP** (Apache + MySQL) with **PHP 8.0 or newer**
- `AllowOverride All` for `.htaccess` (on by default in XAMPP)
- A modern web browser

---

## 🚀 Installation

### Step 1: Clone the repository into `htdocs`

```bash
cd C:\xampp\htdocs
git clone <this-repository-url> agri_rent_system
```

### Step 2: Start XAMPP

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### Step 3: Import the database

1. Open `http://localhost/phpmyadmin`
2. Go to the **Import** tab → choose `database/agriproject.sql` → **Go**

The script creates (or resets) the `agriproject` database with demo data, so there is no need to create it first.
From a terminal you can run instead:

```bash
C:\xampp\mysql\bin\mysql.exe -u root < database\agriproject.sql
```

### Step 4: Check the database settings

If your MySQL user is not `root` with an empty password, edit the `db` section of `config/config.php`:

```php
'db' => [
    'host' => 'localhost',
    'name' => 'agriproject',
    'user' => 'root',
    'pass' => '',
],
```

### Step 5: Open the website

```
http://localhost/agri_rent_system/
```

If the database is missing, every page shows a "Database setup required" screen that points to Step 3.

### 🔑 Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@agrirent.test` | `Admin@123` |
| Employee | `employee@agrirent.test` | `Employee@123` |
| Farmer | `farmer@agrirent.test` | `Farmer@123` |

More seeded accounts (same password as their role):

- `jamal@`, `salma@`, `aminul@agrirent.test`: approved farmers
- `nadia@`, `tania@agrirent.test`: farmers waiting for document verification
- `habib@agrirent.test`: farmer who has not verified their email
- `rafiq@agrirent.test`: farmer whose verification was rejected
- `sumaiya@`, `rasel@agrirent.test`: employees

The login page also has **Demo accounts** buttons that fill these in.

---

## 🧪 Testing Email, OTP and Payment Flows

**Emails.** The mail driver is `log` by default, so nothing leaves your computer. Every email (verification,
password reset, 2FA codes, booking and payment notices) is saved in `storage/mail/` and can be read at
`http://localhost/agri_rent_system/index.php?r=mailbox` (localhost only).

**Payments.** bKash, Nagad and card use a simulated gateway. No real money moves.

| What to test | How |
|---|---|
| Successful card payment | `4242 4242 4242 4242`, any future expiry, any 3-digit CVC |
| Declined card | `4000 0000 0000 0002` |
| bKash / Nagad | Any number like `01712345678`; the OTP is shown on screen; any 4–5 digit PIN |
| Failed wallet payment | PIN `0000` |
| Cash on delivery | Booking stays "Pending payment" until an employee marks it paid |

**Coupons.** `FARM10` (10% off), `HARVEST500` (৳500 off orders of ৳3,000+), `NEW20` (20% off),
`MONSOON15` (expired, shows the error message).

**Two-factor login.** Turn it on from **Profile** (or for everyone under **Settings → Security**).
The 6-digit code appears in the mail outbox.

---

## 🏗️ Project Structure

```
agri_rent_system/
├── index.php                 # Front controller: every request starts here
├── .htaccess                 # Blocks source, config, SQL and logs
├── manifest.json             # PWA manifest
├── service-worker.js         # Offline caching
├── offline.html              # Page shown when offline
├── config/
│   └── config.php            # Database, mail, Google OAuth, security settings
├── app/
│   ├── bootstrap.php         # Autoloader, error handler, session start
│   ├── helpers.php           # url(), e(), t(), money(), badge() ...
│   ├── core/                 # Router, Controller, Model, View, Database, Session, Auth, Validator, Lang
│   ├── controllers/          # One controller per module (Auth, Equipment, Booking, Payment ...)
│   ├── models/               # Database access and business rules
│   ├── services/             # Pricing, Availability, Health, Mailer, Pdf, Upload, Notifier, Settings
│   ├── views/                # Layouts, partials and one folder per module
│   └── lang/                 # Bangla translations
├── assets/
│   ├── css/                  # style.css
│   ├── js/                   # app.js, pages.js, charts.js, offline.js
│   └── img/                  # Logo, hero images, equipment icons
├── database/
│   └── agriproject.sql       # Schema + demo data
├── storage/                  # Mail outbox, logs, sessions (not web accessible)
└── uploads/                  # Equipment, profile, complaint and verification files
```

---

## 🧩 How MVC Works Here

Every page goes through `index.php` with a route, for example `index.php?r=booking/store`.

```
Browser ──► index.php ──► Router ──► Controller ──► Model / Service ──► MySQL
                                         │
                                         └──► View (HTML) or JSON ──► Browser
```

Example: **placing a booking**

| Layer | File | Job |
|---|---|---|
| Router | `app/core/Router.php` | Maps `booking/store` to `BookingController::store()` |
| Controller | `app/controllers/BookingController.php` | Checks login and role, validates input |
| Service | `app/services/Pricing.php`, `Availability.php` | Calculates the price and checks free dates |
| Model | `app/models/Booking.php` | Saves the booking inside a transaction |
| Response | — | JSON for AJAX forms, or redirect + flash message |

### Main Modules

| Module | Routes | Code |
|---|---|---|
| Authentication | `auth/*` | `AuthController` |
| Verification | `verification/index`, `profile/index` | `VerificationController`, `ProfileController` |
| Equipment & Categories | `equipment/*`, `category/index` | `EquipmentController`, `CategoryController` |
| Booking | `booking/*` | `BookingController` |
| Payment | `payment/*` | `PaymentController` |
| Rentals & Delivery | `rental/index`, `delivery/index` | `RentalController`, `DeliveryController` |
| Maintenance & Inspection | `maintenance/*`, `inspection/index` | `MaintenanceController`, `InspectionController` |
| Smart Matching | `smart/index` | `SmartController` |
| Emergency Requests | `emergency/*` | `EmergencyController` |
| Support | `wishlist/*`, `complaint/*`, `chat/*`, `notification/*` | matching controllers |
| Dashboards & Analytics | `dashboard/index`, `analytics/index` | `DashboardController`, `AnalyticsController` |
| Settings | `settings/index` | `SettingsController` |

---

## 🔄 Rental Lifecycle

```
Farmer:   Register ──► Verify email ──► Upload documents ──► Approved by employee
Booking:  Choose equipment ──► Live quote ──► Book ──► Pay ──► Delivered
Return:   Rental ends ──► Equipment returned ──► Inspection ──► Review & rating
Options:  Extend rental │ Cancel booking │ Emergency request │ Complaint
```

---

## 📐 Project Management & Design

| Artifact | Link |
|---|---|
| Project report (PRD, design, Git workflow, testing) | [AgriRent_Project_Report.pdf](docs/AgriRent_Project_Report.pdf) |
| UI design (Figma) | [Figma file](https://www.figma.com/design/92rjWx3kj4qrw5pvTFe5jF/Agricultural-Equipment-Rental-And-Management-System?node-id=0-1&t=uvIhS6bg7S890HzH-1) |
| Task board (Trello) | [Trello board](https://trello.com/b/OmDgi9mf/agricultural-equipment-rental-and-management-system) |
| Timeline | [Gantt chart](https://www.onlinegantt.com/#/shared/01b22a57-6a80-4b83-8439-ac3fe8055bfa) |

The project report also covers the PRD (functional and non-functional requirements), use case and class
diagrams, COCOMO estimation (organic mode, 17.8 KLOC) and test cases for every module.

---

## 🎓 Academic Information

This project was built as a **group project** for the **Software Engineering** course.

### Course Details

| | |
|---|---|
| **University** | American International University-Bangladesh (AIUB) |
| **Faculty** | Faculty of Science and Technology (FST) |
| **Department** | Department of Computer Science |
| **Course** | Software Engineering |
| **Semester** | Spring 2025-26 |

### Project Team

| SL | Name | Student ID |
|---|---|---|
| 1 | Sadia Ahmed | 23-55053-3 |
| 2 | Orpyta Karmokar Aupy | 23-55051-3 |
| 3 | Samiul Hasan | 23-55331-3 |
| 4 | Sumit Paul | 23-53602-3 |
| 5 | Tanni Mandal | 23-53424-3 |

---

## 🐛 Known Limitations

- Payments use a **simulated** gateway (no real bKash / Nagad / card processing)
- "Continue with Google" needs your own OAuth credentials in `config/config.php`
- Emails stay in the local outbox until the mail driver is switched to `smtp`
- Emergency requests locate farmers by browser geolocation, or by district centre if that is denied
- Built and tested on XAMPP only

## 🔮 Future Enhancements

- Real payment gateway integration
- SMS notifications for OTPs and booking updates
- GPS tracking of equipment during delivery
- Mobile app for farmers

---

## 📄 License

This project was created for educational purposes as part of the **Software Engineering** course at AIUB.
Feel free to use it for learning with proper attribution.

<div align="center">

⭐ If you found this project helpful, please give it a star! ⭐

Made with ❤️ by the AgriRent team

</div>
