<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);

$dateStr = $data->date;

$dateTime = new DateTime($dateStr);

$formattedDate = $dateTime->format('Y-m-d');

global $wpdb;
$table_name_booking = $wpdb->prefix . 'booking_data'; 
$table_name_users = $wpdb->prefix . 'users';

$query = "SELECT bd.*, u.user_nicename, u.is_delete
          FROM $table_name_booking bd
          LEFT JOIN $table_name_users u ON JSON_UNQUOTE(JSON_EXTRACT(bd.booking_details, '$.member')) = u.ID
          WHERE bd.booking_details LIKE %s
          AND JSON_EXTRACT(bd.booking_details, '$.status') = '1'";

// Check if user is logged in and if their ID is not 1
$user_id = get_current_user_id();
if ($user_id !== 1) {
    $query .= $wpdb->prepare(" AND bd.user_id = %d", $user_id);
}

$query = $wpdb->prepare(
    $query,
    '%"datePicker":"%' . $formattedDate . '%"%'
);

$results = $wpdb->get_results($query, ARRAY_A);

header('Content-Type: application/json');
echo json_encode($results);

exit();
?>