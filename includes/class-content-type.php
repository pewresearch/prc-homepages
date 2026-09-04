<?php
/**
 * Homepage content type.
 *
 * @package PRC\Platform\Homepages
 */

namespace PRC\Platform\Homepages;

/**
 * Registers the homepage CPT and related content-type hooks.
 */
class Content_Type {

	/**
	 * The post type slug.
	 *
	 * @var string
	 */
	public static $post_type = 'homepage';

	/**
	 * Register content-type hooks.
	 *
	 * @param Loader $loader Plugin loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'init', $this, 'register_type' );
		$loader->add_filter( 'post_link', $this, 'modify_homepage_permalink', 10, 2 );
		$loader->add_action( 'admin_bar_menu', $this, 'add_front_page_quick_edit', 999 );
		$loader->add_filter( 'prc_platform_post_publish_pipeline_post_types', $this, 'opt_into_publish_pipeline' );
	}

	/**
	 * Opt the homepage post type into the post-publish pipeline.
	 *
	 * @hook prc_platform_post_publish_pipeline_post_types
	 *
	 * @param array $post_types Allowed post types.
	 * @return array
	 */
	public function opt_into_publish_pipeline( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = array();
		}
		$post_types[] = self::$post_type;
		return $post_types;
	}

	/**
	 * Add notes support to the homepage post type.
	 */
	public function add_notes_support() {
		$supports        = get_all_post_type_supports( self::$post_type );
		$editor_supports = array( 'notes' => true );
		// `add_post_type_support()` doesn't merge support sub-properties, so we explicitly merge it here.
		if ( is_array( $supports['editor'] ) && isset( $supports['editor'][0] ) && is_array( $supports['editor'][0] ) ) {
			$editor_supports = array_merge( $editor_supports, $supports['editor'][0] );
		}
		add_post_type_support( self::$post_type, 'editor', $editor_supports );
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
		$supports = array( 'title', 'editor', 'revisions', 'custom-fields', 'comments', 'thumbnail' );
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
		$this->add_notes_support();
	}

	/**
	 * Modifies the homepage permalink to point to the homepage when published,
	 * otherwise returns the original permalink when previewing or saving.
	 *
	 * @hook post_link
	 *
	 * @param string  $url The permalink.
	 * @param \WP_Post $post The post object.
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
			return '';
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
			return '';
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
}
