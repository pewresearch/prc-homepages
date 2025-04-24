<?php
/**
 * Utility functions for the PRC Homepages plugin.
 *
 * @package PRC\Platform\Homepages
 */

use PRC\Platform\Homepages\Plugin;

/**
 * Get the latest homepage ID.
 *
 * @return int|false The latest homepage ID or false if no homepage is found.
 */
function get_latest_homepage_id() {
	$post_type = Plugin::$post_type;
	$query     = new WP_Query(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);

	if ( ! $query->have_posts() ) {
		return false;
	}

	$homepage_id = $query->posts[0];

	return $homepage_id;
}

/**
 * Get the latest homepage title.
 *
 * @return string The latest homepage title.
 */
function get_latest_homepage_title() {
	$id = get_latest_homepage_id();
	if ( ! $id ) {
		return '';
	}

	return wp_sprintf(
		'Homepage: %1$s',
		get_the_date( 'm.d.Y', $id )
	);
}

/**
 * Get the latest homepage permalink.
 *
 * @return string The latest homepage permalink.
 */
function get_latest_homepage_permalink() {
	$id = get_latest_homepage_id();
	if ( ! $id ) {
		return '';
	}

	$permalink = get_permalink( $id );
	return $permalink;
}
