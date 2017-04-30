<?php
/*
Plugin Name: Comment Form Builder
Version: 0.2
Description: A simple plugin to add custom fields on comments.
Author: Ravi Shakya
License: GPL2
*/

/* Include necessary files */
include( plugin_dir_path( __FILE__ ) . 'functions/meta-box.php' );
include( plugin_dir_path( __FILE__ ) . 'functions/frontend.php' );
include( plugin_dir_path( __FILE__ ) . 'functions/edit-comment-meta.php' );
include( plugin_dir_path( __FILE__ ) . 'functions/options.php' );

/**
* Create post type comment form under comment menu
*/

add_action( 'init', 'cfb_register_post_type' );
function cfb_register_post_type() {

	/**
	 * Post Type: Comment Forms.
	 */

	$labels = array(
		"name" => __( 'Comment Forms', 'cfb' ),
		"singular_name" => __( 'Comment Form', 'cfb' ),
		'edit_item' => __( 'Edit Comment Form', 'cfb' ),
		'search_items' => __( 'Search Form', 'cfb' ),
	);

	$args = array(
		"label" => __( 'Comment Forms', 'cfb' ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"has_archive" => false,
		"show_in_menu" => "edit-comments.php",
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "comment_form_builder", "with_front" => true ),
		"query_var" => true,
		"supports" => array( "title" ),
		'capabilities' => array(
		  	'create_posts' => cfb_check_comment_form(), 
		),
	);

	register_post_type( "comment_form_builder", $args );
}

/**
* For free version allow only one post creation
* @param returns boolean
* If true, allow create post
*/

function cfb_check_comment_form(){

	$args = array(
		'post_type' => 'comment_form_builder',
		'post_status' => array( 'publish' , 'pending' , 'draft', 'future', 'private', 'inherit', 'trash' ),
		'posts_per_page' => 1
	);

	$query = new WP_Query( $args );

	if( $query->have_posts() && cfb_is_plugin_active( 'pro-addons-comment-form-builder/index.php' ) == false ):
		return false;
	else:
		return true;
	endif;

}

add_action( 'admin_enqueue_scripts', 'cfb_custom_wp_admin_style' );
function cfb_custom_wp_admin_style() {

	global $post;
	$screen = get_current_screen();
	//echo '<pre>'; print_r($screen); echo '</pre>';

	/**
	* Display in comment_form_builder post type
	* Display in comment listings
	*/

	if( 
		( is_object( $post ) && $post->post_type == 'comment_form_builder' ) 
		|| 
		$screen->base == 'edit-comments' 
		||
		( !empty( $_GET['action'] ) && $_GET['action'] = 'editcomment' && $screen->base == 'comment'  )
		||
		( !empty( $_GET['page'] ) && $_GET['page'] == 'cfb' ) 
		||
		( !empty( $_GET['page'] ) && $_GET['page'] == 'cfb_abt' )
	){

		wp_enqueue_style( 'cfb_wp_admin_css', plugin_dir_url( __FILE__ ) . 'css/backend-style.css', false, '1.0.0' );
	    wp_enqueue_script( 'jquery-ui-sortable' );
	    wp_register_script('cfb_wp_admin_js', plugin_dir_url( __FILE__ ) . 'js/backend.js', array( 'jquery' ) , '1.0.0' );

	    // Localize the script with new data
		$translation_array = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'loader' => admin_url( 'images/spinner.gif' ),
		);
		wp_localize_script( 'cfb_wp_admin_js', 'cfb_object', $translation_array );
		wp_enqueue_script( 'cfb_wp_admin_js' );

		cfb_scripts_frontend_back();

	}


}

add_action( 'wp_enqueue_scripts', 'cfb_frontend_scripts' );
function cfb_frontend_scripts() {

    wp_enqueue_style( 'cfb_styles', plugin_dir_url( __FILE__ ) . 'css/frontend-style.css' , false );    
    /* Add jquery validation */
    wp_enqueue_script( 'cfb_jquery_validation', plugin_dir_url( __FILE__ ) . 'js/jquery.validate.min.js', array( 'jquery' ), '1.0.0' );
    wp_enqueue_script( 'cfb_jquery_additional_methods', plugin_dir_url( __FILE__ ) . 'js/additional-methods.min.js',  array( 'jquery' ), '1.0.0' );

    wp_register_script( 'cfb_jquery_frontend', plugin_dir_url( __FILE__ ) . 'js/frontend.js',  array( 'jquery' ), '1.0.0' );

    global $post;
    $translation_array = array(
		'rules' => cfb_get_validation_rules( $post ),
		'messages' => cfb_get_validation_messages( $post ),
		'pro_version' => cfb_is_plugin_active( 'pro-addons-comment-form-builder/index.php' ) ? 'active' : 'inactive',
		'comment_form_id' => get_option( 'cfb_comment_form_id' ),
		'offset' => get_option( 'cfb_error_offset' , 50 )

	);

	wp_localize_script( 'cfb_jquery_frontend', 'cfb_object', $translation_array );
	wp_enqueue_script( 'cfb_jquery_frontend' );
	cfb_scripts_frontend_back();
	
}

function cfb_scripts_frontend_back(){

	if( cfb_is_plugin_active( 'pro-addons-comment-form-builder/index.php' ) ){

		$google_map_api = get_option( 'google_map_api' );

		/**
		* Add datepicker scripts and styles
		*/

		wp_enqueue_script( 'jquery-ui-datepicker' , array( 'jquery' ) );

		/**
		* Add jquery filer scripts and styles
		*/

		wp_enqueue_style( 'cfb_jquery.filer.css', plugin_dir_url( __FILE__ ) . 'css/jquery.filer.css' , false );
		wp_enqueue_style( 'cfb_jquery.filer-dragdropbox-theme.css', plugin_dir_url( __FILE__ ) . 'css/jquery.filer-dragdropbox-theme.css' , false );
		wp_enqueue_style( 'cfb_jquery.filer-icons-css', plugin_dir_url( __FILE__ ) . 'fonts/jquery.filer-icons/jquery-filer.css' , false );
		wp_enqueue_script( 'cfb_jquery_filter', plugin_dir_url( __FILE__ ) . 'js/jquery.filer.min.js',  array( 'jquery' ), '1.0.0' );

		wp_enqueue_script( 'cfb_front_n_backend_js', plugin_dir_url( __FILE__ ) . 'js/frontend-n-backend.js',  array( 'jquery' ), '1.0.0' );

		wp_enqueue_script( 'cfb_google_maps_api', '//maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key=' . $google_map_api, array(), '1.0.0', false );

		wp_enqueue_script( 'cfb_google_captcha_api', '//www.google.com/recaptcha/api.js?onload=cfb_captchaCallback&render=explicit', array(), '1.0.0', true );


	}

}

function cfb_get_validation_messages( $post ){

	$comment_form_id = cfb_get_comment_form_id( $post );
	$messages = array();

	$extra_fields = get_post_meta( $comment_form_id , 'comment_custom_fields' , true );

	if( empty( $extra_fields ) || !is_array( $extra_fields ) ){
		return json_encode( array() );
	}

	foreach ($extra_fields as $key => $value) {

		if( !empty( $value['type'] ) ){

			switch ( $value['type'] ) {

				case 'textarea':

					if( $value['required'] == 'yes' ){
						$name = $value['name'];
						$messages[$name]['checkSpaces'] = esc_html__( 'This field is required.', 'cfb' ); ;
					}

					break;

				case 'multi_select':

					if( $value['required'] == 'yes' ){
						$name = $value['name'] . '[]';
						$messages[$name]['multi_select'] = esc_html__( 'Please select atleast one element.', 'cfb' );
					}

					break;

				case 'url':

					$name = $value['name'];
					$messages[$name]['checkurl'] = esc_html__( 'Please enter a valid URL.', 'cfb' );

					break;
				
				default:
					# code...
					break;
			}

		}

	}

	return json_encode( apply_filters( 'cfb_validation_messages' , $messages ) );

}

function cfb_get_validation_rules( $post ){

	$comment_form_id = cfb_get_comment_form_id( $post );
	$rules = array();

	$extra_fields = get_post_meta( $comment_form_id , 'comment_custom_fields' , true );

	if( empty( $extra_fields ) || !is_array( $extra_fields ) ){
		return json_encode( array() );
	}

	foreach ($extra_fields as $key => $value) {

		if( !empty( $value['type'] ) ){

			switch ( $value['type'] ) {

				case 'comment':
					$rules['comment']['required'] = true;
					break;

				case 'name':
					$rules['author']['required'] = true;
					$rules['author']['minlength'] = 3;
					break;

				case 'predefined_email':
					$rules['email']['required'] = true;
					$rules['email']['email'] = true;
					break;

				case 'text':

					if( $value['required'] == 'yes' ){
						$name = $value['name'];
						$rules[$name]['required'] = true;
					}

					break;

				case 'textarea':

					if( $value['required'] == 'yes' ){
						$name = $value['name'];
						$rules[$name]['required'] = true;
						$rules[$name]['checkSpaces'] = true;
					}

					break;

				case 'radio':

					if( $value['required'] == 'yes' ){
						$name = $value['name'];
						$rules[$name]['required'] = true;
					}

					break;

				case 'checkbox':

					if( $value['required'] == 'yes' ){
						$name = $value['name'] . '[]';
						$rules[$name]['required'] = true;
					}

					break;

				case 'dropdown':
					$rules = cfb_get_select_validation_rule( $value , $rules );
					break;

				case 'multi_select':
					$rules = cfb_get_multiselect_validation_rule( $value , $rules );
					break;

				case 'url':

					$name = $value['name'];
					if( $value['required'] == 'yes' ){
						$rules[$name]['required'] = true;
					}
					$rules[$name]['checkurl'] = true;

					break;

				case 'email':

					$name = $value['name'];
					if( $value['required'] == 'yes' ){
						$rules[$name]['required'] = true;
					}
					$rules[$name]['email'] = true;

					break;

				case 'taxonomy':

					switch ( $value['display_option'] ) {

						case 'select':	
						case 'radio':						
							$rules = cfb_get_select_validation_rule( $value , $rules );
							break;

						case 'multi_select':
						case 'checkbox':
							$rules = cfb_get_multiselect_validation_rule( $value , $rules , 'checkbox' );
							break;
						
						default:
							# code...
							break;
					}

					break;

				case 'date':

					$name = $value['name'];
					if( $value['required'] == 'yes' ){
						$rules[$name]['required'] = true;
					}
					
					$rules[$name]['date'] = false;

					break;

				case 'file_upload':
				case 'user_image':

					$name = $value['name'] . '[]';
					if( $value['required'] == 'yes' ){
						$rules[$name]['required'] = true;
					}

					break;

				case 'google_maps':
					$name = $value['name'];
					if( $value['required'] == 'yes' ){
						$rules[$name]['required'] = true;
					}
					break;

				case 'reCaptcha':
					$rules['g-recaptcha-response']['required'] = true;
					break;

				case 'really_simple_captcha':
					$name = $value['name'];
					$rules[$name]['required'] = true;
					break;

				default:
					# code...
					break;
			}

		}

	}

	//echo '<pre>'; print_r( $rules ); echo '</pre>';
	return json_encode( apply_filters( 'cfb_validation_rules' , $rules ) );

}

function cfb_get_multiselect_validation_rule( $value , $rules , $type = null ){

	if( $value['required'] == 'yes' ){
		$name = $value['name'] . '[]';
		$rules[$name]['required'] = true;

		if( $type != 'checkbox' ){
			$rules[$name]['multi_select'] = true;
		}
	}
	return $rules;
}

function cfb_get_select_validation_rule( $value , $rules ){

	if( $value['required'] == 'yes' ){
		$name = $value['name'];
		$rules[$name]['required'] = true;
	}

	return $rules;

}

add_filter( 'post_updated_messages', 'cfb_custom_message' );
function cfb_custom_message( $messages ){
	$messages['comment_form_builder'][1] = __( 'Comment Form Updated' , 'cfb' );
	return $messages;
}

add_filter('post_row_actions','cfb_action_row', 9999, 2 );
function cfb_action_row($actions, $post){
    if ($post->post_type =="comment_form_builder"){
        unset($actions['clone']);
        unset($actions['edit_as_new_draft']);
        unset($actions['view']);
    }
    return $actions;
}

/**
* Add link to the plugins page
*/

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cfb_add_plugin_action_links' );
function cfb_add_plugin_action_links( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'options-general.php?page=cfb' ) . '">Settings</a>',
			'github' => '<a href="https://github.com/ravishakya/comment-form-builder">Github Page</a>', 
		),
		$links
	);
}

add_action( 'admin_notices', 'cfb_notice_pro_version' );
function cfb_notice_pro_version() {

	cfb_update_notice_settings();

	global $post;
	$status = is_plugin_active( 'pro-addons-comment-form-builder/index.php' );
	$screen = get_current_screen();
	//echo '<pre>'; print_r($screen); echo '</pre>';

	// IF pro version not installed show the message
	if( !empty( $_GET['post_type'] ) && $_GET['post_type'] == 'comment_form_builder' && $status == false ){ ?>

	    <div class="notice notice-warning">
	        <p>You are using free version of the <strong>Comment Form Builder</strong> so you can create only one comment form. PRO Version Coming soon !!!</p>
	    </div>

    	<?php
    }

    if( is_object( $post ) && $post->post_type == 'comment_form_builder' && get_option( 'hide_notice_edit_comment_form' ) != true && $screen->parent_base == 'edit-comments' ){ ?>

    	<div class="notice notice-warning">
	        <p>Need help ??? <a target="_blank" href="https://youtu.be/yzzooz4EZzU">Click Here</a> to see the tutorial on how to add comment forms. <a href="<?php echo esc_url_raw( "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ) . '&cfb_status_edit_form=hide'; ?>" class="button">Close</a></p>
	    </div>

    	<?php
    }
}

function cfb_update_notice_settings(){

	// Hide the notice in the comment form 
	if( !empty( $_GET['cfb_status_edit_form'] ) && $_GET['cfb_status_edit_form'] == 'hide' ){

		update_option( 'hide_notice_edit_comment_form' , true );

	}

}

add_action( 'admin_menu', 'cfb_admin_menu' );
function cfb_admin_menu() {

	add_submenu_page(
        '', // Hide from menu
        'About US', // Title
        'Changlog', // Submenu title
        'manage_options', // capabilities
        'cfb_abt', // page url
        'cfb_about' // callback function
    );

}

function cfb_about(){ ?>
	
	<div class="wpcb_about_us">
		<h1>Comment Form Builder<span class="small">Version 0.2</span></h1>

		<h3>Tutorial : How to add custom fields</h3>
		<iframe width="560" height="315" src="https://www.youtube.com/embed/yzzooz4EZzU" frameborder="0" allowfullscreen></iframe>

		<h3>Changelog</h3>

		<h4>Version 0.2</h4>
		<ul>
			<li>New : Hide Predefine Fields ( Name, Email, Website & Comment )</li>
			<li>New : Change labels of predefined fields ( Name, Email, Website & Comment )</li>
		</ul>

		<h4>Version 0.1</h4>
		<ul>
			<li>Initial release</li>
		</ul>

	</div>
	
	<?php
}

/**
* After activate the plugin redirect to the about page
*/

register_activation_hook( __FILE__ , 'cfb_after_plugin_activate');
add_action( 'admin_init', 'cfb_after_plugin_redirect' );

function cfb_after_plugin_activate() {
    add_option( 'cfb_plugin_do_activation_redirect' , true);
}

function cfb_after_plugin_redirect() {

    if ( get_option('cfb_plugin_do_activation_redirect', false) ) {

        delete_option('cfb_plugin_do_activation_redirect');

        if( !isset( $_GET['activate-multi'] ) ){
          	wp_redirect( admin_url( '/admin.php?page=cfb_abt' ) );
        }

    }

}