# Super Admin Dashboard

## Overview
The Super Admin portal is the highest level of access in CyberAware, exclusively for **Intellectual Technology CC** to manage organizational subscriptions and training content across the entire platform.

## Key Features

### 1. **Dashboard** (`dashboard.php`)
- Platform-wide metrics and KPIs
- Active organizations and subscriptions count
- Total users across all organizations
- Training completion rates
- Revenue tracking
- Top performing training modules
- Recent organizations overview

### 2. **Organizations Management** (`organizations.php`)
- View all client organizations
- Add new organizations
- Track users per organization
- Monitor subscription status
- Organization contact information

### 3. **Subscriptions Management** (`subscriptions.php`)
- View all active and expired subscriptions
- Track subscription status (Active, Expired, Pending)
- Monitor subscription revenue
- View subscription dates and plan details
- Cost tracking per user

### 4. **Training Content** (`training-content.php`)
- Manage all training modules
- View module usage statistics
- Track average scores per module
- Monitor module performance
- Add/edit training modules
- Module activation/deactivation

## Role & Permissions

**Super Admin Role:**
- `role = 'superadmin'` in users table
- Full platform access
- Can view all organizations and their data
- Can manage subscriptions and renewals
- Can update training content for all organizations
- Cannot directly manage individual users (delegated to Organization Admins)

## Access Control

Super Admin URLs are protected with role-based authentication:
```php
if (!isLoggedIn() || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../pages/access-denied.php');
    exit;
}
```

## Dashboard Navigation Flow

1. **Home** → `index.php` (Redirects to `superadmin/dashboard.php` if logged in as superadmin)
2. **Dashboard** → Overview of platform metrics
3. **Organizations** → Manage all client organizations
4. **Subscriptions** → Monitor and manage subscriptions
5. **Training Content** → Manage modules and content
6. **Logout** → `../logout.php`

## Database Tables Used

- `users` - Super admin user information
- `organizations` - Client organizations
- `subscriptions` - Subscription management
- `training_modules` - Available training content
- `training_sessions` - User training activity
- `users` - User information across organizations

## File Structure

```
superadmin/
├── dashboard.php              # Main dashboard
├── organizations.php          # Organization management
├── subscriptions.php         # Subscription management
├── training-content.php      # Training content management
├── superadmin-sidebar.php    # Sidebar navigation
└── README.md                 # This file
```

## Features Available

### Dashboard Metrics
- ✅ Total Organizations
- ✅ Active Subscriptions
- ✅ Total Users
- ✅ Training Modules
- ✅ Revenue Tracking
- ✅ Completion Rate
- ✅ Top Modules
- ✅ Recent Organizations

### Organizations
- ✅ View all organizations
- ✅ Add new organization
- ✅ Track user count per organization
- ✅ View subscription status
- ✅ Edit organization details (planned)

### Subscriptions
- ✅ View all subscriptions
- ✅ Track active vs expired
- ✅ Monitor revenue
- ✅ View subscription details
- ✅ Renewal management (planned)

### Training Content
- ✅ View all modules
- ✅ Track usage statistics
- ✅ View average scores
- ✅ Monitor performance
- ✅ Add/Edit modules (planned)
- ✅ Activate/Deactivate (planned)

## Future Enhancements

- [ ] Subscription renewal automation
- [ ] Module versioning and rollback
- [ ] Advanced analytics and reporting
- [ ] Bulk user imports
- [ ] Custom reporting templates
- [ ] Integration with billing systems
- [ ] API access for partners
- [ ] Role-based sub-admins

## Security Notes

1. Super Admin session is validated on every page
2. Role verification uses both `$_SESSION['role']` and database checks
3. All user inputs are sanitized using `htmlspecialchars()`
4. Database queries use prepared statements to prevent SQL injection
5. Access denied errors redirect to `access-denied.php`

## Usage

### Accessing the Super Admin Portal
1. Login with a Super Admin account
2. You will be redirected to `superadmin/dashboard.php`
3. Use the sidebar navigation to access different sections

### Viewing Organizations
- Go to **Organizations** section
- View all client organizations with their details
- Click **View** to see subscription details

### Managing Subscriptions
- Go to **Subscriptions** section
- View active, pending, and expired subscriptions
- Monitor annual revenue

### Managing Training Content
- Go to **Training Content** section
- View all available training modules
- Track usage and performance metrics

## Support

For issues or questions regarding the Super Admin portal, contact Intellectual Technology CC or refer to the main README.md file.
