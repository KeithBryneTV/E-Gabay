# E-GABAY ASC - COMPLETE SYSTEM DOCUMENTATION & RECREATION GUIDE

## 📋 OVERVIEW

**E-GABAY ASC** (Academic Support and Counseling System) ay isang komprehensibong web-based platform para sa counseling at academic support sa mga educational institutions. Ang system ay nag-facilitate ng secure communication between students, counselors, at administrative staff.

---

## 🗄️ DATABASE STRUCTURE & TABLE RELATIONSHIPS

### 📊 CORE DATABASE TABLES

#### 1. **ROLES TABLE**
```sql
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT '1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```
**Purpose**: Defines user roles (student, counselor, admin, staff)

#### 2. **USERS TABLE** (Central table)
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);
```
**Purpose**: Main user accounts and authentication

#### 3. **STUDENT_PROFILES TABLE**
```sql
CREATE TABLE student_profiles (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    course VARCHAR(100),
    year_level VARCHAR(20),
    section VARCHAR(50),
    contact_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```
**Purpose**: Extended student information

#### 4. **COUNSELOR_PROFILES TABLE**
```sql
CREATE TABLE counselor_profiles (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    availability TEXT,
    contact_number VARCHAR(20),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```
**Purpose**: Extended counselor information

#### 5. **CONSULTATION_REQUESTS TABLE**
```sql
CREATE TABLE consultation_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    counselor_id INT(11) NULL,
    issue_description TEXT NOT NULL,
    issue_category VARCHAR(100) NULL,
    preferred_date DATE NOT NULL,
    preferred_time TIME NOT NULL,
    communication_method ENUM('in_person', 'phone', 'video', 'email', 'chat', 'voice') NOT NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending', 'live', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    counselor_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (counselor_id) REFERENCES users(user_id)
);
```
**Purpose**: Consultation requests and management

#### 6. **CHAT_SESSIONS TABLE**
```sql
CREATE TABLE chat_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    counselor_id INT(11) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('active', 'closed') NOT NULL DEFAULT 'active',
    consultation_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (counselor_id) REFERENCES users(user_id),
    FOREIGN KEY (consultation_id) REFERENCES consultation_requests(id)
);
```
**Purpose**: Chat session management

#### 7. **CHAT_MESSAGES TABLE**
```sql
CREATE TABLE chat_messages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    chat_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (chat_id) REFERENCES chat_sessions(id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```
**Purpose**: Individual chat messages

#### 8. **FEEDBACK TABLE**
```sql
CREATE TABLE feedback (
    id INT(11) NOT NULL AUTO_INCREMENT,
    consultation_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    rating INT(11) NOT NULL,
    comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (consultation_id) REFERENCES consultation_requests(id),
    FOREIGN KEY (student_id) REFERENCES users(user_id)
);
```
**Purpose**: Student feedback for consultations

#### 9. **NOTIFICATIONS TABLE**
```sql
CREATE TABLE notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL, 
    reference_id INT(11) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```
**Purpose**: System notifications

#### 10. **SYSTEM_LOGS TABLE**
```sql
CREATE TABLE system_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```
**Purpose**: Activity logging and audit trail

#### 11. **SETTINGS TABLE**
```sql
CREATE TABLE settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    value TEXT NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'General',
    is_active TINYINT(1) NOT NULL DEFAULT '1',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY setting_key (setting_key)
);
```
**Purpose**: System configuration

---

## 🔗 TABLE RELATIONSHIPS & CONNECTIVITY

### **PRIMARY RELATIONSHIPS:**

1. **USERS** → **ROLES** (Many-to-One)
   - `users.role_id` → `roles.role_id`

2. **STUDENT_PROFILES** → **USERS** (One-to-One)
   - `student_profiles.user_id` → `users.user_id`

3. **COUNSELOR_PROFILES** → **USERS** (One-to-One)
   - `counselor_profiles.user_id` → `users.user_id`

4. **CONSULTATION_REQUESTS** → **USERS** (Many-to-One)
   - `consultation_requests.student_id` → `users.user_id`
   - `consultation_requests.counselor_id` → `users.user_id`

5. **CHAT_SESSIONS** → **USERS** & **CONSULTATION_REQUESTS** (Many-to-One)
   - `chat_sessions.student_id` → `users.user_id`
   - `chat_sessions.counselor_id` → `users.user_id`
   - `chat_sessions.consultation_id` → `consultation_requests.id`

6. **CHAT_MESSAGES** → **CHAT_SESSIONS** & **USERS** (Many-to-One)
   - `chat_messages.chat_id` → `chat_sessions.id`
   - `chat_messages.user_id` → `users.user_id`

7. **FEEDBACK** → **CONSULTATION_REQUESTS** & **USERS** (Many-to-One)
   - `feedback.consultation_id` → `consultation_requests.id`
   - `feedback.student_id` → `users.user_id`

8. **NOTIFICATIONS** → **USERS** (Many-to-One)
   - `notifications.user_id` → `users.user_id`

9. **SYSTEM_LOGS** → **USERS** (Many-to-One)
   - `system_logs.user_id` → `users.user_id`

---

## 🏗️ SYSTEM ARCHITECTURE

### **TECHNOLOGY STACK:**
- **Backend**: PHP 8.x
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 5.3.3, Vanilla JavaScript
- **Communication**: PDO for database, AJAX for real-time updates
- **Security**: bcrypt password hashing, CSRF protection, prepared statements

### **FILE STRUCTURE:**
```
EGABAY/
├── 📁 api/                     # REST API endpoints
│   ├── check_session.php
│   ├── delete_notification.php
│   ├── end_chat_session.php
│   ├── get_chat_messages.php
│   ├── get_counselor_availability.php
│   ├── get_notifications.php
│   ├── mark_notification_read.php
│   ├── send_chat_message.php
│   └── upload_chat_file.php
├── 📁 assets/                  # Static resources
│   ├── 📁 css/
│   │   ├── style.css
│   │   ├── chat-modern.css
│   │   └── print.css
│   ├── 📁 js/
│   │   ├── main.js
│   │   ├── chat-modern.js
│   │   └── notifications.js
│   └── 📁 images/
├── 📁 classes/                 # PHP classes
│   ├── Auth.php               # Authentication management
│   ├── Chat.php               # Chat system logic
│   ├── Consultation.php       # Consultation management
│   ├── Database.php           # Database connection
│   ├── SecurityManager.php    # Security features
│   └── Utility.php            # Helper functions
├── 📁 config/                  # Configuration
│   ├── config.php             # Main configuration
│   └── database.php           # Database settings
├── 📁 dashboard/               # User interfaces
│   ├── 📁 admin/              # Admin panel
│   ├── 📁 counselor/          # Counselor interface
│   ├── 📁 student/            # Student interface
│   └── index.php              # Dashboard router
├── 📁 includes/                # Shared components
│   ├── auth.php               # Authentication helpers
│   ├── functions.php          # Core functions
│   ├── utility.php            # Utility functions
│   ├── header.php             # Global header
│   └── footer.php             # Global footer
├── 📁 uploads/                 # File uploads
│   ├── 📁 chat_files/         # Chat attachments
│   └── 📁 profile_pictures/   # User avatars
├── index.php                   # Landing page
├── login.php                   # Authentication
├── register.php                # User registration
└── egabay_db.sql              # Database schema
```

---

## 📝 CORE SYSTEM CLASSES

### **1. Database Class** (`classes/Database.php`)
- **Purpose**: Handles database connections and queries
- **Key Methods**:
  - `getConnection()` - PDO connection with error handling
  - `executeQuery()` - Prepared statement execution
  - `getRecord()` - Fetch single record
  - `getRecords()` - Fetch multiple records
  - `insert()`, `update()`, `delete()` - CRUD operations

### **2. Auth Class** (`classes/Auth.php`)
- **Purpose**: User authentication and authorization
- **Key Methods**:
  - `login()` - User login with password verification
  - `register()` - New user registration
  - `getUserById()`, `getUserByUsername()` - User retrieval
  - `hasPermission()` - Role-based access control
  - `logActivity()` - Activity logging

### **3. Chat Class** (`classes/Chat.php`)
- **Purpose**: Real-time messaging system
- **Key Methods**:
  - `createSession()` - New chat session
  - `sendMessage()` - Send chat message with file support
  - `getMessages()` - Retrieve chat messages
  - `getUserSessions()` - User's chat sessions
  - `markMessagesAsRead()` - Read receipt management

### **4. Consultation Class** (`classes/Consultation.php`)
- **Purpose**: Consultation request management
- **Key Methods**:
  - `createRequest()` - New consultation request
  - `updateStatus()` - Status management (pending/live/completed/cancelled)
  - `getRequestsByStudent()`, `getRequestsByCounselor()` - Filtered retrieval
  - `getStatistics()` - Analytics and reporting

### **5. SecurityManager Class** (`classes/SecurityManager.php`)
- **Purpose**: Security features and monitoring
- **Key Methods**:
  - `isBlocked()` - Check login blocks
  - `recordLoginAttempt()` - Track login attempts
  - `requiresCaptcha()` - CAPTCHA requirement logic
  - `logSecurityEvent()` - Security event logging

---

## 🚀 INSTALLATION & SETUP REQUIREMENTS

### **PREREQUISITES:**
1. **Web Server**: Apache/Nginx with PHP support
2. **PHP 8.0+** with extensions:
   - PDO MySQL
   - mbstring
   - openssl
   - json
   - session
   - curl (for email features)
3. **MySQL/MariaDB 5.7+**
4. **Optional**: Composer for dependency management

### **STEP-BY-STEP INSTALLATION:**

#### **1. Environment Setup**
```bash
# Clone/download project files
git clone [repository] EGABAY
cd EGABAY
```

#### **2. Database Setup**
```sql
-- Create database
CREATE DATABASE egabay_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u username -p egabay_db < egabay_db.sql
```

#### **3. Configuration Files**

**A. Database Configuration** (`config/database.php`):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'egabay_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

**B. Main Configuration** (`config/config.php`):
```php
// Site settings
define('SITE_NAME', 'E-GABAY ASC');
define('SITE_DESC', 'Academic Support and Counseling System');

// Role definitions
define('ROLE_STUDENT', 1);
define('ROLE_COUNSELOR', 2);
define('ROLE_ADMIN', 3);
define('ROLE_STAFF', 4);

// Status definitions
define('STATUS_PENDING', 'pending');
define('STATUS_LIVE', 'live');
define('STATUS_COMPLETED', 'completed');
define('STATUS_CANCELLED', 'cancelled');
```

#### **4. Directory Permissions**
```bash
chmod 755 uploads/
chmod 755 uploads/chat_files/
chmod 755 uploads/profile_pictures/
chmod 644 config/*.php
```

#### **5. PHP Configuration** (`php.ini`):
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
session.gc_maxlifetime = 3600
memory_limit = 256M
```

#### **6. Web Server Configuration**

**Apache (.htaccess)**:
```apache
RewriteEngine On
DirectoryIndex index.php

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Prevent access to sensitive files
<Files "*.php">
    Order allow,deny
    Allow from all
</Files>

<Files "config/*">
    Order deny,allow
    Deny from all
</Files>
```

---

## 👥 USER ROLES & PERMISSIONS

### **ROLE DEFINITIONS:**

#### **1. STUDENT (Role ID: 1)**
- **Permissions**:
  - View own profile and consultations
  - Request new consultations
  - Participate in chat sessions
  - Receive notifications
  - Provide feedback

#### **2. COUNSELOR (Role ID: 2)**
- **Permissions**:
  - All student permissions
  - Manage assigned consultations
  - Access student profiles (consultation-related)
  - Conduct chat sessions
  - Generate reports

#### **3. ADMIN (Role ID: 3)**
- **Permissions**:
  - Full system access
  - User management for all roles
  - System configuration
  - Advanced reporting
  - Security management

#### **4. STAFF (Role ID: 4)**
- **Permissions**:
  - All counselor permissions
  - Limited administrative functions
  - User management (students only)
  - System monitoring

---

## 🔐 SECURITY FEATURES

### **AUTHENTICATION & AUTHORIZATION:**
- Password hashing with bcrypt
- Session management with regeneration
- Role-based access control (RBAC)
- Account verification system

### **DATA PROTECTION:**
- CSRF protection on all forms
- Input validation and sanitization
- SQL injection prevention (prepared statements)
- XSS protection (output escaping)

### **MONITORING & LOGGING:**
- Activity logging with IP tracking
- Failed login attempt monitoring
- Security event tracking
- System log management

---

## 📊 KEY FEATURES

### **CORE FUNCTIONALITY:**

#### **Consultation System:**
- Request consultations with preferred dates/times
- Anonymous consultation options
- Multiple communication methods (chat, in-person, video)
- Status tracking (pending → live → completed)
- Counselor assignment and notes

#### **Real-time Chat:**
- Live messaging between students and counselors
- File sharing capabilities
- Message read receipts
- Session management
- Chat history

#### **Notification System:**
- Real-time web notifications
- Email notifications
- Notification categorization
- Read/unread status management

#### **User Management:**
- Profile management with picture upload
- Extended profiles for students and counselors
- Account activation/deactivation
- Role-based access

#### **Reporting & Analytics:**
- Consultation statistics
- User activity reports
- System usage analytics
- Export capabilities

---

## 🔄 SYSTEM WORKFLOW

### **CONSULTATION PROCESS:**
1. **Student** submits consultation request
2. **System** notifies admins and counselors
3. **Admin/Counselor** reviews and assigns counselor
4. **System** updates status to 'live' and notifies student
5. **Chat session** created for communication
6. **Counselor** conducts consultation and adds notes
7. **Status** updated to 'completed'
8. **Student** provides feedback (optional)

### **CHAT WORKFLOW:**
1. **Chat session** created linked to consultation
2. **Real-time messaging** between student and counselor
3. **File sharing** for documents/images
4. **Message tracking** with read receipts
5. **Session closure** when consultation ends

---

## 🛠️ API ENDPOINTS

### **AUTHENTICATION:**
- `api/check_session.php` - Session validation

### **CHAT SYSTEM:**
- `api/get_chat_messages.php` - Retrieve messages
- `api/send_chat_message.php` - Send new message
- `api/upload_chat_file.php` - File upload
- `api/end_chat_session.php` - Close session

### **NOTIFICATIONS:**
- `api/get_notifications.php` - Get user notifications
- `api/get_notification_count.php` - Unread count
- `api/mark_notification_read.php` - Mark as read
- `api/delete_notification.php` - Remove notification

### **AVAILABILITY:**
- `api/get_counselor_availability.php` - Counselor schedules

---

## 📱 RESPONSIVE DESIGN

### **DESIGN FEATURES:**
- **Bootstrap 5.3.3** framework
- **Mobile-first** approach
- **Glass morphism** UI design
- **Heavenly gradient** backgrounds
- **Accessibility** compliant (WCAG 2.1)

### **BROWSER SUPPORT:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## 🚀 DEFAULT ACCESS

### **INITIAL LOGIN:**
- **Username**: `admin`
- **Password**: `admin123`
- **⚠️ IMPORTANT**: Change default credentials immediately after first login

---

## 🔧 MAINTENANCE

### **REGULAR TASKS:**
- Database backup and optimization
- Log file management and cleanup
- Security updates and patches
- User account maintenance
- System performance monitoring

### **MONITORING:**
- Activity logs review
- Security event analysis
- System resource usage
- User feedback collection

---

## ✅ DEPLOYMENT CHECKLIST

- [ ] Database created and schema imported
- [ ] Configuration files updated
- [ ] Directory permissions set correctly
- [ ] PHP settings configured
- [ ] Web server configuration applied
- [ ] Default admin password changed
- [ ] Email settings configured
- [ ] SSL certificate installed (recommended)
- [ ] Backup procedures established
- [ ] Security headers implemented

---

## 🎯 CONCLUSION

Ang E-GABAY ASC ay comprehensive system na nag-provide ng secure, user-friendly platform para sa academic counseling at support. Ang documentation na ito ay nagbibigay ng complete guide para sa installation, configuration, at maintenance ng system.

Para sa mga additional questions o technical support, i-refer ang system logs at admin dashboard para sa detailed monitoring at troubleshooting capabilities.

---

**© 2025 E-GABAY ASC - Complete System Documentation** 