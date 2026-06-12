# CyberAware Portal Summary

## 🎯 System Overview

CyberAware has **4 separate portals** with role-based access control, each tailored to specific user types and organizational levels.

---

## 📊 Portal Comparison

| Feature | Trainee | Manager | Admin | Super Admin |
|---------|---------|---------|-------|------------|
| **Primary Role** | 🎓 Learner | 👔 Team Lead | 🛡️ Organization | 👑 Platform |
| **Access Level** | Low | Medium | High | Highest |
| **Dashboard** | ✅ | ✅ | ✅ | ✅ |
| **View Training** | ✅ | ✅ Dept | ✅ Org | ✅ Platform |
| **Manage Users** | ❌ | Limited | ✅ | ✅ |
| **Manage Content** | ❌ | ❌ | ✅ | ✅ |
| **Manage Subscriptions** | ❌ | ❌ | ❌ | ✅ |
| **Manage Organizations** | ❌ | ❌ | ❌ | ✅ |
| **View Reports** | Personal | Department | Organization | Platform |
| **Export Data** | ❌ | ❌ | ✅ CSV | ✅ Advanced |

---

## 🏗️ Portal Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    CyberAware Platform                       │
│                   (index.php → login.php)                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ├─ Role: superadmin
                     │  └─ /superadmin/dashboard.php ─────┐
                     │     ├─ Platform Analytics           │
                     │     ├─ Organization Management      │
                     │     ├─ Subscription Management      │
                     │     └─ Training Content Mgmt        │
                     │
                     ├─ Role: admin
                     │  └─ /admin/dashboard.php ─────────┐
                     │     ├─ User Management             │
                     │     ├─ Campaign Management         │
                     │     ├─ Compliance Reports          │
                     │     └─ Security Operations         │
                     │
                     ├─ Role: manager
                     │  └─ /manager/dashboard.php ───────┐
                     │     ├─ Team Performance            │
                     │     ├─ Department Analytics        │
                     │     ├─ Team Member Tracking        │
                     │     └─ Risk Assessment             │
                     │
                     └─ Role: trainee
                        └─ /trainee/dashboard.php ────────┐
                           ├─ Training Modules            │
                           ├─ Progress Tracking           │
                           ├─ Phishing Simulations        │
                           └─ Certificates               │
```

---

## 🌐 Portal Details

### 1️⃣ **TRAINEE PORTAL** 🎓
**URL:** `http://localhost/cyberaware-new-Edits/trainee/dashboard.php`

**Purpose:** Complete gamified cybersecurity training

**Key Features:**
- 7 interactive training modules
- Gamification (XP, streaks, lives, badges)
- Phishing email simulations
- Real-time progress tracking
- Certificate generation
- Personal performance dashboard

**User Journey:**
1. Login with trainee credentials
2. View available modules (only registered modules)
3. Complete module lessons and quizzes
4. Earn XP and maintain streaks
5. Unlock certificates
6. Track improvement over time

**Navigation:**
```
Trainee Dashboard
├─ My Modules
├─ Phishing Inbox
├─ My Progress
├─ Certificates
├─ Leaderboard
├─ Profile
└─ Logout
```

---

### 2️⃣ **MANAGER PORTAL** 👔
**URL:** `http://localhost/cyberaware-new-Edits/manager/dashboard.php`

**Purpose:** Monitor department team training progress

**Key Features:**
- Team member performance overview
- Department completion rates
- At-risk employee identification
- Incident tracking
- Activity history
- Progress reports
- Performance metrics

**User Journey:**
1. Login with manager credentials
2. View team member overview
3. Check completion rates
4. Identify at-risk employees
5. Track incidents
6. Export team reports (coming soon)

**Navigation:**
```
Manager Dashboard
├─ Team Members
├─ Performance Overview
├─ Incidents
├─ Reports
├─ Settings
└─ Logout
```

---

### 3️⃣ **ADMIN PORTAL** 🛡️
**URL:** `http://localhost/cyberaware-new-Edits/admin/dashboard.php`

**Purpose:** Manage organization-wide training and security

**Key Features:**
- User and department management
- Training campaign creation
- Phishing simulation management
- Compliance snapshot and reports
- CSV export for audits
- Incident reporting
- Risk heatmap
- Module management
- Department scoring

**User Journey:**
1. Login with admin credentials
2. View security operations dashboard
3. Manage users and departments
4. Create training campaigns
5. Launch phishing simulations
6. Monitor compliance metrics
7. Generate compliance reports
8. Export data for audits

**Navigation:**
```
Admin Dashboard
├─ Dashboard
├─ Users (Manage, Add, Delete)
├─ Departments
├─ Campaigns
├─ Phishing Inbox
├─ Modules
├─ Reports
├─ Incidents
├─ Compliance Snapshot
├─ Export Report
├─ Department Scores
├─ Risk Heatmap
├─ Notifications
└─ Logout
```

---

### 4️⃣ **SUPER ADMIN PORTAL** 👑 ⭐
**URL:** `http://localhost/cyberaware-new-Edits/superadmin/dashboard.php`

**Purpose:** Platform administration & organizational management

**Organization:** Intellectual Technology CC (Exclusive)

**Key Features:**
- Platform-wide analytics and KPIs
- Organization management
- Subscription lifecycle management
- Training content management
- Revenue tracking
- Multi-organization oversight
- Top-performing module analytics
- System health monitoring

**User Journey:**
1. Login with superadmin credentials (Intellectual Technology CC)
2. View platform-wide dashboard
3. Manage client organizations
4. Track subscriptions and renewals
5. Monitor training content
6. Analyze platform metrics
7. Generate advanced reports
8. Manage system settings

**Navigation:**
```
Super Admin Dashboard
├─ Dashboard (Platform Overview)
├─ Organizations (View, Add, Edit)
├─ Subscriptions (Management & Renewals)
├─ Training Content (Modules & Materials)
├─ Campaigns (Platform-wide)
├─ Analytics (Advanced Reporting)
├─ Audit Logs
├─ Settings
└─ Logout
```

**Dashboard Widgets:**
- Total Organizations
- Active Subscriptions
- Total Users
- Training Modules
- Revenue Tracking
- Completion Rate
- Top Performing Modules
- Recent Organizations
- Subscription Status
- Annual Revenue

---

## 🔐 Session & Role-Based Access

### Session Management Flow

```
User Login (pages/login.php)
        ↓
Validate Credentials
        ↓
Create Session: $_SESSION['user_id'] = <id>
Create Session: $_SESSION['role'] = <role>
        ↓
Redirect to index.php
        ↓
index.php checks $_SESSION['role'] and redirects:
├─ 'superadmin' → /superadmin/dashboard.php
├─ 'admin' → /admin/dashboard.php
├─ 'manager' → /manager/dashboard.php
└─ 'trainee' → /trainee/dashboard.php
```

### Access Control Implementation

**Every portal page starts with:**
```php
// Check if logged in
if (!isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

// Check if correct role
if ($_SESSION['role'] !== 'required_role') {
    header('Location: ../pages/access-denied.php');
    exit;
}
```

This ensures:
- ✅ Users cannot access portals without logging in
- ✅ Users can only access their designated portal
- ✅ Session variables maintain state across pages
- ✅ Logout properly destroys session

---

## 📁 File Structure

```
/superadmin/
├─ dashboard.php           # Main dashboard
├─ organizations.php       # Org management
├─ subscriptions.php       # Sub management
├─ training-content.php    # Content management
└─ README.md              # Documentation

/admin/
├─ dashboard.php
├─ manage-users.php
├─ manage_modules.php
├─ campaigns.php
├─ reports.php
└─ ... (20+ files)

/manager/
├─ dashboard.php
├─ profile.php
└─ settings.php

/trainee/
├─ dashboard.php
├─ register-modules.php
├─ progress.php
├─ phish-inbox.php
├─ certificate.php
├─ modules/
│  ├─ phishing.php
│  ├─ credentials.php
│  └─ ... (5 more)
└─ trainee-sidebar.php
```

---

## 🔗 Cross-Portal Linking

Portals are linked through login/logout flows:

```
Public Site (index.php)
        ↓
Login (pages/login.php)
        ↓
Session Created
        ↓
Redirects to appropriate portal based on role
        ↓
Portal Dashboard (role-specific)
        ↓
Sidebar Navigation (within portal)
        ↓
Logout (logout.php) → Back to login
```

**Important:** Direct URL access to portals is blocked. Users must login first.

---

## 🎯 Key Features by Portal

### Trainee Features
- ✅ Training modules (7 total)
- ✅ Gamification system
- ✅ Progress tracking
- ✅ Certificate generation
- ✅ Phishing simulations
- ✅ Performance analytics

### Manager Features
- ✅ Team oversight
- ✅ Department reports
- ✅ At-risk identification
- ✅ Performance metrics
- ✅ Incident monitoring
- ✅ Team analytics

### Admin Features
- ✅ User management
- ✅ Campaign management
- ✅ Compliance reporting
- ✅ Module management
- ✅ Phishing simulations
- ✅ CSV export
- ✅ Incident reporting
- ✅ Risk assessment

### Super Admin Features
- ✅ Organization management
- ✅ Subscription tracking
- ✅ Content management
- ✅ Platform analytics
- ✅ Revenue tracking
- ✅ Multi-org oversight
- ✅ Advanced reporting

---

## 🚀 Deployment Checklist

- [ ] Super Admin account created (Intellectual Technology CC)
- [ ] Test login for all 4 roles
- [ ] Verify role-based redirect works correctly
- [ ] Check session security (HTTPS)
- [ ] Test logout functionality
- [ ] Verify access control (try accessing wrong portal)
- [ ] Test on different browsers
- [ ] Test responsive design (mobile, tablet)
- [ ] Verify all links work
- [ ] Check database connectivity
- [ ] Test CSV exports
- [ ] Verify email notifications

---

## 📝 Test Workflow

### Step 1: Trainee Portal
1. Login: `trainee` / `Uncle@foddy1`
2. Should see: Dashboard → Training modules
3. Complete a module
4. Check progress

### Step 2: Manager Portal
1. Login: `manager` / `Uncle@foddy1`
2. Should see: Team overview
3. Check team member performance

### Step 3: Admin Portal
1. Login: `admin` / `Uncle@foddy1`
2. Should see: Full admin dashboard
3. Try managing users
4. Create a campaign

### Step 4: Super Admin Portal
1. Login: `superadmin` / `Uncle@foddy1`
2. Should see: Platform dashboard
3. View organizations
4. Check subscriptions

---

## ⚠️ Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Wrong portal after login | Clear cookies, logout, login again |
| Cannot access portal | Check role in database (users table) |
| Session expired | Login again, may need to extend timeout |
| Permission denied | Wrong role, check $_SESSION['role'] |
| Links not working | Check file paths, ensure database connection |

---

## 📞 Support

**For Portal Issues:**
- Check access control in `config/database.php`
- Verify session is started on each page
- Check user role in database
- Review error logs in server

**For Super Admin Access:**
- Contact: Intellectual Technology CC
- Verify user role is 'superadmin' in database
- Check SSL certificate (production)

---

**Last Updated:** April 21, 2026  
**Status:** ✅ Production Ready

Created for CyberAware Platform v1.0.0
