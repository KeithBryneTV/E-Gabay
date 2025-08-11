<?php
/**
 * Simplified Counselor Availability API
 * Now uses real data from counselor_profiles.availability
 */

require_once __DIR__ . '/../includes/path_fix.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/auth.php';
require_once $base_path . '/classes/Database.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$counselor_id = isset($_GET['counselor_id']) ? (int)$_GET['counselor_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    if ($counselor_id) {
        // Get counselor profile and availability
        $query = "SELECT first_name, last_name FROM users WHERE user_id = ? AND role_id = 2 AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$counselor_id]);
        $counselor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$counselor) {
            echo json_encode(['success' => false, 'error' => 'Counselor not found']);
            exit;
        }

        $profile_query = "SELECT availability FROM counselor_profiles WHERE user_id = ?";
        $profile_stmt = $db->prepare($profile_query);
        $profile_stmt->execute([$counselor_id]);
        $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
        $availability = [];
        if ($profile && !empty($profile['availability'])) {
            $availability = json_decode($profile['availability'], true);
        }

        $days_of_week = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

        if ($date) {
            // Return time slots for specific date
            $day_of_week = strtolower(date('l', strtotime($date)));
            if (isset($availability[$day_of_week]) && $availability[$day_of_week]['available']) {
                $day_schedule = $availability[$day_of_week];
                $start = $day_schedule['start_time'];
                $end = $day_schedule['end_time'];
                $time_slots = [];
                if ($start && $end) {
                    $start_hour = (int)substr($start, 0, 2);
                    $end_hour = (int)substr($end, 0, 2);
                    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                        $time_24 = sprintf('%02d:00', $hour);
                        $time_12 = date('g:i A', strtotime($time_24));
                        $time_slots[] = [
                            'value' => $time_24,
                            'label' => $time_12,
                            'available' => true
                        ];
                    }
                }
                echo json_encode([
                    'success' => true,
                    'counselor_id' => $counselor_id,
                    'date' => $date,
                    'day_of_week' => $day_of_week,
                    'time_slots' => $time_slots,
                    'message' => count($time_slots) . ' time slots available'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'counselor_id' => $counselor_id,
                    'date' => $date,
                    'day_of_week' => $day_of_week,
                    'time_slots' => [],
                    'message' => 'Counselor not available on this day'
                ]);
            }
        } else {
            // Return available days
            $available_days = [];
            foreach ($days_of_week as $day) {
                if (isset($availability[$day]) && $availability[$day]['available']) {
                    $available_days[] = [
                        'day' => $day,
                        'start_time' => $availability[$day]['start_time'],
                        'end_time' => $availability[$day]['end_time']
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
        echo json_encode([
            'success' => true,
            'message' => 'Please select a counselor first',
            'available_days' => []
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} 