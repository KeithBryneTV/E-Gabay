<?php
// Include path fix helper
require_once __DIR__ . '/../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/auth.php';
require_once $base_path . '/includes/utility.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Debug session info
error_log("API Session: " . session_id() . ", User ID: " . ($_SESSION['user_id'] ?? 'not set'));

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("API Authentication failed - user not logged in");
    echo json_encode(['success' => false, 'error' => 'Unauthorized access - please login']);
    exit;
}

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    // Get parameters
    $counselor_id = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    
    // Log for debugging
    error_log("API Call: counselor_id=$counselor_id, date=$date, user_id=" . ($_SESSION['user_id'] ?? 'not set'));
    
    // If specific counselor requested
    if ($counselor_id) {
        // Get specific counselor availability (with fallback if counselor_profiles doesn't exist)
        $query = "SELECT u.first_name, u.last_name, cp.availability 
                  FROM users u 
                  LEFT JOIN counselor_profiles cp ON u.user_id = cp.user_id 
                  WHERE u.user_id = ? AND u.role_id = ? AND u.is_verified = 1 AND u.is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$counselor_id, ROLE_COUNSELOR]);
        $counselor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$counselor) {
            error_log("Counselor not found: counselor_id=$counselor_id, role=" . ROLE_COUNSELOR);
            echo json_encode(['success' => false, 'error' => 'Counselor not found or not active']);
            exit;
        }
        
        error_log("Found counselor: " . json_encode($counselor));
        
        // Handle cases where availability column doesn't exist or is null
        $availability = [];
        if (isset($counselor['availability']) && !empty($counselor['availability'])) {
            $availability = json_decode($counselor['availability'], true) ?: [];
        }
        
        // If no availability is set, provide default business hours
        if (empty($availability)) {
            $availability = [
                'monday' => ['available' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
                'tuesday' => ['available' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
                'wednesday' => ['available' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
                'thursday' => ['available' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
                'friday' => ['available' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
                'saturday' => ['available' => false, 'start_time' => '', 'end_time' => ''],
                'sunday' => ['available' => false, 'start_time' => '', 'end_time' => '']
            ];
        }
        
        // If specific date requested, get available times for that date
        if ($date) {
            $day_of_week = strtolower(date('l', strtotime($date)));
            
            // Log for debugging
            error_log("Getting time slots for counselor $counselor_id on $date ($day_of_week)");
            error_log("Availability data: " . json_encode($availability));
            
            if (isset($availability[$day_of_week]) && isset($availability[$day_of_week]['available']) && $availability[$day_of_week]['available']) {
                $day_availability = $availability[$day_of_week];
                
                // Generate time slots (1-hour intervals)
                $start_time = isset($day_availability['start_time']) ? $day_availability['start_time'] : '09:00';
                $end_time = isset($day_availability['end_time']) ? $day_availability['end_time'] : '17:00';
                
                $time_slots = [];
                $current_time = strtotime($start_time);
                $end_timestamp = strtotime($end_time);
                
                // Validate time range
                if ($current_time === false || $end_timestamp === false || $current_time >= $end_timestamp) {
                    // Invalid time range, use default
                    $current_time = strtotime('09:00');
                    $end_timestamp = strtotime('17:00');
                }
                
                while ($current_time < $end_timestamp) {
                    $time_24 = date('H:i', $current_time);
                    $time_12 = date('g:i A', $current_time);
                    
                    // Check if this time slot is already booked
                    $booking_query = "SELECT COUNT(*) as count FROM consultation_requests 
                                    WHERE counselor_id = ? AND preferred_date = ? AND preferred_time = ? 
                                    AND status IN ('pending', 'live')";
                    $booking_stmt = $db->prepare($booking_query);
                    $booking_stmt->execute([$counselor_id, $date, $time_24]);
                    $booking_result = $booking_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($booking_result['count'] == 0) {
                        $time_slots[] = [
                            'value' => $time_24,
                            'label' => $time_12,
                            'available' => true
                        ];
                    }
                    
                    $current_time += 3600; // Add 1 hour
                }
                
                echo json_encode([
                    'success' => true,
                    'counselor' => [
                        'id' => $counselor_id,
                        'name' => $counselor['first_name'] . ' ' . $counselor['last_name']
                    ],
                    'date' => $date,
                    'time_slots' => $time_slots
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'counselor' => [
                        'id' => $counselor_id,
                        'name' => $counselor['first_name'] . ' ' . $counselor['last_name']
                    ],
                    'date' => $date,
                    'time_slots' => [],
                    'message' => 'Counselor is not available on this day'
                ]);
            }
        } else {
            // Return available days for the counselor
            $available_days = [];
            foreach ($availability as $day => $schedule) {
                if (isset($schedule['available']) && $schedule['available']) {
                    $available_days[] = [
                        'day' => $day,
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'counselor' => [
                    'id' => $counselor_id,
                    'name' => $counselor['first_name'] . ' ' . $counselor['last_name']
                ],
                'available_days' => $available_days
            ]);
        }
    } else {
        // Get all counselors with their availability
        $query = "SELECT u.user_id, u.first_name, u.last_name, cp.availability 
                  FROM users u 
                  LEFT JOIN counselor_profiles cp ON u.user_id = cp.user_id 
                  WHERE u.role_id = ? AND u.is_verified = 1 AND u.is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([ROLE_COUNSELOR]);
        $counselors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counselor_list = [];
        foreach ($counselors as $counselor) {
            // Handle cases where availability doesn't exist
            $availability = [];
            if (isset($counselor['availability']) && !empty($counselor['availability'])) {
                $availability = json_decode($counselor['availability'], true) ?: [];
            }
            
            // If no availability set, assume they have default business hours
            $has_availability = true;
            
            // If availability is set, check if they have any available days
            if (!empty($availability)) {
                $has_availability = false;
                foreach ($availability as $day => $schedule) {
                    if (isset($schedule['available']) && $schedule['available']) {
                        $has_availability = true;
                        break;
                    }
                }
            }
            
            $counselor_list[] = [
                'id' => $counselor['user_id'],
                'name' => $counselor['first_name'] . ' ' . $counselor['last_name'],
                'has_availability' => $has_availability
            ];
        }
        
        echo json_encode([
            'success' => true,
            'counselors' => $counselor_list
        ]);
    }
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Counselor availability API error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Return user-friendly error
    echo json_encode([
        'success' => false, 
        'error' => 'Unable to load counselor availability. Please try again.',
        'debug_info' => [
            'message' => $e->getMessage(),
            'counselor_id' => $counselor_id ?? 'not set',
            'date' => $date ?? 'not set'
        ]
    ]);
}
?> 