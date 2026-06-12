# CyberAware - Cybersecurity Awareness Training Platform

A gamified cybersecurity awareness training platform built for African organisations. Real phishing simulations, interactive lessons, and measurable results.

## 🎯 Overview

CyberAware is a comprehensive security awareness training platform that combines gamification with practical cybersecurity education. Employees learn through interactive modules, phishing simulations, and real-world scenarios while earning XP, maintaining streaks, and unlocking achievements.

**Built for:** Organisations in Namibia and across Africa  
**Status:** Production Ready ✅  
**Version:** 1.0.0

---

## ✨ Key Features

### 🎮 Gamification System
- **XP Points** - Earn points for completing modules and quizzes
- **Daily Streaks** - Maintain consecutive days of training
- **Lives System** - 5 lives per day, lose lives on incorrect answers
- **Levels & Badges** - Progress through levels and unlock achievements
- **Leaderboards** - Compete with colleagues (coming soon)

### 📚 Training Modules (7 Total)
1. **Phishing Email Recognition** - Identify suspicious emails and phishing attempts
2. **Credential Harvesting Awareness** - Spot fake login pages and credential theft
3. **Social Engineering Defense** - Recognize manipulation tactics and social attacks
4. **Malware & Attachment Safety** - Identify dangerous files and malware vectors
5. **Website & Link Safety** - Verify URLs and detect typosquatting attacks
6. **Password Security** - Create strong passwords and manage credentials
7. **Ransomware Awareness** - Understand ransomware threats and prevention

### 🎯 Module Features
- Sequential unlocking (pass 70% to unlock next module)
- Interactive lessons with real-world scenarios
- Quizzes with immediate feedback
- Progress tracking and scoring
- Certificate generation upon completion

### 📧 Phishing Inbox Simulation
- Weekly phishing email simulations
- Real-world email scenarios
- Track who clicks, reports, or ignores phishing
- Measure employee awareness improvement

### 📊 Admin Dashboard
- Real-time compliance reporting
- Department-level security posture tracking
- Individual employee progress monitoring
- CSV export for compliance audits
- Module management and assignment

### 👥 User Management
- Role-based access control (Admin, Manager, Trainee)
- Department assignment
- User activity tracking
- Compliance snapshot reporting

---

## 🚀 Getting Started

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (recommended for local development)

### Installation

1. **Clone/Download the project**
   ```bash
   cd /path/to/cyberaware
   ```

2. **Create the database**
   - Open phpMyAdmin
   - Create a new database named `cyberaware`
   - Import the database schema:
     ```sql
     -- Run the SQL files in this order:
     database/cyberaware_test.sql
     database/create_module_registration.sql
     database/create_phish_inbox_tables.sql
     database/create_contact_table.sql
     database/add_requested_modules.sql
     ```

3. **Configure database connection**
   - Edit `config/database.php`
   - Update DB_HOST, DB_USER, DB_PASS if needed
   - Default: localhost, root, (no password)

4. **Access the application**
   ```
   http://localhost/cyberaware-new-Edits/
   ```

---

## 👤 Test Credentials

### Trainee Account
- **Username:** `trainee`
- **Password:** `Uncle@foddy1`
- **Role:** Trainee
- **Access:** Training modules, progress tracking, certificates
- **URL:** `http://localhost/cyberaware-new-Edits/trainee/dashboard.php`

### Admin Account
- **Username:** `admin`
- **Password:** `Uncle@foddy1`
- **Role:** Administrator
- **Access:** Full admin dashboard, user management, reporting
- **URL:** `http://localhost/cyberaware-new-Edits/admin/dashboard.php`

### Manager Account
- **Username:** `manager`
- **Password:** `Uncle@foddy1`
- **Role:** Manager
- **Access:** Department reporting, team progress
- **URL:** `http://localhost/cyberaware-new-Edits/manager/dashboard.php`

### Super Admin Account
- **Username:** `superadmin`
- **Password:** `Uncle@foddy1`
- **Role:** Super Administrator
- **Access:** Platform administration, subscription management, content updates
- **URL:** `http://localhost/cyberaware-new-Edits/superadmin/dashboard.php`
- **Organization:** Intellectual Technology CC (Exclusive)

---

## 📁 Project Structure

```
cyberaware/
├── admin/                          # Admin dashboard pages
│   ├── dashboard.php              # Main admin dashboard
│   ├── manage-users.php           # User management
│   ├── manage_modules.php         # Module management
│   ├── campaigns.php              # Campaign management
│   ├── reports.php                # Reporting
│   ├── export-report.php          # Export compliance reports
│   └── compliance-snapshot.php    # Compliance overview
│
├── manager/                        # Manager dashboard pages
│   ├── dashboard.php              # Manager dashboard
│   ├── profile.php                # Manager profile
│   └── settings.php               # Manager settings
│
├── superadmin/                     # Super Admin portal (Intellectual Technology CC)
│   ├── dashboard.php              # Platform overview & analytics
│   ├── organizations.php          # Organization management
│   ├── subscriptions.php          # Subscription management
│   ├── training-content.php       # Training content management
│   ├── superadmin-sidebar.php     # Navigation sidebar
│   └── README.md                  # Super Admin documentation
│
├── trainee/                        # Trainee pages
│   ├── dashboard.php              # Trainee dashboard
│   ├── register-modules.php       # Module registration
│   ├── progress.php               # Progress tracking
│   ├── phish-inbox.php            # Phishing simulations
│   ├── certificate.php            # Certificates
│   ├── modules/                   # Training module files
│   │   ├── phishing.php
│   │   ├── credentials.php
│   │   ├── social.php
│   │   ├── attachments.php
│   │   ├── links.php
│   │   ├── password.php
│   │   └── ransomware.php
│   └── trainee-sidebar.php        # Navigation sidebar
│
├── pages/                          # Public pages
│   ├── login.php                  # Login page
│   ├── register.php               # Registration (if enabled)
│   ├── training_module.php        # Module detail page
│   ├── simulation.php             # Simulation runner
│   └── training_result.php        # Results page
│
├── config/                         # Configuration
│   └── database.php               # Database connection & helpers
│
├── includes/                       # Shared includes
│   ├── header.php                 # Page header
│   ├── footer.php                 # Page footer
│   ├── functions.php              # Helper functions
│   └── trainee-sidebar.php        # Trainee navigation
│
├── database/                       # Database schemas
│   ├── cyberaware_test.sql        # Main schema
│   ├── create_module_registration.sql
│   ├── create_phish_inbox_tables.sql
│   ├── create_contact_table.sql
│   └── add_requested_modules.sql
│
├── assets/                         # Static assets
│   ├── css/
│   │   └── style.css
│   ├── logos/                     # Company logos
│   └── videos/
│       └── modules/
│
├── index.php                       # Homepage
├── contact.php                     # Contact form
├── logout.php                      # Logout handler
└── README.md                       # This file
```

---

## 🔐 Database Schema

### Core Tables

**users**
- User accounts with roles (trainee, admin, manager)
- Password hashing with bcrypt
- Department assignment
- Activity tracking

**training_modules**
- 7 core security modules
- Module metadata (difficulty, duration, category)
- Active/inactive status

**user_module_registrations**
- Tracks which modules each trainee registered for
- Registration order (sequential learning path)
- Status (pending, in_progress, completed)

**user_module_progress**
- Tracks trainee progress per module
- Scores, attempts, best score, average score
- Pass/fail status and dates

**training_sessions**
- Individual training session records
- Start/end times
- Final scores

**training_actions**
- Detailed action logs (quiz answers, simulations)
- Timestamp tracking

**inbox_messages**
- Phishing email templates
- Legitimate email samples
- Message content and metadata

**inbox_assignments**
- Phishing simulation assignments to trainees
- Delivery tracking

**inbox_actions**
- Trainee responses to phishing emails
- Click/report/ignore tracking

**contact_requests**
- Demo request form submissions
- Requested modules
- Lead tracking

---

## 🎓 How It Works

### For Trainees

1. **Request Demo** (Homepage)
   - Fill out demo form
   - Select desired training modules
   - Receive login credentials

2. **Register for Modules** (Dashboard)
   - View only modules requested during demo
   - Select modules to start training
   - Modules appear in learning path order

3. **Complete Training**
   - Start with first module
   - Complete lessons and quizzes
   - Earn XP and maintain streaks
   - Pass module (70%+) to unlock next

4. **Track Progress**
   - View completed modules
   - Check scores and attempts
   - See mastery level
   - Download certificates

5. **Phishing Simulations**
   - Receive simulated phishing emails
   - Click, report, or ignore
   - Get immediate feedback
   - Track improvement over time

### For Admins

1. **User Management**
   - Create/edit/delete users
   - Assign departments
   - Manage roles and permissions

2. **Module Management**
   - Create and edit training modules
   - Assign modules to departments
   - Track module completion rates

3. **Reporting**
   - View compliance dashboard
   - Export compliance reports (CSV)
   - Department-level analytics
   - Individual employee tracking

4. **Campaigns**
   - Create phishing campaigns
   - Assign to departments
   - Track results and metrics

---

## 👥 User Roles & Portals

CyberAware has a multi-role access system with separate dashboards for each user type:

### **Trainee Portal** 🎓
- **Role:** `trainee`
- **Purpose:** Complete cybersecurity training modules
- **Access:** Training modules, progress tracking, certificates, phishing simulations
- **URL:** `http://localhost/cyberaware-new-Edits/trainee/dashboard.php`
- **Features:**
  - Interactive training modules with gamification
  - XP, streaks, lives, and leaderboards
  - Progress tracking and score visualization
  - Certificate generation upon completion
  - Phishing email simulations

### **Manager Portal** 👔
- **Role:** `manager`
- **Purpose:** Monitor team training progress and performance
- **Access:** Department-level reporting, team member tracking, incident overview
- **URL:** `http://localhost/cyberaware-new-Edits/manager/dashboard.php`
- **Features:**
  - Team member performance overview
  - Department completion rates
  - At-risk employee identification
  - Training progress metrics
  - Activity history

### **Admin Portal** 🛡️
- **Role:** `admin`
- **Purpose:** Manage organization-wide training and security operations
- **Access:** User management, module assignment, campaigns, compliance reporting
- **URL:** `http://localhost/cyberaware-new-Edits/admin/dashboard.php`
- **Features:**
  - User and department management
  - Training campaign creation and monitoring
  - Phishing simulation management
  - Compliance snapshot and audit reports
  - CSV export for compliance audits
  - Incident reporting and tracking
  - Risk heatmap visualization

### **Super Admin Portal** 👑
- **Role:** `superadmin`
- **Purpose:** Platform administration and organizational management
- **Access:** Subscription management, content updates, multi-organization reporting
- **URL:** `http://localhost/cyberaware-new-Edits/superadmin/dashboard.php`
- **Organization:** Intellectual Technology CC (Exclusive)
- **Features:**
  - Organization and subscription management
  - Platform-wide analytics and KPIs
  - Training content management
  - Revenue tracking
  - Subscription lifecycle management
  - Top-performing module tracking
  - Multi-organization oversight

### **Login Flow with Role-Based Redirect**

When a user logs in:
1. PHP sessions maintain login state (`session_start()`)
2. User role is stored in `$_SESSION['role']`
3. System checks role and redirects to appropriate dashboard:

```
Login Success
    ↓
superadmin → /superadmin/dashboard.php
manager → /manager/dashboard.php
admin → /admin/dashboard.php
trainee → /trainee/dashboard.php
```

See `config/database.php` (lines 29-31) and `index.php` for session initialization and redirect logic.

---

## 🔐 Access Control System

The system uses PHP sessions to maintain login state and redirects users based on their role:

**Session Management** (`config/database.php`):
```php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check login status
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Helper function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && strtolower($_SESSION['role']) === strtolower($role);
}
```

**Access Control** (`index.php`):
```php
if (isLoggedIn()) {
    if (hasRole('superadmin')) {
        header('Location: superadmin/dashboard.php');
    } elseif (hasRole('manager')) {
        header('Location: manager/dashboard.php');
    } elseif (hasRole('admin')) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: trainee/dashboard.php');
    }
    exit();
}
```

Each portal verifies user role on page load and denies access if unauthorized.

---

### Module Registration
- Trainees can only register for modules they requested during demo signup
- Unregistered modules remain permanently locked

### Sequential Unlocking
- First registered module is immediately accessible
- Subsequent modules unlock only after passing previous module (70%+)
- Locked modules show lock icon and reason

### Module Access
- Direct URL access to modules is blocked
- Access control checks on every module page
- Unauthorized access redirects to dashboard

---

## 📊 Gamification Mechanics

### XP System
- 140 XP per completed module
- 4 XP per percentage point of score
- Example: 85% score = 140 + (85 × 4) = 480 XP

### Levels
- Level increases every 2 modules completed
- Visual level indicator on dashboard
- Progress bar showing XP to next level

### Streaks
- Maintained by completing at least one lesson per day
- Displayed prominently on dashboard
- Visual streak counter with fire emoji

### Lives
- 5 lives per day
- Lose 1 life on incorrect quiz answer
- Lives reset daily at midnight

---

## 🛠️ Configuration

### Database Connection (`config/database.php`)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cyberaware');
```

### Email Configuration
- Contact form emails sent to: `info@intellectualtechnology.com.na`
- Update in `contact.php` to change recipient

### Module IDs
- 1: Phishing Emails
- 2: Credential Harvesting
- 3: Social Engineering
- 4: Malware & Attachments
- 5: Website & Link Safety
- 6: Password Security
- 7: Ransomware Awareness

---

## 🧪 Testing

### Test Scenarios

**Scenario 1: Module Registration**
1. Go to homepage → Request Demo
2. Select specific modules (e.g., Phishing & Password)
3. Login as trainee
4. Go to "Register for Training Modules"
5. ✅ Only selected modules appear

**Scenario 2: Sequential Unlocking**
1. Register for Phishing and Password modules
2. Complete Phishing with 70%+ score
3. Try accessing Password module
4. ✅ Module now accessible

**Scenario 3: Access Control**
1. Try accessing module directly via URL without registration
2. ✅ Redirected to dashboard with error

**Scenario 4: Admin Reporting**
1. Login as admin
2. Go to Dashboard → Compliance Export
3. ✅ CSV downloads with trainee data

---

## 📱 Responsive Design

- ✅ Desktop (1920px+)
- ✅ Tablet (768px - 1024px)
- ✅ Mobile (320px - 767px)
- ✅ Touch-friendly interface
- ✅ Optimized navigation

---

## 🚀 Deployment

### Production Checklist
- [ ] Update database credentials in `config/database.php`
- [ ] Set up SSL certificate (HTTPS)
- [ ] Configure email service for notifications
- [ ] Update contact form recipient email
- [ ] Set up automated backups
- [ ] Configure error logging
- [ ] Test all user flows
- [ ] Load test with expected user count

### Recommended Hosting
- PHP 7.4+ support
- MySQL 5.7+ support
- 2GB+ RAM
- 10GB+ storage
- SSL certificate included

---

## 🐛 Troubleshooting

### Database Connection Error
- Check `config/database.php` credentials
- Verify MySQL is running
- Ensure `cyberaware` database exists

### Module Not Showing
- Verify module is marked as `is_active = 1` in database
- Check user registration in `user_module_registrations`
- Verify module ID matches in module file

### Login Issues
- Clear browser cookies
- Check user `is_active` status in database
- Verify password hash is correct

### Email Not Sending
- Check PHP mail configuration
- Verify email address is valid
- Check server error logs

---

## 📞 Support & Contact

**For Demo Requests:**
- Email: info@intellectualtechnology.com.na
- Phone: +264 81 870 6257
- Location: Windhoek, Namibia

**For Technical Issues:**
- Check troubleshooting section above
- Review error logs in server
- Contact development team

---

## 📄 License

CyberAware © 2026 Intellectual Technology cc. All rights reserved.

Built for organisations in Namibia 🇳🇦

---

## 🎯 Roadmap

### Completed ✅
- Core training modules (7)
- Gamification system
- Module registration & unlocking
- Phishing simulations
- Admin dashboard
- Compliance reporting
- Certificate generation

### In Progress 🔄
- Leaderboard system
- Advanced analytics
- Mobile app
- API integration

### Planned 📋
- Multi-language support
- Custom module builder
- Integration with HRIS systems
- Advanced threat simulations
- Video content library

---

## 👥 Team

**Developed by:** Intellectual Technology cc  
**For:** CyberAware Platform  
**Region:** Namibia, Africa

---

## 📝 Version History

**v1.0.0** (April 2026)
- Initial release
- 7 training modules
- Gamification system
- Admin dashboard
- Phishing simulations
- Compliance reporting

---

**Last Updated:** April 21, 2026  
**Status:** Production Ready ✅
