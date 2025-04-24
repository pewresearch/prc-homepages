<?php
/**
 * Feed functionality for homepages.
 *
 * @package PRC\Platform\Homepages
 */

namespace PRC\Platform\Homepages;

/**
 * Class Feed
 *
 * Handles the generation and registration of homepage feeds.
 *
 * @package PRC\Platform\Homepages
 */
class Feed {
	/**
	 * Cache key for the feed content.
	 *
	 * @var string
	 */
	const FEED_CACHE_KEY = 'prc_homepage_feed_content';

	/**
	 * Cache key for story IDs.
	 *
	 * @var string
	 */
	const STORY_IDS_CACHE_KEY = 'prc_homepage_story_ids';

	/**
	 * Cache lifetime in seconds (1 hour).
	 *
	 * @var int
	 */
	const CACHE_LIFETIME = 3600;

	/**
	 * Constructor.
	 *
	 * @param Loader $loader The loader instance.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'init', $this, 'init' );
		$loader->add_action( 'save_post_homepage', $this, 'flush_feed_cache' );
	}

	/**
	 * Initialize the feed functionality.
	 *
	 * @return void
	 */
	public function init() {
		add_feed( 'homepage', array( $this, 'generate_feed' ) );
	}

	/**
	 * Flush the feed cache when a homepage is saved.
	 *
	 * @return void
	 */
	public function flush_feed_cache() {
		delete_transient( self::FEED_CACHE_KEY );
		delete_transient( self::STORY_IDS_CACHE_KEY );
	}

	/**
	 * Generate the homepage feed content.
	 *
	 * @return void
	 */
	public function generate_feed() {
		$feed_content = get_transient( self::FEED_CACHE_KEY );

		if ( false === $feed_content ) {
			$story_post_ids = $this->collect_story_item_post_ids();

			ob_start();
			header( 'Content-Type: application/rss+xml; charset=UTF-8' );
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
<channel>
	<title><?php bloginfo_rss( 'name' ); ?> Homepage Feed</title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss( 'url' ); ?></link>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT', 'homepage' ), false ) ); ?></lastBuildDate>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
			<?php
			foreach ( $story_post_ids as $story_id ) {
				$story = get_post( $story_id );
				if ( ! $story ) {
					continue;
				}

				$thumbnail_id = get_post_thumbnail_id( $story );
				$thumbnail    = $thumbnail_id ? wp_get_attachment_image_src( $thumbnail_id, 'full' ) : null;
				?>
		<item>
			<title><?php echo esc_html( get_the_title( $story ) ); ?></title>
			<link><?php echo esc_url( get_permalink( $story ) ); ?></link>
			<guid isPermaLink="false"><?php echo esc_url( get_permalink( $story ) ); ?></guid>
			<pubDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true, $story ), false ) ); ?></pubDate>
			<description><![CDATA[<?php echo wp_kses_post( get_the_excerpt( $story ) ); ?>]]></description>
				<?php if ( $thumbnail ) : ?>
			<enclosure url="<?php echo esc_url( $thumbnail[0] ); ?>" type="<?php echo esc_attr( get_post_mime_type( $thumbnail_id ) ); ?>" />
				<?php endif; ?>
		</item>
				<?php
			}
			?>
</channel>
</rss>
			<?php
			$feed_content = ob_get_clean();
			set_transient( self::FEED_CACHE_KEY, $feed_content, self::CACHE_LIFETIME );
		}

		echo $feed_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is pre-escaped during generation
	}

	/**
	 * Recursively find all story item blocks in a given array of blocks.
	 *
	 * @param array $blocks The blocks to search through.
	 * @return array The story item blocks found.
	 */
	public function recursively_find_story_item_blocks( $blocks ) {
		$story_item_blocks = array();

		foreach ( $blocks as $block ) {
			if ( 'prc-block/story-item' === $block['blockName'] ) {
				$story_item_blocks[] = $block;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$story_item_blocks = array_merge( $story_item_blocks, $this->recursively_find_story_item_blocks( $block['innerBlocks'] ) );
			}
		}

		return $story_item_blocks;
	}

	/**
	 * Collect the post IDs of the 10 most recent story items from the latest homepage.
	 *
	 * @return array
	 */
	public function collect_story_item_post_ids() {
		$story_post_ids = get_transient( self::STORY_IDS_CACHE_KEY );

		if ( false !== $story_post_ids ) {
			return $story_post_ids;
		}

		// Get the latest homepage.
		$homepage = get_posts(
			array(
				'post_type'   => 'homepage',
				'numberposts' => 1,
			)
		);

		wp_reset_postdata();

		if ( empty( $homepage ) ) {
			return array();
		}

		$homepage = $homepage[0];

		// Get the story item blocks from the homepage content.
		$homepage_blocks = parse_blocks( $homepage->post_content );

		// Get the first 10 blocks that have a blockName of prc-block/story-item.
		$story_item_blocks = $this->recursively_find_story_item_blocks( $homepage_blocks );

		// Get the post IDs from the story item blocks.
		$story_item_post_ids = array_map(
			function ( $block ) {
				return $block['attrs']['postId'];
			},
			$story_item_blocks
		);

		set_transient( self::STORY_IDS_CACHE_KEY, $story_item_post_ids, self::CACHE_LIFETIME );

		return $story_item_post_ids;
	}
}
