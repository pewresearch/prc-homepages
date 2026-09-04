<?php
/**
 * Plugin class.
 *
 * @package    PRC\Platform\Homepages
 */

namespace PRC\Platform\Homepages;

use WP_Query;

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
	 * Define the core functionality of the platform as initialized by hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = '1.0.0';
		$this->plugin_name = 'prc-homepages';

		$this->load_dependencies();

		$this->loader->add_action( 'init', $this, 'block_init' );
		new Content_Type( $this->get_loader() );
		new Feed( $this->get_loader() );
		new Admin_Dataview_Lists( $this->get_loader() );
		new Featured_Image_Capture( $this->get_loader() );
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
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-loader.php';
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-content-type.php';
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-feed.php';
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-admin-dataview-lists.php';
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-featured-image-capture.php';

		$this->loader = new Loader();
	}

	/**
	 * Renders the latest homepage block.
	 *
	 * @param array    $attributes The attributes of the block.
	 * @param string   $content The content of the block.
	 * @param \WP_Block $block The block object.
	 * @return string The rendered block.
	 */
	public function render_latest_homepage_block( $attributes, $content, $block ) {
		$homepage = false;
		$args     = array(
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_type'      => Content_Type::$post_type,
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
	 * Run the loader to execute all the hooks with WordPress.
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
