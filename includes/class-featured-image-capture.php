<?php
/**
 * Capture a 1200x1200 screenshot of a homepage's singular URL on publish.
 *
 * @package PRC\Platform\Homepages
 */

namespace PRC\Platform\Homepages;

/**
 * Schedules and runs featured-image screenshot capture for homepage posts.
 */
class Featured_Image_Capture {

	public const ACTION_HOOK  = 'prc_homepages_capture_featured_image';
	public const ACTION_GROUP = 'prc-homepages';

	public const META_ATTACHMENT_ID = '_homepage_screenshot_attachment_id';
	public const META_CONTENT_HASH  = '_homepage_screenshot_hash';
	public const META_GENERATED_AT  = '_homepage_screenshot_generated_at';

	public const VIEWPORT_WIDTH  = 1200;
	public const VIEWPORT_HEIGHT = 1200;
	public const SELECTOR        = '.wp-block-post-content';
	public const WAIT_MS         = 10000;

	/**
	 * Register hooks.
	 *
	 * @param Loader $loader Plugin loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'prc_platform_async_on_publish', $this, 'maybe_schedule', 10, 1 );
		$loader->add_action( 'prc_platform_async_on_update', $this, 'maybe_schedule', 10, 1 );
		$loader->add_action( self::ACTION_HOOK, $this, 'capture', 10, 1 );
	}

	/**
	 * Resolve Firebase render endpoint URLs.
	 *
	 * @return array<string, string>
	 */
	public static function get_endpoints(): array {
		$endpoints = apply_filters( 'prc_platform_firebase_render_endpoints', array() );
		return is_array( $endpoints ) ? $endpoints : array();
	}

	/**
	 * Whether the screenshotElement Cloud Function is configured.
	 *
	 * @return bool
	 */
	public static function is_configured(): bool {
		$endpoints = self::get_endpoints();
		return ! empty( $endpoints['screenshot_element'] ) && class_exists( '\\PRC\\Platform\\Firebase' );
	}

	/**
	 * Whether a capture job is already pending or running for this post.
	 *
	 * Uses as_get_scheduled_actions with explicit status checks (PENDING + RUNNING)
	 * so a currently-executing job also blocks duplicate scheduling.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_action_pending( int $post_id ): bool {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return false;
		}

		$actions = as_get_scheduled_actions(
			array(
				'hook'                  => self::ACTION_HOOK,
				'args'                  => array( 'post_id' => $post_id ),
				'group'                 => self::ACTION_GROUP,
				'status'                => array(
					\ActionScheduler_Store::STATUS_PENDING,
					\ActionScheduler_Store::STATUS_RUNNING,
				),
				'partial_args_matching' => 'like',
				'per_page'              => 1,
				'offset'                => 0,
			),
			'ids'
		);

		return ! empty( $actions );
	}

	/**
	 * Whether a URL is this homepage post's singular permalink, not the site root.
	 *
	 * @param string $url      Candidate permalink.
	 * @param string $home_url Site home URL.
	 * @return bool
	 */
	public static function is_singular_homepage_url( string $url, string $home_url ): bool {
		$url  = rtrim( $url, '/' );
		$home = rtrim( $home_url, '/' );
		if ( '' === $url || $url === $home ) {
			return false;
		}

		$path = (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?? '' );
		return 1 === preg_match( '#/homepage/[^/]+#', $path );
	}

	/**
	 * Content hash used to skip no-op recaptures.
	 *
	 * Accepts WP_Post or the pipeline's enriched stdClass from
	 * setup_extra_wp_post_object_fields().
	 *
	 * @param object $post Homepage post or WP_Post-like object.
	 * @return string
	 */
	public static function compute_content_hash( object $post ): string {
		return hash(
			'sha256',
			implode(
				'|',
				array(
					(string) $post->ID,
					(string) $post->post_modified_gmt,
					(string) $post->post_content,
				)
			)
		);
	}

	/**
	 * Enqueue a capture job when the pipeline reports a homepage publish or update.
	 *
	 * @hook prc_platform_async_on_publish
	 * @hook prc_platform_async_on_update
	 *
	 * @param object $post Extended WP_Post-like object from the pipeline.
	 */
	public function maybe_schedule( $post ): void {
		// Pipeline passes stdClass from setup_extra_wp_post_object_fields(), not WP_Post.
		if ( ! is_object( $post ) || empty( $post->ID ) || empty( $post->post_type ) ) {
			return;
		}

		if ( Content_Type::$post_type !== $post->post_type ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		if ( ! self::is_configured() ) {
			return;
		}

		$post_id  = (int) $post->ID;
		$new_hash = self::compute_content_hash( $post );
		$stored   = (string) get_post_meta( $post_id, self::META_CONTENT_HASH, true );
		if ( '' !== $stored && $new_hash === $stored ) {
			return;
		}

		if ( self::is_action_pending( $post_id ) ) {
			return;
		}

		as_enqueue_async_action(
			self::ACTION_HOOK,
			array( 'post_id' => $post_id ),
			self::ACTION_GROUP
		);
	}

	/**
	 * Action Scheduler callback: screenshot the singular URL and set the featured image.
	 *
	 * @param int $post_id Homepage post ID.
	 * @throws \RuntimeException When generation or sideload fails.
	 */
	public function capture( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( Content_Type::$post_type !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		$url = (string) get_permalink( $post );
		if ( ! self::is_singular_homepage_url( $url, (string) home_url() ) ) {
			throw new \RuntimeException( 'Homepage screenshot URL is not the singular permalink.' );
		}

		$png = $this->request_screenshot_bytes( $url );
		if ( is_wp_error( $png ) ) {
			throw new \RuntimeException( $png->get_error_message() );
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$tmp_file = wp_tempnam( "homepage-shot-{$post_id}.png" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $tmp_file, $png );

		$stem       = sanitize_file_name( $post->post_name ? $post->post_name : (string) $post_id );
		$file_array = array(
			'name'     => sprintf( '%s-%d-%d.png', $stem, $post_id, time() ),
			'tmp_name' => $tmp_file,
			'type'     => 'image/png',
			'error'    => 0,
			'size'     => filesize( $tmp_file ),
		);

		$attachment_id = media_handle_sideload( $file_array, $post_id );
		@unlink( $tmp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( is_wp_error( $attachment_id ) ) {
			throw new \RuntimeException( $attachment_id->get_error_message() );
		}

		$previous = (int) get_post_meta( $post_id, self::META_ATTACHMENT_ID, true );
		set_post_thumbnail( $post_id, (int) $attachment_id );

		if ( $previous && $previous !== (int) $attachment_id ) {
			wp_delete_attachment( $previous, true );
		}

		update_post_meta( $post_id, self::META_ATTACHMENT_ID, (int) $attachment_id );
		update_post_meta( $post_id, self::META_CONTENT_HASH, self::compute_content_hash( $post ) );
		update_post_meta( $post_id, self::META_GENERATED_AT, gmdate( 'c' ) );
	}

	/**
	 * Call the Firebase screenshotElement function and return PNG bytes.
	 *
	 * @param string $url Absolute singular homepage URL.
	 * @return string|\WP_Error
	 */
	public function request_screenshot_bytes( string $url ) {
		$endpoints = self::get_endpoints();
		$endpoint  = $endpoints['screenshot_element'] ?? '';
		if ( '' === $endpoint ) {
			return new \WP_Error( 'homepage_screenshot', 'screenshot_element endpoint is not configured.' );
		}

		$firebase = new \PRC\Platform\Firebase();
		$id_token = $firebase->get_id_token( $endpoint );
		if ( is_wp_error( $id_token ) ) {
			return $id_token;
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 120,
				'headers' => array(
					'Authorization' => 'Bearer ' . $id_token,
					'Content-Type'  => 'application/json',
					'Accept'        => 'image/png',
				),
				'body'    => wp_json_encode(
					array(
						'url'            => $url,
						'selector'       => self::SELECTOR,
						'waitMs'         => self::WAIT_MS,
						'viewportWidth'  => self::VIEWPORT_WIDTH,
						'viewportHeight' => self::VIEWPORT_HEIGHT,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 || '' === $body ) {
			$message = 'Screenshot failed';
			$json    = json_decode( $body, true );
			if ( is_array( $json ) && ! empty( $json['error'] ) ) {
				$message = (string) $json['error'];
			}
			return new \WP_Error(
				'homepage_screenshot',
				$message,
				array( 'status' => $code )
			);
		}

		return $body;
	}
}
