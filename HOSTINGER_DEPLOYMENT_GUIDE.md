# EGABAY Hostinger Deployment Guide 🚀

## Prerequisites ✅

Before uploading to Hostinger, ensure you have:
- **Hostinger hosting account** with PHP and MySQL support
- **Domain name** pointed to your hosting
- **FTP/File Manager access** to your hosting
- **MySQL database** created in Hostinger control panel
- **EGABAY system files** ready for upload

---

## 📋 Step-by-Step Deployment Process

### 1. **Database Setup** 🗄️

#### Create MySQL Database in Hostinger:
1. **Login to Hostinger Control Panel**
2. **Go to "Databases" → "MySQL Databases"**
3. **Create new database:**
   ```
   Database Name: egabay_db (or your preferred name)
   Username: egabay_user (or your preferred username)
   Password: [Strong password]
   ```
4. **Note down the database credentials:**
   ```
   Host: localhost (usually)
   Database: your_database_name
   Username: your_username
   Password: your_password
   ```

#### Import Database:
1. **Go to "phpMyAdmin" in Hostinger control panel**
2. **Select your database**
3. **Click "Import" tab**
4. **Upload your `egabay_db.sql` file**
5. **Click "Go" to import**

### 2. **File Upload** 📁

#### Upload Files via File Manager:
1. **Go to "File Manager" in Hostinger control panel**
2. **Navigate to `public_html` folder** (or your domain folder)
3. **Upload all EGABAY files EXCEPT:**
   ```
   ❌ config/database.php (modify this separately)
   ❌ .git/ folder (if present)
   ❌ README.md (optional)
   ❌ *.md files (optional documentation files)
   ```

#### Directory Structure in Hostinger:
```
public_html/
├── api/
├── assets/
├── classes/
├── config/
├── dashboard/
├── includes/
├── uploads/
├── vendor/
├── index.php
├── login.php
├── register.php
└── ... (other PHP files)
```

### 3. **Configuration Setup** ⚙️

#### Update Database Configuration:
1. **Edit `config/database.php` in File Manager or download → edit → upload:**

```php
<?php
// Hostinger Database Configuration
define('DB_HOST', 'localhost');  // Usually localhost for Hostinger
define('DB_USERNAME', 'your_hostinger_db_username');
define('DB_PASSWORD', 'your_hostinger_db_password');
define('DB_NAME', 'your_hostinger_db_name');

// Site Configuration
define('SITE_URL', 'https://yourdomain.com');  // Your actual domain
define('SITE_NAME', 'EGABAY');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// MySQLi connection for legacy code
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    error_log("MySQLi connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}
?>
```

#### Update Main Config:
2. **Edit `config/config.php`:**

```php
<?php
// Site Configuration for Hostinger
define('SITE_URL', 'https://yourdomain.com');  // Replace with your domain
define('SITE_NAME', 'EGABAY Consultation System');

// Email Configuration (configure with your hosting email)
define('SMTP_HOST', 'smtp.hostinger.com');  // Hostinger SMTP
define('SMTP_USERNAME', 'noreply@yourdomain.com');  // Your email
define('SMTP_PASSWORD', 'your_email_password');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');

// File Upload Configuration
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('PROFILE_UPLOAD_PATH', UPLOAD_PATH . 'profile_pictures/');
define('CHAT_UPLOAD_PATH', UPLOAD_PATH . 'chat_files/');

// Other configurations...
?>
```

### 4. **Folder Permissions** 🔒

#### Set Correct Permissions:
1. **In File Manager, right-click folders and select "Permissions":**
   ```
   uploads/ folder: 755 or 777
   uploads/profile_pictures/: 755 or 777
   uploads/chat_files/: 755 or 777
   config/ folder: 644 or 755
   ```

### 5. **SSL & Security** 🛡️

#### Enable SSL in Hostinger:
1. **Go to "SSL" in control panel**
2. **Enable "Let's Encrypt SSL" for your domain**
3. **Force HTTPS redirect**

#### Update .htaccess (if needed):
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Pretty URLs (if using clean URLs)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^\.]+)$ $1.php [NC,L]
```

---

## 🧪 Testing After Deployment

### 1. **Basic Functionality Test:**
- ✅ Visit `https://yourdomain.com`
- ✅ Test registration/login
- ✅ Access dashboards (student, counselor, admin)
- ✅ Test consultation system
- ✅ Test AI helper

### 2. **Database Connection Test:**
- ✅ Check if data loads properly
- ✅ Test create/read/update operations
- ✅ Verify file uploads work

### 3. **Email Testing:**
- ✅ Test password reset emails
- ✅ Test notification emails
- ✅ Verify SMTP configuration

---

## 🔧 Common Hostinger Issues & Solutions

### **Database Connection Errors:**
```
Problem: "Database connection failed"
Solution: 
- Check database credentials in config/database.php
- Ensure database exists in Hostinger control panel
- Verify database user has proper permissions
```

### **File Upload Issues:**
```
Problem: Files not uploading
Solution:
- Check folder permissions (755 or 777)
- Verify upload paths in configuration
- Check PHP upload limits in hosting
```

### **Email Not Working:**
```
Problem: Emails not sending
Solution:
- Use Hostinger SMTP settings
- Create email account in hosting control panel
- Update SMTP configuration in config/config.php
```

### **SSL Certificate Issues:**
```
Problem: Mixed content warnings
Solution:
- Enable SSL in Hostinger control panel
- Update all URLs to use HTTPS
- Check .htaccess for HTTPS redirect
```

---

## 📞 Support Resources

### **Hostinger Documentation:**
- [PHP Hosting Guide](https://support.hostinger.com/en/articles/1583477-how-to-upload-a-website)
- [Database Management](https://support.hostinger.com/en/articles/1583339-how-to-create-a-mysql-database)
- [Email Setup](https://support.hostinger.com/en/articles/1583477-how-to-create-an-email-account)

### **EGABAY Developer Contact:**
- **Facebook:** [Keith Torda](https://www.facebook.com/Keithtordaofficial1/)
- **Email:** keithorario@gmail.com
- **Support:** Technical issues and deployment assistance

---

## ✅ Final Checklist

Before going live, ensure:
- [ ] Database imported successfully
- [ ] All files uploaded to public_html
- [ ] Database configuration updated
- [ ] Folder permissions set correctly
- [ ] SSL certificate enabled
- [ ] Test all major functions
- [ ] Email configuration working
- [ ] AI helper functioning
- [ ] All URLs using HTTPS

---

**🎉 Congratulations! Your EGABAY system should now be live on Hostinger!**

Visit your domain to start using the system. For any issues, contact the developer using the information above. 