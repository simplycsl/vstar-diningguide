<?php
/**
* Plugin Name: VStar Dining Guide
* Version: 1.0.0
* Plugin URI: https://cohere.studio
* Description: Curate a collection of restaurants and other businesses in your region that cater to vegans and vegetarians
* Author: Cohere Studio
* Author URI: https://cohere.studio
* Requires at least: 4.0
* Tested up to: 4.0
*
* Text Domain: vstar-diningguide
* Domain Path: /lang/
*
* @package WordPress
* @author Cohere Studio
* @since 1.0.0
*/


// include necessary styles for admin
function vstar_dg_adminstyle() {
	wp_enqueue_style('vstar-dg-admin', plugin_dir_url( __FILE__ ) . 'admin.css', '' , filemtime( plugin_dir_path( __FILE__ ) . 'admin.css' ) );
}
add_action('admin_enqueue_scripts', 'vstar_dg_adminstyle');



function vstar_dg_dependency_checks() {
	$GLOBALS['vstar_dg_dependencies']['eventsmanager'] = is_plugin_active( 'events-manager/events-manager.php' );
}
add_action( 'admin_init', 'vstar_dg_dependency_checks' );






$places = new VStar_Dining_Guide_Post_Type('place', 'Places', 'Place', 'Restaurants, etc', [
	'menu_icon' => 'dashicons-location-alt',
	'menu_position' => 25,
	'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'comments'],
	'hierarchical' => false,
	'delete_with_user' => false,
	'register_meta_box_cb' => 'vstar_dg_metasetup',
	'rest_base' => 'places',
	'show_in_rest' => true,
	'taxonomies' => ['place_types', 'place_amenities', 'place_locales', 'place_times'],
]);

// modify default instructions for new posts' titles
add_filter( 'enter_title_here', function( $title ) {
  $screen = get_current_screen();
  if  ( 'places' == $screen->post_type ) {
      $title = 'Enter place name';
  }
  return $title;
} );
// disable Gutenberg editor for custom post types
add_filter('use_block_editor_for_post_type', function($enabled, $post_type) {
  $remove_gutenberg_from = ['place'];
  if (in_array($post_type, $remove_gutenberg_from)) {
      return false;
  }
  return $enabled;
}, 10, 2);






function vstar_dg_metasetup(WP_Post $post) {

	add_meta_box('vstar_dg_place_links', 'Place Links', function() use ($post) {
		$meta = get_post_meta($post->ID);
		$values = [
			'website' => (array_key_exists('vstar_dg_website', $meta) ? $meta['vstar_dg_website'][0] : ''),
			'facebook' => (array_key_exists('vstar_dg_facebook', $meta) ? $meta['vstar_dg_facebook'][0] : ''),
			'instagram' => (array_key_exists('vstar_dg_instagram', $meta) ? $meta['vstar_dg_instagram'][0] : ''),
		];
		$nonce = wp_nonce_field( 'vstar_dg_place_links', 'vstar_dg_nonce' );

echo <<<INPUT
{$nonce}
<label>
	Website
	<input type="url" value="{$values['website']}" name="vstar_dg_website" id="vstar_dg_website">
</label>
<label>
	Facebook
	<input type="url" value="{$values['facebook']}" name="vstar_dg_facebook" id="vstar_dg_facebook">
</label>
<label>
	Instagram
	<input type="url" value="{$values['instagram']}" name="vstar_dg_instagram" id="vstar_dg_instagram">
</label>

INPUT;
  });
	add_meta_box('vstar_dg_place_locations', 'Place Locations', function() use ($post) {

		if ( $GLOBALS['vstar_dg_dependencies']['eventsmanager'] ) {

			global $wpdb;
			$title_string = str_replace("'", "\'", $post->post_title) . "%";
			$locations_query = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_em_locations WHERE location_name LIKE %s", $title_string) );
			if ( $locations_query ) {
				$locations_list = '';
				foreach ( $locations_query as $location ) {
					$locations_list .= "<tr><td>$location->location_name</td><td>$location->location_address, $location->location_postcode</td></tr>";
				}
echo <<<EXTANT
<h3>Existing Locations</h3>
<table>
	<tr><th>Location Name</th><th>Address</th></tr>
	{$locations_list}
</table>
EXTANT;
			}


echo <<<INPUT
<h3>Add New Location</h3>
<p>If the Place has multiple locations, make sure that the new location name matches the Place name's beginning, with any specific location details coming at the end. Example: <em>{$post->post_title} - West</em></p>
<p><a href="/wp-admin/post-new.php?post_type=location" target="_blank">Add new Location</a></p>
INPUT;

// <label>
// 	Location Name
// 	<input type="text" name="vstar_dg_locationname" id="vstar_dg_locationname" value="{$post->post_title}">
// </label>
// <label>
// 	Address
// 	<input type="text" name="vstar_dg_address" id="vstar_dg_address">
// </label>
// <label>
// 	City
// 	<input type="text" name="vstar_dg_city" id="vstar_dg_city" value="Bloomington">
// </label>
// <label>
// 	ZIP
// 	<input type="text" name="vstar_dg_zip" id="vstar_dg_zip">
// </label>

		}
		else {
			print "<p>Locations are not supported without the Events Manager plugin.</p>";
		}
  });



	add_meta_box('vstar_dg_place_recommendations', 'Place Recommendations', function() use ($post) {

		$users = get_users();
		$userlist = '';
		foreach ( $users as $user ) {
			$userlist .= "<option value='{$user->ID}'>{$user->display_name}</option>";
		}

echo <<<COMMENTS
<label>
	Reviewer
	<select name="vstar_dg_comment_author" id="vstar_dg_comment_author">
		<option value="" disabled selected>Select an author</option>
		{$userlist}
	</select>
</label>
<label>
	Recommendation
	<textarea name="vstar_dg_comment_content" id="vstar_dg_comment_content"></textarea>
</label>
COMMENTS;
	});


}





add_action('save_post', function($post_id){

	$metas = [
		'vstar_dg_website' => [
			'type' => 'url',
		],
		'vstar_dg_facebook' => [
			'type' => 'url',
		],
		'vstar_dg_instagram' => [
			'type' => 'url',
		],
	];


  $post = get_post($post_id);

  // Do not save meta for a revision or on autosave
  if ( $post->post_type != 'place' || wp_is_post_revision($post_id) )
    return;

  // // Do not save meta if fields are not present, like during a restore
  // if( !isset($_POST) )
  //   return;
	//
  // // Secure with nonce field check
  // if ( check_admin_referer('vstar_dg_place_details', 'vstar_dg_nonce') ) {

		// process and save each value
		foreach ($metas as $key => $prop) {

			switch ($prop['type']) {

		    case 'text':
					$value = filter_var( trim($_POST[$key]), FILTER_SANITIZE_STRING );
		    break;

				case 'url':
					$value = filter_var( trim($_POST[$key]), FILTER_SANITIZE_URL );
				break;

			}

			update_post_meta($post_id, $key, $value);
		}
		// // process and save location info
		// $locationname = filter_var( trim($_POST['vstar_dg_locationname']), FILTER_SANITIZE_STRING );
		// $address = filter_var( trim($_POST['vstar_dg_address']), FILTER_SANITIZE_STRING );
		// $city = filter_var( trim($_POST['vstar_dg_city']), FILTER_SANITIZE_STRING );
		// $zip = filter_var( trim($_POST['vstar_dg_zip']), FILTER_SANITIZE_STRING );
		//
		// $location = EM_Locations::create();


		// reviews as comments
		if ( $_POST['vstar_dg_comment_content'] != '' ) {
			$comment = wp_new_comment([
				'comment_post_ID' => $post_id,
				'comment_content' => $_POST['vstar_dg_comment_content'],
				'user_id' => $_POST['vstar_dg_comment_author'],
			]);

			wp_set_comment_status($comment, 'approve');
		}

});


























function vstar_dg_taxonomies() {
	$typelabels = array(
		'name'              => _x( 'Types', 'taxonomy general name' ),
		'singular_name'     => _x( 'Type', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Types' ),
		'all_items'         => __( 'All Types' ),
		'parent_item'       => __( 'Parent Type' ),
		'parent_item_colon' => __( 'Parent Type:' ),
		'edit_item'         => __( 'Edit Type' ),
		'update_item'       => __( 'Update Type' ),
		'add_new_item'      => __( 'Add New Type' ),
		'new_item_name'     => __( 'New Type Name' ),
		'menu_name'         => __( 'Types' ),
	);
	$typeargs   = array(
		'hierarchical'      => true,
		'public'						=> true,
		'labels'            => $typelabels,
		'show_ui'           => true,
		'show_in_menu' 		=> true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'type' ],
	);
	register_taxonomy( 'place_types', 'place', $typeargs );


	$amenlabels = array(
		'name'              => _x( 'Amenities', 'taxonomy general name' ),
		'singular_name'     => _x( 'Amenity', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Amenities' ),
		'all_items'         => __( 'All Amenities' ),
		'parent_item'       => __( 'Parent Amenity' ),
		'parent_item_colon' => __( 'Parent Amenity:' ),
		'edit_item'         => __( 'Edit Amenity' ),
		'update_item'       => __( 'Update Amenity' ),
		'add_new_item'      => __( 'Add New Amenity' ),
		'new_item_name'     => __( 'New Amenity Name' ),
		'menu_name'         => __( 'Amenities' ),
	);
	$amenargs   = array(
		'hierarchical'      => true,
		'public'						=> true,
		'labels'            => $amenlabels,
		'show_ui'           => true,
		'show_in_menu' 		=> true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'amenity' ],
	);
	register_taxonomy( 'place_amenities', 'place', $amenargs );


	$localelabels = array(
		'name'              => _x( 'Locales', 'taxonomy general name' ),
		'singular_name'     => _x( 'Locale', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Locales' ),
		'all_items'         => __( 'All Locales' ),
		'parent_item'       => __( 'Parent Locale' ),
		'parent_item_colon' => __( 'Parent Locale:' ),
		'edit_item'         => __( 'Edit Locale' ),
		'update_item'       => __( 'Update Locale' ),
		'add_new_item'      => __( 'Add New Locale' ),
		'new_item_name'     => __( 'New Locale Name' ),
		'menu_name'         => __( 'Locales' ),
	);
	$localeargs   = array(
		'hierarchical'      => true,
		'public'						=> true,
		'labels'            => $localelabels,
		'show_ui'           => true,
		'show_in_menu' 		=> true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'locale' ],
	);
	register_taxonomy( 'place_locales', 'place', $localeargs );


	$timelabels = array(
		'name'              => _x( 'Times', 'taxonomy general name' ),
		'singular_name'     => _x( 'Time', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Times' ),
		'all_items'         => __( 'All Times' ),
		'parent_item'       => __( 'Parent Time' ),
		'parent_item_colon' => __( 'Parent Time:' ),
		'edit_item'         => __( 'Edit Time' ),
		'update_item'       => __( 'Update Time' ),
		'add_new_item'      => __( 'Add New Time' ),
		'new_item_name'     => __( 'New Time Name' ),
		'menu_name'         => __( 'Times' ),
	);
	$timeargs   = array(
		'hierarchical'      => true,
		'public'						=> true,
		'labels'            => $timelabels,
		'show_ui'           => true,
		'show_in_menu' 		=> true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'time' ],
	);
	register_taxonomy( 'place_times', 'place', $timeargs );


	$foodslabels = array(
		'name'              => _x( 'Foods', 'taxonomy general name' ),
		'singular_name'     => _x( 'Food', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Foods' ),
		'all_items'         => __( 'All Foods' ),
		'parent_item'       => __( 'Parent Food' ),
		'parent_item_colon' => __( 'Parent Food:' ),
		'edit_item'         => __( 'Edit Food' ),
		'update_item'       => __( 'Update Food' ),
		'add_new_item'      => __( 'Add New Food' ),
		'new_item_name'     => __( 'New Food Name' ),
		'menu_name'         => __( 'Foods' ),
	);
	$foodsargs   = array(
		'hierarchical'      => true,
		'public'						=> true,
		'labels'            => $foodslabels,
		'show_ui'           => true,
		'show_in_menu' 		=> true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'food' ],
	);
	register_taxonomy( 'place_foods', 'place', $foodsargs );




}
add_action( 'init', 'vstar_dg_taxonomies' );


function vstar_dg_registerterms() {
	$types = [
		[
			'name' => 'Favorite',
			'slug' => 'favorite',
			'description' => 'A favorite of BloomingVeg, offering a well-labeled menu with plenty of vegan options.',
		],
		[
			'name' => 'Restaurant',
			'slug' => 'restaurant',
			'description' => 'Offers seating and a full menu.',
		],
		[
			'name' => 'Bar',
			'slug' => 'bar',
			'description' => 'Primarily serves alcohol, but also offers food.',
		],
		[
			'name' => 'Food Truck',
			'slug' => 'foodtruck',
			'description' => 'Mobile food services.',
		],
		[
			'name' => 'Bakery or Café',
			'slug' => 'bakerycafe',
			'description' => 'Primarily serves baked goods and/or coffee.',
		],
		[
			'name' => 'Grocery',
			'slug' => 'grocery',
			'description' => 'Primarily serves unprepared food.',
		],
	];
	$amenities = [
		[
			'name' => 'Alcohol',
			'slug' => 'alcohol',
			'description' => 'Offers drinks beyond just beer or wine.',
		],
		[
			'name' => 'Gluten-free Options',
			'slug' => 'glutenfree',
			'description' => '',
		],
		[
			'name' => 'Wifi',
			'slug' => 'wifi',
			'description' => '',
		],
		[
			'name' => 'Desserts',
			'slug' => 'desserts',
			'description' => '',
		],
		[
			'name' => 'Well-labeled Menu',
			'slug' => 'welllabeledmenu',
			'description' => "Their menu has clearly labeled vegetarian and vegan options so you won't have to ask a million questions.",
		],
	];
	$locales = [
		[
			'name' => 'North',
			'slug' => 'north',
			'description' => '',
		],
		[
			'name' => 'South',
			'slug' => 'south',
			'description' => '',
		],
		[
			'name' => 'West',
			'slug' => 'west',
			'description' => '',
		],
		[
			'name' => 'Downtown',
			'slug' => 'downtown',
			'description' => '',
		],
		[
			'name' => 'Campus',
			'slug' => 'campus',
			'description' => '',
		],
		[
			'name' => 'East',
			'slug' => 'east',
			'description' => '',
		],
	];

	$times = [
		[
			'name' => 'Breakfast',
			'slug' => 'breakfast',
			'description' => 'Serves breakfast-style food in the morning, from 5am–11am.',
		],
		[
			'name' => 'Lunch',
			'slug' => 'lunch',
			'description' => 'Serves a lunch-time menu, from 11am–4pm.',
		],
		[
			'name' => 'Dinner',
			'slug' => 'dinner',
			'description' => 'Serves a dinner-time menu, from 4pm–9pm.',
		],
		[
			'name' => 'Late Night',
			'slug' => 'latenight',
			'description' => "We call 'late' being open past 9pm.",
		],
	];

	foreach ($types as $type) {
		$insert = wp_insert_term( $type['name'], 'place_types', [ 'description' => $type['description'], 'slug' => $type['slug'] ] );
	}
	foreach ($amenities as $amenity) {
		$insert = wp_insert_term( $amenity['name'], 'place_amenities', [ 'description' => $amenity['description'], 'slug' => $amenity['slug'] ] );
	}
	foreach ($locales as $locale) {
		$insert = wp_insert_term( $locale['name'], 'place_locales', [ 'description' => $locale['description'], 'slug' => $locale['slug'] ] );
	}
	foreach ($times as $time) {
		$insert = wp_insert_term( $time['name'], 'place_times', [ 'description' => $time['description'], 'slug' => $time['slug'] ] );
	}
}
// register_activation_hook( __FILE__, 'vstar_dg_registerterms' );






// RESTORE THIS BACK TO register_activation_hook after development
add_action( 'init', 'vstar_dg_registerterms', 99 );

























class VStar_Dining_Guide_Post_Type {

	/**
	 * The name for the custom post type.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_type;

	/**
	 * The plural name for the custom post type posts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plural;

	/**
	 * The singular name for the custom post type posts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $single;

	/**
	 * The description of the custom post type.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $description;

	/**
	 * The options of the custom post type.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $options;

	/**
	 * Constructor
	 *
	 * @param string $post_type Post type.
	 * @param string $plural Post type plural name.
	 * @param string $single Post type singular name.
	 * @param string $description Post type description.
	 * @param array  $options Post type options.
	 */
	public function __construct( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) {
			return;
		}

		// Post type name and labels.
		$this->post_type   = $post_type;
		$this->plural      = $plural;
		$this->single      = $single;
		$this->description = $description;
		$this->options     = $options;

		// Regsiter post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Display custom update messages for posts edits.
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_updated_messages' ), 10, 2 );
	}

	/**
	 * Register new post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		//phpcs:disable
		$labels = array(
			'name'               => $this->plural,
			'singular_name'      => $this->single,
			'name_admin_bar'     => $this->single,
			'add_new'            => _x( 'Add New', $this->post_type, 'vstar-diningguide' ),
			'add_new_item'       => sprintf( __( 'Add New %s', 'vstar-diningguide' ), $this->single ),
			'edit_item'          => sprintf( __( 'Edit %s', 'vstar-diningguide' ), $this->single ),
			'new_item'           => sprintf( __( 'New %s', 'vstar-diningguide' ), $this->single ),
			'all_items'          => sprintf( __( 'All %s', 'vstar-diningguide' ), $this->plural ),
			'view_item'          => sprintf( __( 'View %s', 'vstar-diningguide' ), $this->single ),
			'search_items'       => sprintf( __( 'Search %s', 'vstar-diningguide' ), $this->plural ),
			'not_found'          => sprintf( __( 'No %s Found', 'vstar-diningguide' ), $this->plural ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'vstar-diningguide' ), $this->plural ),
			'parent_item_colon'  => sprintf( __( 'Parent %s' ), $this->single ),
			'menu_name'          => $this->plural,
		);
		//phpcs:enable

		$args = array(
			'labels'                => apply_filters( $this->post_type . '_labels', $labels ),
			'description'           => $this->description,
			'public'                => true,
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'can_export'            => true,
			'rewrite'               => true,
			'capability_type'       => 'post',
			'has_archive'           => true,
			'hierarchical'          => true,
			'show_in_rest'          => true,
			'rest_base'             => $this->post_type,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'supports'              => array( 'title', 'editor', 'excerpt', 'comments', 'thumbnail' ),
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-admin-post',
		);

		$args = array_merge( $args, $this->options );

		register_post_type( $this->post_type, apply_filters( $this->post_type . '_register_args', $args, $this->post_type ) );
	}

	/**
	 * Set up admin messages for post type
	 *
	 * @param  array $messages Default message.
	 * @return array           Modified messages.
	 */
	public function updated_messages( $messages = array() ) {
		global $post, $post_ID;
		//phpcs:disable
		$messages[ $this->post_type ] = array(
			0  => '',
			1  => sprintf( __( '%1$s updated. %2$sView %3$s%4$s.', 'vstar-diningguide' ), $this->single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
			2  => __( 'Custom field updated.', 'vstar-diningguide' ),
			3  => __( 'Custom field deleted.', 'vstar-diningguide' ),
			4  => sprintf( __( '%1$s updated.', 'vstar-diningguide' ), $this->single ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s.', 'vstar-diningguide' ), $this->single, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( '%1$s published. %2$sView %3$s%4s.', 'vstar-diningguide' ), $this->single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
			7  => sprintf( __( '%1$s saved.', 'vstar-diningguide' ), $this->single ),
			8  => sprintf( __( '%1$s submitted. %2$sPreview post%3$s%4$s.', 'vstar-diningguide' ), $this->single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->single, '</a>' ),
			9  => sprintf( __( '%1$s scheduled for: %2$s. %3$sPreview %4$s%5$s.', 'vstar-diningguide' ), $this->single, '<strong>' . date_i18n( __( 'M j, Y @ G:i', 'vstar-diningguide' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
			10 => sprintf( __( '%1$s draft updated. %2$sPreview %3$s%4$s.', 'vstar-diningguide' ), $this->single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->single, '</a>' ),
		);
		//phpcs:enable

		return $messages;
	}

	/**
	 * Set up bulk admin messages for post type
	 *
	 * @param  array $bulk_messages Default bulk messages.
	 * @param  array $bulk_counts   Counts of selected posts in each status.
	 * @return array                Modified messages.
	 */
	public function bulk_updated_messages( $bulk_messages = array(), $bulk_counts = array() ) {

		//phpcs:disable
		$bulk_messages[ $this->post_type ] = array(
			'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'vstar-diningguide' ), $bulk_counts['updated'], $this->single, $this->plural ),
			'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'vstar-diningguide' ), $bulk_counts['locked'], $this->single, $this->plural ),
			'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'vstar-diningguide' ), $bulk_counts['deleted'], $this->single, $this->plural ),
			'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'vstar-diningguide' ), $bulk_counts['trashed'], $this->single, $this->plural ),
			'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'vstar-diningguide' ), $bulk_counts['untrashed'], $this->single, $this->plural ),
		);
		//phpcs:enable

		return $bulk_messages;
	}

}


















?>
