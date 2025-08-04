<?php
/**
 * Helper functions for formatting counselor availability
 */

/**
 * Format counselor availability JSON as a nice table
 * 
 * @param string $availabilityJson JSON string of availability data
 * @return string HTML output of formatted availability table
 */
function formatAvailabilityDisplay($availabilityJson) {
    // Try to decode the JSON availability
    $availability = json_decode($availabilityJson, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($availability)) {
        $html = '<div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th>Day</th>
                        <th>Status</th>
                        <th>Hours</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            if (isset($availability[$day])) {
                $html .= '<tr>
                    <td class="fw-bold text-capitalize">' . $day . '</td>
                    <td>';
                
                if (isset($availability[$day]['available']) && $availability[$day]['available'] === true) {
                    $html .= '<span class="badge bg-success">Available</span>';
                } else {
                    $html .= '<span class="badge bg-secondary">Unavailable</span>';
                }
                
                $html .= '</td><td>';
                
                if (isset($availability[$day]['available']) && $availability[$day]['available'] === true) {
                    $start_time = isset($availability[$day]['start_time']) ? $availability[$day]['start_time'] : '';
                    $end_time = isset($availability[$day]['end_time']) ? $availability[$day]['end_time'] : '';
                    
                    if (!empty($start_time) && !empty($end_time)) {
                        $html .= formatTime($start_time) . ' - ' . formatTime($end_time);
                    } else {
                        $html .= 'Not specified';
                    }
                } else {
                    $html .= '-';
                }
                
                $html .= '</td></tr>';
            }
        }
        
        $html .= '</tbody></table></div>';
        return $html;
    }
    
    return '<p class="fw-bold">' . htmlspecialchars($availabilityJson) . '</p>';
} 