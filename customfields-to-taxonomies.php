<?php
/**
 * Plugin Name: Convert custom fields to custom taxonomies
 * Description: The two major systems for adding data to posts in WordPress are custom taxonomies and custom fields. This plugin is useful to convert custom fields into custom taxonomies.
 * Version: 1.0.3
 * Author: Shounak Gupte
 * Author URI: http://www.shounakgupte.com
 * License: GPLv3
 */

define( 'CTF_TAX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CTF_TAX_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CTF_TAX_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

add_action( 'init', 'ctf_tax_init_functions', 99 );
function ctf_tax_init_functions() {
	
	/* Enqueue scripts for backend */
	function ctf_tax_backend_enqueue() {
		if( isset($_GET['page']) && $_GET['page'] == 'convert-customfields-to-taxonomies' ) {
			wp_enqueue_style( 'ctf_tax_backend_style', CTF_TAX_PLUGIN_URL .'css/ctf_tax.css' );
			
			wp_enqueue_script( 'ctf_tax_backend_script', CTF_TAX_PLUGIN_URL .'js/ctf_tax.js',array('jquery'), '', true );
			wp_localize_script( 'ctf_tax_backend_script', 'ctf', array( 
				'ajax_url' => admin_url( 'admin-ajax.php' )
			));
		}
	}
	add_action( 'admin_enqueue_scripts', 'ctf_tax_backend_enqueue', 99 );
	
	/* Add Action Link */
	add_filter( 'plugin_action_links_' . CTF_TAX_PLUGIN_BASENAME, 'ctf_tax_action_link' );
	function ctf_tax_action_link( $links ) {
		$links[] = '<a href="'. admin_url( 'admin.php?page=convert-customfields-to-taxonomies' ) .'">'. __( 'Start Convert', 'ctf-tax' ) .'</a>';
		return $links;
	}
	
	/* Add Menu Item on Backend */
	add_action('admin_menu', 'ctf_tax_plugin_menu', 9);
	function ctf_tax_plugin_menu() {
		add_menu_page('Custom Fields to Taxonomies', 'Custom Fields to Taxonomies', 'administrator', 'convert-customfields-to-taxonomies', 'ctf_tax_plugin_settings_page', 'dashicons-image-rotate');
	}
	function ctf_tax_plugin_settings_page() { ?>
		<div class="clear"></div>
		<div class="process">
			<h2><?php _e('Convert Custom Field to Taxonomy','ctf-tax'); ?></h2>
			<div class="wrap_ctf_to_tax">
				<form id="ctf_tax_convert" method="post" action="">
					<p>
						<label style="width: 20%; float: left;margin-top:5px;"><b><?php _e('Select Custom Field','ctf-tax'); ?>: </b></label>
						<select name="ctf_key" id="ctf_key">
							<option value=""><?php _e('Select Key','ctf-tax'); ?></option>
							<?php $cf_key = ctf_tax_get_metakey_list(); ?>
							<?php if( is_array( $cf_key ) ) : foreach( $cf_key as $cf ) : ?>
							<option value="<?php echo esc_attr($cf); ?>"><?php echo esc_html($cf); ?></option>
							<?php endforeach; endif; ?>
						</select>
						
						<span id="count" class="meta_key_count"><?php _e('Posts Count','ctf-tax'); ?>: <span id="post_count">0</span></span>
					</p>
					
					<p>
						<label style="width: 20%; float: left;margin-top:5px;"><b><?php _e('Select Taxonomy','ctf-tax'); ?>: </b></label>
						<select name="ctf_tax" id="ctf_tax">
							<?php $ctf_tax = ctf_tax_get_tax_list(); ?>
							<?php if( is_array( $ctf_tax ) ) : foreach( $ctf_tax as $key=>$name ) : ?>
							<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
							<?php endforeach; endif; ?>
						</select>
					</p>
					
					<p>
						<label style="width: 20%; float: left;margin-top:5px;"><?php _e('Offset','ctf-tax'); ?>: </label>
						<input type="text" name="ctf_offset" id="ctf_offset" value="0" style="width: 50px;" />
					</p>
					<p class="noted">
						<?php _e('Example: Posts count = 1000. If offset = 10, we will convert posts from 10 to 1000.','ctf-tax'); ?>
					</p>
					
					<p>
						<label style="width: 20%; float: left;margin-top:5px;"><?php _e('Separate tags with','ctf-tax'); ?>: </label>
						<input type="text" name="ctf_separate" id="ctf_separate" value="," style="width: 30px;padding-left:10px;padding-right:10px;" />
					</p>
					
					<p class="noted">
						<?php _e('Example: Custom field value is: <b>London, Liverpool</b>, after convert, we have 2 taxonomies <b>London</b> and <b>Liverpool</b>','ctf-tax'); ?>
					</p>

					<p class="submit" style="margin-top: 0px !important;">
						<input type="submit" name="start_convert" disabled value="Start Convert" id="start_convert" style="cursor: pointer; padding: 6px 20px !important;font-size: 15px; background: #191e23;border: 0; color: #fff; border: 1px solid #ccc;outline: none;text-transform: uppercase;">
						
						<span class="spinner"></span>
						<span class="log"></span>
					</p>
					
					<input type="hidden" value="" id="post_ids" />
					<input type="hidden" value="" id="post_count_completed" />
					<hr />
					<div class="message"><?php _e('Note:', 'ctf-tax'); ?></div>
					<div class="message"><?php _e('- Please don\'t reload the page when converting', 'ctf-tax'); ?></div>
					<div class="message"><b><?php _e('- Please backup database for your website before conversion', 'ctf-tax'); ?></b></div>
				</form>
			</div>
		</div>
		<div class="history">
			<h2><?php _e('History','ctf-tax'); ?></h2>
			<?php
				$history = ctf_get_history();
				if( $history ) {
			?>
				<table class="table_history">
					<tr>
						<th><?php _e('Meta Key','ctf-tax'); ?></th>
						<th><?php _e('Taxonomy','ctf-tax'); ?></th>
						<th><?php _e('Process','ctf-tax'); ?></th>
					</tr>
				<?php foreach( $history as $his ) { $value = maybe_unserialize( $his->option_value ); ?>
					<?php if( is_array($value) && isset($value['key']) ) { ?>
					<tr>
						<td><?php echo esc_html( $value['key'] ); ?></td>
						<td><?php echo esc_html( $value['tax'] ); ?></td>
						<td><a class="ctf_continue_convert" href="#" title="Click to continue" data-key="<?php echo esc_attr( $value['key'] ); ?>" data-tax="<?php echo esc_attr( $value['tax'] ); ?>"><?php echo esc_html( $value['process'] ); ?></a></td>
					</tr>
				<?php } ?>
				<?php } ?>
				</table>
			<?php } ?>
		</div>
		<div class="clear"></div>
	<?php
	}
	
	function ctf_tax_get_metakey_list() {
		global $wpdb;
		$meta_key = $wpdb->get_col( "
			SELECT meta_key
			FROM $wpdb->postmeta
			GROUP BY meta_key
			ORDER BY meta_key ASC
		" );
		
		return apply_filters( 'ctf_tax_get_metakey_list', $meta_key );
	}
	
	function ctf_tax_metakey_count( $meta_key ) {
		if( empty($meta_key) ) return 0;
		
		global $wpdb;
		$ids = $wpdb->get_results( $wpdb->prepare("
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = %s
		", esc_sql( $meta_key ) ) );
		
		$post_ids = array();
		if( is_array( $ids ) && sizeof( $ids ) ) {
			foreach( $ids as $id ) {
				$post_ids[] = $id->post_id;
			}
		}
		
		$post_ids = array_unique($post_ids);
		$count = sizeof($post_ids);
		
		return apply_filters( 'ctf_tax_metakey_count', $count, $meta_key );
	}
	
	function ctf_tax_get_post_ids_by_metakey( $meta_key ) {
		if( empty($meta_key) ) return '';
		
		global $wpdb;
		$ids = $wpdb->get_results( $wpdb->prepare("
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = %s
			ORDER BY post_id DESC
		", esc_sql( $meta_key ) ) );
		
		$post_ids = array();
		if( is_array( $ids ) && sizeof( $ids ) ) {
			foreach( $ids as $id ) {
				$post_ids[] = $id->post_id;
			}
		}
		if( is_array( $post_ids ) ) {
			$post_ids = array_unique($post_ids);
			$post_ids = implode( ',', $post_ids );
		}
		
		return apply_filters( 'ctf_tax_get_post_ids_by_metakey', $post_ids, $meta_key );
	}
	
	function ctf_tax_get_tax_list() {
		$taxonomies = array();
		foreach ( get_taxonomies( array( 'show_ui' => true ), 'objects' ) as $tax_name => $tax_obj )
			$taxonomies[$tax_name] = $tax_obj->label ? $tax_obj->label : $tax_name;

		return apply_filters( 'ctf_tax_get_tax_list', $taxonomies );
	}
	
	function ctf_convert_customfield_to_term( $post_id = 0, $meta_key = '', $taxonomy = '', $separate = ',' ) {
		$meta_value = get_post_meta( $post_id, $meta_key, true );
		if( ! is_string( $meta_value ) ) return false;
		if( !taxonomy_exists( $taxonomy ) ) return false;
		
		$meta_value = explode( $separate, $meta_value );
		wp_set_object_terms( $post_id, $meta_value, $taxonomy );
		return true;
	}

	function ctf_get_history() {
		global $wpdb;
		$history = $wpdb->get_results( "
			SELECT *
			FROM $wpdb->options
			WHERE `option_name` LIKE '%ctf_tax_history_%'
			ORDER BY option_id DESC
		" );
		return apply_filters( 'ctf_get_history', $history );
	}
	
	add_action('wp_ajax_ctf_get_posts_count', 'ctf_get_posts_count_action');
	add_action('wp_ajax_nopriv_ctf_get_posts_count', 'ctf_get_posts_count_action');

	function ctf_get_posts_count_action() {
		$key = sanitize_text_field( $_POST['ctf_key'] );
		echo ctf_tax_metakey_count( $key ); die();
	}
	
	add_action('wp_ajax_ctf_get_post_ids', 'ctf_get_post_ids_action');
	add_action('wp_ajax_nopriv_ctf_get_post_ids', 'ctf_get_post_ids_action');

	function ctf_get_post_ids_action() {
		$key = sanitize_text_field( $_POST['ctf_key'] );
		echo ctf_tax_get_post_ids_by_metakey( $key ); die();
	}

	add_action('wp_ajax_ctf_convert_ctf', 'ctf_convert_ctf_action');
	add_action('wp_ajax_nopriv_ctf_convert_ctf', 'ctf_convert_ctf_action');

	function ctf_convert_ctf_action() {
		$key = sanitize_text_field( $_POST['ctf_key'] );
		$tax = sanitize_text_field( $_POST['ctf_tax'] );
		$separate = sanitize_text_field( $_POST['ctf_separate'] );
		$number_completed = sanitize_text_field( $_POST['number_completed'] );
		$count = intval( $_POST['count'] );
		$post_ids = $car = explode( ',', sanitize_text_field( $_POST['post_ids'] ) );
		
		do_action( 'ctf_before_convert', $key, $tax, $separate, $number_completed, $count, $post_ids );
		
		$history = array(
			'process' => $number_completed . '/'. $count,
			'key' => $key,
			'tax' => $tax
		);
		update_option( 'ctf_tax_history_'. $key .'_'. $tax, $history );
		foreach( $post_ids as $id ) {
			$id = trim($id);
			ctf_convert_customfield_to_term($id, $key, $tax, $separate);
		}
		
		do_action( 'ctf_after_convert', $key, $tax, $separate, $number_completed, $count, $post_ids );
		echo 1; die(); 
	}
	
	add_action('wp_ajax_ctf_update_history', 'ctf_update_history_action');
	add_action('wp_ajax_nopriv_ctf_update_history', 'ctf_update_history_action');

	function ctf_update_history_action() {
		$key = sanitize_text_field( $_POST['ctf_key'] );
		$tax = sanitize_text_field( $_POST['ctf_tax'] );
		$separate = sanitize_text_field( $_POST['ctf_separate'] );
		$number_completed = sanitize_text_field( $_POST['number_completed'] );
		$count = intval( $_POST['count'] );
		$post_ids = $car = explode( ',', sanitize_text_field( $_POST['post_ids'] ) );
		$history = array(
			'process' => $number_completed . '/'. $count,
			'key' => $key,
			'tax' => $tax
		);
		update_option( 'ctf_tax_history_'. $key .'_'. $tax, $history );
		echo 1; die(); 
	}
}