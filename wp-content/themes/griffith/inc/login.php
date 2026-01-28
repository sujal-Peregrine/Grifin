<?php
	function griffith_login_text($text) {
		if ($text == 'Lost your password?') {
			$text = '';
		}
		if ($text == 'Username or Email Address') {
			$text = 'Email Address';
		}
		return $text;
	}
	add_filter('gettext', 'griffith_login_text');

	function griffith_login_icons() {
		echo '<link rel="icon" href="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" sizes="32x32" />' . PHP_EOL;
		echo '<link rel="icon" href="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" sizes="192x192" />' . PHP_EOL;
		echo '<link rel="apple-touch-icon-precomposed" href="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" />' . PHP_EOL;
		echo '<meta name="msapplication-TileImage" content="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" />' . PHP_EOL;
	}
	add_action('login_head', 'griffith_login_icons');

	function griffith_login_css() {
		wp_enqueue_style('login-stylesheet', get_parent_theme_file_uri('/assets/css/login.css'), array(), '1.0', 'all');
	}
	add_action('login_enqueue_scripts', 'griffith_login_css');

    function griffith_login_header() {
        return get_bloginfo('url');
    }
    add_filter('login_headerurl', 'griffith_login_header');

    function griffith_login_header_text() {
		global $dashboard_settings;
        return $dashboard_settings['brokerage'] . ' Dashboard';
    }
    add_filter('login_headertext', 'griffith_login_header_text');

	function griffith_title() { 
		global $dashboard_settings;
		return $dashboard_settings['brokerage'] . ' Dashboard';
	}
	add_filter('login_title', 'griffith_title', 10, 2);

	function griffith_login_form() {
		echo '<p class="login-label"><span><em>Enter your email address and password to sign in.</em></span></p><p class="submit custom-submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Log In" /> <a class="button button-primary button-large button-reset" href="/wp-login.php?action=lostpassword">Send Password</a></p>' . PHP_EOL;
	}
	add_action('login_form', 'griffith_login_form');

    function griffith_login_auth($user, $email, $password) {
        $_POST['redirect_to'] = home_url('/wp-admin/');
        if (is_a($user, 'WP_User')) return $user;
        if (empty($email) && empty($password)) {
            if (is_wp_error($user)) return $user;
        }
        else if (empty($email)) {
            $error = new WP_Error();
            $error->add('empty_username', __('Your email address field is empty.'));
            return $error;
        }
		else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error = new WP_Error();
 			$error->add('empty_username', __('An invalid email address was entered.'));
			return $error;
		}
        else if (empty($password)) {
            $error = new WP_Error();
            if (empty($password)) $error->add('empty_password', __('Your password field is empty.'));
            return $error;
        }
		if ($email == 'dev@locn.tech') {
			$user = get_user_by('email', $email);
			if (!$user) return new WP_Error('invalid_username', __('An invalid email address was entered.'));
			$user = apply_filters('wp_authenticate_user', $user, $password);
			if (is_wp_error($user)) return $user;
			if (!wp_check_password($password, $user->user_pass, $user->ID)) return new WP_Error('incorrect_password', __('Your password entered is incorrect.'));
			return $user;
		}
		else {
            if (is_a($user, 'WP_User')) return $user;
            $email = trim($_POST['log']);
            $password = trim($_POST['pwd']);
			$external_db = new wpdb(DB_USER, DB_PASSWORD, DB_SITE, DB_HOST);
			$profile = $external_db->get_results("SELECT ID, post_title FROM wp_posts AS p, wp_postmeta AS m WHERE p.ID = m.post_id AND m.meta_key = 'royal_profile_email' AND m.meta_value = '$email' AND p.post_type = 'royal_profiles' AND p.post_status = 'publish' LIMIT 1");
			if (empty($profile[0]->ID)) {
				$error = new WP_Error();
				$error->add('invalid_username', __('An invalid email address was entered.'));
				return $error;
			}
			else {
				$id = $profile[0]->ID;
				$meta = $external_db->get_results("SELECT meta_key, meta_value FROM wp_postmeta WHERE post_id = '$id'");
				foreach ($meta AS $key => $value) {
					if ($value->meta_key == 'royal_profile_password') {
						$match = $value->meta_value;
					}
					else if ($value->meta_key == 'royal_profile_agent_id') {
						$agent = $value->meta_value;
					}
				}
				if (empty($match) || $match != $password) {
					$error = new WP_Error();
					$error->add('incorrect_password', __( 'Your password entered is incorrect.'));
					return $error;
				}
				else {
					$name = trim($profile[0]->post_title);
					$agent = trim($agent);
					setcookie('AGENT_ID', $id, time() + (365 * 24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN);
					setcookie('AGENT_NAME', esc_html($name), time() + (365 * 24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN);
					setcookie('AGENT_KEY', esc_html($agent), time() + (365 * 24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN);
					global $dashboard_settings;
                    $login = array();
                    $login['user_login'] = $dashboard_settings['email'];
                    $login['user_password'] = $dashboard_settings['password'];
                    $login['remember'] = false;
					$user = wp_signon($login, false);
                    if ($user->ID == $dashboard_settings['id'] && $user->roles[0] == 'subscriber') {
                        wp_set_current_user($user->ID);
                        wp_set_auth_cookie($user->ID);
                        wp_redirect('/wp-admin/');
                    }
                    else {
                        $error = new WP_Error();
                        $error->add('incorrect_password', __('The dashboard is currently offline at this time. Please try again later.'));
                        return $error;
                    }
				}
			}
		}
    }
    remove_filter ('authenticate', 'wp_authenticate_username_password', 20, 3);
    add_filter ('authenticate', 'griffith_login_auth', 20, 3);

	function griffith_lostpassword_button() {
		echo '<p class="submit custom-submit"><input type="submit" name="wp-agents" id="wp-agents" class="button button-primary button-large" value="Send" /></p>' . PHP_EOL;
	}
	add_action('lostpassword_form', 'griffith_lostpassword_button');

	function griffith_send_password($message) {
		global $errors;
		global $dashboard_settings;
		$err = $errors->get_error_codes();
		if (!empty($err)) return;
		if ($_GET['action'] == 'lostpassword') {
			return '<p class="message">Enter the email address associated with your <a target="_blank" href="https://' . $dashboard_settings['domain'] . '/realtors/"><strong>agent profile</strong></a>, then click Send to have the password emailed to you.</p>';
		}
		else {
			return $message;
		}
	}
	add_filter('login_message', 'griffith_send_password');

	function griffith_lostpassword_message($error) {
		global $errors;
		global $dashboard_settings;
		$err = $errors->get_error_codes();
		if ($_POST['wp-agents'] == 'Send') {
			if (in_array('empty_username', $err)) $error = 'Enter the email address used in your <a target="_blank" href="https://' . $dashboard_settings['domain'] . '/realtors/"><strong>agent profile</strong></a> to have your password sent to you.';
			else if (in_array('invalid_email', $err)) $error = 'No <a target="_blank" href="https://' . $dashboard_settings['domain'] . '/realtors/"><strong>agent profiles</strong></a> are using the email address you entered. Please try again.';
		}
		return $error;
	}
	add_filter('login_errors', 'griffith_lostpassword_message');

	function royal_remove_lostpassword () {
		if (isset($_GET['action'])) {
			if (in_array( $_GET['action'], array('retrievepassword'))) {
				wp_redirect(home_url('/wp-admin/'), 301);
				exit;
			}
		}
	}
	add_action('login_init', 'royal_remove_lostpassword');

	function griffith_lostpassword_post($errors) {
		global $dashboard_settings;
		$username = trim($_POST['user_login']);
		if (empty($username)) {
			$errors->add('empty_username', 'Enter the email address used in your <a target="_blank" href="https://' . $dashboard_settings['domain'] . '/realtors/"><strong>agent profile</strong></a> to have your password sent to you.');
		}
		else {
			$external_db = new wpdb(DB_USER, DB_PASSWORD, DB_SITE, DB_HOST);
			$profile = $external_db->get_results("SELECT ID, post_title FROM wp_posts AS p, wp_postmeta AS m WHERE p.ID = m.post_id AND m.meta_key = 'royal_profile_email' AND m.meta_value = '$username' AND p.post_type = 'royal_profiles' AND p.post_status = 'publish' LIMIT 1");
			wp_reset_query();
			if (empty($profile[0]->ID)) {
				$errors->add('invalid_email', 'No <a target="_blank" href="https://' . $dashboard_settings['domain'] . '/realtors/"><strong>agent profiles</strong></a> are using the email address you entered. Please try again.');
			}
			else {
				$id = $profile[0]->ID;
				$meta = $external_db->get_results("SELECT meta_key, meta_value FROM wp_postmeta WHERE post_id = '$id' AND meta_key = 'royal_profile_password' LIMIT 1");
				$password = $meta[0]->meta_value;
				if (empty($password)) {
					$password = substr(md5('pass'. $id), 0, 12);
					$insert = $external_db->query("INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$id', 'royal_profile_password', '$password') ON DUPLICATE KEY UPDATE meta_value = '$password'");
				}
				$name = trim($profile[0]->post_title);
				$email = $username;	
				$headers[] = "From: " . $dashboard_settings['brokerage'] . " <" . $dashboard_settings['email'] . ">";
				$message  = "";
				$message .= "Your password to sign in to your " . $dashboard_settings['brokerage'] . " Dashboard \n";
				$message .= home_url('/')."\n\n";
				$message .= "Email Address: " . $email . "\n";
				$message .= "Password: " . $password . "\n\n";
				wp_mail($name . "<" . $email . ">", $dashboard_settings['brokerage'] . " Dashboard Password", $message, $headers);
				wp_redirect('/wp-login.php?checkemail=confirm');
				wp_reset_query();
				exit;
			}
		}
	}
	add_action('lostpassword_post', 'griffith_lostpassword_post', 10, 1);

	function griffith_password_confirmation($translations, $text) {
		if (preg_match('/check your email/i', $text)) {
			$translations = '<strong>Your password has been successfully sent.</strong> Check your email to retrieve your password.';
		}
		return $translations;
	}
	add_filter('gettext', 'griffith_password_confirmation', 10, 3);

	function griffith_cookie_logout($expiration, $user_id, $remember) {
		global $dashboard_settings;
		if ($user->ID === $user_id) {
			$expiration = 31556926;
		}
		return $expiration;
	}
	add_filter('auth_cookie_expiration', 'griffith_cookie_logout', 10, 3);

	function griffith_after_logout() {
		unset($_COOKIE['AGENT_ID']);
		setcookie('AGENT_ID', null, strtotime('-1 day'));
		unset($_COOKIE['AGENT_NAME']);
		setcookie('AGENT_NAME', null, strtotime('-1 day'));
		unset($_COOKIE['AGENT_KEY']);
		setcookie('AGENT_KEY', null, strtotime('-1 day'));
	 }
	 add_action('wp_logout', 'griffith_after_logout');

	function griffith_login_redirect() {
		return '/wp-admin/';
	}
	add_filter('login_redirect', 'griffith_login_redirect', 10, 3);
	add_filter('login_display_language_dropdown', '__return_false');
?>