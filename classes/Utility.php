<?php
class Utility {
    // Clean input data
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    // Generate random string
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    // Format date in Philippine timezone
    public static function formatDate($date, $format = 'M d, Y') {
        try {
            $dateTime = new DateTime($date, new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone('Asia/Manila'));
            return $dateTime->format($format);
        } catch (Exception $e) {
            return date($format, strtotime($date));
        }
    }
    
    // Format time in Philippine timezone
    public static function formatTime($time, $format = 'h:i A') {
        try {
            $dateTime = new DateTime($time, new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone('Asia/Manila'));
            return $dateTime->format($format);
        } catch (Exception $e) {
            return date($format, strtotime($time));
        }
    }
    
    // Get time ago in Philippine timezone
    public static function timeAgo($datetime) {
        try {
            $timeObj = new DateTime($datetime, new DateTimeZone('UTC'));
            $timeObj->setTimezone(new DateTimeZone('Asia/Manila'));
            
            $nowObj = new DateTime('now', new DateTimeZone('Asia/Manila'));
            
            $diff = $nowObj->getTimestamp() - $timeObj->getTimestamp();
            
            if ($diff < 60) {
                return 'Just now';
            } elseif ($diff < 3600) {
                $mins = floor($diff / 60);
                return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } elseif ($diff < 604800) {
                $days = floor($diff / 86400);
                return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            } else {
                return self::formatDate($datetime);
            }
        } catch (Exception $e) {
            // Fallback
            $time = strtotime($datetime);
            $now = time();
            $diff = $now - $time;
            
            if ($diff < 60) {
                return 'Just now';
            } else {
                return self::formatDate($datetime);
            }
        }
    }
    
    // Truncate text
    public static function truncateText($text, $length = 100, $append = '...') {
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length) . $append;
        }
        return $text;
    }
    
    // Get file extension
    public static function getFileExtension($filename) {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }
    
    // Check if file is an image
    public static function isImage($filename) {
        $ext = self::getFileExtension($filename);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        return in_array(strtolower($ext), $imageExtensions);
    }
    
    // Generate slug
    public static function generateSlug($text) {
        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Trim
        $text = trim($text, '-');
        
        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        
        // Lowercase
        $text = strtolower($text);
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }
    
    // Format file size
    public static function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        } else {
            return '0 bytes';
        }
    }
    
    // Get settings
    public static function getSetting($key, $default = '') {
        global $db;
        
        try {
            $stmt = $db->prepare("SELECT value FROM settings WHERE setting_key = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['value'];
            }
        } catch (Exception $e) {
            error_log("Error getting setting: " . $e->getMessage());
        }
        
        return $default;
    }
    
    // Update setting
    public static function updateSetting($key, $value) {
        global $db;
        
        try {
            // Check if setting exists
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['count'] > 0) {
                // Update existing setting
                $stmt = $db->prepare("UPDATE settings SET value = ? WHERE setting_key = ?");
                return $stmt->execute([$value, $key]);
            } else {
                // Insert new setting
                $stmt = $db->prepare("INSERT INTO settings (setting_key, value, description) VALUES (?, ?, ?)");
                return $stmt->execute([$key, $value, 'Added via settings page']);
            }
        } catch (Exception $e) {
            error_log("Error updating setting: " . $e->getMessage());
            return false;
        }
    }
}
?> 