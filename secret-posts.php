<?php
/**
 * Plugin Name: Secret Posts
 * Plugin URI: http://scootah.com/
 * Description: Mark WordPress posts as private after a specified number of page views or time.
 * Version: 1.0
 * Author: Scott Grant
 * Author URI: http://scootah.com/
 */
class WP_Secret_Posts {

	/**
	 * Store reference to singleton object.
	 */
	private static $instance = null;

	/**
	 * The domain for localization.
	 */
	const DOMAIN = 'wp-secret-posts';

	/**
	 * Instantiate, if necessary, and add hooks.
	 */
	public function __construct() {
		if ( isset( self::$instance ) ) {
			wp_die( esc_html__(
				'WP_Secret_Posts is already instantiated!',
				self::DOMAIN ) );
		}

		self::$instance = $this;

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ) );

		add_action( 'the_post', array( $this, 'check_the_post' ) );
	}

	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Initialize meta box.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'secret_posts',
			'Secret Posts',
			array( $this, 'generate_meta_box' ),
			'',
			'normal'
		);
	}

	/**
	 * Show HTML for the zone details stored in post meta.
	 */
	public function generate_meta_box( $post ) {
		$post_id = intval( $post->ID );
		$post_views = get_post_meta( $post_id, 'secret_posts_views', true );
		$post_date = get_post_meta( $post_id, 'secret_posts_date', true );
?>
<p>
Mark as private after
<input type="text" name="secret_posts_views" style="width: 64px;" value="<?php echo( $post_views ); ?>">
views or this date:
<input type="text" name="secret_posts_date" value="<?php echo( $post_date ); ?>">
</p>
<?php
	}

	/**
	 * Extract the updates from $_POST and save in post meta.
	 */
	public function save_post_meta( $post_id ) {
		if ( isset( $_POST[ 'secret_posts_views' ] ) ) {
			update_post_meta( $post_id, 'secret_posts_views',
				$_POST[ 'secret_posts_views' ] );
		}

		if ( isset( $_POST[ 'secret_posts_date' ] ) ) {
			update_post_meta( $post_id, 'secret_posts_date',
				$_POST[ 'secret_posts_date' ] );
		}
	}

	public function check_the_post( $post ) {
		$views = intval( get_post_meta( $post->ID, 'secret_posts_views', true ) );
		$date = get_post_meta( $post->ID, 'secret_posts_date', true );

		$lock = false;
		if ( $views > 0 ) {
			$views -= 1;
			update_post_meta( $post->ID, 'secret_posts_views', $views );

			if ( $views == 0 ) {
				$lock = true;
			}
		}

		if ( strlen( $date ) > 0 && strtotime( $date ) < time() ) {
			$lock = true;
		}

		if ( $lock ) {
			wp_update_post(
				array(
					'ID' => $post->ID,
					'post_status' => 'private',
				)
			);
		}
	}

}

$wp_secret_posts = new WP_Secret_Posts();
