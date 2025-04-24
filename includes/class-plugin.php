<?php
/**
 * Plugin class.
 *
 * @package    PRC\Platform\Homepages
 */

namespace PRC\Platform\Homepages;

use WP_Error;
use WP_Query;
use WP_Post;

/**
 * Plugin class.
 *
 * @package    PRC\Platform\Homepages
 */
class Plugin {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The post type of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $post_type    The post type of the plugin.
	 */
	public static $post_type = 'homepage';

	/**
	 * Define the core functionality of the platform as initialized by hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = '1.0.0';
		$this->plugin_name = 'prc-homepages';

		$this->load_dependencies();

		$this->loader->add_action( 'init', $this, 'register_type' );
		$this->loader->add_filter( 'post_link', $this, 'modify_homepage_permalink', 10, 2 );
		$this->loader->add_action( 'admin_bar_menu', $this, 'add_front_page_quick_edit', 999 );
		$this->loader->add_action( 'init', $this, 'block_init' );
		new Feed( $this->get_loader() );
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Load plugin loading class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-loader.php';
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-feed.php';

		// Initialize the loader.
		$this->loader = new Loader();
	}

	/**
	 * Get the template for the homepage.
	 *
	 * @return array
	 */
	public function get_template() {
		return array(
			array(
				'core/group',
				array(
					'layout' => array(
						'type'        => 'constrained',
						'contentSize' => '1200px',
					),
				),
				array(
					array(
						'prc-block/grid-controller',
						array(
							'dividerColor' => 'gray',
							'className'    => 'is-pattern__featured-layout',
						),
						array(
							array(
								'prc-block/grid-column',
								array(
									'gridLayout' => array(
										'index'       => '1',
										'desktopSpan' => '3',
										'tabletSpan'  => '6',
										'mobileSpan'  => '4',
									),
								),
								array(
									array(
										'prc-block/story-item',
										array(
											'imageSize'    => 'A2',
											'metaTaxonomy' => 'category',
											'postId'       => 0,
										),
									),
									array(
										'prc-block/story-item',
										array(
											'imageSize'    => 'A2',
											'metaTaxonomy' => 'category',
											'postId'       => 0,
										),
									),
								),
							),
							array(
								'prc-block/grid-column',
								array(
									'gridLayout' => array(
										'index'       => '2',
										'desktopSpan' => '6',
										'tabletSpan'  => '12',
										'mobileSpan'  => '4',
									),
								),
								array(
									array(
										'prc-block/story-item',
										array(
											'imageSize'    => 'A1',
											'metaTaxonomy' => 'category',
											'postId'       => 0,
										),
									),
								),
							),
							array(
								'prc-block/grid-column',
								array(
									'gridLayout' => array(
										'index'       => '3',
										'desktopSpan' => '3',
										'tabletSpan'  => '6',
										'mobileSpan'  => '4',
									),
								),
								array(
									array(
										'prc-block/story-item',
										array(
											'imageSize'    => 'A2',
											'metaTaxonomy' => 'category',
											'postId'       => 0,
										),
									),
									array(
										'prc-block/story-item',
										array(
											'imageSize'    => 'A2',
											'metaTaxonomy' => 'category',
											'postId'       => 0,
										),
									),
								),

							),
						),
					),
				),
			),
		);
	}

	/**
	 * Register the post type.
	 *
	 * @hook init
	 *
	 * @since    1.0.0
	 */
	public function register_type() {
		$labels   = array(
			'name'                  => 'Homepages',
			'singular_name'         => 'Homepage',
			'menu_name'             => 'Homepages',
			'name_admin_bar'        => 'Homepage',
			'archives'              => 'Homepages Archives',
			'parent_item_colon'     => 'Parent Homepage:',
			'all_items'             => 'All Homepages',
			'add_new_item'          => 'Add New Homepage',
			'add_new'               => 'Add New',
			'new_item'              => 'New Homepage',
			'edit_item'             => 'Edit Homepage',
			'update_item'           => 'Update Homepage',
			'view_item'             => 'View Homepage',
			'search_items'          => 'Search Homepages',
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into Homepage',
			'uploaded_to_this_item' => 'Uploaded to this Homepage',
			'items_list'            => 'Homepages list',
			'items_list_navigation' => 'Homepages list navigation',
			'filter_items_list'     => 'Filter Homepage list',
		);
		$rewrite  = array(
			'slug'       => 'homepage',
			'with_front' => true,
			'pages'      => false,
			'feeds'      => false,
		);
		$supports = array( 'title', 'editor', 'revisions', 'custom-fields', 'comments' );
		$args     = array(
			'label'               => 'Homepage',
			'description'         => 'A custom content type that enables dynamic management of website homepages. Provides editorial control through native scheduling, revision history, and preview capabilities. Editors can create, schedule, and preview different homepage versions before they go live, ensuring seamless content transitions and maintaining a complete history of homepage changes. Perfect for organizations that need to coordinate homepage updates or maintain multiple versions of their front page content.',
			'labels'              => $labels,
			'supports'            => $supports,
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 61,
			'menu_icon'           => 'dashicons-layout',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'show_in_rest'        => true,
			'rewrite'             => $rewrite,
			'capability_type'     => 'post',
			'template'            => $this->get_template(),
		);

		register_post_type( self::$post_type, $args );
	}

	/**
	 * Modifies the homepage permalink to point to the homepage when published,
	 * otherwise returns the original permalink when previewing or saving.
	 *
	 * @hook post_link
	 *
	 * @param string  $url The permalink.
	 * @param WP_Post $post The post object.
	 * @return string The modified permalink.
	 */
	public function modify_homepage_permalink( $url, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return $url;
		}
		if ( self::$post_type === $post->post_type ) {
			return home_url();
		}
		return $url;
	}

	/**
	 * Adds a quick edit link to the admin bar for the homepage.
	 *
	 * @hook admin_bar_menu
	 *
	 * @param mixed $admin_bar The admin bar object.
	 * @return string|void
	 */
	public function add_front_page_quick_edit( $admin_bar ) {
		if ( ! is_front_page() ) {
			return ''; // Bail early if not the frontpage.
		}
		$homepage = false;
		$args     = array(
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_type'      => self::$post_type,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);
		$homepage = get_posts( $args );
		if ( ! empty( $homepage ) ) {
			$homepage = array_pop( $homepage );
		}
		if ( ! $homepage ) {
			return ''; // Bail early if no homepage.
		}
		$link = get_edit_post_link( $homepage );
		if ( null !== $link ) {
			// Remove the "edit page" link for the page that the homepage is occupying.
			$admin_bar->remove_menu( 'edit' );
			$admin_bar->add_menu(
				array(
					'id'    => 'edit',
					'title' => __( 'Edit Homepage' ),
					'href'  => $link,
					'meta'  => array(
						'title' => __( 'Edit Homepage' ),
					),
				)
			);
		}
	}

	/**
	 * Renders the latest homepage block.
	 *
	 * @param array    $attributes The attributes of the block.
	 * @param string   $content The content of the block.
	 * @param WP_Block $block The block object.
	 * @return string The rendered block.
	 */
	public function render_latest_homepage_block( $attributes, $content, $block ) {
		$homepage = false;
		$args     = array(
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_type'      => self::$post_type,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);
		$homepage = new WP_Query( $args );

		if ( ! $homepage->have_posts() ) {
			return ''; // Bail early if no homepage.
		}

		if ( ! $homepage->have_posts() && is_user_logged_in() ) {
			$content = '<div class="warning">No homepage found. <a href="' . esc_url( admin_url( 'post-new.php?post_type=homepage' ) ) . '">Create a new homepage.</a></div>';
		}

		if ( $homepage->have_posts() ) {
			$homepage_id      = $homepage->posts[0];
			$homepage_content = get_post_field( 'post_content', $homepage_id );
			$homepage_content = apply_filters( 'the_content', $homepage_content );
			if ( $homepage_content ) {
				$content = $homepage_content;
			}
		}

		wp_reset_postdata();

		return $content;
	}


	/**
	 * Initializes the blocks.
	 *
	 * @hook init
	 */
	public function block_init() {
		register_block_type(
			PRC_HOMEPAGES_DIR . '/build',
			array(
				'render_callback' => array( $this, 'render_latest_homepage_block' ),
			)
		);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PRC\Platform\Homepages\Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
