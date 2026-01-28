<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

global $wpdb;
$table_name_booking = $wpdb->prefix . 'booking_data'; 

$user_id = get_current_user_id();
$query = $wpdb->prepare(
    "SELECT bd.*, u.user_nicename, u.is_delete
    FROM $table_name_booking bd
    LEFT JOIN $wpdb->users u ON JSON_UNQUOTE(JSON_EXTRACT(bd.booking_details, '$.member')) = u.ID
    WHERE bd.user_id = %d",
    $user_id
);

$results = $wpdb->get_results($query, ARRAY_A);

header('Content-Type: application/json');
echo json_encode($results);

exit();
?>
