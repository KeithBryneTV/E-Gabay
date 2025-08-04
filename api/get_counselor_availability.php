<?php
// Include path fix helper
require_once __DIR__ . '/../includes/path_fix.php';

// Required includes with absolute paths
require_once $base_path . '/config/config.php';

// Include required classes
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/classes/Auth.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
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
    
    // Get parameters
    $counselor_id = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    
    // If specific counselor requested
    if ($counselor_id) {
        // Get specific counselor availability
        $query = "SELECT cp.availability, u.first_name, u.last_name 
                  FROM counselor_profiles cp 
                  JOIN users u ON cp.user_id = u.user_id 
                  WHERE cp.user_id = ? AND u.is_verified = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$counselor_id]);
        $counselor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$counselor) {
            echo json_encode(['success' => false, 'error' => 'Counselor not found']);
            exit;
        }
        
        $availability = json_decode($counselor['availability'], true) ?: [];
        
        // If specific date requested, get available times for that date
        if ($date) {
            $day_of_week = strtolower(date('l', strtotime($date)));
            
            if (isset($availability[$day_of_week]) && $availability[$day_of_week]['available']) {
                $day_availability = $availability[$day_of_week];
                
                // Generate time slots (1-hour intervals)
                $start_time = $day_availability['start_time'];
                $end_time = $day_availability['end_time'];
                
                $time_slots = [];
                $current_time = strtotime($start_time);
                $end_timestamp = strtotime($end_time);
                
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
                if ($schedule['available']) {
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
                  WHERE u.role_id = ? AND u.is_verified = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([ROLE_COUNSELOR]);
        $counselors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counselor_list = [];
        foreach ($counselors as $counselor) {
            $availability = json_decode($counselor['availability'], true) ?: [];
            $has_availability = false;
            
            // Check if counselor has any available days
            foreach ($availability as $day => $schedule) {
                if ($schedule['available']) {
                    $has_availability = true;
                    break;
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
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?> 