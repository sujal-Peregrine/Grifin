<?php
/*

Plugin Name: Members Plugin

Description: A simple plugin to Members

Version: 1.0

Author: Island

*/
function enqueue_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js', array('jquery', 'popper'), '5.3.2', true);
    wp_enqueue_script('popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.10.2/umd/popper.min.js', array('jquery'), '2.10.2', true);
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', array('jquery'), '1.11.5', true);
    wp_enqueue_style('bootstrap', plugin_dir_url(__FILE__) . '/bootstrap.min.css');
    wp_enqueue_style('datatables-style', 'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css');
    wp_enqueue_script('zxcvbn', 'https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js', array(), '4.4.2', true);
    wp_enqueue_script('password-strength-meter');
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
    wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);
    wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');

}

function enqueue_custom_scripts() {
    // Enqueue your JavaScript file
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'custom-script.js', array('jquery'), '1.0', true);

    // Get the URL of the avatar image directory
    $avatar_image_url = plugin_dir_url(__FILE__) . 'avatar.png';

    // Localize the JavaScript file with the avatar image URL
    wp_localize_script('custom-script', 'avatarData', array(
        'avatarImageUrl' => $avatar_image_url
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


add_action('admin_enqueue_scripts', 'enqueue_scripts');

function members_menu()
{
    add_menu_page(
        'Members',
        'Members',
        'manage_options',
        'members',
        'members_page'
    );

    add_submenu_page(
        'members',
        'Create Member',
        'Create Member',
        'manage_options',
        'create_member_page',
        'create_member_page'
    );

    add_submenu_page(
        null,
        'Edit Member',
        'Edit Member',
        'manage_options',
        'edit_member_page',
        'edit_member_page'
    );

    add_submenu_page(
        null,
        'View Member',
        'View Member',
        'manage_options',
        'view_member_page',
        'view_member_page'
    );
}

add_action('admin_menu', 'members_menu');

add_action('wp_ajax_create_member', 'create_member_callback');

function add_is_delete_column_to_wp_users_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'users';
    $column_name = 'is_delete';
    
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
    
    // If the column doesn't exist, add it
    if (!$column_exists) {
        $sql = "ALTER TABLE $table_name ADD $column_name TINYINT(1) DEFAULT 0";
        dbDelta($sql);
    }
}

add_action('admin_init', 'add_is_delete_column_to_wp_users_table');


function create_member_callback()
{

    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $username = sanitize_text_field($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = sanitize_text_field($_POST['password']);
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $connected_members_raw = isset($_POST['connected_members']) ? $_POST['connected_members'] : array();
    $phone = sanitize_text_field($_POST['phone']);
    $summer_day_allocation = sanitize_text_field($_POST['summer_day_allocation']);
    $hunt_day_allocation = sanitize_text_field($_POST['hunt_day_allocation']);

    $connected_members = array_map('intval', $connected_members_raw);
    $connected_members = array_filter($connected_members, 'absint');

    if (empty($username) || empty($email) || !is_email($email) || email_exists($email) || username_exists($username)) {
        $response = array('success' => false, 'message' => 'Invalid username or email.');
        wp_send_json($response);
    }

    $user_id = wp_insert_user(
        array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => 'club_member',
        )
    );

    if (is_wp_error($user_id)) {
        $response = array('success' => false, 'message' => 'Error creating user.');
    } else {

        update_user_meta($user_id, 'title', $title);
        update_user_meta($user_id, 'description', $description);
        update_user_meta($user_id, 'connected_members', $connected_members);
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'hunt_day_allocation', $hunt_day_allocation);
        update_user_meta($user_id, 'summer_day_allocation', $summer_day_allocation);

        $response = array('success' => true, 'message' => 'User created successfully.');
        wp_send_json($response);

    }
    exit;
}

add_action('admin_footer', 'initialize_select2');

function initialize_select2()
{
    ?>
    <script>
        jQuery(document).ready(function ($) {

            $('#connected_members').select2();
        });
    </script>
    <?php
}

add_action('wp_ajax_edit_member', 'edit_member_callback');

function edit_member_callback()
{
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $username = sanitize_text_field($_POST['username']);
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $email = sanitize_email($_POST['email']);
    $title = sanitize_text_field($_POST['title']);
    $phone = sanitize_text_field($_POST['phone']);
    $summer_day_allocation = sanitize_text_field($_POST['summer_day_allocation']);
    $hunt_day_allocation = sanitize_text_field($_POST['hunt_day_allocation']);
    $description = sanitize_textarea_field($_POST['description']);
    $connected_members_raw = isset($_POST['connected_members']) ? $_POST['connected_members'] : array();

    $connected_members = array_map('intval', $connected_members_raw);
    $connected_members = array_filter($connected_members, 'absint');

    $user_data = get_userdata($user_id);

    if (!$user_data || !is_email($email)) {
        $response = array('success' => false, 'message' => 'Invalid user data.');
        wp_send_json($response);
    }

    $user_update = wp_update_user(
        array(
            'ID' => $user_id,
            'user_email' => $email,
        )
    );

    update_user_meta($user_id, 'title', $title);
    update_user_meta($user_id, 'description', $description);
    update_user_meta($user_id, 'connected_members', $connected_members);
    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'hunt_day_allocation', $hunt_day_allocation);
    update_user_meta($user_id, 'summer_day_allocation', $summer_day_allocation);

    if (is_wp_error($user_update)) {
        $response = array('success' => false, 'message' => 'Error updating user.');
    } else {
        $response = array('success' => true, 'message' => 'User updated successfully.');
    }

    $redirect_url = add_query_arg(array('edit_user_message' => urlencode(json_encode($response)), 'user_id' => $user_id), admin_url('admin.php?page=members'));
    wp_redirect($redirect_url);
    exit;
}

add_action('admin_post_edit_member', 'edit_member_callback');
add_action('admin_post_nopriv_edit_member', 'edit_member_callback');

add_action('wp_ajax_delete_member', 'delete_member_callback');

function delete_member_callback()
{
    $member_id = intval($_POST['member_id']);
    $user = get_user_by('ID', $member_id);
    if ($user) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'users';
        $wpdb->update($table_name, array('is_delete' => 1), array('ID' => $member_id));

        $response = array('success' => true, 'message' => 'User marked as deleted.');
    } else {
        $response = array('success' => false, 'message' => 'User not found.');
    }
    ob_clean();
    wp_send_json($response);
}

add_action('wp_ajax_get_user_emails', 'get_user_emails');
add_action('wp_ajax_nopriv_get_user_emails', 'get_user_emails');

function get_user_emails() {
    global $wpdb;

    $search_term = $_GET['term']; // The search term entered by the user

    // Fetch user emails that match the search term
    $user_emails = $wpdb->get_col($wpdb->prepare("
        SELECT user_email
        FROM $wpdb->users
        WHERE user_email LIKE %s
    ", '%' . $wpdb->esc_like($search_term) . '%'));

    // Return JSON response
    wp_send_json($user_emails);
    wp_die();
}



add_action('wp_ajax_member_page_table', 'member_page_table_callback');

function member_page_table_callback()
{
    global $wpdb;

    $selected_date = sanitize_text_field($_POST['selected_date']);
    $email = sanitize_text_field($_POST['email']);
    $connected_members = $_POST['connected_members'];


// Parse date range
$date_range = explode(' to ', $selected_date);
$start_date = isset($date_range[0]) ? sanitize_text_field($date_range[0]) : '';
$end_date = isset($date_range[1]) ? sanitize_text_field($date_range[1]) : '';

    // echo $end_date;
    // die;

    if (!empty($connected_members)) {
        $connected_members_string = implode(',', array_map('intval', $connected_members));
        $where_clause_connected_members = " AND u.ID IN ($connected_members_string)";
    } else {
        $where_clause_connected_members = '';
    }

    // Construct WHERE clause based on date range
    if (!empty($start_date) && !empty($end_date)) {
        $where_clause_date = $wpdb->prepare(" AND DATE(u.user_registered) BETWEEN %s AND %s", $start_date, $end_date);
    } elseif (!empty($start_date)) {
        $where_clause_date = $wpdb->prepare(" AND DATE(u.user_registered) = %s", $start_date);
    } else {
        $where_clause_date = '';
    }

    if (!empty($email)) {
        $where_clause_email = $wpdb->prepare(" AND u.user_email LIKE %s", '%' . $email . '%');
    } else {
        $where_clause_email = '';
    }

    $where_clause = $where_clause_date . $where_clause_email . $where_clause_connected_members;


    $table_name = $wpdb->users;

    $columns = array(
        'ID' => 'User ID',
        'user_login' => 'Username',
        'name' => 'Name',
        'user_email' => 'Email',
        'phone' => 'Phone',
        'user_registered' => 'Registration Date',
        'title' => 'Title',
        'description' => 'Description',
        'connected_members' => 'Connected Members',
    );

    $data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT u.*, 
                    COALESCE(m1.meta_value, 'N/A') as first_name, 
                    COALESCE(m2.meta_value, 'N/A') as last_name,
                    COALESCE(m3.meta_value, 'N/A') as connected_members,
                    COALESCE(m4.meta_value, 'N/A') as description,
                    COALESCE(m5.meta_value, 'N/A') as title,
                    COALESCE(m6.meta_value, 'N/A') as phone
             FROM $wpdb->users u
             LEFT JOIN $wpdb->usermeta m1 ON u.ID = m1.user_id AND m1.meta_key = %s
             LEFT JOIN $wpdb->usermeta m2 ON u.ID = m2.user_id AND m2.meta_key = %s
             LEFT JOIN $wpdb->usermeta m3 ON u.ID = m3.user_id AND m3.meta_key = %s
             LEFT JOIN $wpdb->usermeta m4 ON u.ID = m4.user_id AND m4.meta_key = %s
             LEFT JOIN $wpdb->usermeta m5 ON u.ID = m5.user_id AND m5.meta_key = %s
             LEFT JOIN $wpdb->usermeta m6 ON u.ID = m6.user_id AND m6.meta_key = %s
             WHERE u.is_delete = %d $where_clause
             ORDER BY u.ID DESC",
            'first_name',
            'last_name',
            'connected_members',
            'description',
            'title',
            'phone',
            0
        )
    );

    // Process data to replace user IDs with user nicenames for connected members
    foreach ($data as $key => $user) {
        if (!empty($user->connected_members)) {
            $connected_member_ids = unserialize($user->connected_members);
            $connected_member_nicenames = array();
            foreach ($connected_member_ids as $connected_member_id) {
                $connected_member = get_userdata($connected_member_id);
                if ($connected_member) {
                    $connected_member_nicenames[] = $connected_member->user_nicename;
                }
            }
            $data[$key]->connected_members = implode(', ', $connected_member_nicenames);
        }
    }

    $response = array(
        'columns' => $columns,
        'data' => $data,
    );

    wp_send_json($response);
}




function members_page()
{
    global $wpdb;

    $table_name = $wpdb->users;

    $columns = array(
        'ID' => 'User ID',
        'user_login' => 'Username',
        'name' => 'Name',
        'user_email' => 'Email',
        'phone' => 'Phone',
        'user_registered' => 'Registration Date',
        'title' => 'Title',
        'description' => 'Description',
        'connected_members' => 'Connected Members',
    );

    $data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT u.*, GROUP_CONCAT(m1.meta_value) as connected_members
             FROM $wpdb->users u
             JOIN $wpdb->usermeta m ON u.ID = m.user_id
             LEFT JOIN $wpdb->usermeta m1 ON u.ID = m1.user_id AND m1.meta_key = 'connected_members'
             WHERE m.meta_key = %s
             AND m.meta_value = %s
             AND u.is_delete = %d
             GROUP BY u.ID
             ORDER BY u.ID DESC",
            'wp_capabilities',
            serialize(array('club_member' => true)),
            0
        )
    );
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

    </style>

    <body>

        <div class="card" id="tasksList">
            <div class="card-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Members List</h5>
                    <div class="flex-shrink-0">
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-secondary"
                                href="<?php echo admin_url('admin.php?page=create_member_page'); ?>">New User +</a>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="hr">
            <div id="email_suggestions" style="display: none; max-height: 150px; overflow-y: auto; border: 1px solid #ccc; max-height: 150px;
  overflow-y: auto;
  border: 1px solid rgb(204, 204, 204);
  top: 127px;
  position: absolute;
  left: 37px;
  width: 22%;
  background: whitesmoke;
  z-index: 999;"></div>
            <div class="container">
                <div class="row">
                    <div class="col">
                        <div class="search">
                            <img class="search-icon"
                                src="https://uxwing.com/wp-content/themes/uxwing/download/user-interface/search-icon.png"
                                alt="">
                                <input type="text" placeholder="Search By Email" id="email_get">
                        </div>
                    </div>
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
                                        "SELECT * FROM $wpdb->users WHERE is_delete = %d ORDER BY display_name ASC",
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
            <div class="card-body" style="height: 100%;">
                <div class="table-responsive table-card mb-0 pb-3">
                    <table class="table align-middle table-nowrap mb-0 " id="membersTable">
                        <thead class="table-light text-muted ">
                            <tr>
                                <th class="sort" data-column="ID">User ID</th>
                                <th class="sort" data-column="user_login">Club Member</th>
                                <th class="sort" data-column="name">Name</th>
                                <th class="sort" data-column="user_email">Email</th>
                                <th class="sort" data-column="phone">Phone</th>
                                <th class="sort" data-column="user_registered">Registration Date</th>
                                <th class="sort" data-column="title">Title</th>
                                <th class="sort" data-column="description">Description</th>
                                <th class="sort" data-column="connected_members">Connected Members</th>
                                <th class="sort" data-column="action" style="width: 1500px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="membersTableBody">
                            <!-- Table rows will be dynamically generated here -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <!-- Add this HTML markup where you want to display the Bootstrap modal -->
        <div id="descriptionModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <!-- Content will be dynamically inserted here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </body>

    <script>
        var table;

        jQuery(document).ready(function ($) {


            var table = $('#membersTable').DataTable({
                order: [[0, 'desc']],
                
            });

            $('#button-addon2').on('click', function () {
                var selectedDate = $('#datepicker').val();
                var email = $('#email_get').val();
                var connected_members = $('#filter_members').val();
                fetchMemberData(selectedDate, email, connected_members);
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

    fetchMemberData('', '', '');
});




jQuery(document).ready(function($) {
    $('#email_get').on('input', function() {
        var searchTerm = $(this).val();
        if (searchTerm.length >= 1) { // Minimum characters to trigger suggestions
            $.ajax({
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'get_user_emails',
                    term: searchTerm
                },
                success: function(data) {
                    displaySuggestions(data);
                }
            });
        } else {
            $('#email_suggestions').hide(); // Hide suggestions if input length is less than 2 characters
        }
    });

    // Function to display email suggestions
    function displaySuggestions(emails) {
        var $suggestions = $('#email_suggestions');
        $suggestions.empty();

        if (emails.length > 0) {
            $.each(emails, function(index, email) {
                var $emailItem = $('<div class="email-item">' + email + '</div>');
                $emailItem.on('click', function() {
                    $('#email_get').val(email);
                    $suggestions.hide();
                });
                $suggestions.append($emailItem);
            });

            $suggestions.show();
        } else {
            $suggestions.hide();
        }
    }

    // Close suggestions when clicking outside the input and suggestion box
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#email_get, #email_suggestions').length) {
            $('#email_suggestions').hide();
        }
    });
});





            var selectedDate = $('#datepicker').val();
            var email = '';
            var cpnnected_members = '';
            fetchMemberData(selectedDate, email);


            function fetchMemberData(selectedDate, email, connected_members) {

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'member_page_table',
                        selected_date: selectedDate,
                        email: email,
                        connected_members: connected_members
                    },
                    success: function (response) {
            // Clear the table before adding new rows
            table.clear().draw();
            
            if (response.data.length === 0) {
                // If data is empty, return
                return;
            }

                        
                        $.each(response.data, function (index, row) {
                            var html = '<tr>';
                            $.each(response.columns, function (columnKey, columnName) {
                                html += '<td data-column="' + columnKey + '" class="position-relative">';
                                if (columnKey === 'user_login') {
                                    html += '<a href="admin.php?page=edit_member_page&user_id=' + row.ID + '">' + row[columnKey] + '</a>';
                                } else if (columnKey === 'user_registered') {
                                    html += new Date(row['user_registered']).getDate() + ' ' + ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][new Date(row['user_registered']).getMonth()] + ', ' + new Date(row['user_registered']).getFullYear();
                                } else if (columnKey === 'connected_members') {
    var connectedMembers = row.connected_members.split(','); 

    for (var i = 0; i < connectedMembers.length; i++) {
        var userNicename = connectedMembers[i].trim(); // Trim to remove any leading/trailing spaces

        // Check if userNicename is not empty
        if (userNicename !== '') {
            html += '<div class="image-container inline-block-style">';
            html += '<img src="https://img.icons8.com/fluency/48/user-male-circle--v1.png" alt="Your Image">';
            html += '<div class="text-overlay">';
            html += '<p>' + userNicename + '</p>';
            html += '</div>';
            html += '</div>';
        }
    }
}

 else if (columnKey === 'description') {
                                    let description = row[columnKey];
                                    let words = description.split(' ');
                                    if (words.length > 6) {
                                        description = words.slice(0, 6).join(' ') + ' ...';
                                    }
                                    html += description;
                                } else if (columnKey === 'name') {
                                    var firstName = row.first_name ? row.first_name : '';
                                    var lastName = row.last_name ? row.last_name : '';
                                    var fullName = firstName + ' ' + lastName;
                                    html += '<a href="admin.php?page=view_member_page&user_id=' + row.ID + '">' + fullName + '</a>';
                                } else {
                                    html += row[columnKey];
                                }
                                html += '</td>';
                            });
                            html += '<td style="display: flex; align-items: center;" data-column="action" class="position-relative">';
                            html += '<button class="btn delete-member" data-member-id="' + row.ID + '">';
                            html += '<i class="fa fa-trash" aria-hidden="true"></i>';
                            html += '</button>';
                            html += '<a href="admin.php?page=view_member_page&user_id=' + row.ID + '">';
                            html += '<i class="fa fa-eye" style="color: black;" aria-hidden="true"></i>';
                            html += '</a>';
                            html += '</td>';
                            console.log(html);
                            table.row.add($(html)).draw();
                        });
                    },

                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });



                
            }

            function getConnectedMemberLogin(memberId) {
                var memberLogin = '';
                var userMeta = userMetaMap[memberId];
                if (userMeta && userMeta.connected_members) {
                    var connectedMembers = userMeta.connected_members;
                    var memberIds = connectedMembers.split(',');
                    if (memberIds.length > 0) {
                        var member = userMetaMap[memberIds[0]];
                        memberLogin = member ? member.user_login : '';
                    }
                }
                return memberLogin;
            }

            $('.show-more').on('click', function (event) {
                event.preventDefault();
                var fullDescription = $(this).data('full-description');

                $('#descriptionModal').find('.modal-body').html(fullDescription);

                $('#descriptionModal').modal('show');
            });

            // $('.delete-member').on('click', function () {
            //     console.log('here');
            //     var member_id = $(this).data('member-id');

            //     var confirmDelete = confirm("Are you sure you want to delete this user?");
            //     if (!confirmDelete) {
            //         return;
            //     }

            //     $.ajax({
            //         type: 'POST',
            //         url: ajaxurl,
            //         data: {
            //             action: 'delete_member',
            //             member_id: member_id,
            //         },
            //         success: function (response) {
            //             try {
            //                 window.location.href = "<?php echo admin_url('admin.php?page=members'); ?>";
            //             } catch (error) {
            //                 console.error("Invalid JSON response:", response);
            //             }
            //         }
            //     });
            // });

            $(document).on('click', '.delete-member', function () {
    var member_id = $(this).data('member-id');

    // Display SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // User confirmed deletion, send AJAX request
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'delete_member',
                    member_id: member_id,
                },
                success: function (response) {
                    if (response.success) {
                        // Member deletion was successful
                        Swal.fire(
                            'Deleted!',
                            response.message,
                            'success'
                        ).then(() => {
                            // Reload the page to reflect the changes
                            window.location.reload();
                        });
                    } else {
                        // Member deletion failed
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    // Handle error
                    Swal.fire(
                        'Error!',
                        'An error occurred while deleting the member.',
                        'error'
                    );
                }
            });
        }
    });
});


flatpickr("#datepicker", {
    mode: 'range', // Enable date range selection
    dateFormat: 'Y-m-d',
});



            $('#filter_members').select2();

        });
    </script>

    <?php
}

function view_member_page()
{
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $user_data = get_userdata($user_id);
    $connected_members = get_user_meta($user_id, 'connected_members', true);

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
        <!-- First Card: User Information -->
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">View Member Details</h5>
                    <div class="flex-shrink-0">
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-secondary"
                                href="<?php echo esc_url(admin_url('admin.php?page=members')); ?>">Members List</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($user_data): ?>

                    <table class="table">
                        <tr>
                            <td><strong>Username:</strong></td>
                            <td>
                                <?php echo esc_html($user_data->user_login); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>First Name:</strong></td>
                            <td>
                                <?php echo esc_html(get_user_meta($user_id, 'first_name', true)); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Last Name:</strong></td>
                            <td>
                                <?php echo esc_html(get_user_meta($user_id, 'last_name', true)); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>
                                <?php echo esc_attr($user_data->user_email); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>
                                <?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Password</strong></td>

                            <td>
                                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Reset Password</a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Title:</strong></td>
                            <td>
                                <?php echo esc_html(get_user_meta($user_id, 'title', true)); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Total Hunt Day Allocation:</strong></td>
                            <td>
                                <?php echo esc_html(get_user_meta($user_id, 'hunt_day_allocation', true)); ?>
                            </td>
                        </tr>

                    </table>

                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <p>User not found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Second Card: Connected Members -->
        <?php if (!empty($connected_members)): ?>
            <div class="connected-members-card card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Connected Members</h5>
                        <div class="flex-shrink-0">

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($connected_members)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Title</th>
                                    <th>Total Hunt Day Allocation:</th>
                                </tr>
                            </thead>
                            <tbody>
    <?php foreach ($connected_members as $connected_member_id): ?>
        <?php
        $connected_member = get_user_by('ID', $connected_member_id);
        if ($connected_member) { // Check if user exists
            $connected_username = $connected_member->user_login;
            $connected_first_name = get_user_meta($connected_member_id, 'first_name', true);
            $connected_last_name = get_user_meta($connected_member_id, 'last_name', true);
            $connected_title = get_user_meta($connected_member_id, 'title', true);
            $connected_hunt_day_allocation = get_user_meta($connected_member_id, 'hunt_day_allocation', true);
        } else {
            continue; // Skip this member if user doesn't exist
        }
        ?>

        <tr>
            <td style="<?php echo empty($connected_username) ? 'color: red;' : ''; ?>">
                <?php if (empty($connected_username)): ?>
                    <i>NULL</i>
                <?php else: ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=view_member_page&user_id=' . $connected_member_id)); ?>">
                        <?php echo esc_html($connected_username); ?>
                    </a>
                <?php endif; ?>
            </td>

            <td style="<?php echo empty($connected_first_name) ? 'color: red;' : ''; ?>">
                <?php echo empty($connected_first_name) ? '<i>NULL</i>' : esc_html($connected_first_name); ?>
            </td>

            <td style="<?php echo empty($connected_last_name) ? 'color: red;' : ''; ?>">
                <?php echo empty($connected_last_name) ? '<i>NULL</i>' : esc_html($connected_last_name); ?>
            </td>

            <td style="<?php echo empty($connected_title) ? 'color: red;' : ''; ?>">
                <?php echo empty($connected_title) ? '<i>NULL</i>' : esc_html($connected_title); ?>
            </td>

            <td style="<?php echo empty($connected_hunt_day_allocation) ? 'color: red;' : ''; ?>">
                <?php echo empty($connected_hunt_day_allocation) ? '<i>NULL</i>' : esc_html($connected_hunt_day_allocation); ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

                        </table>
                    <?php else: ?>
                        <p>No connected members.</p>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            $('#phone').on('input', function () {
                var phone = $(this).val();

                phone = phone.replace(/\D/g, '');

                if (phone.length > 10) {
                    phone = phone.slice(0, 10);
                    $(this).val(phone);
                }

                if (phone.length !== 10) {
                    $('#phoneError').text('Please enter a valid 10-digit phone number');
                } else {
                    $('#phoneError').text('');
                }
            });
        });
    </script>

    <?php
}

function create_member_page()
{
    ?>
    <style>
        .card {
            min-width: 100% !important;
        }

        .mem-form input {
            border: 1px solid #d9dfff;
            height: 35px;
            box-shadow: 0 0 1px #00000026;
        }

        .mem-form label {
            font-size: 13px;
            text-shadow: 0 0 5px #00000026;
        }
    </style>
    <div class="card">
        <div class="card-header border-0">
            <div class="d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1">Create Member</h5>
                <div class="flex-shrink-0">
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=members')); ?>">User
                            List</a>

                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form class="mem-form" id="userCreationForm" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                method="post">
                <input type="hidden" name="action" value="create_member">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name:</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username:</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="password" class="form-label">Password:</label>
                        <div class="input-group">
                            <button type="button" id="generatePassword" class="btn btn-secondary">Generate Password</button>
                            <input type="text" id="password" name="password" class="form-control password-input"
                                autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary show-password"
                                style="display: none;">Show</button>
                            <button type="button" class="btn btn-outline-primary hide-password">Hide</button>
                        </div>
                        <small id="passwordHelp" class="form-text text-muted"></small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone:</label>
                    <input type="number" id="phone" name="phone" class="form-control" required>
                    <span class="error" id="phoneError"></span>
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label">Title:</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="2"
                        style="border: 1px solid #ced4da;" required></textarea>
                </div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <label for="hunt_day_allocation" class="form-label">Total Hunt Day Allocation:</label>
                        <input type="number" id="hunt_day_allocation" name="hunt_day_allocation"
                            class="form-control allocation" required min="0" max="50">
                        <span class="error">(0-50)</span>
                    </div>

                    <div class="col-md-6">
                        <label for="summer_day_allocation" class="form-label">Total Summer Day Allocation:</label>
                        <input type="number" id="summer_day_allocation" name="summer_day_allocation"
                            class="form-control allocation" required min="0" max="50">
                        <span class="error">(0-50)</span>
                    </div>
                </div>

                <?php
global $wpdb;
$query = "SELECT * FROM {$wpdb->users} WHERE is_delete = 0";
$users = $wpdb->get_results($query);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <label for="connected_members" class="form-label">Connected Members:</label><br>
        <select id="connected_members" name="connected_members[]" class="form-control select2" multiple>
            <?php foreach ($users as $user) : ?>
                <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

                <button type="submit" class="btn btn-success">Submit</button>
            </form>
        </div>
    </div>
    <script>
        jQuery(document).ready(function ($) {

            $('#membersTable').DataTable(
                {
                    "dom": '<"dt-buttons"Bf><"clear">lirtp',
                    "paging": true,
                    "autoWidth": true,
                    "buttons": [
                        'colvis',
                        'copyHtml5',
                        'csvHtml5',
                        'excelHtml5',
                        'pdfHtml5',
                        'print'
                    ]
                }
            );

            $('.allocation').on('input', function () {
                var enteredValue = $(this).val();

                if (enteredValue < 0) {

                    $(this).val(0);
                } else if (enteredValue > 50) {

                    $(this).val(50);
                }
            });

            function calculatePasswordStrength(password) {
                var length = password.length;
                var hasUpperCase = /[A-Z]/.test(password);
                var hasLowerCase = /[a-z]/.test(password);
                var hasNumbers = /\d/.test(password);
                var hasSymbols = /[!@#$%^&*()_+]/.test(password);

                if (length < 8 || !(hasUpperCase && hasLowerCase && hasNumbers && hasSymbols)) {
                    return 1; // Weak password
                } else if (length < 12) {
                    return 2; // Medium password
                } else {
                    return 3; // Strong password
                }
            }


            $('#phone').on('input', function () {
                var phone = $(this).val();

                phone = phone.replace(/\D/g, '');

                if (phone.length > 10) {
                    phone = phone.slice(0, 10);
                    $(this).val(phone);
                }

                if (phone.length !== 10) {
                    $('#phoneError').text('Please enter a valid 10-digit phone number');
                } else {
                    $('#phoneError').text('');
                }
            });

            $('#generatePassword').on('click', function () {
                var generatedPassword = generateRandomPassword();
                $('#password').val(generatedPassword);
                updatePasswordStrength(generatedPassword);
            });
            $(".show-password, .hide-password").on('click', function () {
                var passwordField = $("#password");
                var passwordType = passwordField.attr('type');

                if (passwordType === 'password') {
                    passwordField.attr("type", "text");
                    $(".show-password").hide();
                    $(".hide-password").show();
                } else {
                    passwordField.attr("type", "password");
                    $(".hide-password").hide();
                    $(".show-password").show();
                }
            });

            $('#password').on('input', function () {
                var password = $(this).val();

                updatePasswordStrength(password);
            });

            $('#togglePassword').on('click', function () {
                var passwordField = $('#password');
                var passwordType = passwordField.attr('type');
                passwordField.attr('type', passwordType === 'password' ? 'text' : 'password');
            });

            $('#userCreationForm').on('submit', function (e) {
                e.preventDefault();

                var password = $('#password').val();
                var strength = calculatePasswordStrength(password);

                // Check if password strength is weak
                if (strength === 1) {
    Swal.fire({
        title: "Weak Password",
        text: "Please choose a stronger password.",
        icon: "error",
        confirmButtonText: "OK",
    }).then(() => {
        // Scroll to the passwordHelp element
        $('html, body').animate({
            scrollTop: $("#passwordHelp").offset().top
        }, 1000);
    });
    return; // Prevent form submission
}


                var username = $('#username').val();
                var first_name = $('#first_name').val();
                var last_name = $('#last_name').val();
                var email = $('#email').val();
                // var password = $('#password').val();
                var title = $('#title').val();
                var description = $('#description').val();

                var connectedMembers = $('#connected_members').val();
                var phone = $('#phone').val();
                var hunt_day_allocation = $('#hunt_day_allocation').val();
                var summer_day_allocation = $('#summer_day_allocation').val();

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'create_member',
                        username: username,
                        email: email,
                        password: password,
                        title: title,
                        description: description,
                        connected_members: connectedMembers,
                        first_name: first_name,
                        last_name: last_name,
                        phone: phone,
                        hunt_day_allocation: hunt_day_allocation,
                        summer_day_allocation: summer_day_allocation,
                    },
                    success: function (response) {
                        console.log(response);
    if (response.success) {
        Swal.fire({
            title: 'Success',
            text: response.message, // This will display "User created successfully."
            icon: 'success'
        });
        window.location.href = "<?php echo esc_url(admin_url('admin.php?page=members')); ?>";
    } else {
        Swal.fire({
            title: 'Error',
            text: 'Duplicate email Please try another one', // This will display the error message like "Invalid username or email."
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
}


                });
            });

            function generateRandomPassword() {
                var length = 12;
                var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
                var password = "";

                var uppercaseChar = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                var lowercaseChar = "abcdefghijklmnopqrstuvwxyz";
                var numberChar = "0123456789";
                var symbolChar = "!@#$%^&*()_+";

                password += getRandomChar(uppercaseChar);
                password += getRandomChar(lowercaseChar);
                password += getRandomChar(numberChar);
                password += getRandomChar(symbolChar);

                for (var i = 4; i < length; i++) {
                    password += charset.charAt(Math.floor(Math.random() * charset.length));
                }

                password = password.split('').sort(function () {
                    return 0.5 - Math.random();
                }).join('');

                return password;
            }

            function getRandomChar(charset) {
                return charset.charAt(Math.floor(Math.random() * charset.length));
            }

            function updatePasswordStrength(password) {
                var strength = calculatePasswordStrength(password);
                var strengthText = getStrengthText(strength);
                var feedbackColor = getStrengthColor(strength);
                $('#passwordHelp').text('Password Strength: ' + strengthText).css('color', feedbackColor);
            }

            function calculatePasswordStrength(password) {

                var length = password.length;
                var hasUpperCase = /[A-Z]/.test(password);
                var hasLowerCase = /[a-z]/.test(password);
                var hasNumbers = /\d/.test(password);
                var hasSymbols = /[!@#$%^&*()_+]/.test(password);

                if (length < 8 || !(hasUpperCase && hasLowerCase && hasNumbers && hasSymbols)) {
                    return 1;
                } else if (length < 12) {
                    return 2;
                } else {
                    return 3;
                }
            }

            function getStrengthText(strength) {
                switch (strength) {
                    case 1:
                        return 'Weak';
                    case 2:
                        return 'Medium';
                    case 3:
                        return 'Strong';
                    default:
                        return '';
                }
            }

            function getStrengthColor(strength) {
                switch (strength) {
                    case 1:
                        return 'red';
                    case 2:
                        return 'orange';
                    case 3:
                        return 'green';
                    default:
                        return '';
                }
            }
        });
    </script>

    <?php
}

function edit_member_page()
{
    ?>
    <style>
        .card {
            min-width: 100% !important;
        }
    </style>

    <div class="wrap">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Edit Member Details</h5>
                    <div class="flex-shrink-0">
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-secondary"
                                href="<?php echo esc_url(admin_url('admin.php?page=members')); ?>">Members List</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php
                $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
                $user_data = get_userdata($user_id);

                if ($user_data):
                    $registration_date = get_user_meta($user_id, 'user_registered', true);
                    $connected_members = get_user_meta($user_id, 'connected_members', true);
                    ?>
                    <form id="userEditForm" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="edit_member">
                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name:</label>
                                <input type="text" id="first_name" name="first_name" class="form-control"
                                    value="<?php echo esc_html(get_user_meta($user_id, 'first_name', true)); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name:</label>
                                <input type="text" id="last_name" name="last_name" class="form-control"
                                    value="<?php echo esc_html(get_user_meta($user_id, 'last_name', true)); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="user_login" class="form-label">User Name:</label>
                                <input type="text" id="user_login" name="user_login"
                                    value="<?php echo esc_attr($user_data->user_login); ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo esc_attr($user_data->user_email); ?>" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control"
                                    value="<?php echo esc_html(get_user_meta($user_id, 'phone', true)); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title:</label>
                            <input type="text" id="title" name="title" class="form-control"
                                value="<?php echo esc_html(get_user_meta($user_id, 'title', true)); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description:</label>
                            <textarea id="description" name="description" class="form-control" rows="2"
                                style="border: 1px solid #ced4da;"
                                required><?php echo esc_html(get_user_meta($user_id, 'description', true)); ?></textarea>
                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label for="hunt_day_allocation" class="form-label">Total Hunt Day Allocation:</label>
                                <input type="number" id="hunt_day_allocation" name="hunt_day_allocation"
                                    class="form-control allocation" required min="0" max="50"
                                    value="<?php echo esc_html(get_user_meta($user_id, 'hunt_day_allocation', true)); ?>">
                                <span class="error">(0-50)</span>
                            </div>

                            <div class="col-md-6">
                                <label for="summer_day_allocation" class="form-label">Total Summer Day Allocation:</label>
                                <input type="number" id="summer_day_allocation" name="summer_day_allocation"
                                    class="form-control allocation" required min="0" max="50"
                                    value="<?php echo esc_html(get_user_meta($user_id, 'summer_day_allocation', true)); ?>">
                                <span class="error">(0-50)</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="connected_members" class="form-label">Connected Members:</label><br>
                            <select id="connected_members" name="connected_members[]" class="form-control" multiple>
                                <?php
                                if (!is_array($connected_members)) {
                                    $connected_members = array();
                                }

                                $all_users = get_users();
                                foreach ($all_users as $user) {
                                    $selected = in_array($user->ID, $connected_members) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->user_login) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success">Update</button>
                        <a class="btn btn-primary" href="<?php echo esc_url(wp_lostpassword_url()); ?>">Reset Password</a>

                    </form>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <p>User not found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {

            $('#connected_members').select2();
            $('.allocation').on('input', function () {
                var enteredValue = $(this).val();

                if (enteredValue < 0) {

                    $(this).val(0);
                } else if (enteredValue > 50) {

                    $(this).val(50);
                }
            });

            $('#phone').on('input', function () {
                var phone = $(this).val();

                phone = phone.replace(/\D/g, '');

                if (phone.length > 10) {
                    phone = phone.slice(0, 10);
                    $(this).val(phone);
                }

                if (phone.length !== 10) {
                    $('#phoneError').text('Please enter a valid 10-digit phone number');
                } else {
                    $('#phoneError').text('');
                }
            });
        });
    </script>
    <?php
}

add_action('admin_post_create_member', 'create_member_callback');
add_action('admin_post_nopriv_create_member', 'create_member_callback');

function add_club_member_menu_item()
{
    if (current_user_can('club_member')) {
        add_menu_page(
            'Member Profile',
            'Member Profile',
            'club_member',
            'club_members_page',
            'club_members_page_callback',
            'dashicons-groups',
            20
        );
    }
}

function club_members_page_callback()
{
    $user_id = get_current_user_id();
    $user_data = get_userdata($user_id);
    $connected_members = get_user_meta($user_id, 'connected_members', true);
    if (isset($_POST['update_profile'])) {

        $new_email = sanitize_email($_POST['new_email']);
        $new_phone = sanitize_text_field($_POST['new_phone']);

        wp_update_user(array('ID' => $user_id, 'user_email' => $new_email));
        update_user_meta($user_id, 'phone', $new_phone);

        $user_data = get_userdata($user_id);
    }
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
        <!-- First Card: User Information -->
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Member Details</h5>
                    <div class="flex-shrink-0">

                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($user_data): ?>
                    <form method="post" action="">

                        <table class="table">
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td>
                                    <?php echo esc_html($user_data->user_login); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>First Name:</strong></td>
                                <td>
                                    <?php echo esc_html(get_user_meta($user_id, 'first_name', true)); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Last Name:</strong></td>
                                <td>
                                    <?php echo esc_html(get_user_meta($user_id, 'last_name', true)); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>
                                    <input type="email" name="new_email" value="<?php echo esc_attr($user_data->user_email); ?>"
                                        required>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td>
                                    <input type="number" name="new_phone" id="phone"
                                        value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>">
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Password</strong></td>

                                <td>
                                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Reset Password</a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Title:</strong></td>
                                <td>
                                    <?php echo esc_html(get_user_meta($user_id, 'title', true)); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Total Hunt Day Allocation:</strong></td>
                                <td>
                                    <?php echo esc_html(get_user_meta($user_id, 'hunt_day_allocation', true)); ?>
                                </td>
                            </tr>

                        </table>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>

                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <p>User not found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <script>
        jQuery(document).ready(function ($) {
            $('#phone').on('input', function () {
                var phone = $(this).val();

                phone = phone.replace(/\D/g, '');

                if (phone.length > 10) {
                    phone = phone.slice(0, 10);
                    $(this).val(phone);
                }

                if (phone.length !== 10) {
                    $('#phoneError').text('Please enter a valid 10-digit phone number');
                } else {
                    $('#phoneError').text('');
                }
            });
        });
    </script>

    <?php
}

add_action('admin_menu', 'add_club_member_menu_item');

?>