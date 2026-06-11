# EDUMS Feature Implementation Plan

This document outlines the implementation plan for adding key features to the EDUMS CodeIgniter 3 project. Each feature includes specific tasks, file modifications, and verification steps.

## Current Project Status
- Branch: `fix/TASK-17850-email-fix` (Email OTP fixes)
- Framework: CodeIgniter 3
- Authentication: Basic login + Two-factor OTP via email
- Database: MySQL with users and user_otp tables

---

## Feature 1: Password Reset Implementation

### Description
Add secure password reset functionality with email token delivery and validation.

### Tasks Breakdown

#### 1.1 Database Migration
**File:** `application/migrations/20250611000001_add_password_reset_fields.php`
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_password_reset_fields extends CI_Migration {
    public function up() {
        $this->dbforge->add_column('users', [
            'password_reset_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE
            ],
            'password_reset_expires' => [
                'type' => 'DATETIME',
                'null' => TRUE
            ],
            'password_reset_requested_at' => [
                'type' => 'DATETIME',
                'null' => TRUE
            ]
        ]);
        
        $this->dbforge->add_column('users', [
            'CONSTRAINT unique_email UNIQUE (email)'
        ]);
    }
    
    public function down() {
        $this->dbforge->drop_column('users', 'password_reset_token');
        $this->dbforge->drop_column('users', 'password_reset_expires');
        $this->dbforge->drop_column('users', 'password_reset_requested_at');
    }
}
```

#### 1.2 Controller Implementation
**File:** `application/controllers/Password_reset.php`
- `forgot_password()` - Show form, validate email, send token
- `reset_password($token)` - Validate token, show reset form
- `update_password()` - Update password with validation

#### 1.3 Model Methods
**File:** `application/models/User_model.php`
- `get_by_email($email)` - Retrieve user by email
- `set_reset_token($user_id, $token)` - Store reset token
- `get_user_by_reset_token($token)` - Validate token
- `clear_reset_token($user_id)` - Clean up after reset
- `update_password($user_id, $password)` - Update password

#### 1.4 Views
**Files:**
- `application/views/forgot_password_view.php` - Email request form
- `application/views/reset_password_view.php` - New password form
- `application/views/password_reset_success_view.php` - Confirmation page

#### 1.5 Email Template
**File:** `application/views/emails/password_reset_template.php`
HTML template with reset link and expiration info

#### 1.6 Route Configuration
**File:** `application/config/routes.php`
```php
$route['password/forgot'] = 'password_reset/forgot_password';
$route['password/reset/(:any)'] = 'password_reset/reset_password/$1';
$route['password/update'] = 'password_reset/update_password';
```

### Security Considerations
- Token: 32-byte random string via `bin2hex(random_bytes(16))`
- Expiration: 1 hour from request
- Rate limit: 1 request per 15 minutes per email
- Token invalidation after use or password change

### Verification Steps
1. Run migration: `php index.php migration latest`
2. Test forgot password flow with valid email
3. Verify token expiration
4. Test password update with valid/invalid tokens
5. Confirm email delivery and template rendering

---

## Feature 2: CSRF Protection Enhancement

### Description
Ensure all forms have proper CSRF token protection following CI3 best practices.

### Tasks Breakdown

#### 2.1 Update Login Form
**File:** `application/views/login_view.php`
- Remove manual form tags
- Use `form_open()` helper to auto-include CSRF
- Ensure POST method is set

#### 2.2 Update Registration Form
**File:** `application/views/register_view.php`
- Remove manual form tags
- Use `form_open()` helper to auto-include CSRF
- Ensure POST method is set

#### 2.3 Standardize OTP Form
**File:** `application/views/verify_otp_view.php`
- Replace manual CSRF injection with form helper
- Maintain current functionality

#### 2.4 Security Configuration Review
**File:** `application/config/config.php`
- Verify CSRF settings are optimal
- Consider adjusting token expiration
- Ensure regeneration is enabled

### Security Benefits
- Prevents CSRF attacks on all authentication forms
- Consistent protection across the application
- Automatic token management by CI3

### Verification Steps
1. Check page source for CSRF tokens in all forms
2. Submit forms without tokens (should fail)
3. Verify token regeneration on each request
4. Test with expired tokens

---

## Feature 3: Account Lockout Mechanism

### Description
Implement account lockout after multiple failed login attempts with secure unlock process.

### Tasks Breakdown

#### 3.1 Database Migration
**File:** `application/migrations/20250611000002_add_account_lockout_fields.php`
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_account_lockout_fields extends CI_Migration {
    public function up() {
        $this->dbforge->add_column('users', [
            'login_attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ],
            'locked_until' => [
                'type' => 'DATETIME',
                'null' => TRUE
            ],
            'status' => [
                'type' => "ENUM('active', 'locked', 'suspended')",
                'default' => 'active'
            ]
        ]);
        
        $this->dbforge->add_column('users', [
            'last_login_attempt' => [
                'type' => 'DATETIME',
                'null' => TRUE
            ]
        ]);
    }
    
    public function down() {
        $this->dbforge->drop_column('users', 'login_attempts');
        $this->dbforge->drop_column('users', 'locked_until');
        $this->dbforge->drop_column('users', 'status');
        $this->dbforge->drop_column('users', 'last_login_attempt');
    }
}
```

#### 3.2 Update Login Logic
**File:** `application/controllers/User.php`
- Track failed attempts in `login()`
- Lock account after threshold (5 attempts)
- Show lockout message with duration
- Reset counter on successful login

#### 3.3 Model Methods
**File:** `application/models/User_model.php`
- `increment_login_attempts($user_id)` - Track failed attempts
- `lock_account($user_id, $duration)` - Lock account
- `is_account_locked($user_id)` - Check lock status
- `reset_login_attempts($user_id)` - Clear counter

#### 3.4 Admin Unlock Interface
**File:** `application/controllers/Admin.php`
- `unlock_account($user_id)` - Admin unlock function
- `view_locked_accounts()` - List locked accounts

#### 3.5 Notification System
**Email notifications** for:
- Account locked (to user)
- Admin notification of lockouts

### Security Parameters
- Max attempts: 5
- Lock duration: 30 minutes
- Counter reset: After successful login or admin unlock
- Attempt tracking: Per user, per IP

### Verification Steps
1. Test lockout after 5 failed attempts
2. Verify lock duration timer
3. Test unlock after expiration
4. Test admin unlock functionality
5. Verify email notifications

---

## Feature 4: Admin Dashboard

### Description
Create an admin dashboard for user management, system monitoring, and security oversight.

### Tasks Breakdown

#### 4.1 Admin Authentication
**File:** `application/controllers/Admin.php`
- Check admin role in constructor
- Secure all admin methods
- Role-based access control

#### 4.2 Dashboard Overview
**File:** `application/views/admin/dashboard_view.php`
Metrics display:
- Total users count
- Active sessions
- Failed login attempts (24h)
- OTP usage statistics
- Locked accounts

#### 4.3 User Management
**Files:**
- `application/views/admin/users_list.php` - Paginated user list
- `application/views/admin/user_edit.php` - Edit user details
- `application/views/admin/user_create.php` - Create new user

#### 4.4 Security Monitoring
**Files:**
- `application/views/admin/login_attempts.php` - Failed attempts log
- `application/views/admin/otp_logs.php` - OTP usage tracking
- `application/views/admin/locked_accounts.php` - Account lockouts

#### 4.5 Model Enhancements
**File:** `application/models/Admin_model.php`
- `get_user_statistics()` - Dashboard metrics
- `get_login_attempts($limit)` - Security logs
- `get_otp_statistics()` - OTP usage data
- `search_users($query)` - User search

#### 4.6 Access Control
**Database Migration:** Add role field to users table
```php
'role' => [
    'type' => "ENUM('user', 'admin')",
    'default' => 'user'
]
```

### Security Features
- Session-based admin authentication
- IP whitelist capability
- Action logging for audit trail
- Secure file uploads for avatars

### Verification Steps
1. Verify admin-only access restrictions
2. Test user CRUD operations
3. Verify dashboard data accuracy
4. Test search and pagination
5. Confirm security log accuracy

---

## Implementation Priority & Dependencies

### Phase 1 (Critical Security)
1. **CSRF Protection Enhancement** - Immediate security fix
2. **Account Lockout Mechanism** - Prevent brute force attacks

### Phase 2 (User Experience)
3. **Password Reset Implementation** - Common user need

### Phase 3 (Administration)
4. **Admin Dashboard** - Management and monitoring

### Dependencies
- All features require database migrations
- Admin dashboard needs account lockout data
- Password reset needs email configuration validated

---

## Testing Strategy

### Unit Testing
- Model method testing
- Validation rule testing
- Token generation/validation

### Integration Testing
- Complete authentication flows
- Email delivery verification
- Session security testing

### Security Testing
- CSRF token validation
- SQL injection prevention
- XSS protection verification
- Session hijacking prevention

### Performance Testing
- Database query optimization
- Email queue performance
- Dashboard load times

---

## EDUMS Compliance Checklist

### Code Quality
- [ ] Follow CI3 naming conventions
- [ ] Use proper indentation (tabs)
- [ ] Include security headers in all files
- [ ] Escape all output with `html_escape()`

### Security Standards
- [ ] CSRF protection on all forms
- [ ] Input validation and sanitization
- [ ] Secure password handling
- [ ] Proper session configuration
- [ ] Database query protection

### GitLab Workflow
- [ ] Create feature branches: `feat/TASK-XXXXX-description`
- [ ] Atomic commits with proper messages
- [ ] Include CRM task references
- [ ] Create MRs for code review

### Documentation
- [ ] Update AGENTS.md with new features
- [ ] Document API endpoints
- [ ] Create user guides for new features

---

## Next Steps

1. **Start with CSRF Protection** (Task #2)
   - Quick win, immediate security improvement
   - No database changes required
   - Can be done in current branch or new feature branch

2. **Create Feature Branches**
   ```
   git checkout develop
   git pull origin develop
   git checkout -b feat/TASK-XXXXX-password-reset
   ```

3. **Implement Features Sequentially**
   - Complete each feature fully before starting next
   - Test thoroughly before creating MR
   - Update documentation as needed

4. **Code Review Process**
   - Self-review using security checklist
   - Peer review through GitLab MR
   - Merge to develop after approval

---

## Notes & Considerations

### Email Configuration
- Verify SMTP settings before password reset
- Consider email queue for bulk operations
- Test with various email providers

### Session Security
- Consider implementing session IP matching
- Enable secure cookies for HTTPS
- Implement session expiration policies

### Database Performance
- Add indexes for frequently queried fields
- Consider soft delete implementation
- Plan for data archiving strategy

### Future Enhancements
- Time-based OTP (TOTP) support
- LDAP/Active Directory integration
- Multi-tenant architecture preparation
- API rate limiting implementation