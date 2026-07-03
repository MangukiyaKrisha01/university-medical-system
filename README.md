# 🏥 University Medical Application System

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-XAMPP-CA2136?style=for-the-badge&logo=apache&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

A web-based medical leave application management system for universities with role-based access control, OTP email verification, and a complete digital workflow.

[Features](#-features) • [Demo](#-demo-credentials) • [Installation](#-installation) • [Tech Stack](#-tech-stack) • [Screenshots](#-project-structure) • [Security](#-security)

</div>

---

## 📋 Table of Contents

- [About the Project](#-about-the-project)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Database Design](#-database-design)
- [Installation](#-installation)
- [Demo Credentials](#-demo-credentials)
- [Security](#-security)
- [API Used](#-api-used)
- [Future Improvements](#-future-improvements)

---

## 📖 About the Project

The **University Medical Application System** digitizes the entire medical leave application process in a university. It eliminates manual paperwork by providing a clean, role-based digital workflow:

```
Student Applies → HOD Reviews → Receptionist Verifies Hospital Visit
```

### Problem Solved
- ❌ No more handwritten paper forms
- ❌ No more lost application records  
- ❌ No more unclear application status
- ✅ Real-time status tracking
- ✅ Department-wise filtering
- ✅ Complete audit trail with timestamps

---

## ✨ Features

### 🎓 Student Module
- Register with email OTP verification
- Login / Logout with session management
- Forgot password via email OTP
- Apply for medical leave (reason, date, time)
- Real-time application status tracking
- View complete application history with search & filter
- Edit profile and change password

### 👨‍🏫 HOD Module
- Login with email + HOD ID + password
- View **only** their department's applications
- Approve or Reject applications with custom remarks
- Filter applications by status
- View receptionist verification status

### 🏥 Receptionist Module
- View only HOD-approved applications
- Mark student as "Visited Hospital" with notes
- View recently verified applications list

### 👨‍💼 Admin Module
- System-wide dashboard with statistics
- Add new HODs with auto-generated unique HOD IDs
- View all users across all roles
- Monitor all applications system-wide

---

## 🛠️ Tech Stack

| Layer | Technology | Reason |
|-------|-----------|--------|
| **Frontend** | HTML5, CSS3, JavaScript | Pure custom code — no framework overhead |
| **Backend** | PHP 8.0+ (Procedural) | Native XAMPP support, zero configuration |
| **Database** | MySQL 5.7+ | Relational data, included in XAMPP |
| **Server** | Apache (XAMPP) | Industry standard for local PHP development |
| **Email** | Gmail SMTP | Free, reliable OTP delivery |
| **Fonts** | Google Fonts (DM Sans) | Clean modern typography via CDN |

### Why PHP + MySQL over MERN Stack?
- ✅ Runs natively on XAMPP — zero extra setup
- ✅ Zero external dependencies — just copy and run
- ✅ Perfect for role-based form management systems
- ✅ Industry standard for academic projects
- ✅ PHP powers 77% of all websites worldwide

---

## 📁 Project Structure

```
sgp/
├── 📄 index.php                    → Auto-redirect based on login role
├── 📄 database.sql                 → Full DB schema + sample data
│
├── 📂 config/
│   ├── 📄 db.php                   → MySQL database connection
│   ├── 📄 helpers.php              → Shared utility functions
│   ├── 📄 sidebar.php              → Shared sidebar navigation
│   └── 📄 mailer.php               → Gmail SMTP email sender
│
├── 📂 auth/
│   ├── 📄 login.php                → Role-based login
│   ├── 📄 register.php             → Student registration + OTP
│   ├── 📄 forgot_password.php      → Password reset via OTP
│   ├── 📄 otp_verify.php           → OTP verification handler
│   └── 📄 logout.php               → Session destroy + redirect
│
├── 📂 student/
│   ├── 📄 dashboard.php            → Stats + recent applications
│   ├── 📄 apply.php                → Submit medical leave
│   ├── 📄 history.php              → Full application history
│   └── 📄 profile.php              → Edit profile + change password
│
├── 📂 hod/
│   ├── 📄 dashboard.php            → Department stats + pending list
│   └── 📄 manage_applications.php  → Approve/Reject with remarks
│
├── 📂 receptionist/
│   └── 📄 dashboard.php            → Verify hospital visits
│
├── 📂 admin/
│   ├── 📄 dashboard.php            → System overview
│   └── 📄 add_hod.php              → Add HOD + generate HOD ID
│
└── 📂 assets/
    ├── 📂 css/
    │   └── 📄 style.css            → Complete custom CSS
    └── 📂 js/
        └── 📄 script.js            → Sidebar, filters, validation
```

---

## 🗄️ Database Design

### Tables

#### `users` — All system users
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK AUTO_INCREMENT | Unique user ID |
| name | VARCHAR(100) | Full name |
| email | VARCHAR(150) UNIQUE | Login email |
| password | VARCHAR(255) | bcrypt hashed |
| role | ENUM | student/hod/receptionist/admin |
| hod_id | VARCHAR(20) | Links student to department |
| department | VARCHAR(100) | Department name |
| phone | VARCHAR(20) | Mobile number |
| otp | VARCHAR(10) | Temporary OTP storage |
| otp_expiry | DATETIME | 15-minute OTP validity |
| is_verified | TINYINT | 0=unverified, 1=verified |
| created_at | DATETIME | Registration timestamp |

#### `hods` — HOD department mapping
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK AUTO_INCREMENT | Record ID |
| user_id | INT FK | References users.id |
| hod_id | VARCHAR(20) UNIQUE | Auto-generated e.g. HOD-CS-001 |
| department | VARCHAR(100) | Department name |
| created_at | DATETIME | Creation timestamp |

#### `applications` — Medical leave applications
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK AUTO_INCREMENT | Application ID |
| student_id | INT FK | References users.id |
| hod_id | VARCHAR(20) | Department HOD ID |
| reason | TEXT | Medical reason |
| leave_date | DATE | Requested leave date |
| leave_time | TIME | Requested leave time |
| status | ENUM | pending/hod_approved/hod_rejected/receptionist_verified |
| hod_remark | TEXT | HOD's approval/rejection note |
| receptionist_remark | TEXT | Hospital visit note |
| hod_action_at | DATETIME | When HOD acted |
| receptionist_action_at | DATETIME | When receptionist verified |
| created_at | DATETIME | Submission timestamp |

### Relationships
```
users (id) ──────────── hods (user_id)
users (id) ──────────── applications (student_id)
hods (hod_id) ────────── applications (hod_id)
```

---

## 🚀 Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) installed (Apache + MySQL + PHP)
- Gmail account with 2-Step Verification enabled

### Step 1 — Clone Repository
```bash
git clone https://github.com/YOUR_USERNAME/university-medical-system.git
```

### Step 2 — Copy to XAMPP
```
Copy the sgp/ folder to:
C:\xampp\htdocs\sgp\
```

### Step 3 — Import Database
1. Open `http://localhost/phpmyadmin`
2. Click **New** → Create database named `university_medical`
3. Click **Import** → Select `database.sql` → Click **Go**

### Step 4 — Configure Email (Gmail SMTP)
Open `config/mailer.php` and update:
```php
define('GMAIL_ADDRESS',      'your_gmail@gmail.com');
define('GMAIL_APP_PASSWORD', 'xxxxxxxxxxxxxxxxxxxx'); // 16-char App Password
define('SMTP_FROM',          'your_gmail@gmail.com');
```

#### How to get Gmail App Password:
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable **2-Step Verification**
3. Go to [App Passwords](https://myaccount.google.com/apppasswords)
4. Create → Copy the 16-character password (remove spaces)

### Step 5 — Start XAMPP & Run
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Open browser → `http://localhost/sgp/`

---

## 🔑 Demo Credentials

| Role | Email | HOD ID | Password |
|------|-------|--------|----------|
| **Admin** | admin@university.edu | — | password |
| **HOD (CS)** | hod.cs@university.edu | HOD-CS-001 | password |
| **HOD (EC)** | hod.ec@university.edu | HOD-EC-002 | password |
| **Receptionist** | receptionist@university.edu | — | password |
| **Student 1** | arjun@student.edu | — | password |
| **Student 2** | neha@student.edu | — | password |
| **Student 3** | rohan@student.edu | — | password |

> **Note:** HOD login requires entering the HOD ID in an additional field that appears when HOD role is selected.

---

## 🔐 Security

| Security Feature | Implementation |
|-----------------|----------------|
| **Password Hashing** | `password_hash()` with bcrypt algorithm |
| **SQL Injection Prevention** | Prepared statements with `bind_param()` on all queries |
| **XSS Prevention** | `htmlspecialchars()` + `strip_tags()` on all output |
| **Session Authentication** | `$_SESSION` with role verification on every page |
| **Role-Based Access Control** | `requireLogin('role')` blocks unauthorized access |
| **OTP Security** | 6-digit, 15-minute expiry, deleted after use |
| **TLS Encryption** | STARTTLS on Gmail SMTP connection |
| **Input Validation** | Server-side validation on all form submissions |
| **Email Masking** | Partial email shown on screen (`arr***@gmail.com`) |
| **Department Isolation** | HODs only see their own department via session filter |

---

## 📧 API Used

### Gmail SMTP (Email OTP)

| Detail | Value |
|--------|-------|
| **Service** | Google Gmail SMTP |
| **Protocol** | SMTP with STARTTLS |
| **Host** | smtp.gmail.com |
| **Port** | 587 |
| **Auth** | Gmail App Password |
| **Cost** | Free (500 emails/day) |
| **Library** | None — custom PHP socket implementation |

#### SMTP Flow
```
PHP fsockopen() → smtp.gmail.com:587
→ EHLO
→ STARTTLS (upgrade to TLS 1.2)
→ AUTH LOGIN (base64 credentials)
→ MAIL FROM / RCPT TO
→ DATA (base64 HTML email)
→ QUIT
```

---

## 🔄 Application Workflow

```
┌─────────────────────────────────────────────────────────┐
│                  APPLICATION LIFECYCLE                  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Student Submits                                        │
│       ↓                                                 │
│  Status: PENDING ⏳                                     │
│       ↓                                                 │
│  HOD Reviews                                            │
│       ↓              ↓                                  │
│  HOD APPROVED ✅   HOD REJECTED ❌                      │
│       ↓                                                 │
│  Receptionist Verifies                                  │
│       ↓                                                 │
│  HOSPITAL VERIFIED 🏥                                   │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🌐 Protocols Used

| Protocol | Purpose | Port |
|----------|---------|------|
| **HTTP** | Browser ↔ Apache communication | 80 |
| **SMTP** | OTP email delivery | 587 |
| **TLS 1.2** | Encrypts SMTP connection | 587 |
| **TCP/IP** | Underlying network transmission | — |
| **MySQL Wire** | PHP ↔ Database communication | 3306 |

---

## 🚧 Future Improvements

- [ ] PDF download of approved application
- [ ] Email notification on HOD approve/reject
- [ ] Admin can edit/delete users
- [ ] Analytics charts on admin dashboard
- [ ] CSRF token protection
- [ ] Rate limiting on OTP requests
- [ ] HTTPS support for live deployment
- [ ] REST API for mobile app integration
- [ ] Notification system (bell icon)
- [ ] Dark mode support

---

## 👨‍💻 Author

**Krisha Mangukiya**

[![GitHub](https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white)](https://github.com/YOUR_USERNAME)

---

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

<div align="center">
Made with ❤️ for University Medical Leave Management
</div>
