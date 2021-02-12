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












$places = new VStar_Dining_Guide_Post_Type('place', 'Places', 'Place', 'Restaurants, etc', [
	'menu_icon' => 'dashicons-location-alt',
	'menu_position' => 25,
	'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
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
	add_meta_box('vstar_dg_place_meta', 'Place Details', function() use ($post) {
		$meta = get_post_meta($post->ID);
		$values = [
			'address' => (array_key_exists('vstar_dg_address', $meta) ? $meta['vstar_dg_address'][0] : ''),
			'website' => (array_key_exists('vstar_dg_website', $meta) ? $meta['vstar_dg_website'][0] : ''),
			'facebook' => (array_key_exists('vstar_dg_facebook', $meta) ? $meta['vstar_dg_facebook'][0] : ''),
			'instagram' => (array_key_exists('vstar_dg_instagram', $meta) ? $meta['vstar_dg_instagram'][0] : ''),
			'review' => (array_key_exists('vstar_dg_review', $meta) ? $meta['vstar_dg_review'][0] : ''),

			// '' => (array_key_exists('vstar_dg_', $meta) ? $meta->vstar_dg_ : ''),
			// '' => (array_key_exists('vstar_dg_', $meta) ? $meta->vstar_dg_ : ''),
			// '' => (array_key_exists('vstar_dg_', $meta) ? $meta->vstar_dg_ : ''),
		];

		$nonce = wp_nonce_field( 'vstar_dg_place_details', 'vstar_dg_nonce' );



echo <<<INPUT
{$nonce}
<label>
	Address
	<input type="text" value="{$values['address']}" name="vstar_dg_address" id="vstar_dg_address">
</label>

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

<label>
	Review
	<textarea name="vstar_dg_review" id="vstar_dg_review">{$values['review']}</textarea>
</label>





INPUT;





  });
}





add_action('save_post', function($post_id){

	$metas = [
		'vstar_dg_address' => [
			'type' => 'text',
		],
		'vstar_dg_website' => [
			'type' => 'url',
		],
		'vstar_dg_facebook' => [
			'type' => 'url',
		],
		'vstar_dg_instagram' => [
			'type' => 'url',
		],
		'vstar_dg_review' => [
			'type' => 'text',
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




}
add_action( 'init', 'vstar_dg_taxonomies' );


function vstar_dg_registerterms() {
	$types = [
		[
			'name' => 'Favorite',
			'slug' => 'favorite',
			'description' => '',
		],
		[
			'name' => 'Restaurant',
			'slug' => 'restaurant',
			'description' => '',
		],
		[
			'name' => 'Bar',
			'slug' => 'bar',
			'description' => '',
		],
		[
			'name' => 'Food Truck',
			'slug' => 'foodtruck',
			'description' => '',
		],
		[
			'name' => 'Bakery or Café',
			'slug' => 'bakerycafe',
			'description' => '',
		],
		[
			'name' => 'Grocery',
			'slug' => 'grocery',
			'description' => '',
		],
	];
	$amenities = [
		[
			'name' => 'Full Bar',
			'slug' => 'fullbar',
			'description' => 'Versus just beer/wine',
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
			'description' => "Their menu has clearly labeled vegetarian and vegan options so you won't have to ask a million questions",
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
			'description' => '',
		],
		[
			'name' => 'Lunch',
			'slug' => 'lunch',
			'description' => '',
		],
		[
			'name' => 'Dinner',
			'slug' => 'dinner',
			'description' => '',
		],
		[
			'name' => 'Late Night',
			'slug' => 'latenight',
			'description' => "We call 'late' being open past 9pm",
		],
		[
			'name' => '24 Hours',
			'slug' => 'twentyfourhours',
			'description' => '',
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














class Test_Terms {

    function __construct() {
        register_activation_hook( __FILE__,array( $this,'activate' ) );
        add_action( 'init', array( $this, 'create_cpts_and_taxonomies' ) );
    }

    function activate() {
        $this->create_cpts_and_taxonomies();
        $this->register_new_terms();
    }

    function create_cpts_and_taxonomies() {

        $args = array(
            'hierarchical'                      => true,
            'labels' => array(
                'name'                          => _x('Test Tax', 'taxonomy general name' ),
                'singular_name'                 => _x('Test Tax', 'taxonomy singular name'),
                'search_items'                  => __('Search Test Tax'),
                'popular_items'                 => __('Popular Test Tax'),
                'all_items'                     => __('All Test Tax'),
                'edit_item'                     => __('Edit Test Tax'),
                'edit_item'                     => __('Edit Test Tax'),
                'update_item'                   => __('Update Test Tax'),
                'add_new_item'                  => __('Add New Test Tax'),
                'new_item_name'                 => __('New Test Tax Name'),
                'separate_items_with_commas'    => __('Seperate Test Tax with Commas'),
                'add_or_remove_items'           => __('Add or Remove Test Tax'),
                'choose_from_most_used'         => __('Choose from Most Used Test Tax')
            ),
            'query_var'                         => true,
            'rewrite'                           => array('slug' =>'test-tax')
        );
        register_taxonomy( 'test_tax', array( 'post' ), $args );
    }

    function register_new_terms() {
        $this->taxonomy = 'test_tax';
        $this->terms = array (
            '0' => array (
                'name'          => 'Tester 1',
                'slug'          => 'tester-1',
                'description'   => 'This is a test term one',
            ),
            '1' => array (
                'name'          => 'Tester 2',
                'slug'          => 'tester-2',
                'description'   => 'This is a test term two',
            ),
        );

        foreach ( $this->terms as $term_key=>$term) {
                wp_insert_term(
                    $term['name'],
                    $this->taxonomy,
                    array(
                        'description'   => $term['description'],
                        'slug'          => $term['slug'],
                    )
                );
            unset( $term );
        }

    }
}
// $Test_Terms = new Test_Terms();












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
