<?php
/**
 * Registers the Homepages DataViews admin list.
 *
 * @package PRC\Platform\Homepages
 */

namespace PRC\Platform\Homepages;

/**
 * Soft-depends on prc-wp-admin-dataview via the register_lists action.
 */
class Admin_Dataview_Lists {
	/**
	 * Constructor.
	 *
	 * @param Loader $loader Plugin loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'prc_wp_admin_dataview_register_lists', $this, 'register_lists', 10, 1 );
	}

	/**
	 * Register the homepage list config.
	 *
	 * @param object $lists List registry from prc-wp-admin-dataview.
	 */
	public function register_lists( $lists ): void {
		if ( ! is_object( $lists ) || ! method_exists( $lists, 'register' ) ) {
			return;
		}

		foreach ( self::list_configs() as $config ) {
			$lists->register( $config );
		}
	}

	/**
	 * List configs owned by this plugin.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function list_configs(): array {
		return array(
			array(
				'postType'  => Plugin::$post_type,
				'pageSlug'  => 'prc-wp-admin-dataview-homepage',
				'menuTitle' => __( 'All Homepages', 'prc-homepages' ),
				'pageTitle' => __( 'All Homepages', 'prc-homepages' ),
			),
		);
	}
}
