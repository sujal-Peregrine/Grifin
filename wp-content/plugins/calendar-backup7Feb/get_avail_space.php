<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);
$selectedMonth = $data->selected_month;
$selectedYear = $data->selected_year;

$startDate = new DateTime("$selectedYear-$selectedMonth-01");
$endDate = clone $startDate;
$endDate->modify('last day of this month');

$datesAndRemainingSpaces = [];
$currentDate = clone $startDate;

global $wpdb;
$table_name = $wpdb->prefix . 'booking_data'; 

while ($currentDate <= $endDate) {
    $formattedDate = $currentDate->format('Y-m-d');
    $currentDateBack = clone $currentDate;
    $currentDateBack->modify('+1 day');
    if ($currentDateBack >= new DateTime()) {
        $query = $wpdb->prepare(
            "SELECT booking_details FROM $table_name WHERE booking_details LIKE %s AND booking_details NOT LIKE %s AND booking_details NOT LIKE %s",
            '%"datePicker":"%' . $formattedDate . '%"%',
            '%"status":"3"%',
            '%"status":"4"%'
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $totalGuests = 0;
        foreach ($results as $result) {
            $bookingDetails = json_decode($result['booking_details'], true);
            if (isset($bookingDetails['numberOfGuests'])) {
                $totalGuests += (int)$bookingDetails['numberOfGuests'];
            }
        }

        $table_name_available_spaces = $wpdb->prefix . 'available_spaces'; 
        $query_available_spaces = $wpdb->prepare(
            "SELECT available_spaces FROM $table_name_available_spaces WHERE my_calendar_date = %s",
            $formattedDate
        );

        $available_spaces_results = $wpdb->get_results($query_available_spaces);

        if (!empty($available_spaces_results) && isset($available_spaces_results[0]->available_spaces)) {
            $available_spaces = (int)$available_spaces_results[0]->available_spaces;
        } else {
            $available_spaces = 20;
        }
        $remainingSpaces = ($available_spaces - $totalGuests);

        $datesAndRemainingSpaces[$formattedDate] = ($remainingSpaces <= 0) ? 'Full' : $remainingSpaces;
    }

    $currentDate->modify('+1 day');
}

header('Content-Type: application/json');
echo json_encode(['selected_month' => $selectedMonth, 'selected_year' => $selectedYear, 'datesAndRemainingSpaces' => $datesAndRemainingSpaces]);
exit();
