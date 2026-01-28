<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');
global $wpdb;
$jsonData = file_get_contents('php://input');

$data = json_decode($jsonData);


$user_id = 1;
$selected_month = $data->selected_month;
$selected_year = $data->selected_year;
$existing_record = get_user_meta($user_id, 'my_calendar_data', true);

if (!empty($existing_record) && is_array($existing_record)) {
    if (isset($existing_record[$selected_year])) {
        if (isset($existing_record[$selected_year][$selected_month])) {
            $response = esc_html($existing_record[$selected_year][$selected_month]['my_calendar_title']);
        } else {
            $response = 'No title available for the selected month and year.';
        }
    } else {
        $response = 'No title available for the selected year.';
    }
} else {
    $response = 'No calendar data available.';
}

echo $response;
