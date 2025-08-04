# E-GABAY ASC - Academic Support and Counseling System

> **A comprehensive web-based counseling and academic support system developed for educational institutions**

![EGABAY](assets/images/egabay-logo.png)

## 📋 Table of Contents

- [Overview](#overview)
- [System Features](#system-features)
- [User Roles & Permissions](#user-roles--permissions)
- [Technical Specifications](#technical-specifications)
- [Installation & Setup](#installation--setup)
- [System Architecture](#system-architecture)
- [Security Features](#security-features)
- [API Documentation](#api-documentation)
- [File Structure](#file-structure)
- [Database Schema](#database-schema)
- [Contributing](#contributing)
- [Credits](#credits)

## 🎯 Overview

E-GABAY ASC is a modern, web-based Academic Support and Counseling System designed to facilitate seamless communication between students, counselors, and administrative staff. The system provides a secure platform for consultation requests, real-time chat sessions, appointment scheduling, and comprehensive reporting.

### 🌟 Key Highlights

- **Multi-role Access Control** - Student, Counselor, Admin, Staff roles
- **Real-time Chat System** - WebSocket-based messaging with file sharing
- **Consultation Management** - Request, approve, schedule, and track consultations
- **Anonymous Consultation Support** - Privacy-focused counseling options
- **Responsive Design** - Mobile-first Bootstrap 5 interface
- **Security-First Architecture** - CSRF protection, input validation, audit logging

## 🚀 System Features

### 📚 Core Functionality

#### 🎓 Student Features
- **Profile Management**
  - Personal information updates
  - Profile picture upload (up to 10MB)
  - Academic information (course, year, section)
  - Contact details management

- **Consultation System**
  - Request new consultations
  - Choose communication method (chat/in-person/video)
  - Anonymous consultation options
  - Track consultation status
  - View consultation history

- **Real-time Chat**
  - Live messaging with assigned counselors
  - File sharing capabilities (documents, images)
  - Message read receipts
  - Chat session management

- **Notifications**
  - Real-time system notifications
  - Email notifications for important updates
  - Notification history and management

#### 👨‍🏫 Counselor Features
- **Consultation Management**
  - View and manage assigned consultations
  - Approve/reject consultation requests
  - Schedule appointments
  - Add consultation notes and recommendations

- **Student Communication**
  - Start and manage chat sessions
  - Access student profiles (with permissions)
  - Send system notifications to students

- **Reporting & Analytics**
  - Generate consultation reports
  - Track student engagement
  - View counseling statistics

#### 🛠️ Administrative Features
- **User Management**
  - Create, edit, and manage user accounts
  - Role assignment and permissions
  - Bulk user operations
  - Account activation/deactivation

- **System Configuration**
  - Site settings management
  - Email configuration
  - Security settings
  - Academic year management

- **Comprehensive Reporting**
  - System usage analytics
  - User activity reports
  - Consultation statistics
  - Export capabilities

- **System Monitoring**
  - Activity logs and audit trails
  - Security monitoring
  - System health checks
  - Log management with clear functionality

#### 📊 Advanced Features
- **Dashboard Analytics**
  - Real-time statistics widgets
  - Interactive charts and graphs
  - Quick action buttons
  - System overview metrics

- **Notification System**
  - Multi-channel notifications (web, email)
  - Customizable notification preferences
  - Notification categories and filtering
  - Read/unread status management

- **Security Features**
  - Enhanced authentication system
  - CSRF protection on all forms
  - Input validation and sanitization
  - Session management
  - IP-based access logging

## 👥 User Roles & Permissions

### 🎓 Student (Role ID: 1)
**Access Level**: Basic User
- View own profile and consultations
- Request new consultations
- Participate in assigned chat sessions
- Receive and manage notifications
- Update personal information

### 👨‍🏫 Counselor (Role ID: 2)
**Access Level**: Professional User
- All student permissions
- Manage assigned consultations
- Conduct chat sessions with students
- Access student profiles (consultation-related)
- Generate basic reports

### 👔 Staff (Role ID: 4)
**Access Level**: Support User
- All counselor permissions
- Limited administrative functions
- User management (students only)
- System monitoring capabilities

### 🛡️ Administrator (Role ID: 3)
**Access Level**: Full System Access
- Complete system control
- User management for all roles
- System configuration
- Security management
- Advanced reporting and analytics
- System maintenance tools

## 🛠️ Technical Specifications

### 🏗️ Technology Stack

#### Backend
- **PHP 8.x** - Server-side scripting language
- **MySQL/MariaDB** - Relational database management
- **PDO** - Database abstraction layer
- **PHPMailer** - Email sending functionality

#### Frontend
- **Bootstrap 5.3.3** - Responsive CSS framework
- **Vanilla JavaScript** - Client-side interactivity
- **Font Awesome 6.5.1** - Icon library
- **Google Fonts (Inter)** - Typography
- **CSS3** - Custom styling with variables and gradients

#### Features
- **Responsive Design** - Mobile-first approach
- **Glass Morphism UI** - Modern visual design
- **Real-time Updates** - AJAX-based interactions
- **File Upload Support** - Multi-format file handling
- **Cross-browser Compatibility** - Modern browser support

### 📱 Design Philosophy

#### 🎨 User Interface
- **Heavenly Gradient Background** - Soft, eye-friendly color scheme
- **Card-based Layout** - Modern, organized content presentation
- **Micro-interactions** - Subtle animations for better UX
- **Accessibility First** - Respects `prefers-reduced-motion`
- **Mobile Optimization** - Touch-friendly interface elements

#### 🔧 Performance
- **Optimized Animations** - Reduced duration for low-end devices
- **Lazy Loading** - Efficient resource management
- **Compressed Assets** - Optimized file sizes
- **Database Indexing** - Fast query performance

## 🔐 Security Features

### 🛡️ Authentication & Authorization
- **Role-based Access Control (RBAC)**
- **Session Management** - Secure session handling
- **Password Hashing** - bcrypt with salt
- **Account Verification** - Email-based verification system

### 🔒 Data Protection
- **CSRF Protection** - Token-based form security
- **Input Validation** - Server-side sanitization
- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Output escaping

### 📊 Monitoring & Logging
- **Activity Logging** - Comprehensive audit trail
- **Security Event Tracking** - Failed login attempts
- **System Log Management** - Admin-controlled log clearing
- **IP Address Tracking** - User activity monitoring

## 📁 File Structure

```
EGABAY/
├── 📁 api/                     # REST API endpoints
│   ├── end_chat_session.php
│   ├── get_chat_messages.php
│   ├── send_chat_message.php
│   └── upload_chat_file.php
├── 📁 assets/                  # Static resources
│   ├── 📁 css/
│   │   ├── style.css          # Main stylesheet
│   │   ├── chat-modern.css    # Chat interface styles
│   │   └── print.css          # Print-specific styles
│   ├── 📁 js/
│   │   ├── main.js            # Core JavaScript
│   │   ├── chat-modern.js     # Chat functionality
│   │   └── notifications.js   # Notification system
│   └── 📁 images/
│       ├── asc-logo.png       # System logo
│       └── favicon.ico        # Site icon
├── 📁 classes/                 # PHP classes
│   ├── Auth.php               # Authentication
│   ├── Chat.php               # Chat system
│   ├── Consultation.php       # Consultation management
│   ├── Database.php           # Database connection
│   ├── SecurityManager.php    # Security utilities
│   └── Utility.php            # Helper functions
├── 📁 config/                  # Configuration files
│   ├── config.php             # Main configuration
│   └── database.php           # Database settings
├── 📁 dashboard/               # User dashboards
│   ├── 📁 admin/              # Administrator interface
│   ├── 📁 counselor/          # Counselor interface
│   ├── 📁 student/            # Student interface
│   └── 📁 staff/              # Staff interface
├── 📁 includes/                # Shared components
│   ├── header.php             # Global header
│   ├── footer.php             # Global footer
│   ├── auth.php               # Authentication helpers
│   └── functions.php          # Utility functions
├── 📁 uploads/                 # User uploads
│   ├── 📁 chat_files/         # Chat attachments
│   └── 📁 profile_pictures/   # User avatars
├── index.php                   # Landing page
├── login.php                   # User login
├── register.php                # User registration
├── profile.php                 # User profile management
└── egabay_db.sql              # Database schema
```

## 🗄️ Database Schema

### 📊 Core Tables

#### Users & Authentication
- `users` - User accounts and basic information
- `roles` - User role definitions
- `student_profiles` - Extended student information
- `counselor_profiles` - Extended counselor information

#### Consultation System
- `consultation_requests` - Consultation requests and details
- `chat_sessions` - Chat session management
- `chat_messages` - Individual chat messages

#### System Management
- `system_logs` - Activity and audit logging
- `system_notifications` - User notifications
- `settings` - System configuration

#### Security & Monitoring
- `login_attempts` - Failed login tracking
- `security_logs` - Security event logging

## 🚀 Installation & Setup

### 📋 Prerequisites

1. **Web Server** - Apache/Nginx with PHP support
2. **PHP 8.0+** - With required extensions:
   - PDO MySQL
   - mbstring
   - openssl
   - json
   - session
3. **MySQL/MariaDB 5.7+**
4. **Composer** (optional, for dependency management)

### 🔧 Installation Steps

1. **Clone/Download** the project files to your web directory
2. **Database Setup**:
   ```bash
   # Import the database schema
   mysql -u username -p < egabay_db.sql
   ```
3. **Configuration**:
   - Update `config/database.php` with your database credentials
   - Configure email settings in admin panel
4. **Permissions**:
   ```bash
   # Set proper permissions for upload directories
   chmod 755 uploads/
   chmod 755 uploads/chat_files/
   chmod 755 uploads/profile_pictures/
   ```
5. **Default Access**:
   - Username: `admin`
   - Password: `admin123`
   - **⚠️ Change default credentials immediately**

### 🔧 Configuration

#### Required PHP Settings
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
session.gc_maxlifetime = 3600
```

#### Recommended Apache Settings
```apache
# .htaccess (auto-generated)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
```

## 🔍 File Cleanup Recommendations

### ❌ Files to Consider Removing

#### 🗂️ Duplicate/Legacy Dependencies
- `phpmailer/` directory (duplicate of `vendor/phpmailer/`)
- `vendor/phpmailer/phpmailer/README.txt` (documentation file)

#### 📦 Backup Files
- `backups/backup_2025-08-02_16-42-07.sql` (old backup, keep only recent)

#### 🛠️ Development Files
- `setup.php` (after initial setup completion)
- `setup_enhanced_security.php` (after security configuration)

#### 🔧 Maintenance Files
- `maintenance.php` and `maintenance_check.php` (unless actively using maintenance mode)

#### 📸 Media Files
- `asc logo.png` (root directory duplicate of `assets/images/asc-logo.png`)

### ✅ Critical Files to Keep
- All `dashboard/` subdirectories and files
- All `classes/` files
- All `includes/` files
- All `api/` endpoints
- All `assets/` resources
- `egabay_db.sql` (latest schema)
- Core authentication files (`login.php`, `register.php`, `profile.php`)

## 📈 System Analytics

### 📊 Dashboard Metrics
- **Total Users** by role
- **Active Consultations** count
- **Pending Requests** tracking
- **System Activity** trends

### 📋 Reporting Features
- **User Activity Reports**
- **Consultation Analytics**
- **System Usage Statistics**
- **Export Capabilities** (CSV, PDF)

## 🤝 Contributing

### 👨‍💻 Development Team

- **Keith Bryan O. Torda** - Lead Developer & UI/UX Designer
- **Richael M. Ulibas** - Paperworks Designer
- **Christian Ancheta** - Content Writer

### 📅 Project Timeline
- **Started**: April 27, 2024
- **Purpose**: CAPSTONE 2 PROJECT
- **Version**: 1.0.0

### 🛠️ Development Standards
- **PSR-12** coding standards
- **Security-first** development approach
- **Mobile-responsive** design requirements
- **Accessibility** compliance (WCAG 2.1)

## 📞 Support & Maintenance

### 🔧 System Maintenance
- Regular security updates
- Database optimization
- Log file management
- Backup procedures

### 📊 Monitoring
- Performance metrics tracking
- Error logging and analysis
- User feedback collection
- System health monitoring

---

## 🎉 Credits

**Built with passion for education and student support** ❤️

This system was developed as part of a Capstone 2 project to provide educational institutions with a comprehensive, secure, and user-friendly counseling and academic support platform.

**Technologies**: PHP 8.x • MySQL • Bootstrap 5 • JavaScript • CSS3 • HTML5

---

*© 2025 E-GABAY ASC. All rights reserved. Developed by Keith Bryan O. Torda & Team* 