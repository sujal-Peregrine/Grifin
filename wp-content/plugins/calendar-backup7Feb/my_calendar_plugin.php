<?php
/*
Plugin Name: Custom Calendar Plugin
Description: A simple plugin to demonstrate Calendar integration.
Version: 1.0
Author: Island
*/
function create_available_spaces_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'available_spaces';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        my_calendar_date DATE NOT NULL,
        available_spaces INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
create_available_spaces_table();
register_activation_hook(__FILE__, 'create_booking_table');
register_activation_hook(__FILE__, 'create_waitlist_table');
function enqueue_fullcalendar_scripts()
{
    wp_enqueue_style('app-css', plugin_dir_url(__FILE__) . '/app.css');
    wp_enqueue_style('bootstrap', plugin_dir_url(__FILE__) . '/bootstrap.css');
    wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_style('choices', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
    wp_enqueue_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js', array(), null, true);
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);
    wp_enqueue_script('choices', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', array(), null, true);
    wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js', array('jquery', 'moment'), '6.1.10', true);
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array('jquery'), null, true);
    // wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10');
    wp_enqueue_script('calendar', plugins_url('calendar.js?q=3', __FILE__), array('jquery'), null, true);
    // Include Toastr CSS
    wp_enqueue_style('toastr-css', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css');
    wp_localize_script(
        'calendar',
        'ajax_object',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
        )
    );
    wp_enqueue_script('toastr-js', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js', array('jquery'), '', true);
}
add_action('wp_enqueue_scripts', 'enqueue_fullcalendar_scripts');
function my_calendar_shortcode($atts)
{
    ob_start(); ?>
    <?php
    $user_id = 1;
    $default_events = get_user_meta($user_id, 'my_calendar_events_data', true);
    if (is_user_logged_in()) {
        ?>
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
                /*Ruchit*/
                font-size: 13px;
                font-weight: 500;
            }

            .booking input {
                /*Ruchit*/
                /* height: 43px; */
                border: 1px solid #d3d8de;
            }

            .booking th {
                /*Ruchit*/
                font-size: 18px;
                font-weight: 300;
            }

            .arr {
                /*Ruchit*/
                display: flex;
                align-items: center;
                justify-content: space-between;
                height: 43px;
                width: 100%;
                border: 1px solid #d3d8de;
                border-radius: 4px;
            }

            .arr input {
                /*Ruchit*/
                width: 100%;
                border: none;
            }

            .arr img {
                /*Ruchit*/
                width: 30px;
                padding-right: 16px;
            }

            .arr img {
                /*Ruchit*/
                width: 30px;
                padding-right: 16px;
            }

            /*Ruchit*/
            .booking-modal {
                width: 85% !important;
            }

            /*Ruchit*/
            .booking-tr {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 0 5rem !important;
                border-bottom: 1px solid #00000026 !important;
            }

            /*Ruchit*/
            .fc-event,
            {
            cursor: pointer !important;
            }

            .booking td {
                /*Ruchit*/
                font-size: 13px;
                font-weight: 500;
            }

            .booking input {
                /*Ruchit*/
                /* height: 43px; */
                border: 1px solid #d3d8de;
            }

            .booking th {
                /*Ruchit*/
                font-size: 18px;
                font-weight: 300;
            }

            .arr {
                /*Ruchit*/
                display: flex;
                align-items: center;
                justify-content: space-between;
                height: 43px;
                width: 100%;
                border: 1px solid #d3d8de;
                border-radius: 4px;
            }

            .arr input {
                /*Ruchit*/
                width: 70%;
                border: none;
            }

            .arr img {
                /*Ruchit*/
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
                /* Center-align the text in the dropdown menu */
            }

            #suggestion-box .user-suggestion {
                cursor: pointer;
                padding: 5px;
            }

            #suggestion-box .user-suggestion:hover {
                background-color: #f0f0f0;
            }

            .mandatory-note {
                left: 12px;
                position: absolute;
                font-size: 13px;
                font-style: italic;
            }
        </style>
        <div class="row">
            <div class="col-12">
                <div class="row">
                    <div class="col-xl-9">
                        <div class="row mb-3 justify-content-end">
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" id="my-button"> Book Now</button>
                            </div>
                            <div class="col-md-2">
                                <button id="my-button-waitlist" class="btn btn-warning">Add to WaitList</button>
                            </div>
                        </div>
                        <div class="card card-h-100">
                            <div class="card-body">
                                <input type="hidden" id="defaultEventsField"
                                    value="<?php echo esc_attr(json_encode($default_events)); ?>">
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <!-- Primary Alert -->
                                        <div class="alert border-0 alert-danger" role="alert">
                                            <strong>Description Box: &nbsp; </strong>
                                        </div>
                                    </div>
                                </div>
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div><!-- end col -->
                    <div class="col-xl-3">

                        <div class="input-group mb-3">
                            <input type="hidden" name="hidden_name" id="hidden_name">
                            <input type="text" class="form-control" placeholder="Search User" id="user-search-input"
                                aria-label="Search User" aria-describedby="button-addon2">
                            <button class="btn btn-outline-secondary" type="button" id="button-addon2"><i class="fa fa-search"
                                    aria-hidden="true"></i>
                            </button>
                            <button class="btn btn-outline-success" type="button" id="clear-button"><i class="fas fa-undo"
                                    aria-hidden="true"></i></button>

                        </div>
                        <div id="suggestion-box" style="max-height: 80px; overflow-y: auto;"></div>

                        <script>
                            document.getElementById("user-search-input").addEventListener("input", function () {
                                var searchValue = this.value.trim();
                                if (searchValue !== '') {
                                    var xhr = new XMLHttpRequest();
                                    xhr.open("POST", "<?php echo admin_url('admin-ajax.php'); ?>");
                                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                    xhr.onreadystatechange = function () {
                                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                                            document.getElementById("suggestion-box").innerHTML = this.responseText;
                                        }
                                    };
                                    xhr.send("action=user_autosuggest&search_value=" + searchValue);
                                } else {
                                    document.getElementById("suggestion-box").innerHTML = "";
                                }
                            });

                            document.getElementById("suggestion-box").addEventListener("click", function (e) {
                                if (e.target && e.target.matches("div.user-suggestion")) {
                                    var selectedUser = e.target.textContent;
                                    document.getElementById("user-search-input").value = selectedUser;
                                    document.getElementById("suggestion-box").innerHTML = ""; // Clear suggestion box
                                }
                            });
                        </script>


                        <br>


                        <!-- Additional content outside the card on the right side -->
                        <div class="alert alert-success" role="alert">
                            <i class="fa fa-user"></i>

                            <strong>Hi! </strong><b>
                                <?php echo esc_html(get_user_meta(get_current_user_id(), 'first_name', true)); ?>
                                <?php echo esc_html(get_user_meta(get_current_user_id(), 'last_name', true)); ?>
                            </b><br><br>
                            <div style="font-size:0.9rem"><strong>Total Hunt Day Allocation: </strong>
                                <?php echo esc_html(get_user_meta(get_current_user_id(), 'hunt_day_allocation', true)); ?><br>
                                <strong>Total Summer Day Allocation: </strong>
                                <?php echo esc_html(get_user_meta(get_current_user_id(), 'summer_day_allocation', true)); ?>
                            </div>
                        </div>

                        <hr>
                        <p class=""><strong>Bookings</strong></p>

                        <div class="pe-2 me-n1 mb-3" data-simplebar style="max-height: 250px; overflow-y: auto;">
                            <div id="bookings_data" class="simplebar-content" style="max-height: 100%;"></div>
                        </div>


                        <hr>
                        <p class=""><strong>Events</strong></p>
                        <div class="pe-2 me-n1 mb-3" data-simplebar style="height: 400px">
                            <div id="upcoming-event-list"></div>
                        </div>
                    </div>
                </div>
                <div style='clear:both'></div>
            </div>
        </div>
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
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
                                                        <input type="text" class="form-control flatpickr" id="date-picker"
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
                                                        <input type="text" class="form-control" id="number-of-guests"
                                                            pattern="[0-9]*"
                                                            oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                                                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/99/Sample_User_Icon.png"
                                                            alt="">
                                                    </div>
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
                                                        <input class="form-check-input" type="radio" name="bringDog"
                                                            id="bringDogYes" value="Yes">
                                                        <label class="form-check-label" for="bringDogYes">Yes</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="bringDog"
                                                            id="bringDogNo" value="No">
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
                                                        <input class="form-check-input" type="radio" name="dogGender" id="male"
                                                            value="Male">
                                                        <label class="form-check-label" for="male">Male</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="dogGender"
                                                            id="female" value="Female">
                                                        <label class="form-check-label" for="female">Female</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="dogGender"
                                                            id="not_applicable" value="" checked>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="save-booking">Save changes</button>
                    </div>

                </div>
            </div>
        </div>
        <div class="modal fade" id="waitlistModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header mb-0 pb-0">
                        <h5 class="modal-title" id="exampleModalLabel">Wait List Form</h5>
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
                                                    <select class="form-select" id="member-select-waitlist" required>
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
                                                <td>Preferred Date <span class="text-danger">*</span></td>
                                                <td>
                                                    <div class="arr"> <!-- Ruchit -->
                                                        <input type="text" class="form-control flatpickr" id="prefered_date">
                                                        <img src="https://icons.veryicon.com/png/o/miscellaneous/administration/calendar-335.png"
                                                            id="calendar_img" alt="">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td># Total Group Size <span class="text-danger">*</span></td>
                                                <td>
                                                    <div class="arr"> <!-- Ruchit -->
                                                        <input type="number" class="form-control" id="number-of-guests-waitlist"
                                                            pattern="[0-9]*"
                                                            oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                                                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/99/Sample_User_Icon.png"
                                                            alt="">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Arrival Method <span class="text-danger">*</span></td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            name="arrivalMethod-waitlist" id="boat" value="Boat" required>
                                                        <label class="form-check-label" for="boat">Boat</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            name="arrivalMethod-waitlist" id="self" value="Self" required>
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
                                                        <input class="form-check-input" type="radio" name="gender-waitlist"
                                                            id="stag" value="Stag" required>
                                                        <label class="form-check-label" for="stag">Stag</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="gender-waitlist"
                                                            id="mixed" value="Mixed" required>
                                                        <label class="form-check-label" for="mixed">Mixed</label>
                                                    </div>
                                                    <span id="gender-alert" class="text-danger"></span> <!-- Span for alert -->
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Number of Hunters <span class="text-danger">*</span></td>
                                                <td>
                                                    <input type="text" class="form-control" id="number-of-waitlist-members"
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
                                                        <input class="form-check-input" type="radio" name="bringDog-waitlist"
                                                            id="bringDogYes" value="Yes">
                                                        <label class="form-check-label" for="bringDogYes">Yes</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="bringDog-waitlist"
                                                            id="bringDogNo" value="No">
                                                        <label class="form-check-label" for="bringDogNo">No</label>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Breed</td>
                                                <td>
                                                    <input type="text" class="form-control" id="breed-waitlist">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Dog Gender</td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="dogGender-waitlist"
                                                            id="male" value="Male">
                                                        <label class="form-check-label" for="male">Male</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="dogGender-waitlist"
                                                            id="female" value="Female">
                                                        <label class="form-check-label" for="female">Female</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="dogGender-waitlist"
                                                            id="not_applicable" value="" checked>
                                                        <label class="form-check-label" for="not_applicable">N/A</label>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Preferred Hunt</td>
                                                <td>
                                                    <select class="form-select" id="preferred-hunt-waitlist">
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
                                                        id="special-instructions-waitlist"></textarea>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <span class="mandatory-note">Note: <span class="text-danger">*</span> fields are mandatory</span><button
                            type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="save-waitlist">Save to Waitlist</button>
                    </div>

                </div>
            </div>
        </div>
        <div class="modal fade" id="event-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-title-event"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container">
                            <div class="d-flex mb-2">
                                <div class="flex-grow-1 d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <i class="ri-calendar-event-line text-muted fs-16"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="d-block fw-semibold mb-0" id="event-start-date-tag"></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="flex-shrink-0 me-3">
                                    <i class="ri-map-pin-line text-muted fs-16"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="d-block fw-semibold mb-0"> <span id="event-location-tag"></span></h6>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0 me-3">
                                    <i class="ri-discuss-line text-muted fs-16"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="d-block text-muted mb-0" id="event-description-tag"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Booking Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="modalBody">
                        <table class="table align-middle table-nowrap mb-0">
                            <tbody id="table-content" style="max-height: 400px; overflow-y: auto; display: block;">
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script>

            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            jQuery(document).ready(function ($) {
                var data = {
                    'action': 'get_user_bookings'
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            displayBookings(response.data);
                        } else {
                            console.error('Error occurred while fetching bookings data:', response.data);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error occurred while fetching bookings data:', error);
                    }
                });

                function displayBookings(bookings) {
                    var bookingDataHTML = '';
                    var colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
                    bookings.forEach(function (booking, index) {
                        var bookingDetails = JSON.parse(booking.booking_details);
                        var bookingDates = bookingDetails.datePicker.split(',');
                        var startDate = new Date(bookingDates[0]); // First date is the start date
                        var endDate = bookingDates.length > 1 ? new Date(bookingDates[bookingDates.length - 1]) : null; // Initialize endDate as null if only one date is available
                        var numberOfGuests = parseInt(bookingDetails.numberOfGuests);
                        var bookingInfo = '<strong style="font-size: smaller;">' + startDate.getDate() + ' ' + getMonthName(startDate.getMonth()) + ' ' + startDate.getFullYear();

                        // Check if endDate is not null and different from startDate
                        if (endDate && !isNaN(endDate.getTime()) && !datesAreEqual(startDate, endDate)) {
                            bookingInfo += ' to ' + endDate.getDate() + ' ' + getMonthName(endDate.getMonth()) + ' ' + endDate.getFullYear();
                        }

                        // Function to check if two dates are equal
                        function datesAreEqual(date1, date2) {
                            return date1.getDate() === date2.getDate() && date1.getMonth() === date2.getMonth() && date1.getFullYear() === date2.getFullYear();
                        }


                        bookingInfo += '</strong> <span class="badge bg-' + colors[index % colors.length] + '">' + numberOfGuests + '</span>';
                        var bookingCard = '<div class="card mb-3">';
                        bookingCard += '<div class="card-body">';
                        bookingCard += '<h5 class="card-title">Booking ' + (index + 1) + '</h5>';
                        bookingCard += '<p class="card-text">' + bookingInfo + '</p>';
                        bookingCard += '</div>';
                        bookingCard += '</div>';
                        bookingDataHTML += bookingCard;
                    });
                    $('#bookings_data').html(bookingDataHTML);
                }



                function getMonthName(monthIndex) {
                    var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];
                    return monthNames[monthIndex];
                }
            });



            document.addEventListener('DOMContentLoaded', function () {
                flatpickr("#prefered_date", {
                    mode: "range",
                    enableTime: false,
                    dateFormat: "Y-m-d",
                    minDate: "today"
                });


                document.getElementById('calendar_img').addEventListener('click', function () {
                    var flatpickrInstance = flatpickr("#prefered_date");
                    flatpickrInstance.open();
                });
                document.getElementById('save-waitlist').addEventListener('click', function () {
                    // Retrieve form data
                    var member = document.querySelector('#member-select-waitlist').value;
                    var numberOfHunters = document.querySelector('#number-of-guests-waitlist').value;
                    var prefered_date = document.querySelector('#prefered_date').value;

                    // Retrieve optional form data
                    var arrivalMethodElement = document.querySelector('input[name="arrivalMethod-waitlist"]:checked');
                    var genderElement = document.querySelector('input[name="gender-waitlist"]:checked');
                    var numberOfGuestsElement = document.querySelector('#number-of-waitlist-members');

                    // Validate required fields
                    if (!member || !numberOfHunters || !prefered_date || !arrivalMethodElement || !genderElement || !numberOfGuestsElement) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Please fill in all required fields.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        return; // Exit function if validation fails
                    }

                    // Extract values if elements exist
                    var arrivalMethod = arrivalMethodElement.value;
                    var gender = genderElement.value;
                    var numberOfGuests = numberOfGuestsElement.value;

                    // Retrieve optional form data
                    var breedElement = document.querySelector('#breed-waitlist');
                    var dogGenderElement = document.querySelector('input[name="dogGender-waitlist"]:checked');
                    var bringDogElement = document.querySelector('input[name="bringDog-waitlist"]:checked');
                    var preferredHuntElement = document.querySelector('#preferred-hunt-waitlist');
                    var specialInstructionsElement = document.querySelector('#special-instructions-waitlist');

                    // Extract values if elements exist
                    var breed = breedElement ? breedElement.value : '';
                    var dogGender = dogGenderElement ? dogGenderElement.value : '';
                    var bringDog = bringDogElement ? bringDogElement.value : '';
                    var preferredHunt = preferredHuntElement ? preferredHuntElement.value : '';
                    var specialInstructions = specialInstructionsElement ? specialInstructionsElement.value : '';

                    // Construct waitlistData object
                    var waitlistData = {
                        'member': member,
                        'numberOfHunters': numberOfHunters,
                        'prefered_date': prefered_date,
                        'arrivalMethod': arrivalMethod,
                        'gender': gender,
                        'numberOfGuests': numberOfGuests,
                        'breed': breed,
                        'dogGender': dogGender,
                        'bringDog': bringDog,
                        'preferredHunt': preferredHunt,
                        'specialInstructions': specialInstructions
                    };


                    var nonce = '<?php echo wp_create_nonce("save-waitlist-data"); ?>';

                    jQuery.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'save_waitlist_data',
                            security: nonce,
                            data: waitlistData
                        },
                        success: function (response) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Waitlist Proceed for Approval',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(function () {
                                location.reload();
                            });
                        },
                        error: function (error) {
                            console.error(error);
                        },
                        complete: function () {
                            // Re-enable the button after completion (whether success or error)
                            document.getElementById('save-waitlist').disabled = false;
                        }
                    });
                });


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
    if (!numberOfHunters) emptyFields.push('Number of Hunters');
    if (!gender) emptyFields.push('Gender');

    // Extract start and end date from the range
    var dateRange = datePicker.split(' to ');
    var startDate = new Date(dateRange[0]);
    var endDate = new Date(dateRange[1]);

    // Check if any part of the range falls between September and December
    var requiresExtraFields = false;
    for (var d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        var month = d.getMonth(); // 0-based (0 = Jan, 11 = Dec)
        // alert(month);
        if (month >= 8 && month <= 11) {
            requiresExtraFields = true;
            break;
        }
    }

    if (requiresExtraFields) {
        if (!bringDog) emptyFields.push('Bring Dog');
        if (breed === '') emptyFields.push('Breed');
        if (!dogGender) emptyFields.push('Dog Gender');
        if (preferredHunt === '') emptyFields.push('Preferred Hunt');
        if (specialInstructions === '') emptyFields.push('Special Instructions');
    }

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

    var formData = {
        'member': document.querySelector('#member-select').value,
        'datePicker': document.querySelector('#date-picker').value,
        'numberOfGuests': document.querySelector('#number-of-guests').value,
        'arrivalMethod': document.querySelector('input[name="arrivalMethod"]:checked').value,
        'gender': document.querySelector('input[name="gender"]:checked').value,
        'numberOfHunters': document.querySelector('#number-of-hunters').value
    };

    // Conditionally add hunting-related fields if they are filled
    if (breed !== '') formData['breed'] = breed;
    if (dogGender) formData['dogGender'] = dogGender.value;
    if (bringDog) formData['bringDog'] = bringDog.value;
    if (preferredHunt !== '') formData['preferredHunt'] = preferredHunt;
    if (specialInstructions !== '') formData['specialInstructions'] = specialInstructions;

    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce("save-booking-data"); ?>';
    
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'save_booking_data',
            security: nonce,
            data: formData
        },
        success: function (response) {
            console.log(response.remaining_guests);
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
            }
            else if (response.noSpace !== undefined) {
                Swal.fire({
                    title: 'Available Spaces',
                    text: 'Sorry, No space for this date.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            }
            else {
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
            document.getElementById('save-booking').disabled = false;
        },
        complete: function () {
            // Re-enable the button after completion (whether success or error)
            document.getElementById('save-booking').disabled = false;
        }
    });
});

            });

            jQuery(document).ready(function ($) {

                $('#clear-button').click(function () {
                    $('#user-search-input').val('');

                    $('#hidden_name').val('');

                    $('.view-bookings-button').show();
                });

                // Function to handle the click event for the next button
                $('.fc-next-button').click(function () {
                    var searchValue = $('#user-search-input').val();
                    if (searchValue !== '') {
                        var waitlistData = {
                            action: 'fetch_bookings',
                            search_value: searchValue
                        };
                        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: waitlistData,
                            success: function (response) {
                                console.log(response);



                                if (response != 0) {
                                    var bookings = response.split('}{');
                                    if (bookings.length > 1) {
                                        bookings[0] += '}';
                                        for (var i = 1; i < bookings.length - 1; i++) {
                                            bookings[i] = '{' + bookings[i] + '}';
                                        }
                                        bookings[bookings.length - 1] = '{' + bookings[bookings.length - 1];
                                    }

                                    var uniqueDates = [];
                                    var hasSetMember = false; // Flag to check if member has been set

                                    bookings.forEach(function (booking) {
                                        var bookingDetails = JSON.parse(booking);
                                        console.log(bookingDetails.member)
                                        if (!hasSetMember) {
                                            $('#hidden_name').val(bookingDetails.member);
                                            hasSetMember = true; // Update the flag
                                        }

                                        var datePicker = bookingDetails.datePicker.split(',');
                                        datePicker.forEach(function (date) {
                                            if (!uniqueDates.includes(date)) {
                                                uniqueDates.push(date);
                                            }
                                        });
                                    });

                                    // Hide the "Booked Members" link for all .fc-day elements
                                    $('.fc-day').find('.view-bookings-button').hide();

                                    // Show the "Booked Members" link only for uniqueDates
                                    uniqueDates.forEach(function (date) {
                                        $('.fc-day[data-date="' + date + '"]').find('.view-bookings-button').show();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Bookings',
                                        text: 'Sorry, No Bookings for this user.',
                                        icon: 'info',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });
                    }
                });

                // Function to handle the click event for the previous button
                $('.fc-prev-button').click(function () {
                    var searchValue = $('#user-search-input').val();
                    if (searchValue !== '') {
                        var waitlistData = {
                            action: 'fetch_bookings',
                            search_value: searchValue
                        };
                        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: waitlistData,
                            success: function (response) {
                                console.log(response);



                                if (response != 0) {
                                    var bookings = response.split('}{');
                                    if (bookings.length > 1) {
                                        bookings[0] += '}';
                                        for (var i = 1; i < bookings.length - 1; i++) {
                                            bookings[i] = '{' + bookings[i] + '}';
                                        }
                                        bookings[bookings.length - 1] = '{' + bookings[bookings.length - 1];
                                    }

                                    var uniqueDates = [];
                                    var hasSetMember = false; // Flag to check if member has been set

                                    bookings.forEach(function (booking) {
                                        var bookingDetails = JSON.parse(booking);
                                        console.log(bookingDetails.member)
                                        if (!hasSetMember) {
                                            $('#hidden_name').val(bookingDetails.member);
                                            hasSetMember = true; // Update the flag
                                        }

                                        var datePicker = bookingDetails.datePicker.split(',');
                                        datePicker.forEach(function (date) {
                                            if (!uniqueDates.includes(date)) {
                                                uniqueDates.push(date);
                                            }
                                        });
                                    });

                                    // Hide the "Booked Members" link for all .fc-day elements
                                    $('.fc-day').find('.view-bookings-button').hide();

                                    // Show the "Booked Members" link only for uniqueDates
                                    uniqueDates.forEach(function (date) {
                                        $('.fc-day[data-date="' + date + '"]').find('.view-bookings-button').show();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Bookings',
                                        text: 'Sorry, No Bookings for this user.',
                                        icon: 'info',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });
                    }
                });





                $('#button-addon2').click(function () {
                    var searchValue = $('#user-search-input').val();
                    if (searchValue !== '') {
                        var waitlistData = {
                            action: 'fetch_bookings',
                            search_value: searchValue
                        };
                        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: waitlistData,
                            success: function (response) {
                                console.log(response);



                                if (response != 0) {
                                    var bookings = response.split('}{');
                                    if (bookings.length > 1) {
                                        bookings[0] += '}';
                                        for (var i = 1; i < bookings.length - 1; i++) {
                                            bookings[i] = '{' + bookings[i] + '}';
                                        }
                                        bookings[bookings.length - 1] = '{' + bookings[bookings.length - 1];
                                    }

                                    var uniqueDates = [];
                                    var hasSetMember = false; // Flag to check if member has been set

                                    bookings.forEach(function (booking) {
                                        var bookingDetails = JSON.parse(booking);
                                        console.log(bookingDetails.member)
                                        if (!hasSetMember) {
                                            $('#hidden_name').val(bookingDetails.member);
                                            hasSetMember = true; // Update the flag
                                        }

                                        var datePicker = bookingDetails.datePicker.split(',');
                                        datePicker.forEach(function (date) {
                                            if (!uniqueDates.includes(date)) {
                                                uniqueDates.push(date);
                                            }
                                        });
                                    });

                                    // Hide the "Booked Members" link for all .fc-day elements
                                    $('.fc-day').find('.view-bookings-button').hide();

                                    // Show the "Booked Members" link only for uniqueDates
                                    uniqueDates.forEach(function (date) {
                                        $('.fc-day[data-date="' + date + '"]').find('.view-bookings-button').show();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Bookings',
                                        text: 'Sorry, No Bookings for this user.',
                                        icon: 'info',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });
                    }
                });


            });






        </script>
        <?php
        return ob_get_clean();
    } else {
        $login_url = wp_login_url(get_permalink());
        wp_redirect($login_url);
        exit();
    }
}
add_shortcode('my_calendar', 'my_calendar_shortcode');

function logout_button_shortcode()
{
    if (is_user_logged_in()) {
        $logout_button = '<a href="' . wp_logout_url() . '" style="color: aliceblue; font-size: 14px; text-decoration: none;" onmouseover="this.style.color=\'orange\'" onmouseout="this.style.color=\'aliceblue\'"> <i class="fas fa-sign-out-alt"></i> Logout</a>';
        $admin_button = '<a href="' . admin_url() . '" style="color: aliceblue; font-size: 14px; text-decoration: none;" onmouseover="this.style.color=\'orange\'" onmouseout="this.style.color=\'aliceblue\'"> <i class="fas fa-user-cog"></i> Admin</a>';
        return '<div style="display: flex; flex-direction: row; justify-content: flex-end; position: absolute; top: 128px; right: 35px;z-index:999">' . $admin_button . '&nbsp&nbsp' . $logout_button . '</div>';
    } else {
        return '';
    }
}
// Add the shortcode with priority 999
add_shortcode('logout_button', 'logout_button_shortcode', 999);


// function custom_login_redirect($redirect_to, $request, $user) {
//     if (!empty($user->ID)) {
//         header('Location: https://beta.griffithisland.com');
//         exit;
//     }
//     return $redirect_to;
// }
// add_filter('login_redirect', 'custom_login_redirect', 10, 3);



//search 
add_action('wp_ajax_fetch_bookings', 'fetch_bookings_callback');
add_action('wp_ajax_nopriv_fetch_bookings', 'fetch_bookings_callback');

function fetch_bookings_callback()
{
    global $wpdb;

    $search_value = sanitize_text_field($_POST['search_value']);

    // Fetch user ID from the users table based on user_login
    $user_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE user_login = %s",
            $search_value
        )
    );


    if ($user_id) {
        // Fetch bookings where user_id matches $user_id
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM wp_booking_data WHERE user_id = %d",
                $user_id
            )
        );

        if ($bookings) {
            foreach ($bookings as $booking) {
                print_r($booking->booking_details);
            }
        } else {
            return false;
        }
    } else {
        echo '<div>No user found</div>';
    }

    wp_die();
}










function restrict_access_to_calendar()
{
    // Check if the user is not logged in and trying to access the calendar page
    if (!is_user_logged_in() && is_page('calendar-reservations')) {
        // Redirect them to the login page
        $login_url = wp_login_url(get_permalink());
        wp_redirect($login_url);
        exit();
    }
}
// Hook the function to the 'template_redirect' action
add_action('template_redirect', 'restrict_access_to_calendar');
// Admin menu page to configure calendar title



add_action('wp_ajax_user_autosuggest', 'user_autosuggest_callback');
add_action('wp_ajax_nopriv_user_autosuggest', 'user_autosuggest_callback');

function user_autosuggest_callback()
{
    global $wpdb;

    $search_value = sanitize_text_field($_POST['search_value']);

    $users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->users} WHERE display_name LIKE %s LIMIT 5",
            '%' . $wpdb->esc_like($search_value) . '%'
        )
    );

    if ($users) {
        foreach ($users as $user) {
            echo '<div class="user-suggestion" id="' . esc_attr($user->ID) . '">' . esc_html($user->user_login) . '</div>';
        }

    } else {
        echo '<div>No users found</div>';
    }

    wp_die();
}


function my_calendar_menu()
{
    add_menu_page('Calendar Settings', 'Month Title', 'manage_options', 'my_calendar_settings', 'my_calendar_settings_page');
    add_submenu_page('my_calendar_settings', 'Calendar Events', 'Events', 'manage_options', 'my_calendar_events', 'my_calendar_events_page');
    add_submenu_page('my_calendar_settings', 'Available Spaces', 'Available Spaces', 'manage_options', 'my_calendar_available_spaces', 'my_calendar_available_spaces_page');
}
add_action('admin_menu', 'my_calendar_menu');
function my_calendar_events_page()
{
    $user_id = get_current_user_id();
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_id = get_current_user_id();
        $selected_start_date = sanitize_text_field($_POST['my_calendar_start_date']);
        $selected_end_date = sanitize_text_field($_POST['my_calendar_end_date']);

        $event_description = sanitize_text_field($_POST['my_calendar_event_description']);
        $event_type = sanitize_text_field($_POST['my_calendar_event_type']);
        $existing_record = get_user_meta($user_id, 'my_calendar_events_data', true);
        $current_date = current_time('mysql');
        $event_id = uniqid();

        if (!empty($existing_record) && is_array($existing_record)) {
            $existing_record[] = array(
                'event_id' => $event_id, // Store the event ID

                'start' => $selected_start_date,
                'end' => $selected_end_date,
                'title' => $event_description,
                'className' => $event_type,
                'created_date' => $current_date,
            );
            update_user_meta($user_id, 'my_calendar_events_data', $existing_record);
        } else {
            $new_record = array(
                array(
                    'event_id' => $event_id, // Store the event ID
                    'start' => $selected_start_date,
                    'end' => $selected_end_date,
                    'title' => $event_description,
                    'className' => $event_type,
                    'created_date' => $current_date,
                ),
            );
            update_user_meta($user_id, 'my_calendar_events_data', $new_record);
        }
    }
    wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js', array('jquery', 'moment'), '6.1.10', true);
    wp_enqueue_style('app-css', plugin_dir_url(__FILE__) . '/app.css');
    wp_enqueue_style('bootstrap', plugin_dir_url(__FILE__) . '/bootstrap.css');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array('jquery'), null, true);
    // wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10');
    ?>
    <style>
        #wpfooter {
            position: relative;
        }

        .fc-day-disabled {
            visibility: hidden;
        }

        .card {
            min-width: 100% !important;
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

        .wp-core-ui select {
            border: 1px solid #8c8f94 !important;
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

        .font-size-12 {
            font-size: 12px;
        }

        .fc-daygrid-day-frame {
            display: inline !important;
        }

        .z-index-999 {
            z-index: 999 !important;
        }
    </style>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="d-flex align-items-center">
                                <h5 class="card-title mb-0 flex-grow-1">Calendar Events</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" id="myForm">
                                <?php settings_fields('my_calendar_events_group'); ?>
                                <?php do_settings_sections('my_calendar_events_group'); ?>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="my_calendar_start_date" class="form-label">Select Start
                                            Date:</label>
                                        <input type="text" id="my_calendar_start_date" name="my_calendar_start_date"
                                            class="form-control flatpickr"
                                            value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'my_calendar_start_date', true)); ?>"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="my_calendar_end_date" class="form-label">Select End Date:</label>
                                        <input type="text" id="my_calendar_end_date" name="my_calendar_end_date"
                                            class="form-control flatpickr"
                                            value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'my_calendar_end_date', true)); ?>"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="my_calendar_event_type" class="form-label">Event Type:</label>
                                        <select id="my_calendar_event_type" name="my_calendar_event_type"
                                            class="form-select" required>
                                            <option value="" disabled selected>Select</option>
                                            <option value="bg-warning-subtle" style="color: #ffc107;">&#9632; &nbsp
                                                Warning</option>
                                            <option value="bg-primary-subtle" style="color: #007bff;">&#9632; &nbsp
                                                Primary</option>
                                            <option value="bg-info-subtle" style="color: #17a2b8;">&#9632; &nbsp Info
                                            </option>
                                            <option value="bg-danger-subtle" style="color: #dc3545;">&#9632; &nbsp
                                                Danger</option>
                                            <option value="bg-success-subtle" style="color: #28a745;">&#9632; &nbsp
                                                Success</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="my_calendar_event_description" class="form-label">Event
                                            Description:</label>
                                        <input type="text" id="my_calendar_event_description"
                                            name="my_calendar_event_description" class="form-control"
                                            value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'my_calendar_event_description', true)); ?>"
                                            required>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm()">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <!-- Data card -->
        <div class="card-header border-0">
            <div class="d-flex align-items-center">
            </div>
        </div>
        <div class="row">
            <div class="col-xl-9">
                <div class="card card-h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-0 flex-grow-1">Saved Events</h5>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div id="external-events">
                            <div class="external-event fc-event bg-success-subtle text-success"
                                data-class="bg-success-subtle">
                                <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>Success
                            </div>
                            <div class="external-event fc-event bg-info-subtle text-info" data-class="bg-info-subtle">
                                <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>Info
                            </div>
                            <div class="external-event fc-event bg-primary-subtle text-primary"
                                data-class="bg-primary-subtle">
                                <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>Primary
                            </div>
                            <div class="external-event fc-event bg-warning-subtle text-warning"
                                data-class="bg-warning-subtle">
                                <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>Warning
                            </div>
                            <div class="external-event fc-event bg-danger-subtle text-danger" data-class="bg-danger-subtle">
                                <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>Danger
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <p class=""><strong>Recent Events</strong></p>
                <div class="pe-2 me-n1 mb-3" data-simplebar style="height: 400px">
                    <div id="upcoming-event-list"></div>
                </div>
            </div>
            <?php
            $saved_events = get_user_meta($user_id, 'my_calendar_events_data', true);

            ?>
        </div>
    </div>
    </div>
    <!-- Updated modal with editable fields and save button -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Editable event details fields -->
                    <form id="editEventForm">
                        <input type="hidden" id="eventDetailsId" name="eventDetailsId">
                        <!-- Hidden input field to store the event ID -->
                        <p><strong>Title:</strong> <span id="eventDetailsTitle"></span></p>

                        <p><strong>Start Date:</strong> <input type="text" id="eventDetailsStartDate"
                                class="form-control flatpickr"></p>
                        <p><strong>End Date:</strong> <input type="text" id="eventDetailsEndDate"
                                class="form-control flatpickr"></p>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveEventChanges">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <table style="border-collapse: collapse; width: 100%; border: 1px solid #dee2e6;"
                        class="table align-middle table-nowrap mb-0">
                        <tbody id="table-content" style="max-height: 200px; overflow-y: auto; display: block;">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="availSpaceModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Avail Spaces</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="d-flex align-items-center">
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" id="myCalendarForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="my_calendar_date" class="form-label">Select Date:</label>
                                        <input type="text" id="my_calendar_date" name="my_calendar_date"
                                            class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="available_spaces" class="form-label">Available Spaces Limit:</label>
                                        <input type="number" id="available_spaces" name="available_spaces"
                                            class="form-control" required>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitAvailForm()">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function ($) {


            $('#saveEventChanges').on('click', function () {
                console.log('yes');
                var eventId = $('#eventDetailsId').val();
                var updatedTitle = $('#eventDetailsTitle').text();
                var updatedStartDate = $('#eventDetailsStartDate').val();
                var updatedEndDate = $('#eventDetailsEndDate').val();

                var formData = {
                    action: 'update_event',
                    eventId: eventId,
                    title: updatedTitle,
                    start: updatedStartDate,
                    end: updatedEndDate,
                    nonce: '<?php echo wp_create_nonce('update_the_event'); ?>'

                };

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: formData,
                    success: function (response) {
                        console.log('Event updated successfully:', response);
                        $('#eventDetailsModal').modal('hide');
                        location.reload();


                    },
                    error: function (error) {
                        console.error('Error updating event:', error);

                    }
                });
            });




            window.submitAvailForm = function () {
                var formData = $('#myCalendarForm').serialize();
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'save_available_spaces',
                        data: formData,
                        nonce: '<?php echo wp_create_nonce('my_calendar_nonce'); ?>',
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Data Saved Successfully',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to save data',
                            });
                        }
                    },
                    error: function (error) {
                        console.error('AJAX Error:', error);
                    }
                });
            };
            flatpickr('#my_calendar_date', {});
            var formatDate = function (date) {
                var d = new Date(date),
                    month = '' + (d.getMonth() + 1),
                    day = '' + d.getDate(),
                    year = d.getFullYear();
                if (month.length < 2)
                    month = '0' + month;
                if (day.length < 2)
                    day = '0' + day;
                return [year, month, day].join('-');
            };
            function formatDateNum(date) {
                date = new Date(date);
                var day = date.getDate();
                var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                var month = monthNames[date.getMonth()];
                var year = date.getFullYear();
                return day + " " + month + " " + year;
            }
            const modal = document.getElementById("exampleModal");
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                editable: true,
                droppable: true,
                selectable: true,
                navLinks: true,
                themeSystem: 'bootstrap',
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                showNonCurrentDates: false,
                validRange: {
                    start: moment().startOf('month'),
                    end: moment().endOf('month')
                },
                events: <?php echo json_encode($saved_events); ?>,
                eventContent: function (arg) {
                    if (arg.event.extendedProps && arg.event.extendedProps.icon) {
                        return { html: '<i class="' + arg.event.extendedProps.icon + '"></i> ' + arg.event.title };
                    }
                    return { html: arg.event.title };
                },
                eventColor: function (arg) {
                    if (arg.event.extendedProps && arg.event.extendedProps.eventTypeColor) {
                        return arg.event.extendedProps.eventTypeColor;
                    }
                    return '#3788d8';
                },
                dateClick: function (info) {
                    if (!info.jsEvent.target.classList.contains('add-event-button')) {
                    }
                },
                eventClick: function (info) {
                    displayEventDetails(info.event);
                },
                dayCellDidMount: function (info) {
                    var dateSquare = info.el;
                    if (dateSquare) {
                        var currentDate = new Date();
                        currentDate.setHours(0, 0, 0, 0);
                        var cellDate = info.date;
                        cellDate.setHours(0, 0, 0, 0);
                        var dayFrame = dateSquare.querySelector('.fc-daygrid-day-events');
                        var dateElement = dateSquare.querySelector('.fc-daygrid-day-number');
                        if (dateElement) {
                            dateElement.classList.add('large-font');
                            dateElement.style.fontSize = '23px';
                        }
                        if (dayFrame) {
                            var addButton = document.createElement('a');
                            addButton.href = '#';
                            addButton.innerHTML = '&nbsp;&nbsp;Add Event';
                            addButton.className = 'add-event-button small text-blue text-decoration-underline font-size-12 z-index-999';
                            var viewButton = document.createElement('a');
                            viewButton.href = '#';
                            viewButton.innerHTML = '&nbsp;&nbsp;Booked Members';
                            viewButton.className = 'view-bookings-button small text-decoration-underline text-blue font-size-12 z-index-999';
                            var availButton = document.createElement('a');
                            availButton.href = '#';
                            availButton.innerHTML = '&nbsp;&nbsp;Availalble Space';
                            availButton.className = 'view-bookings-button small text-decoration-underline text-blue font-size-12 z-index-999';
                            var dateStr = new Date(info.date.getTime() - info.date.getTimezoneOffset() * 60000).toISOString();
                            addButton.addEventListener('click', function (event) {
                                console.log(dateStr);
                                console.log(info.date);
                                event.preventDefault();
                                event.stopPropagation();
                                var fakeInfo = {
                                    date: info.date,
                                    dateStr: dateStr,
                                    dayEl: info.el
                                };
                                addNewEvent(fakeInfo);
                            });
                            viewButton.addEventListener('click', function (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                var fakeInfo = {
                                    date: info.date,
                                    dateStr: dateStr,
                                    dayEl: info.el
                                };
                                viewBookings(fakeInfo);
                            });
                            availButton.addEventListener('click', function (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                var fakeInfo = {
                                    date: info.date,
                                    dateStr: dateStr,
                                    dayEl: info.el
                                };
                                availSpace(fakeInfo);
                            });
                            if (cellDate >= currentDate) {
                                info.el.appendChild(addButton);
                            }
                            info.el.appendChild(document.createElement('br'));
                            info.el.appendChild(viewButton);
                            info.el.appendChild(document.createElement('br'));
                            info.el.appendChild(availButton);
                        }
                    }
                },
            });
            flatpickr("#my_calendar_start_date", {
                enableTime: false,
                dateFormat: "Y-m-d",
                minDate: "today", // Disable past days
            });

            flatpickr("#my_calendar_end_date", {
                enableTime: false,
                dateFormat: "Y-m-d",
                minDate: "today", // Disable past days
            });

            flatpickr("#eventDetailsStartDate", {
                enableTime: false,
                dateFormat: "Y-m-d",
                minDate: "today", // Disable past days
            });

            flatpickr("#eventDetailsEndDate", {
                enableTime: false,
                dateFormat: "Y-m-d",
                minDate: "today", // Disable past days
            });



            let table = new DataTable('#myCalendarEventsTable');
            calendar.render();
            function displayEventDetails(event) {
                var modalInstance = bootstrap.Modal.getInstance(eventDetailsModal);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(eventDetailsModal);
                }
                var eventId = event.extendedProps.event_id;

                // Set the value of #eventDetailsId input field
                $('#eventDetailsId').val(eventId);
                document.getElementById('eventDetailsTitle').innerText = event.title;
                $('#eventDetailsStartDate').val(formatDate(event.start));
                $('#eventDetailsEndDate').val(event.end ? formatDate(event.end) : "N/A");

                modalInstance.show();
            }
            function viewBookings(info) {
                var dateStr = info.dateStr;
                var hostUrl = window.location.origin;
                var apiUrl = hostUrl + '/wp-content/plugins/calendar/get_booking_data.php';
                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ date: dateStr }),
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        displayModal(data);
                    })
                    .catch(error => {
                        console.error('There was a problem with the fetch operation:', error);
                    });
            }
            function availSpace(info) {
                var dateWithoutTime = info.dateStr.split("T")[0];
                document.getElementById('my_calendar_date').value = dateWithoutTime;
                const modal = document.getElementById("availSpaceModal");
                var modalInstance = bootstrap.Modal.getInstance(modal);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modal);
                }
                modalInstance.show();
            }
            function addNewEvent(info) {
                var dateWithoutTime = info.dateStr.split("T")[0];
                document.getElementById('my_calendar_start_date').value = dateWithoutTime;
                var modalInstance = bootstrap.Modal.getInstance(modal);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modal);
                }
                modalInstance.show();
            }
            function updateUpcomingEventList() {
                var upcomingEventList = document.getElementById("upcoming-event-list");
                upcomingEventList.innerHTML = null;
                var eventsContainer = document.createElement("div");
                eventsContainer.style.height = "300px";
                eventsContainer.style.overflowY = "auto";
                upcomingEventList.appendChild(eventsContainer);
                calendar.getEvents().forEach(function (event) {
                    if (event.start > new Date()) {
                        var startDateFormatted = formatDateNum(event.start);
                        var endDateFormatted = event.end ? formatDateNum(event.end) : "";
                        var u_event = "<div class='card mb-3'>" +
                            "<div class='card-body'>" +
                            "<div class='d-flex mb-3'>" +
                            "<div class='flex-grow-1'><i class='mdi mdi-checkbox-blank-circle me-2 text-primary'></i><span class='fw-medium'>" + startDateFormatted + (endDateFormatted ? " to " + endDateFormatted : "") + " </span></div>" +
                            "<div class='flex-shrink-0'><small class='badge bg-primary-subtle text-primary ms-auto'>" + (event.extendedProps.startTime || "") + (event.extendedProps.endTime || "") + "</small></div>" +
                            "</div>" +
                            "<h6 class='card-title fs-16'>" + event.title + "</h6>" +
                            "</div>" +
                            "</div>";
                        eventsContainer.innerHTML += u_event;
                    }
                });
            }
            updateUpcomingEventList();
        });
        function submitForm() {
            var form = document.getElementById("myForm");
            if (form.checkValidity()) {
                form.submit();
            } else {
                alert("Please fill in all required fields.");
            }
        }
        function displayModal(data) {
            var modalBody = document.getElementById('table-content');
            modalBody.innerHTML = '';
            data.forEach(booking => {
                var bookingDetails = JSON.parse(booking.booking_details);
                var bookingContent = `
                    <td style="border: 1px solid #dee2e6; padding: 8px;"> ${booking.user_nicename} , ${bookingDetails.numberOfGuests}</td>`;
                var trElement = document.createElement('tr');
                trElement.innerHTML = bookingContent;
                modalBody.appendChild(trElement);
            });
            var bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
            bookingModal.show();
        }

    </script>
    <?php
}
function my_calendar_settings_page()
{
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array('jquery'), null, true);
    // wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10');
    wp_enqueue_script('bootstrap-datepicker', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js', array('jquery'), '1.9.0', true);
    wp_enqueue_style('bootstrap-datepicker-css', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css', array(), '1.9.0');
    // Enqueue Bootstrap Datepicker localization script
    wp_enqueue_script('bootstrap-datepicker-en-GB', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.en-GB.min.js', array('bootstrap-datepicker'), '1.9.0', true);
    $user_id = get_current_user_id();
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Save the form data to user meta
        $user_id = get_current_user_id();
        $selected_date = sanitize_text_field($_POST['my_calendar_date']);
        $calendar_title = sanitize_text_field($_POST['my_calendar_title']);
        $created_at = current_time('timestamp');
        // Extract the month and year from the selected date
        list($selected_month, $selected_year) = explode(' ', $selected_date);
        // Get the existing data for the user
        $existing_record = get_user_meta($user_id, 'my_calendar_data', true);
        // Check if the existing record is an array
        if (!empty($existing_record) && is_array($existing_record)) {
            // Check if both the year and month exist
            if (
                isset($existing_record[$selected_year])
                && isset($existing_record[$selected_year][$selected_month])
                && is_array($existing_record[$selected_year][$selected_month])
            ) {
                // If both year and month exist, update the title
                $existing_record[$selected_year][$selected_month]['my_calendar_title'] = $calendar_title;
            } else {
                // If either year or month doesn't exist, create a new entry for the month
                $existing_record[$selected_year][$selected_month] = array(
                    'my_calendar_title' => $calendar_title,
                    'created_at' => $created_at
                );
            }
            // Update the user meta with the modified data
            update_user_meta($user_id, 'my_calendar_data', $existing_record);
        } else {
            // If the existing record is not an array, create a new one
            $new_record = array(
                $selected_year => array(
                    $selected_month => array(
                        'my_calendar_title' => $calendar_title,
                        'created_at' => $created_at,
                    ),
                ),
            );
            update_user_meta($user_id, 'my_calendar_data', $new_record);
        }
    }
    ?>
    <style>
        .card {
            min-width: 100% !important;
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
    </style>
    <div class="wrap">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Calendar Month Title</h5>
                </div>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <?php settings_fields('my_calendar_group'); ?>
                    <?php do_settings_sections('my_calendar_group'); ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="my_calendar_date" class="form-label">Select Month and Year:</label>
                            <input type="text" id="my_calendar_date" name="my_calendar_date" class="form-control" value=""
                                required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="my_calendar_title" class="form-label">Calendar Description:</label>
                            <textarea id="my_calendar_title" name="my_calendar_title" class="form-control" rows="3" required
                                style="border: 1px solid #ced4da;"></textarea>
                        </div>
                    </div>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
    </div>
    <!-- Table to display calendar data -->
    <div class="card mt-4">
        <div class="card-header border-0">
            <div class="d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1">Calendar Data</h5>
            </div>
        </div>
        <div class="card-body">
            <table id="myCalendarDataTable" class="table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Calendar Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $calendar_data = get_user_meta($user_id, 'my_calendar_data', true);
                    // Check if calendar data exists and it's an array
                    if (!empty($calendar_data) && is_array($calendar_data)) {
                        // Initialize array to store sorted data
                        $sorted_data = array();
                        // Iterate through years and months
                        foreach ($calendar_data as $year => $months) {
                            foreach ($months as $month => $data) {
                                // Check if created_at exists
                                if (isset($data['created_at'])) {
                                    // Store data with created_at
                                    $sorted_data[] = array(
                                        'year' => $year,
                                        'month' => $month,
                                        'title' => $data['my_calendar_title'],
                                        'created_at' => $data['created_at']
                                    );
                                } else {
                                    // Store data without created_at
                                    $sorted_data[] = array(
                                        'year' => $year,
                                        'month' => $month,
                                        'title' => $data['my_calendar_title']
                                    );
                                }
                            }
                        }
                        // Function to sort by created_at if it exists
                        function sortByCreatedAtDesc($a, $b)
                        {
                            if (isset($a['created_at']) && isset($b['created_at'])) {
                                return $b['created_at'] - $a['created_at'];
                            } else {
                                // If created_at doesn't exist for either entry, maintain current order
                                return 0;
                            }
                        }
                        // Sort data by created_at in descending order if it exists
                        usort($sorted_data, 'sortByCreatedAtDesc');
                        // Display sorted data in the table
                        foreach ($sorted_data as $entry) {
                            echo '<tr>';
                            echo '<td>' . esc_html($entry['year']) . '</td>';
                            echo '<td>' . esc_html($entry['month']) . '</td>';
                            echo '<td>' . esc_html($entry['title']) . '</td>';
                            echo '<td><button class="btn edit-button" data-year="' . esc_attr($entry['year']) . '" data-month="' . esc_attr($entry['month']) . '"><i class="fa fa-edit" style="color: black;" aria-hidden="true"></i></button>';
                            echo '<button class="btn delete-button" data-year="' . esc_attr($entry['year']) . '" data-month="' . esc_attr($entry['month']) . '"><i class="fa fa-trash" aria-hidden="true"></i></button></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr>
                                            <td colspan="5">No calendar data found.</td>
                                            <td colspan="5"></td>
                                            <td colspan="5"></td>
                                            <td colspan="5"></td>
                                            </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Calendar Setting</h5>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editYear" name="editYear">
                        <input type="hidden" id="editMonth" name="editMonth">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="my_calendar_title" class="form-label">Calendar Description:</label>
                                <textarea id="editCalendarTitle" name="editCalendarTitle" class="form-control" rows="3"
                                    required="" style="border: 1px solid #ced4da;"></textarea>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Your DataTable initialization script
        jQuery(document).ready(function ($) {
            $('#myCalendarDataTable').DataTable({
                order: [[0, 'desc']],
            });
            var currentDate = new Date();
            var currentMonth = currentDate.getMonth() + 1;
            var currentYear = currentDate.getFullYear();
            $('#my_calendar_date').datepicker({
                format: "MM yyyy",
                viewMode: "months",
                minViewMode: "months",
                autoclose: true,
                orientation: "bottom",
                startView: "years",
                changeMonth: true,
                changeYear: true,
                startDate: new Date(currentYear, currentMonth - 1, 1)
            }).datepicker('setDate', new Date(currentYear, currentMonth - 1, 1));
            $('.edit-button').on('click', function () {
                var year = $(this).data('year');
                var month = $(this).data('month');
                var calendar_data = <?php echo json_encode(get_user_meta($user_id, 'my_calendar_data', true)); ?>;
                var title = calendar_data[year][month]['my_calendar_title'];
                $('#editYear').val(year);
                $('#editMonth').val(month);
                $('#editCalendarTitle').val(title);
                $('#editModal').modal('show');
            });
            $('#editForm').submit(function (e) {
                e.preventDefault();
                var year = $('#editYear').val();
                var month = $('#editMonth').val();
                var newTitle = $('#editCalendarTitle').val();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edit_calendar_entry',
                        user_id: <?php echo json_encode($user_id); ?>,
                        year: year,
                        month: month,
                        new_title: newTitle,
                        security: '<?php echo wp_create_nonce('edit_calendar_entry_nonce'); ?>',
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Calendar Settings updated successfully.',
                            }).then(() => {
                                location.reload(true);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: response.data.message,
                            });
                        }
                    },
                    error: function (error) {
                        console.error(error);
                    }
                });
                $('#editModal').modal('hide');
            });



            $('.delete-button').on('click', function () {
                var year = $(this).data('year');
                var month = $(this).data('month');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'delete_calendar_entry',
                                user_id: <?php echo json_encode($user_id); ?>,
                                year: year,
                                month: month,
                                security: '<?php echo wp_create_nonce('delete_calendar_entry_nonce'); ?>',
                            },
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'Calendar Settings deleted successfully.',
                                    }).then(() => {
                                        location.reload(true);
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: response.data.message,
                                    });
                                }
                            },
                            error: function (error) {
                                console.error(error);
                            }
                        });
                    }
                });
            });
        });
    </script>
    <?php
}



// Define the callback function for handling the AJAX request
function update_event_callback()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'update_the_event')) {
        wp_send_json_error('Unauthorized request');
    }

    // Handle the AJAX request
    if (isset($_POST['action']) && $_POST['action'] === 'update_event') {
        // Retrieve and sanitize posted data
        $event_id = sanitize_text_field($_POST['eventId']);
        $user_id = get_current_user_id();
        $selected_start_date = sanitize_text_field($_POST['start']);
        $selected_end_date = sanitize_text_field($_POST['end']);
        $event_description = sanitize_text_field($_POST['title']);
        $event_type = sanitize_text_field($_POST['event_type']); // Assuming you have an event_type field
        $current_date = current_time('mysql');

        $existing_record = get_user_meta($user_id, 'my_calendar_events_data', true);

        if (!empty($existing_record) && is_array($existing_record)) {
            foreach ($existing_record as &$event) {
                if ($event['event_id'] === $event_id) {
                    // Update only start and end dates
                    $event['start'] = $selected_start_date;
                    $event['end'] = $selected_end_date;
                    $event['updated_date'] = $current_date; // Optionally update the updated date
                    break; // Stop looping once the event is found and updated
                }
            }
            unset($event); // Unset the reference variable to avoid potential side effects
            // Save the updated event data back to user meta
            update_user_meta($user_id, 'my_calendar_events_data', $existing_record);
            wp_send_json_success('Event updated successfully');
        } else {
            wp_send_json_error('No events found');
        }

        wp_send_json_success('Event updated successfully');
    } else {
        // Send error response for invalid action
        wp_send_json_error('Invalid action');
    }
}

// Hook the update_event function to WordPress AJAX action
add_action('wp_ajax_update_event', 'update_event_callback');




add_action('wp_ajax_delete_calendar_entry', 'delete_calendar_entry');
function delete_calendar_entry()
{
    check_ajax_referer('delete_calendar_entry_nonce', 'security');
    $user_id = sanitize_text_field($_POST['user_id']);
    $year = sanitize_text_field($_POST['year']);
    $month = sanitize_text_field($_POST['month']);
    $existing_record = get_user_meta($user_id, 'my_calendar_data', true);
    if (!empty($existing_record) && is_array($existing_record)) {
        if (
            isset($existing_record[$year])
            && isset($existing_record[$year][$month])
        ) {
            unset($existing_record[$year][$month]);
            update_user_meta($user_id, 'my_calendar_data', $existing_record);
            wp_send_json_success('Entry deleted successfully.');
        } else {
            wp_send_json_error('Entry not found.');
        }
    } else {
        wp_send_json_error('No calendar data found.');
    }
    wp_die();
}
add_action('wp_ajax_edit_calendar_entry', 'edit_calendar_entry');
function edit_calendar_entry()
{
    check_ajax_referer('edit_calendar_entry_nonce', 'security');
    $user_id = sanitize_text_field($_POST['user_id']);
    $year = sanitize_text_field($_POST['year']);
    $month = sanitize_text_field($_POST['month']);
    $new_title = sanitize_text_field($_POST['new_title']);
    $existing_record = get_user_meta($user_id, 'my_calendar_data', true);
    if (!empty($existing_record) && is_array($existing_record)) {
        if (
            isset($existing_record[$year])
            && isset($existing_record[$year][$month])
        ) {
            $existing_record[$year][$month]['my_calendar_title'] = $new_title;
            update_user_meta($user_id, 'my_calendar_data', $existing_record);
            wp_send_json_success('Entry updated successfully.');
        } else {
            wp_send_json_error('Entry not found.');
        }
    } else {
        wp_send_json_error('No calendar data found.');
    }
    wp_die();
}
function my_calendar_register_settings()
{
    register_setting('my_calendar_group', 'my_calendar_title');
}
add_action('admin_init', 'my_calendar_register_settings');
add_action('wp_ajax_save_booking_data', 'save_booking_data');
function save_booking_data()
{
    global $wpdb;
    if (isset($_POST['data'])) {
        $user_id = get_current_user_id();
        $booking_data = $_POST['data'];
        $date_range = $booking_data['datePicker'];
        $selected_dates = explode(' to ', $date_range);


        $start_date = new DateTime($selected_dates[0]);
        $end_date = new DateTime($selected_dates[0]);
        if ($selected_dates[1] != null) {
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
            $booking_data['status'] = '2';
            $booking_details = sanitize_text_field(wp_json_encode($booking_data));
            $table_name = $wpdb->prefix . 'booking_data';

            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'booking_details' => $booking_details,
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

function get_total_booked_guests($selected_date, $user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'booking_data';

    $max_guests_per_date = array();

    // Split the selected_date string into individual dates
    $dates = explode(',', $selected_date);

    // Loop through each date and calculate the maximum guests
    foreach ($dates as $current_date_str) {
        // Query the database to get booking details for the current date
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT booking_details FROM $table_name 
                WHERE user_id = %d 
                AND JSON_UNQUOTE(JSON_EXTRACT(booking_details, '$.datePicker')) LIKE %s",
                $user_id,
                '%' . $current_date_str . '%'
            ),
            ARRAY_A
        );

        $max_guests_per_date[$current_date_str] = 0;

        // Iterate over results and update max guests for the current date
        foreach ($results as $result) {
            $booking_data = json_decode($result['booking_details'], true);
            if (isset($booking_data['numberOfGuests'])) {
                $max_guests_per_date[$current_date_str] = max($max_guests_per_date[$current_date_str], intval($booking_data['numberOfGuests']));
            }
        }
    }

    // Calculate the maximum guests across all dates
    $total_booked_guests = max($max_guests_per_date);
    return $total_booked_guests;
}




// Function to get available spaces and booked members for a specific date and user ID
function save_waitlist_data()
{
    check_ajax_referer('save-waitlist-data', 'security');
    $user_id = get_current_user_id(); // Get the current user ID

    // Sanitize and retrieve the data
    $member = sanitize_text_field($_POST['data']['member']);
    $numberOfHunters = sanitize_text_field($_POST['data']['numberOfHunters']);
    $prefered_date = sanitize_text_field($_POST['data']['prefered_date']);
    $arrivalMethod = sanitize_text_field($_POST['data']['arrivalMethod']);
    $gender = sanitize_text_field($_POST['data']['gender']);
    $numberOfGuests = sanitize_text_field($_POST['data']['numberOfGuests']);
    $breed = sanitize_text_field($_POST['data']['breed']);
    $dogGender = sanitize_text_field($_POST['data']['dogGender']);
    $bringDog = sanitize_text_field($_POST['data']['bringDog']);
    $preferredHunt = sanitize_text_field($_POST['data']['preferredHunt']);
    $specialInstructions = sanitize_text_field($_POST['data']['specialInstructions']);

    // Create an associative array with waitlist details
    $waitlist_details = array(
        'member' => $member,
        'number_of_hunters' => $numberOfHunters,
        'prefered_date' => $prefered_date,
        'arrival_method' => $arrivalMethod,
        'gender' => $gender,
        'number_of_guests' => $numberOfGuests,
        'breed' => $breed,
        'dog_gender' => $dogGender,
        'bring_dog' => $bringDog,
        'preferred_hunt' => $preferredHunt,
        'special_instructions' => $specialInstructions
    );

    // Convert the associative array to a JSON string
    $waitlist_details_json = json_encode($waitlist_details);

    // Save waitlist data to the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'waitlist_data';
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'waitlist_details' => $waitlist_details_json,
        )
    );
    wp_die();
}

add_action('wp_ajax_save_waitlist_data', 'save_waitlist_data');
add_action('wp_ajax_nopriv_save_waitlist_data', 'save_waitlist_data');
function my_calendar_available_spaces_page()
{
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10', array('jquery'), null, true);
    // wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10');
    $user_id = get_current_user_id();
    ?>
    <style>
        .card {
            min-width: 100% !important;
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
    </style>
    <div class="wrap">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Create Available Spaces</h5>
                </div>
            </div>
            <div class="card-body">
                <form method="post" action="" id="myCalendarForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="my_calendar_date" class="form-label">Select Date:</label>
                            <input type="text" id="my_calendar_date" name="my_calendar_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="available_spaces" class="form-label">Available Spaces Limit:</label>
                            <input type="number" id="available_spaces" name="available_spaces" class="form-control"
                                required>
                        </div>
                    </div>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Available Spaces</h5>
                </div>
            </div>
            <div class="card-body">
                <table id="myCalendarDataTable" class="table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Available Spaces</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="avail">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Available Spaces</h5>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <!-- Add a hidden field for the ID -->
                        <input type="hidden" id="edit_id" name="id" value="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_available_spaces" class="form-label">Available Spaces Limit:</label>
                                <input type="number" id="edit_available_spaces" name="available_spaces" class="form-control"
                                    required>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            flatpickr('#my_calendar_date', {});
            var table = $('#myCalendarDataTable').DataTable({
                order: [[0, 'desc']],
                columnDefs: [{
                    targets: -1,
                    orderable: false,
                    data: null,
                    defaultContent: "<td><button class='edit btn'><i class='fa fa-edit' style='color: black;' aria-hidden='true'></i></button> <button class='delete btn'><i class='fa fa-trash' aria-hidden='true'></i></button></td>"
                }]
            });
            table.clear().draw();
            $('#myCalendarForm').submit(function (e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'save_available_spaces',
                        data: formData,
                        nonce: '<?php echo wp_create_nonce('my_calendar_nonce'); ?>',
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Data Saved Successfully',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to save data',
                            });
                        }
                    },
                    error: function (error) {
                        console.error('AJAX Error:', error);
                    }
                });
            });
            // AJAX to get available spaces data
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'get_available_spaces',
                    nonce: '<?php echo wp_create_nonce('my_calendar_nonce'); ?>',
                },
                success: function (response) {
                    if (response.success) {
                        table.clear().draw();
                        response.data.forEach(function (item) {
                            table.row.add([
                                item.id,
                                item.available_spaces,
                                item.my_calendar_date,
                            ]).draw();
                        });
                        $('#myCalendarDataTable tbody').on('click', 'button.edit', function () {
                            var data = table.row($(this).parents('tr')).data();
                            var id = data[0]; // Assuming the ID is in the first column
                            // AJAX call for edit
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    action: 'edit_available_space',
                                    nonce: '<?php echo wp_create_nonce('my_calendar_nonce'); ?>',
                                    id: id
                                },
                                success: function (response) {
                                    console.log(response);
                                    if (response.success) {
                                        var editModal = $('#editModal');
                                        // Fill the modal with existing data
                                        editModal.find('#edit_id').val(response.data.data.id);
                                        editModal.find('#edit_available_spaces').val(response.data.data.available_spaces);
                                        // Show the modal
                                        editModal.modal('show');
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: response.message,
                                        });
                                    }
                                },
                                error: function (error) {
                                    console.error('AJAX Error:', error);
                                }
                            });
                        });
                        // Event listener for Edit Form submission
                        $('#editForm').submit(function (e) {
                            e.preventDefault();
                            var formData = $(this).serialize();
                            // AJAX call to update the data
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    action: 'update_available_space',
                                    data: formData,
                                    nonce: '<?php echo wp_create_nonce('my_calendar_nonce'); ?>',
                                },
                                success: function (response) {
                                    if (response.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Data Updated Successfully',
                                        }).then(function () {
                                            // Reload the page or update the table after editing
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: response.message,
                                        });
                                    }
                                },
                                error: function (error) {
                                    console.error('AJAX Error:', error);
                                }
                            });
                        });
                        $('#myCalendarDataTable tbody').on('click', 'button.delete', function () {
                            var data = table.row($(this).parents('tr')).data();
                            var id = data[0]; // Assuming the ID is in the first column
                            Swal.fire({
                                title: 'Are you sure?',
                                text: 'You won\'t be able to revert this!',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'Yes, delete it!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // AJAX call for delete
                                    $.ajax({
                                        type: 'POST',
                                        url: ajaxurl,
                                        data: {
                                            action: 'delete_available_space',
                                            nonce: '<?php echo wp_create_nonce('my_calendar_nonce'); ?>',
                                            id: id
                                        },
                                        success: function (response) {
                                            if (response.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Data Deleted Successfully',
                                                }).then(function () {
                                                    // Reload the page or update the table after deletion
                                                    location.reload();
                                                });
                                            } else {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: response.message,
                                                });
                                            }
                                        },
                                        error: function (error) {
                                            console.error('AJAX Error:', error);
                                        }
                                    });
                                }
                            });
                        });
                    }
                },
                error: function (error) {
                    console.error('AJAX Error:', error);
                }
            });
        });
    </script>
    <?php
}
function save_available_spaces_callback()
{
    check_ajax_referer('my_calendar_nonce', 'nonce');
    $data = wp_unslash($_POST['data']);
    parse_str($data, $parsed_data);
    $my_calendar_date = sanitize_text_field($parsed_data['my_calendar_date']);
    $available_spaces = intval($parsed_data['available_spaces']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'available_spaces';
    $existing_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND my_calendar_date = %s", get_current_user_id(), $my_calendar_date));
    if ($existing_entry) {
        $wpdb->update($table_name, array('available_spaces' => $available_spaces, 'updated_at' => current_time('mysql', 1)), array('id' => $existing_entry->id), array('%d', '%s'), array('%d'));
        wp_send_json_success(array('message' => 'Data updated successfully'));
    } else {
        $wpdb->insert($table_name, array('user_id' => get_current_user_id(), 'my_calendar_date' => $my_calendar_date, 'available_spaces' => $available_spaces, 'created_at' => current_time('mysql', 1), 'updated_at' => current_time('mysql', 1)), array('%d', '%s', '%d', '%s', '%s'));
        if ($wpdb->insert_id) {
            wp_send_json_success(array('message' => 'Data saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save data'));
        }
    }
}
add_action('wp_ajax_save_available_spaces', 'save_available_spaces_callback');
function get_available_spaces_callback()
{
    check_ajax_referer('my_calendar_nonce', 'nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'available_spaces';
    $query = "SELECT * FROM $table_name WHERE user_id = " . get_current_user_id() . " ORDER BY created_at DESC";
    $results = $wpdb->get_results($query);
    if ($results) {
        $available_spaces_data = array();
        foreach ($results as $result) {
            $available_spaces_data[] = array(
                'id' => $result->id,
                'my_calendar_date' => $result->my_calendar_date,
                'available_spaces' => $result->available_spaces,
            );
        }
        wp_send_json_success($available_spaces_data);
    } else {
        wp_send_json_error(array('message' => 'No available spaces data found'));
    }
}
add_action('wp_ajax_get_available_spaces', 'get_available_spaces_callback');
function delete_available_space_callback()
{
    check_ajax_referer('my_calendar_nonce', 'nonce');
    $id = intval($_POST['id']); // Assuming 'id' is sent in the AJAX request
    global $wpdb;
    $table_name = $wpdb->prefix . 'available_spaces';
    $deleted = $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
    if ($deleted) {
        wp_send_json_success(array('message' => 'Data deleted successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete data'));
    }
}
add_action('wp_ajax_delete_available_space', 'delete_available_space_callback');
function edit_available_space_callback()
{
    check_ajax_referer('my_calendar_nonce', 'nonce');
    $id = intval($_POST['id']); // Assuming 'id' is sent in the AJAX request
    global $wpdb;
    $table_name = $wpdb->prefix . 'available_spaces';
    $data = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)
    );
    if ($data) {
        wp_send_json_success(array('message' => 'Data fetched successfully', 'data' => $data));
    } else {
        wp_send_json_error(array('message' => 'Failed to fetch data'));
    }
}
add_action('wp_ajax_edit_available_space', 'edit_available_space_callback');
function update_available_space_callback()
{
    check_ajax_referer('my_calendar_nonce', 'nonce');
    $data = wp_unslash($_POST['data']);
    parse_str($data, $parsed_data);
    $id = intval($parsed_data['id']);
    $available_spaces = intval($parsed_data['available_spaces']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'available_spaces';
    $updated = $wpdb->update(
        $table_name,
        array('available_spaces' => $available_spaces),
        array('id' => $id),
        array('%d'),
        array('%d')
    );
    if ($updated) {
        wp_send_json_success(array('message' => 'Data updated successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update data'));
    }
}
add_action('wp_ajax_update_available_space', 'update_available_space_callback');


add_action('wp_ajax_get_user_bookings', 'get_user_bookings_callback');
add_action('wp_ajax_nopriv_get_user_bookings', 'get_user_bookings_callback');

function get_user_bookings_callback()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    $user_id = get_current_user_id();
    global $wpdb;
    $table_name_booking = $wpdb->prefix . 'booking_data';
    $query = $wpdb->prepare(
        "SELECT bd.*, u.user_nicename, u.is_delete
        FROM $table_name_booking bd
        LEFT JOIN $wpdb->users u ON bd.user_id = u.ID
        WHERE bd.user_id = %d AND JSON_EXTRACT(bd.booking_details, '$.status') = %s
        ORDER BY bd.id DESC",
        $user_id,
        '1' // Use '1' instead of 1
    );

    $results = $wpdb->get_results($query, ARRAY_A);

    wp_send_json_success($results);
}




