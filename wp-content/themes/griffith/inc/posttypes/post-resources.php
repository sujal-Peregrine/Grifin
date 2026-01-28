<?php
	if (!function_exists('griffith_resources_post_type')) {
		function griffith_resources_post_type() {
			$labels = array(
				'name'               => esc_html__('Club Resources', 'griffith'),
				'singular_name'      => esc_html__('Club Resource', 'griffith'),
				'menu_name'          => esc_html__('Club Resources', 'griffith'),
				'name_admin_bar'     => esc_html__('Club Resource', 'griffith'),
				'add_new'            => esc_html__('Add New', 'griffith'),
				'add_new_item'       => esc_html__('Add New Resources', 'griffith'),
				'new_item'           => esc_html__('New Resources', 'griffith'),
				'edit_item'          => esc_html__('Edit Resources', 'griffith'),
				'view_item'          => esc_html__('View Resources', 'griffith'),
				'view_items'         => esc_html__('View Resources', 'griffith'),
				'all_items'          => esc_html__('All Resources', 'griffith'),
				'search_items'       => esc_html__('Search Resources', 'griffith'),
				'parent_item_colon'  => esc_html__('Parent Resources:', 'griffith'),
				'not_found'          => esc_html__('No resources found.', 'griffith'),
				'not_found_in_trash' => esc_html__('No resources found in Trash.', 'griffith')
			);
			$args = array(
				'labels'             => $labels,
				'description'        => esc_html__('Description', 'griffith'),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array('slug' => 'resources', 'with_front' => false),
				'has_archive'        => false,
				'hierarchical'       => true,
				'menu_position'      => null,
				'capability_type'    => 'post',
				'menu_icon' 		 => null,		
				'supports'           => array('title')
			);
			register_post_type('griffith_resources', $args);
		}
	}
	add_action('init', 'griffith_resources_post_type');
	add_action('edit_form_after_editor', function() {
		global $post;
		if ($post->post_type == 'griffith_resources') {
			$griffith_resources_excerpt = sanitize_text_field(get_post_meta($post->ID, 'griffith_resources_excerpt', true));
			$griffith_resources_button = sanitize_text_field(get_post_meta($post->ID, 'griffith_resources_button', true));
			$griffith_resources_link = sanitize_text_field(get_post_meta($post->ID, 'griffith_resources_link', true));
			$griffith_resources_icon = sanitize_text_field(get_post_meta($post->ID, 'griffith_resources_icon', true));
			$griffith_resources_order = sanitize_text_field(get_post_meta($post->ID, 'griffith_resources_order', true));
			$griffith_resources_public = sanitize_text_field(get_post_meta($post->ID, 'griffith_resources_public', true));
			$orders = array(1, 2, 3, 4);
			foreach ($orders as $order) {
				$selected = ($order == $griffith_resources_order ? 'selected' : '');
				$order_options .= '<option value="' . $order . '" ' . $selected . '>' . $order . '</option>';
			}
		?>
			<style>.wp-media-buttons{display:none}</style>
			<div class="griffithisland-form-wrap">
				<ul class="griffithisland-tabs">
					<li class="griffithisland-tab-link current" data-tab="tab-1">
						<i class="fas fa-sliders-h-square fa-3x"></i>
					</li>
					<li class="griffithisland-tab-link" data-tab="tab-2">
						<i class="fab fa-font-awesome-flag fa-3x"></i>
					</li>
				</ul>
				<div id="tab-1" class="griffithisland-tab-content current">
					<ul>
						<li>
							<p><span><strong>Resource Label</strong></span><input id="griffith_resources_button" name="griffith_resources_button" type="text" value="<?php echo esc_html($griffith_resources_button); ?>" /></p>
						</li>
						<li>
							<p><span><strong>Resource Description</strong></span><input id="griffith_resources_excerpt" name="griffith_resources_excerpt" type="text" value="<?php echo esc_html($griffith_resources_excerpt); ?>" /></p>
						</li>
						<li>
							<p><span><strong>Resource Link</strong></span><input id="griffith_resources_link" name="griffith_resources_link" type="url" value="<?php echo esc_html($griffith_resources_link); ?>" /></p>
						</li>
						<li>
							<p><span><strong>Resource Order</strong></span><select name="griffith_resources_order" id="griffith_resources_order"><option value="">--</option><?php echo $order_options; ?></select></p>
						</li>
						<li>
							<p><span><strong>Resource Public</strong></span><select name="griffith_resources_public" id="griffith_resources_public"><option<?php if ($griffith_resources_public == '') echo ' selected'; ?> value="">--</option><option<?php if ($griffith_resources_public == true) echo ' selected'; ?> value="1">Yes</option></select></p>
						</li>
					</ul>
				</div>
                <div id="tab-2" class="griffithisland-tab-content">
					<ul>
						<li>
							<input class="griffith_icon" id="griffith_resources_icon" name="griffith_resources_icon" type="text" value="<?php echo esc_attr($griffith_resources_icon); ?>" />
						</li>
						<li>
							<div class="w3-bar w3-black">
						    	<button class="w3-bar-item w3-button tablink active" onclick="openIcons(event,'solid'); return false;">Solid</button>
						    	<button class="w3-bar-item w3-button tablink" onclick="openIcons(event,'regular'); return false;">Regular</button>
						    	<button class="w3-bar-item w3-button tablink" onclick="openIcons(event,'light'); return false;">Light</button>
						    	<button class="w3-bar-item w3-button tablink" onclick="openIcons(event,'duotone'); return false;">Duotone</button>
						  	</div>
							<div id="solid" class="w3-container w3-border icon">
							  <?php
									global $griffith_font_icons_solid;
									$griffith_fontawesome_icons = $griffith_font_icons_solid;
								?>
								<ul class="the-icons">	
									<?php
										foreach($griffith_fontawesome_icons as $griffith_resources_icons) {
											echo "<li><span" . ($griffith_resources_icons == $griffith_resources_icon ? ' class="current"' : false) ." id='" . esc_html($griffith_resources_icons) . "'><i class='". esc_html($griffith_resources_icons) ."'></i></span></li>";
										}
									?>
								</ul>
							</div>
							<div id="regular" class="w3-container w3-border icon" style="display:none">
							  <?php
									global $griffith_font_icons_regular;
									$griffith_fontawesome_icons = $griffith_font_icons_regular;
								?>
								<ul class="the-icons">	
									<?php
										foreach($griffith_fontawesome_icons as $griffith_resources_icons) {
											echo "<li><span" . ($griffith_resources_icons == $griffith_resources_icon ? ' class="current"' : false) ." id='" . esc_html($griffith_resources_icons) . "'><i class='". esc_html($griffith_resources_icons) ."'></i></span></li>";
										}
									?>
								</ul>
							</div>
							<div id="light" class="w3-container w3-border icon" style="display:none">
							  <?php
									global $griffith_font_icons_light;
									$griffith_fontawesome_icons = $griffith_font_icons_light;
								?>
								<ul class="the-icons">	
									<?php
										foreach($griffith_fontawesome_icons as $griffith_resources_icons) {
											echo "<li><span" . ($griffith_resources_icons == $griffith_resources_icon ? ' class="current"' : false) ." id='" . esc_html($griffith_resources_icons) . "'><i class='". esc_html($griffith_resources_icons) ."'></i></span></li>";
										}
									?>
								</ul>
							</div>
							<div id="duotone" class="w3-container w3-border icon" style="display:none">
							  <?php
									global $griffith_font_icons_duotone;
									$griffith_fontawesome_icons = $griffith_font_icons_duotone;
								?>
								<ul class="the-icons">	
									<?php
										foreach($griffith_fontawesome_icons as $griffith_resources_icons) {
											echo "<li><span" . ($griffith_resources_icons == $griffith_resources_icon ? ' class="current"' : false) ." id='" . esc_html($griffith_resources_icons) . "'><i class='". esc_html($griffith_resources_icons) ."'></i></span></li>";
										}
									?>
								</ul>
							</div>
						</li>
					</ul>
				</div>
			</div>
		<?php
		}
	});
	
	if (!function_exists('griffith_edit_resources_columns')) {
		function griffith_edit_resources_columns($columns) {
			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => esc_html__('Title', 'griffith'),	
				'icon' => esc_html__('Icon', 'griffith'),		
				'date' => esc_html__('Date', 'griffith')
			);
			return $columns;
		}
	}
	add_filter('manage_edit-griffith_resources_columns', 'griffith_edit_resources_columns');

	if (!function_exists('griffith_manage_resources_columns')) {
		function griffith_manage_resources_columns($column) {
			global $post;
			switch($column) {	
				case 'icon' : 
					echo "<i class='". esc_html($post->griffith_resources_icon) ." fa-4x'></i>";
				break;
				default :
				break;
			}
		}
	}
	add_action('manage_griffith_resources_posts_custom_column', 'griffith_manage_resources_columns', 10, 2);

	function griffith_resources_updated_messages($messages) {
		$post = get_post();
		$post_type = get_post_type($post);
		$post_type_object = get_post_type_object($post_type);
		$messages['griffith_resources'] = array(
			0 => '',
			1 => esc_html__('Club Resources Updated. ', 'griffith'),
			2 => esc_html__('Custom field updated. ', 'griffith'),
			3 => esc_html__('Custom field deleted. ', 'griffith'),
			4 => esc_html__('Club Resources Updated. ', 'griffith'),
			5 => isset($_GET['revision']) ? sprintf(esc_html__('Club resource restored to revision from %s ', 'griffith'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
			6 => esc_html__('Club Resource Published. ', 'griffith'),
			7 => esc_html__('Club Resource Saved. ', 'griffith'),
			8 => esc_html__('Club Resource Submitted. ', 'griffith'),
			9 => sprintf(esc_html__('Club resource scheduled for: <strong>%1$s</strong>. ', 'griffith'), date_i18n(__('M j, Y @ G:i', 'griffith'), strtotime($post->post_date))),
			10 => esc_html__('Club Resource Draft Updated. ', 'griffith')
		);
		if ($post_type_object->publicly_queryable && 'griffith_resources' === $post_type) {
			$permalink = get_permalink($post->ID);
			$view_link = sprintf('<a href="%s">%s</a>', esc_url($permalink), esc_html__('View Resource', 'griffith'));
			$messages[$post_type][1] .= $view_link;
			$messages[$post_type][6] .= $view_link;
			$messages[$post_type][9] .= $view_link;
			$preview_permalink = add_query_arg('preview', 'true', $permalink);
			$preview_link = sprintf('<a target="_blank" href="%s">%s</a>', esc_url($preview_permalink), esc_html__('Preview Resource', 'griffith'));
			$messages[$post_type][8]  .= $preview_link;
			$messages[$post_type][10] .= $preview_link;
		}
		return $messages;
	}
	add_filter('post_updated_messages', 'griffith_resources_updated_messages');

	if (!function_exists('griffith_custom_posts_save_resources')) {
		function griffith_custom_posts_save_resources($post_id) {
			if (empty($_POST)) return $post_id;
			if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX)) return;
			if ('page' == isset($_POST['post_type'])) {
				if (!current_user_can('edit_page', $post_id)) {
					return;
				}
			}
			else {
				if (!current_user_can('edit_post', $post_id)) {
				return;
				}
			}
			$griffith_fields = array(
				'griffith_resources_excerpt',
				'griffith_resources_button',
				'griffith_resources_link',
				'griffith_resources_icon',
				'griffith_resources_order',
				'griffith_resources_public'
			);
			foreach ($griffith_fields as $griffith_value) {
				if (isset($griffith_value)) {
					$griffith_new = false;
					$griffith_old = get_post_meta($post_id, $griffith_value, true);
					if (isset($_POST[$griffith_value])) {
						$griffith_new = $_POST[$griffith_value];
					}
					if (isset($griffith_new) && '' == $griffith_new && $griffith_old) {
						delete_post_meta($post_id, $griffith_value, $griffith_old);
					}
					elseif (false === $griffith_new || !isset($griffith_new)) {
						delete_post_meta($post_id, $griffith_value, $griffith_old);
					}
					elseif (isset($griffith_new) && $griffith_new != $griffith_old) {
						update_post_meta($post_id, $griffith_value, $griffith_new);
					}
					elseif (!isset($griffith_old) && isset($griffith_new)) {
						add_post_meta($post_id, $griffith_value, $griffith_new);
					}
				}
			}
		}	
	}
	add_action('save_post', 'griffith_custom_posts_save_resources');
?>