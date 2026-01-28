<?php
/*
Plugin Name: Bookings Plugin Plugin
Description: A simple plugin to Bookings Calendar integration.
Version: 1.0
Author: Island
*/
// Enqueue Bookings scripts
function add_status_column_to_waitlist_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'waitlist_data';
    $charset_collate = $wpdb->get_charset_collate();
    // Check if the column exists
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'status'");
    // If column does not exist, add it
    if (!$column_exists) {
        $sql = "ALTER TABLE $table_name ADD COLUMN status INT DEFAULT 0";
        $wpdb->query($sql);
    }
}
add_status_column_to_waitlist_table();
register_activation_hook(__FILE__, 'add_status_column_to_waitlist_table');
function create_booking_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_data';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        booking_details text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
function create_waitlist_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'waitlist_data';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        waitlist_details text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
function enqueue_bookings_scripts()
{
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css');
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', array(), '5.3.2');
    // Enqueue jQuery
    wp_enqueue_script('jquery', 'https://code.jquery.com/jquery-3.7.1.min.js', array(), '3.7.1', true);
    // Enqueue DataTables JS
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', array('jquery'), '1.13.7', true);
    // Enqueue Bootstrap JS
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.2', true);
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array('jquery'), null, true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10');
}
add_action('admin_enqueue_scripts', 'enqueue_bookings_scripts');
function my_bookings_menu()
{
    add_menu_page('Bookings', 'Bookings', 'manage_options', 'bookings', 'bookings_page');
    add_submenu_page(null, 'View Bookings', 'View Bookings', 'manage_options', 'booking_view', 'booking_view');
}
add_action('admin_menu', 'my_bookings_menu');
function map_status($status_code) {
    switch ($status_code) {
        case 4:
            return 'Cancelled';
        case 1:
            return 'Approved';
        case 2:
            return 'Pending';
        case 3:
            return 'Declined';
        default:
            return 'Unknown';
    }
}

function format_booking_dates($datePicker) {
    // Split the date range into individual dates
    $dates = explode(',', $datePicker);
    
    // If there's only one date, directly format and return it
    if (count($dates) === 1) {
        return date('jS F Y', strtotime($dates[0]));
    }
    
    // Format each date individually
    $formatted_dates = array_map(function($date) {
        return date('jS F Y', strtotime($date));
    }, $dates);
    
    // Join the formatted dates with ' to ' if there are multiple dates
    return $formatted_dates[0] . ' to ' . end($formatted_dates);
}

function booking_view() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_data';
    ?>
    <style>
        .card {
            min-width: 100% !important;
        }
        .connected-members-card {
            margin-top: 20px;
        }
    </style>
    <div class="wrap">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Booking Details</h5>
                    <div class="flex-shrink-0">
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=bookings')); ?>">Booking List</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table">
                    <tbody>
                        <?php
                        if (isset($_GET['booking_id'])) {
                            $booking_id = intval($_GET['booking_id']);
                            $booking_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $booking_id));
                            if ($booking_data) {
                                $booking_details = json_decode($booking_data->booking_details, true); // Decode JSON string to array
                                ?>
                                <tr>
                                    <td><strong>Booking ID:</strong></td>
                                    <td><?php echo $booking_data->id; ?></td>
                                </tr>
                                <tr>
    <td><strong>Member:</strong></td>
    <td>
        <?php 
            $member_id = $booking_details['member'];
            $first_name = get_user_meta($member_id, 'first_name', true);
            $last_name = get_user_meta($member_id, 'last_name', true);
            $full_name = trim($first_name . ' ' . $last_name);
            echo esc_html($full_name);
        ?>
    </td>
</tr>

                                <tr>
                                    <td><strong>Booking Dates:</strong></td>
                                    <td><?php echo format_booking_dates($booking_details['datePicker']); ?></td>
                                </tr>

                                <tr>
                                    <td><strong>Number of Guests:</strong></td>
                                    <td><?php echo $booking_details['numberOfGuests']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Arrival Method:</strong></td>
                                    <td><?php echo $booking_details['arrivalMethod']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Gender:</strong></td>
                                    <td><?php echo $booking_details['gender']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>No. of Hunters:</strong></td>
                                    <td><?php echo $booking_details['numberOfHunters']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Bring Dog:</strong></td>
                                    <td><?php echo $booking_details['bringDog']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Dog Breed:</strong></td>
                                    <td><?php echo $booking_details['breed']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Dog Gender:</strong></td>
                                    <td><?php echo $booking_details['dogGender']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Preferred Hunt:</strong></td>
                                    <td><?php echo $booking_details['preferredHunt']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Special Instructions:</strong></td>
                                    <td><?php echo $booking_details['specialInstructions']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td><?php echo map_status($booking_details['status']); ?></td>
                                </tr>
                               
                                <?php
                            } else {
                                ?>
                                <tr>
                                    <td colspan="2">
                                        <div class="alert alert-danger" role="alert">
                                            <p>No booking found for the provided ID.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="2">
                                    <div class="alert alert-danger" role="alert">
                                        <p>Booking ID not provided.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
function my_bookings_submenu()
{
    add_submenu_page(
        'bookings',        // Parent menu slug
        'Waitlist',        // Page title
        'Waitlist',        // Menu title
        'manage_options',   // Capability required
        'waitlist',        // Menu slug
        'waitlist_page'    // Callback function to display the page content
    );
}
add_action('admin_menu', 'my_bookings_submenu');
function waitlist_page()
{
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array('jquery'), null, true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10');
    ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .header {
            background-color: #f4f9ff;
        }
        .table.dataTable {
            width: 100% !important;
        }
        .member-acc {
            padding-top: 10px;
            margin-top: 1rem;
            background-color: #fafafa;
            box-shadow: 0 0 5px #a1a1a114;
        }
        .tabs p {
            padding-top: 6px;
            margin-bottom: 0 !important;
            font-size: 1rem !important;
        }
        h1 {
            font-size: 2rem !important;
            margin-bottom: 1rem !important;
        }
        .title p>input {
            margin-top: 1rem;
            width: 40px;
            height: 25px;
            outline: none;
        }
        h6 {
            font-weight: 400 !important;
            font-size: 1.1rem !important;
            margin-top: 0.6rem !important;
        }
        h6 input {
            padding: 0 5px;
            height: 35px;
            outline: none;
            border-radius: 5px;
            border: 1px solid #c6c6c6 !important;
        }
        #myTable span {
            color: #58adf9;
        }
        .manage-booking {
            background-color: #fafafa;
        }
        #myTable th {
            padding: 10px;
            font-size: 15px;
            font-weight: 400;
            color: #545454;
        }
        td {
            color: #616060;
            font-weight: 400;
            font-size: 14px;
            padding: 10px;
        }
        .buttons {
            padding-left: 12px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:active {
            border: 1px solid #8b8b8b;
            padding: 6px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            margin-left: 12px !important;
            border: 1px solid #8b8b8b;
            border-radius: 5px;
            padding: 6px 12px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #8b8b8b !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            color: #fff !important;
            background-color: #007bff !important;
        }
        .card {
            min-width: 100% !important;
            padding: 0;
        }
        hr {
            opacity: 0.1;
        }
        .btn-hover:hover {
            color: white !important;
        }
        #myTable_paginate {
            position: absolute;
            bottom: -2%;
            right: 2%;
        }
    </style>
    <style>
        #bookingModal {
            top: 170px !important;
        }
        .fc-event-main {
            cursor: pointer !important;
        }
        .fc-day-disabled {
            visibility: hidden;
        }
        .font-size-12 {
            font-size: 12px;
        }
        .fc-daygrid-day-frame {
            display: inline !important;
        }
        .text-blue {
            color: blue !important;
        }
        .modal-content {
            --vz-modal-margin: 1.75rem;
            margin: var(--vz-modal-margin);
        }
        .booking td {
            font-size: 13px;
            font-weight: 500;
        }
        .booking input {
            border: 1px solid #d3d8de;
        }
        .booking th {
            font-size: 18px;
            font-weight: 300;
        }
        .arr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 43px;
            width: 100%;
            border: 1px solid #d3d8de;
            border-radius: 4px;
        }
        .arr input {
            width: 100%;
            border: none;
        }
        .arr img {
            width: 30px;
            padding-right: 16px;
        }
        .arr img {
            width: 30px;
            padding-right: 16px;
        }
        .booking-modal {
            width: 85% !important;
        }
        .booking-tr {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 5rem !important;
            border-bottom: 1px solid #00000026 !important;
        }
        .fc-event,
        {
        cursor: pointer !important;
        }
        .booking td {
            font-size: 13px;
            font-weight: 500;
        }
        .booking input {
            border: 1px solid #d3d8de;
        }
        .booking th {
            font-size: 18px;
            font-weight: 300;
        }
        .arr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 43px;
            width: 100%;
            border: 1px solid #d3d8de;
            border-radius: 4px;
        }
        .arr input {
            width: 70%;
            border: none;
        }
        .arr img {
            width: 30px;
            padding-right: 16px;
        }
        #upcoming-event-list {
            max-height: 400px;
            overflow-y: hidden;
            transition: overflow-y 0.3s ease;
        }
        #upcoming-event-list:hover {
            overflow-y: auto;
        }
        #upcoming-event-list::-webkit-scrollbar {
            width: 6px;
        }
        #upcoming-event-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }
        #upcoming-event-list::-webkit-scrollbar-track {
            background-color: transparent;
        }
        .event-card {
            margin-bottom: 10px;
        }
        #event-category {
            text-align-last: center;
        }
        #suggestion-box .user-suggestion {
            cursor: pointer;
            padding: 5px;
        }
        #suggestion-box .user-suggestion:hover {
            background-color: #f0f0f0;
        }
        .mandatory-note
            {
                left: 20px;
  position: absolute;
  font-size: 13px;
  font-style: italic;
            }

            .pending {
    color: #8B8000!important;
}

.approved {
    color: green!important;
}

.declined {
    color: red!important;
}

            
    </style>
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header mb-0 pb-0">
                    <h5 class="modal-title" id="exampleModalLabel">Booking Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr>
                <div class="modal-body pt-0 booking">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-6 border">
                                <table class="table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th colspan="2"><strong>Booking Details</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Member <span class="text-danger">*</span></td>
                                            <td>
                                            <select class="form-select" id="member-select" required>
    <option value="" disabled selected>Select Member</option>
    <?php
    $current_user_id = get_current_user_id();

    if ($current_user_id == 1) {
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->users} WHERE is_delete = 0";
        $users = $wpdb->get_results($query);
        foreach ($users as $user) {
            $full_name = get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true);
            echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($full_name) . '</option>';
        }
    } else {
        $current_user = get_userdata($current_user_id);
        if ($current_user) {
            $full_name = get_user_meta($current_user_id, 'first_name', true) . ' ' . get_user_meta($current_user_id, 'last_name', true);
            echo '<option value="' . esc_attr($current_user_id) . '">' . esc_html($full_name) . '</option>';
        }
    }
    ?>
</select>

                                                <span id="member-alert" class="text-danger"></span> <!-- Span for alert -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Arrival and Departure Date <span class="text-danger">*</span></td>
                                            <td>
                                                <div class="arr"> <!-- Ruchit -->
                                                    <input type="text" class="form-control flatpickr" id="date-picker" disabled
                                                        required>
                                                    <img src="https://icons.veryicon.com/png/o/miscellaneous/administration/calendar-335.png"
                                                        id="booking_img" alt="">
                                                </div>
                                                <span id="date-picker-alert" class="text-danger"></span>
                                                <!-- Span for alert -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td># Total Group Size <span class="text-danger">*</span></td>
                                            <td>
                                                <div class="arr"> <!-- Ruchit -->
                                                    <input type="text" class="form-control" id="number-of-guests" disabled
                                                        pattern="[0-9]*"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                                                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/99/Sample_User_Icon.png"
                                                        alt="">
                                                </div>

                                                <input type="hidden" name="request_id" id="request_id">
                                                <span id="number-of-guests-alert" class="text-danger"></span>
                                                <!-- Span for alert -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Arrival Method <span class="text-danger">*</span></td>
                                            <td>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="arrivalMethod"
                                                        id="boat" value="Boat" required>
                                                    <label class="form-check-label" for="boat">Boat</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="arrivalMethod"
                                                        id="self" value="Self" required>
                                                    <label class="form-check-label" for="self">Self</label>
                                                </div>
                                                <span id="arrivalMethod-alert" class="text-danger"></span>
                                                <!-- Span for alert -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Gender <span class="text-danger">*</span></td>
                                            <td>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="gender" id="stag"
                                                        value="Stag" required>
                                                    <label class="form-check-label" for="stag">Stag</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="gender" id="mixed"
                                                        value="Mixed" required>
                                                    <label class="form-check-label" for="mixed">Mixed</label>
                                                </div>
                                                <span id="gender-alert" class="text-danger"></span> <!-- Span for alert -->
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Number of Hunters <span class="text-danger">*</span></td>
                                            <td>
                                                <input type="text" class="form-control" id="number-of-hunters"
                                                    pattern="[0-9]*"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 border">
                                <table class="table ">
                                    <thead class="thead-light">
                                        <tr>
                                        <th colspan="2"><strong>Hunting Information (Optional)</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                        <tr>
                                            <td>Bring Your Own Dog</td>
                                            <td>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="bringDog" id="bringDogYes" value="Yes">
                                                    <label class="form-check-label" for="bringDogYes">Yes</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="bringDog" id="bringDogNo" value="No">
                                                    <label class="form-check-label" for="bringDogNo">No</label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Breed</td>
                                            <td>
                                                <input type="text" class="form-control" id="breed">
                                            </td>
                                        </tr>
                                        <tr>
    <td>Dog Gender</td>
    <td>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="dogGender" id="male" value="Male">
            <label class="form-check-label" for="male">Male</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="dogGender" id="female" value="Female">
            <label class="form-check-label" for="female">Female</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="dogGender" id="not_applicable" value="" checked>
            <label class="form-check-label" for="not_applicable">N/A</label>
        </div>
    </td>
</tr>
                                        <tr>
                                            <td>Preferred Hunt</td>
                                            <td>
                                                <select class="form-select" id="preferred-hunt">
                                                    <option value="No Preference" selected>No Preference</option>
                                                    <option value="mixed">Mixed</option>
                                                    <option value="pheasant">Pheasant</option>
                                                    <option value="chukar">Chukar</option>
                                                    <option value="white-tail-deer">White-tail Deer</option>
                                                    <option value="turkey">Turkey</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Special Instructions/Requirements</td>
                                            <td>
                                                <textarea class="form-control" rows="3"
                                                    id="special-instructions"></textarea>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
    <span class="mandatory-note">Note: <span class="text-danger">*</span> fields are mandatory</span>

    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeModal">Close</button>
                    <button type="button" class="btn btn-primary" id="save-booking">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card w-100">
        <div class="card-header pb-0 w-100">
            <h3>Manage Waitlist</h3>
        </div>
        <div class="card-body pt-0">
            <div>
                <div class="table-responsive-waitlist table-card mb-1">
                    <table class="table table-nowrap align-middle" id="myDatatableWaitlist">
                        <thead class="text-muted table-light">
                            <tr class="text-uppercase">
                                <th class="sort" data-sort="id"> ID</th>
                                <th class="sort" data-sort="customer_name">Club Member</th>
                                <th class="sort" data-sort="status">Number of guests </th>
                                <th class="sort" data-sort="status">Preferred Date</th>
                                <th class="sort" data-sort="status">Status</th>
                            </tr>
                        </thead>
                        <tbody class="border-1">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            let adminUrl = '<?php echo admin_url('admin.php?page=view_member_page&user_id='); ?>';
            let dataTableWaitlist = $('#myDatatableWaitlist').DataTable({
                order: [[0, 'desc']],
                columns: [
                    { data: 'id' },
                    {
                        data: 'user_nicename',
                        render: function (data, type, row) {
                            const waitlistDetails = JSON.parse(row.waitlist_details);
                            const link = '<a href="' + adminUrl + waitlistDetails.member + '">' + data + '</a>';
                            return link;
                        }
                    },
                    {
                        data: 'waitlist_details',
                        render: function (data, type, row) {
                            const waitlistDetails = JSON.parse(data);
                            return waitlistDetails.number_of_hunters;
                        }
                    },
                    {
                        data: 'waitlist_details',
                        render: function (data, type, row) {
                            const waitlistDetails = JSON.parse(data);
                            return waitlistDetails.prefered_date;
                        }
                    },
                    {
    data: 'status',
    render: function (data, type, row) {
        const isDeclined = data === '2';
        const isPending = data === '0';
        const isApproved = data === '1';
        let colorClass = '';
        
        if (isPending) {
            colorClass = 'pending';
        } else if (isApproved) {
            colorClass = 'approved';
        } else if (isDeclined) {
            colorClass = 'declined';
        }

        const dropdownHTML = `<select class="form-control status-dropdown ${colorClass}" data-booking-id="${row.id}" ${isDeclined ? 'disabled' : ''}>
                <option value="1" ${data == 1 ? 'selected' : ''} style="color:black;">Approved</option>
                <option value="0" ${data == 0 ? 'selected' : ''} style="color:black;">Pending</option>
                <option value="2" ${data == 2 ? 'selected' : ''} style="color:black;">Declined</option>
            </select>`;
        return dropdownHTML;
    }
}


                ],
                drawCallback: function () {
                    $('.status-dropdown').on('change', function () {
                        const bookingId = $(this).data('booking-id');
                        const newStatus = $(this).val();
                        console.log(newStatus);
                        if (newStatus === '1') {
                            openModal(bookingId);
                        } else {
                            Swal.fire({
                                title: 'Are you sure?',
                                text: 'Do you really want to update the status?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, update it!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    updateBookingStatus(bookingId, newStatus);
                                } else {
                                    $(this).val($(this).data('previous-status'));
                                }
                            });
                        }
                    });
                }
            });

    
            
            function openModal(bookingId) {
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'get_booking_details',
            booking_id: bookingId
        },
        success: function(response) {
            try {
                const responseData = response;
                if (responseData.success) {
                    const data = responseData.data;
                    console.log(data);
                    // Populate modal fields with data
                    $('#member-select').val(data.member);
                    $('#date-picker').val(data.prefered_date);
                    $('#number-of-guests').val(data.number_of_hunters);
                    $('#number-of-hunters').val(data.number_of_guests);
                    $('#preferred-hunt').val(data.preferred_hunt);
                    $('#special-instructions').val(data.special_instructions);
            
                    // For radio buttons and checkboxes
                    $('input[name="arrivalMethod"][value="' + data.arrival_method + '"]').prop('checked', true);
                    $('input[name="gender"][value="' + data.gender + '"]').prop('checked', true);
                    $('input[name="bringDog"][value="' + data.bring_dog + '"]').prop('checked', true);
                    $('input[name="dogGender"][value="' + data.dog_gender + '"]').prop('checked', true);
                
                    


                    $('#request_id').val(bookingId);
                    $('#exampleModalCenter').modal('show');
                } else {
                    console.error('Error: Success is false in the response');
                }
            } catch (error) {
                console.error('JSON parsing error: ' + error);
            }
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText);
        }
    });
    // Save booking button click event listener
    document.getElementById('save-booking').addEventListener('click', function () {
        this.disabled = true;


var member = document.querySelector('#member-select').value;
var datePicker = document.querySelector('#date-picker').value;
var numberOfGuests = document.querySelector('#number-of-guests').value;
var arrivalMethod = document.querySelector('input[name="arrivalMethod"]:checked');
var gender = document.querySelector('input[name="gender"]:checked');
var numberOfHunters = document.querySelector('#number-of-hunters').value;
var breed = document.querySelector('#breed').value;
var dogGender = document.querySelector('input[name="dogGender"]:checked');
var bringDog = document.querySelector('input[name="bringDog"]:checked');
var preferredHunt = document.querySelector('#preferred-hunt').value;
var specialInstructions = document.querySelector('#special-instructions').value;

var emptyFields = [];
if (member === '') emptyFields.push('Member');
if (datePicker === '') emptyFields.push('Date Picker');
if (numberOfGuests === '') emptyFields.push('Number of Guests');
if (!arrivalMethod) emptyFields.push('Arrival Method');
if (!numberOfHunters) emptyFields.push('Number Of Hunters');
if (!gender) emptyFields.push('Gender');
// if (numberOfHunters === '') emptyFields.push('Number of Hunters');
// if (breed === '') emptyFields.push('Breed');
// if (!dogGender) emptyFields.push('Dog Gender');
// if (!bringDog) emptyFields.push('Bring Dog');

// if (preferredHunt === '') emptyFields.push('Preferred Hunt');
// if (specialInstructions === '') emptyFields.push('Special Instructions');

if (emptyFields.length > 0) {
    Swal.fire({
        title: 'Empty Fields',
        html: 'Please fill the following fields before submitting:<br>' + emptyFields.join('<br>'),
        icon: 'info',
        confirmButtonText: 'OK'
    });
    document.getElementById('save-booking').disabled = false;
    return; 
}
        // Prepare form data for AJAX request
        var formData = {
                                                                        'member': document.querySelector('#member-select').value,
                                                                        'datePicker': document.querySelector('#date-picker').value,
                                                                        'numberOfGuests': document.querySelector('#number-of-guests').value,
                                                                        'arrivalMethod': document.querySelector('input[name="arrivalMethod"]:checked').value,
                                                                        'gender': document.querySelector('input[name="gender"]:checked').value,
                                                                        'request_id': document.querySelector('#request_id').value,
                                                                        'numberOfHunters': document.querySelector('#number-of-hunters').value

                                                                        // 'specialInstructions': document.querySelector('#special-instructions').value
                                                                    };
                                                                    if (breed !== '') formData['breed'] = breed;
                                                                    if (dogGender) formData['dogGender'] = dogGender.value;
                                                                    if (bringDog) formData['bringDog'] = bringDog.value;
                                                                    if (preferredHunt !== '') formData['preferredHunt'] = preferredHunt;
                                                                    if (specialInstructions !== '') formData['specialInstructions'] = specialInstructions;
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var nonce = '<?php echo wp_create_nonce("save-booking-data"); ?>';
        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'save_booking_datas',
                security: nonce,
                data: formData
            },
            success: function (response) {
                // Handle AJAX response
                console.log(response); // Log the response to the console
                if (response.remaining_guests !== undefined) {
                    Swal.fire({
                        title: 'No Spaces',
                        text: response.remaining_guests + ' guests are available for this date.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Bookings Proceed for Approval',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function () {
                        location.reload();
                    });
                } else if (response.noSpace !== undefined) {
                    Swal.fire({
                        title: 'Available Spaces',
                        text: 'Sorry, No space for this date.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function (error) {
                console.error(error);
            },
            complete: function () {
                // Re-enable the button after completion (whether success or error)
                document.getElementById('save-booking').disabled = false;
            }
        });
    });
}
flatpickr('#date-picker', {});
            loadWaitlistData();

            document.getElementById("closeModal").addEventListener("click", function() {
    loadWaitlistData();
});





            function updateBookingStatus(bookingId, newStatus) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_waitlist_booking',
                        booking_id: bookingId,
                        new_status: newStatus
                    },
                    success: function (response) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Status Updated',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(function () {
                            loadWaitlistData();
                        });
                    },
                    error: function (xhr, status, error) {
                    }
                });
            }
            function loadWaitlistData() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_waitlist_data',
                        security: '<?php echo wp_create_nonce('waitlist_nonce'); ?>'
                    },
                    success: function (response) {
                        dataTableWaitlist.clear().draw();
                        if (response.length > 0) {
                            $.each(response, function (index, waitlist) {
                                waitlist.user_nicename = waitlist.user_nicename || '';
                                waitlist.number_of_hunters = waitlist.waitlist_details ? JSON.parse(waitlist.waitlist_details).number_of_hunters : '';
                                waitlist.prefered_date = waitlist.waitlist_details ? JSON.parse(waitlist.waitlist_details).prefered_date : '';
                                dataTableWaitlist.row.add(waitlist).draw(false);
                            });
                        } else {
                        }
                    },
                    error: function (xhr, status, error) {
                    }
                });
            }
        });
    </script>
    <?php
}
function bookings_page()
{
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array('jquery'), null, true);
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10');
    ?>
    <style>
        
        #membersTable_paginate .paginate_button {
            margin: 0 5px;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 5px;
            color: #737373;
            border: 1px solid #ccc;
        }

        #membersTable_paginate .paginate_button.current {
            background-color: #007bff;
            color: #fff;
        }

        #membersTable thead th.sort {
            cursor: pointer;
        }

        #membersTable thead th.sort::after,
        #membersTable thead th.sort::before {
            content: '';
            display: inline-block;
            vertical-align: middle;

        }

        #membersTable thead th.sort {
            width: 50%;

            cursor: pointer;
            white-space: nowrap;
        }

        #membersTable thead th.sort::after {
            content: '\25B2';
        }

        #membersTable thead th.sort.desc::after {
            content: '\25BC';
        }

        #membersTable thead th.sort::after,
        #membersTable thead th.sort::before {
            color: #007bff;
            font-size: 12px;
            margin-top: -5px;
        }

        #membersTable thead th.sort.asc::after,
        #membersTable thead th.sort.desc::after {
            color: #ff0000;
            font-weight: bold;
        }

        .card {
            min-width: 100% !important;
            padding: 0
        }

        #membersTable_filter input[type="search"] {

            box-sizing: border-box;
            border: 0.2px solid #ccc;
            border-radius: 4px;

            margin-bottom: 5px;
            background: #f3f6f9;
        }

        .tooltip-inner {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }

        .table-responsive {
            overflow-y: hidden !important;

        }

        #membersTable tbody tr {
            font-size: 15px;

        }

        #membersTable thead th {
            font-size: 14px;
            white-space: nowrap;
            color: #878a99;
            font-weight: 400;
            line-height: 1.2;

        }

        #membersTable tbody td {
            font-size: 13px;
            white-space: nowrap;
            line-height: 1.2;

        }

        #membersTable tbody tr {
            line-height: 10px;

        }

        #membersTable thead tr {
            line-height: 10px;

        }

        .hr {
            opacity: 0.1;
        }

        .search {
            width: 100%;
            height: 40px;
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 0 10px;
            background-color: #f3f6f9;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .search input,
        .all input {
            width: 100%;
            outline: none !important;
            border: none !important;
            background-color: transparent;
        }

        .all select {
            width: 100%;
            outline: none !important;
            border: none !important;
            background-color: transparent;
        }

        .date input {
            width: 100%;
            height: 40px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background-color: #f3f6f9;

        }

        .search-icon {
            width: 15px;
            opacity: 0.5;
        }

        .all {
            width: 100%;
            height: 40px;
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 0 10px;
            background-color: #f3f6f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .all img {
            width: 15px;
        }

        .image-container img {
            height: 100%;
            width: 100%;
            object-fit: contain;
        }

        .image-container {
            height: 25px;
            width: 25px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;

        }

        .text-overlay {
            text-align: center;
            background: #000;
            color: #fff;
            position: absolute;
            top: -45%;
            border-radius: 5px;
            margin-left: -1rem;
            clip-path: polygon(0% 0%, 100% 0%, 100% 75%, 65% 75%, 49% 100%, 30% 75%, 0% 75%);
            padding: 11px 10px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .text-overlay p {
            margin-bottom: 5px;
        }

        .image-container:hover .text-overlay {
            opacity: 1;
        }

        .more {
            height: 25px;
            width: 25px;
            border-radius: 50%;
            background-color: #6691e7;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .more h4 {
            margin-bottom: 0;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
        }

        .inline-block-style {
            display: inline-block;
            vertical-align: top;

        }

        #membersTable_paginate {
            position: absolute;
            bottom: -2%;
            right: 0;
        }
        .email-item {
    padding: 5px;
    cursor: pointer;
}

.email-item:hover {
    background-color: #007bff; /* Primary blue */
    color: #fff; /* White text */
}

    
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .header {
            background-color: #f4f9ff;
        }
        .table.dataTable {
            width: 100% !important;
        }
        .member-acc {
            padding-top: 10px;
            margin-top: 1rem;
            background-color: #fafafa;
            box-shadow: 0 0 5px #a1a1a114;
        }
        .tabs p {
            padding-top: 6px;
            margin-bottom: 0 !important;
            font-size: 1rem !important;
        }
        h1 {
            font-size: 2rem !important;
            margin-bottom: 1rem !important;
        }
        .title p>input {
            margin-top: 1rem;
            width: 40px;
            height: 25px;
            outline: none;
        }
        h6 {
            font-weight: 400 !important;
            font-size: 1.1rem !important;
            margin-top: 0.6rem !important;
        }
        h6 input {
            padding: 0 5px;
            height: 35px;
            outline: none;
            border-radius: 5px;
            border: 1px solid #c6c6c6 !important;
        }
        #myTable span {
            color: #58adf9;
        }
        .manage-booking {
            background-color: #fafafa;
        }
        #myTable th {
            padding: 10px;
            font-size: 15px;
            font-weight: 400;
            color: #545454;
        }
        td {
            color: #616060;
            font-weight: 400;
            font-size: 14px;
            padding: 10px;
        }
        .buttons {
            padding-left: 12px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:active {
            border: 1px solid #8b8b8b;
            padding: 6px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            margin-left: 12px !important;
            border: 1px solid #8b8b8b;
            border-radius: 5px;
            padding: 6px 12px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #8b8b8b !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            color: #fff !important;
            background-color: #007bff !important;
        }
        .card {
            min-width: 100% !important;
            padding: 0;
        }
        hr {
            opacity: 0.1;
        }
        .btn-hover:hover {
            color: white !important;
        }
        #myTable_paginate {
            position: absolute;
            bottom: -2%;
            right: 2%;
        }
    </style>
    <div class="card w-100">
        <div class="card-header pb-0 w-100">
            <h3>Manage Bookings</h3>
        </div>
        <hr>
        <div class="container">
                <div class="row">
                    <!-- <div class="col">
                        <div class="search">
                            <img class="search-icon"
                                src="https://uxwing.com/wp-content/themes/uxwing/download/user-interface/search-icon.png"
                                alt="">
                                <input type="text" placeholder="Search By Email" id="email_get">
                        </div>
                    </div> -->
                    <div class="col date">
                        <input type="text" id="datepicker" placeholder="Select date range">
                    </div>
                    <div class="col">
                        <div class="all">
                            <!-- <input type="text" placeholder="All"> -->
                            <select id="filter_members" name="filter_members[]" multiple>
    <option value="" disabled selected>Select users</option> <!-- Placeholder option -->
    <?php
    global $wpdb;

    $users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $wpdb->users WHERE is_delete = %d ORDER BY display_name ASC", // Order by display name ascending
            0
        )
    );

    foreach ($users as $user) {
        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
    }
    ?>
</select>

                            <img src="https://static.thenounproject.com/png/1123247-200.png" alt="">

                        </div>
                    </div>
                    <div class="col">
                        <button class="btn btn-outline-secondary" type="button" id="button-addon2"><i class="fa fa-search"
                                aria-hidden="true"></i> Search
                        </button>
                        <button class="btn btn-outline-success" type="button" id="clear-button"><i class="fas fa-undo"
                                aria-hidden="true"></i>Clear</button>
                    </div>
                </div>
            </div>
        <div class="card-body">
            <div class="card-body pt-0">
                <div>
                    <ul class="nav nav-tabs nav-tabs-custom nav-success mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  All active " data-bs-toggle="tab" id="All" role="tab"
                                aria-selected="false" tabindex="-1">
                                <i class="ri-store-2-fill me-1 align-bottom"></i> All
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  Pending text-dark btn-hover btn btn-warning " data-bs-toggle="tab"
                                id="Pending" role="tab" aria-selected="false" tabindex="-1">
                                <i class="ri-store-2-fill me-1 align-bottom"></i> Pending
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  Cancelled text-dark btn-hover btn btn-secondary" data-bs-toggle="tab"
                                id="Cancelled" role="tab" aria-selected="false" tabindex="-1">
                                <i class="ri-checkbox-circle-line me-1 align-bottom"></i> Cancelled
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  Approved text-dark btn-hover btn btn-success" data-bs-toggle="tab"
                                id="Approved" role="tab" aria-selected="false" tabindex="-1">
                                <i class="ri-truck-line me-1 align-bottom"></i> Approved
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  Declined text-dark btn-hover btn btn-danger" data-bs-toggle="tab"
                                id="Declined" role="tab" aria-selected="false" tabindex="-1">
                                <i class="ri-arrow-left-right-fill me-1 align-bottom"></i> Declined
                            </button>
                        </li>
                    </ul>
                    <div class="table-responsive table-card mb-1">
                        <table class="table table-nowrap align-middle" id="myTable">
                            <thead class="text-muted table-light">
                                <tr class="text-uppercase">
                                    <th class="sort" data-sort="id"> ID</th>
                                    <th class="sort" data-sort="customer_name">Member</th>
                                    <th class="sort" data-sort="product_name">Requested</th>
                                    <th class="sort" data-sort="date">Start date</th>
                                    <th class="sort" data-sort="amount">End date</th>
                                    <th class="sort" data-sort="payment">Cost</th>
                                    <th class="sort" data-sort="status">Number of guests </th>
                                    <th class="sort" data-sort="status">Status(#waiting)</th>
                                    <th class="sort" data-sort="editStatus">Edit Status</th>
                                </tr>
                            </thead>
                            <tbody class="border-1">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function ($) {

            $('#button-addon2').on('click', function () {
                var selectedDate = $('#datepicker').val();
                var email = $('#email_get').val();
                var connected_members = $('#filter_members').val();
                loadBookingData('All',selectedDate, email, connected_members);

            });

            $('#clear-button').on('click', function () {
    $('#datepicker').val('');
    $('#email_get').val('');
    
    // Clear Select2 dropdown
    $('#filter_members').val(null).trigger('change');

    // Reinitialize Flatpickr
    flatpickr("#datepicker", {
        mode: 'range', // Enable date range selection
        dateFormat: 'Y-m-d',
    });

    loadBookingData('All');
});

flatpickr("#datepicker", {
    mode: 'range', // Enable date range selection
    dateFormat: 'Y-m-d',
});



            $('#filter_members').select2();
            // Initialize DataTable with an empty dataset
            // let dataTable = $('#myTable').DataTable({
            //     order: [[0, 'desc']], // Assuming the user ID column is at index 0 in your booking table
            // });
            let adminUrl = '<?php echo admin_url('admin.php?page=booking_view&booking_id='); ?>';
let dataTable = $('#myTable').DataTable({
    order: [[0, 'desc']],
    searching: false,
    lengthChange: false,
    columns: [
        { data: 'id' },
        {
            data: 'member',
            render: function (data, type, row) {
                const link = '<a href="' + adminUrl + row.id + '">' + data + '</a>'; // Using row.id instead of row.user_id
                return link;
            }
        },
        { data: 'requested_Date' },
        { data: 'start_date' },
        { data: 'end_date' },
        { data: 'cost' },
        { data: 'numberOfGuests' },
        {
            data: 'status',
            render: function (data, type, row) {
                return getStatusLabel(data);
            }
        },
        {
            data: 'status',
            render: function (data, type, row) {
                const isDeclined = data === 'Declined';
                const isCancelled = data === 'Cancelled';
                const dropdownHTML = `<select class="form-control status-dropdown" data-booking-id="${row.id}" ${isDeclined || isCancelled ? 'disabled' : ''}>
                                    <option value="Approved" ${data === 'Approved' ? 'selected' : ''}>Approved</option>
                                    <option value="Pending" ${data === 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Cancelled" ${data === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                                    <option value="Declined" ${data === 'Declined' ? 'selected' : ''}>Declined</option>
                                </select>`;
                return dropdownHTML;
            }
        }
    ],
    drawCallback: function () {
        $('.status-dropdown').off('change').on('change', function () {
            const bookingId = $(this).data('booking-id');
            const newStatus = $(this).val();
            const previousOption = $(this).find('option:selected');
            const previousStatus = $(this).data('previous-status');
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you really want to update the status?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateBookingStatus(bookingId, newStatus);
                } else {
                    $(this).val(previousStatus);
                }
            });
        });
    }
});


            var selectedDate = $('#datepicker').val();
            var email = '';
            var connected_members = '';
            // Use AJAX to get all booking data initially
            loadBookingData('All',selectedDate, email);


            // Function to load booking data based on the selected tab
            // Function to load booking data based on the selected tab
            function loadBookingData(status,selectedDate, email, connected_members) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_booking_data',
                        status: status,
                        selected_date: selectedDate,
                        email: email,
                        connected_members: connected_members
                    },
                    success: function (response) {
                        // Clear existing data in the table
                        dataTable.clear().draw();
                        // Handle the response and update the table
                        if (response.length > 0) {
                            $.each(response, function (index, booking) {
                                dataTable.row.add(booking).draw(false);
                            });
                            // Add change event listener to handle status changes
                            $('.status-dropdown').on('change', function () {
                                const bookingId = $(this).data('booking-id');
                                const newStatus = $(this).val();
                                const previousOption = $(this).find('option:selected');
                                // Store the previous status value
                                const previousStatus = $(this).data('previous-status');
                                // Log the previous status
                                console.log('Previous Status:', previousStatus);
                                // console.log(previousOption);
                                // Use SweetAlert for confirmation
                                Swal.fire({
                                    title: 'Are you sure?',
                                    text: 'Do you really want to update the status?',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#d33',
                                    confirmButtonText: 'Yes, update it!'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Call a function to update the status on the server
                                        updateBookingStatus(bookingId, newStatus);
                                    } else {
                                        // If user cancels, reset the dropdown to the previous value
                                        $(this).val($(this).data('previous-status'));
                                    }
                                });
                            });
                        } else {
                            // Handle empty response or error
                        }
                    },
                    error: function (xhr, status, error) {
                        // Handle AJAX error
                    },
                });
            }
            function updateBookingStatus(bookingId, newStatus) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_booking_status',
                        booking_id: bookingId,
                        new_status: newStatus,
                    },
                    success: function (response) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Status Updated',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(function () {
                            // Refresh the page if needed
                            // location.reload();
                            loadBookingData('All','','','');
                        });
                    },
                    error: function (xhr, status, error) {
                        // Handle AJAX error
                    },
                });
            }
            // Function to get status label based on status code
            function getStatusLabel(status) {
                switch (status) {
                    case 'Cancelled':
                        return '<span class="badge bg-secondary text-uppercase text-light">Cancelled</span>';
                    case 'Approved':
                        return '<span class="badge bg-success text-uppercase text-light">Approved</span>';
                    case 'Pending':
                        return '<span class="badge bg-warning text-uppercase text-light">Pending</span>';
                    case 'Declined':
                        return '<span class="badge bg-danger text-uppercase text-light">Declined</span>';
                    default:
                        return '<span style="color: red;">null</span>';
                }
            }
            // Handle tab click event
            $('.nav-tabs-custom button').on('click', function (e) {
                e.preventDefault();
                let status = $(this).attr('id');
                loadBookingData(status,'','','');
            });
        });
    </script>
    <?php
}
add_action('wp_ajax_save_booking_datas', 'save_booking_datas');
function save_booking_datas()
{
    global $wpdb;
    check_ajax_referer('save-booking-data', 'security');
    if (isset($_POST['data'])) {
        $user_id = get_current_user_id();
        $booking_data = $_POST['data'];
        
        $date_range = $booking_data['datePicker'];
        $selected_dates = explode(' to ', $date_range);
        
        $start_date = new DateTime($selected_dates[0]);
        $end_date = new DateTime($selected_dates[0]);
        if($selected_dates[1]!=null)
        {
            $end_date = new DateTime($selected_dates[1]);
        }
        $interval = new DateInterval('P1D');
        $date_range_array = array();
        $date_range_array[] = $start_date->format('Y-m-d');
        $current_date = clone $start_date;
        while ($current_date <= $end_date) {
            $date_range_array[] = $current_date->format('Y-m-d');
            $current_date->add($interval);
        }
        $date_range_array[] = $end_date->format('Y-m-d');
        $date_range_array = array_unique($date_range_array);
        $date_range_array = array_values($date_range_array);
        $formatted_dates = implode(',', $date_range_array);
        $booking_data['datePicker'] = $formatted_dates;
        $number_of_guests = $booking_data['numberOfGuests'];
        $total_booked_guests = get_total_booked_guests($formatted_dates, $user_id);
        $available_spaces = array();
        
        foreach ($date_range_array as $date) {
            $available_spaces[$date] = 20; 
            $table_name_available_spaces = $wpdb->prefix . 'available_spaces';
            $query_available_spaces = $wpdb->prepare(
                "SELECT available_spaces FROM $table_name_available_spaces WHERE my_calendar_date = %s",
                $date
            );
            $available_spaces_results = $wpdb->get_results($query_available_spaces);
            if (!empty($available_spaces_results) && isset($available_spaces_results[0]->available_spaces)) {
                $available_spaces[$date] = (int) $available_spaces_results[0]->available_spaces;
            }
        }
        // Check if available spaces are sufficient for booking
        $remaining_guests = min($available_spaces) - $total_booked_guests;
        if ($remaining_guests >= $number_of_guests) {
            // Sufficient space available, proceed with booking
            $booking_data['status'] = '1';
            $booking_details = sanitize_text_field(wp_json_encode($booking_data));
            $table_name = $wpdb->prefix . 'booking_data';
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'booking_details' => $booking_details,
                )
            );
            $wpdb->delete(
                'wp_waitlist_data',
                array(
                    'id' => $booking_data['request_id'] 
                )
            );
            $response = array(
                'success' => true,
                'message' => 'Data saved successfully!',
            );
        } else {
            // Insufficient space available
            if ($remaining_guests <= 0) {
                $response = array(
                    'noSpace' => true,
                    'message' => 'Sorry, no spaces left for this date.',
                );
            } else {
                $response = array(
                    'success' => false,
                    'message' => 'Sorry, only ' . $remaining_guests . ' guests are available for this date.',
                    'remaining_guests' => $remaining_guests,
                    'date' => $selected_dates[1]
                );
            }
        }
    }
    wp_send_json($response);
}
add_action('wp_ajax_get_booking_details', 'get_booking_details');
add_action('wp_ajax_nopriv_get_booking_details', 'get_booking_details');
function get_booking_details() {
    $booking_id = isset($_POST['booking_id']) ? $_POST['booking_id'] : '';
    global $wpdb;
    $table_name = $wpdb->prefix . 'waitlist_data';
    $query = $wpdb->prepare("SELECT waitlist_details FROM $table_name WHERE id = %d", $booking_id);
    $booking_details = $wpdb->get_var($query);
    if ($booking_details) {
        wp_send_json_success(json_decode($booking_details, true));
    } else {
        wp_send_json_error('Booking details not found');
    }
    wp_die();
}
add_action('wp_ajax_get_booking_data', 'get_booking_data');
add_action('wp_ajax_nopriv_get_booking_data', 'get_booking_data');
function get_booking_data()
{
    global $wpdb;

    $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : '';
    $connected_members = isset($_POST['connected_members']) ? $_POST['connected_members'] : array();

    // Parse the selected date range
    $date_range = explode(' to ', $selected_date);
    $start_date = isset($date_range[0]) ? sanitize_text_field($date_range[0]) : '';
    $end_date = isset($date_range[1]) ? sanitize_text_field($date_range[1]) : '';

    $status_map = array(
        'All' => '',
        'Cancelled' => '4',
        'Approved' => '1',
        'Pending' => '2',
        'Declined' => '3',
    );
    $status = isset($_POST['status']) ? $_POST['status'] : 'All';
    $status_value = isset($status_map[$status]) ? $status_map[$status] : '';
    $table_name = $wpdb->prefix . 'booking_data';

    $sql = "SELECT booking.*, 
            first_name.meta_value AS first_name,
            last_name.meta_value AS last_name
            FROM $table_name AS booking 
            LEFT JOIN $wpdb->users AS users ON CAST(JSON_UNQUOTE(JSON_EXTRACT(booking.booking_details, '$.member')) AS UNSIGNED) = users.ID 
            LEFT JOIN $wpdb->usermeta AS first_name ON (users.ID = first_name.user_id AND first_name.meta_key = 'first_name')
            LEFT JOIN $wpdb->usermeta AS last_name ON (users.ID = last_name.user_id AND last_name.meta_key = 'last_name')
            WHERE users.ID IS NOT NULL";

    if (!empty($start_date) && !empty($end_date)) {
        $sql .= $wpdb->prepare(" AND JSON_UNQUOTE(JSON_EXTRACT(booking.booking_details, '$.datePicker')) BETWEEN %s AND %s", $start_date, $end_date);
    }

    if (!empty($status_value)) {
        $sql .= $wpdb->prepare(" AND booking.booking_details LIKE %s", '%"status":"' . $status_value . '"%');
    }

    if (!empty($connected_members)) {
        $connected_members_string = implode(',', array_map('intval', $connected_members));
        $where_clause_connected_members = " AND JSON_UNQUOTE(JSON_EXTRACT(booking.booking_details, '$.member')) IN ($connected_members_string)";
        $sql .= $where_clause_connected_members;
    }

    // Exclude cancelled bookings
    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(booking.booking_details, '$.status')) != '4'";

    $booking_data = $wpdb->get_results($sql);
    $response = array();
    foreach ($booking_data as $booking) {
        $booking_details = json_decode($booking->booking_details, true);
        $dateRange = explode(',', $booking_details['datePicker']);
        $startDate = isset($dateRange[0]) ? $dateRange[0] : '';
        $endDate = isset($dateRange[count($dateRange) - 1]) ? $dateRange[count($dateRange) - 1] : '';
        $response[] = array(
            'id' => isset($booking->id) ? esc_html($booking->id) : null,
            'member' => isset($booking->first_name) && isset($booking->last_name) ? esc_html($booking->first_name . ' ' . $booking->last_name) : null,
            'user_id' => isset($booking_details['member']) ? esc_html($booking_details['member']) : null,
            'requested_Date' => isset($booking_details['requested_Date']) ? date('F j Y', strtotime($booking_details['requested_Date'])) : null,
            'start_date' => isset($startDate) ? date('F j Y', strtotime($startDate)) : null,
            'end_date' => isset($endDate) ? date('F j Y', strtotime($endDate)) : null,
            'cost' => isset($booking_details['cost']) ? esc_html($booking_details['cost']) : null,
            'numberOfGuests' => isset($booking_details['numberOfGuests']) ? esc_html($booking_details['numberOfGuests']) : null,
            'status' => empty($status_value) ? get_status_label($booking_details['status']) : get_status_label($status_value),
        );
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}





function get_status_label($status)
{
    switch ($status) {
        case 4:
            return 'Cancelled';
        case 1:
            return 'Approved';
        case 2:
            return 'Pending';
        case 3:
            return 'Declined';
        default:
            return null;
    }
}
function update_booking_status()
{
    global $wpdb;
    $booking_id = isset($_POST['booking_id']) ? $_POST['booking_id'] : '';
    $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
    $status_map = array(
        'Cancelled' => '4',
        'Approved' => '1',
        'Pending' => '2',
        'Declined' => '3',
    );
    if (!empty($booking_id) && !empty($new_status) && isset($status_map[$new_status])) {
        $table_name = $wpdb->prefix . 'booking_data';
        // Retrieve the existing booking details
        $existing_details = $wpdb->get_var($wpdb->prepare("SELECT booking_details FROM $table_name WHERE id = %d", $booking_id));
        if ($existing_details) {
            // Decode the JSON string
            $booking_details = json_decode($existing_details, true);
            // Update the status in the decoded array
            $booking_details['status'] = $status_map[$new_status];
            // Encode the updated array back to JSON
            $updated_details = json_encode($booking_details);
            // Update the status in the database
            $wpdb->update(
                $table_name,
                array('booking_details' => $updated_details),
                array('id' => $booking_id)
            );
            $user_id = $booking_details['member'];
            $user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM {$wpdb->prefix}users WHERE ID = %d", $user_id));
            $user_name = $wpdb->get_var($wpdb->prepare("SELECT user_login FROM {$wpdb->prefix}users WHERE ID = %d", $user_id));
            $subject = 'Booking Status Update';
            $message = "<div style='background-color: #f2f2f2; padding: 20px; border-radius: 5px;'>";
            $message .= "<p style='font-weight: bold;'>Hello {$user_name},</p>";
            $message .= "<p style='font-weight: bold; color: #333;'>Your booking has been {$new_status} for Griffith Island Club.</p>";
            $message .= "<p>Regards,</p>";
            $message .= "<p>All at Griffith Island Club</p>";
            $message .= "<p><a href='https://beta.griffithisland.com' style='color: #333; text-decoration: none; font-weight: bold;'>Visit Our Website for more info.</a></p>";
            $message .= "</div>";
            // Return success response, if needed
            echo json_encode(array('success' => true));
            wp_mail($user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
        } else {
            // Return error response
            echo json_encode(array('error' => 'Booking details not found'));
        }
    } else {
        // Return error response
        echo json_encode(array('error' => 'Invalid parameters'));
    }
    exit; // Terminate the script
}
add_action('wp_ajax_update_booking_status', 'update_booking_status');
add_action('wp_ajax_update_waitlist_booking', 'update_waitlist_booking');
function update_waitlist_booking()
{
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
    global $wpdb;
    $table_name = $wpdb->prefix . 'waitlist_data';
    $wpdb->update(
        $table_name,
        array('status' => $new_status),
        array('id' => $booking_id),
        array('%s'),
        array('%d')
    );
    wp_send_json_success('Status updated successfully');
}
// Add the following code to your plugin file or theme functions.php
// Register the AJAX action for authenticated users
add_action('wp_ajax_get_waitlist_data', 'get_waitlist_data');
// Register the AJAX action for non-authenticated users
add_action('wp_ajax_nopriv_get_waitlist_data', 'get_waitlist_data');
// Update your server-side function (get_waitlist_data) to include user_nicename
function get_waitlist_data()
{
    global $wpdb;
    $waitlist_table_name = $wpdb->prefix . 'waitlist_data';
    $users_table_name = $wpdb->prefix . 'users';
    $waitlist_data = $wpdb->get_results("
        SELECT waitlist.*, users.user_nicename
        FROM $waitlist_table_name as waitlist
        LEFT JOIN $users_table_name as users ON CAST(JSON_UNQUOTE(JSON_EXTRACT(waitlist.waitlist_details, '$.member')) AS UNSIGNED) = users.ID
    ", ARRAY_A);
    wp_send_json($waitlist_data);
}