<?php
/**
 * Admin class.
 *
 * Author:          Andrei Baicus <andrei@themeisle.com>
 * Created on:      19/07/2018
 *
 * @soundtrack Somewhere Else - Marillion
 * @package    \Optimole\Inc
 * @author     Optimole <friends@optimole.com>
 */

/**
 * Class Optml_Admin
 */
class Optml_Admin {

	/**
	 * Hold the settings object.
	 *
	 * @var Optml_Settings Settings object.
	 */
	public $settings;

	/**
	 * Optml_Admin constructor.
	 */
	public function __construct() {
		$this->settings = new Optml_Settings();

		add_action( 'admin_menu', array( $this, 'add_dashboard_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_traffic_node' ), 9999 );
		add_filter( 'wp_resource_hints', array( $this, 'add_dns_prefetch' ), 10, 2 );
		add_action( 'optml_daily_sync', array( $this, 'daily_sync' ) );
		add_action( 'wp_head', array( $this, 'generator' ) );
		if ( ! is_admin() && $this->settings->is_connected() ) {
			if ( ! wp_next_scheduled( 'optml_daily_sync' ) ) {
				wp_schedule_event( time() + 10, 'daily', 'optml_daily_sync', array() );
			}
		}
	}

	/**
	 * Output Generator tag.
	 */
	public function generator() {
		if ( ! $this->settings->is_connected() ) {
			return;
		}
		if ( ! $this->settings->is_enabled() ) {
			return;
		}
		echo '<meta name="generator" content="Optimole ' . esc_attr( OPTML_VERSION ) . '">';
	}

	/**
	 * Update daily the quota routine.
	 */
	function daily_sync() {

		$api_key = $this->settings->get( 'api_key' );
		if ( empty( $api_key ) ) {
			return;
		}

		$request = new Optml_Api();
		$data    = $request->get_user_data( $api_key );
		if ( $data === false ) {
			return;
		}

		$this->settings->update( 'service_data', $data );

	}

	/**
	 * Adds cdn url for prefetch.
	 *
	 * @param array  $hints Hints array.
	 * @param string $relation_type Type of relation.
	 *
	 * @return array Altered hints array.
	 */
	public function add_dns_prefetch( $hints, $relation_type ) {
		if ( 'dns-prefetch' !== $relation_type ) {
			return $hints;
		}
		if ( ! $this->settings->is_connected() ) {
			return $hints;
		}
		if ( ! $this->settings->is_enabled() ) {
			return $hints;
		}
		$hints[] = sprintf( '//%s', $this->settings->get_cdn_url() );

		return $hints;
	}

	/**
	 * Add the dashboard page.
	 */
	public function add_dashboard_page() {
		add_media_page( 'Optimole', 'Optimole', 'manage_options', 'optimole', array( $this, 'render_dashboard_page' ) );
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard_page() {
		?>
		<div id="optimole-app">
			<app></app>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts needed for admin functionality.
	 */
	public function enqueue() {
		$current_screen = get_current_screen();
		if ( ! isset( $current_screen->id ) ) {
			return;
		}
		if ( $current_screen->id != 'media_page_optimole' ) {
			return;
		}
		wp_register_script( OPTML_NAMESPACE . '-admin', OPTML_URL . 'assets/js/bundle' . ( ! OPTML_DEBUG ? '.min' : '' ) . '.js', array(), OPTML_VERSION );
		wp_localize_script( OPTML_NAMESPACE . '-admin', 'optimoleDashboardApp', $this->localize_dashboard_app() );
		wp_enqueue_script( OPTML_NAMESPACE . '-admin' );
	}

	/**
	 * Localize the dashboard app.
	 *
	 * @return array
	 */
	private function localize_dashboard_app() {
		$api_key        = $this->settings->get( 'api_key' );
		$service_data   = $this->settings->get( 'service_data' );
		$admin_bar_item = $this->settings->get( 'admin_bar_item' );
		$image_replacer = $this->settings->get( 'image_replacer' );

		$args = array(
			'strings'           => $this->get_dashboard_strings(),
			'assets_url'        => OPTML_URL . 'assets/',
			'connection_status' => empty( $service_data ) ? false : true,
			'api_key'           => $api_key,
			'root'              => rest_url( OPTML_NAMESPACE . '/v1' ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'user_data'         => $service_data,
			'admin_bar_item'    => $admin_bar_item,
			'image_replacer'    => $image_replacer,
			'home_url'          => home_url(),
		);

		return $args;
	}

	/**
	 * Get all dashboard strings.
	 *
	 * @return array
	 */
	private function get_dashboard_strings() {
		return array(
			'optimole'            => __( 'Optimole', 'optimole-wp' ),
			'image_cdn'           => __( 'Image CDN', 'optimole-wp' ),
			'connect_btn'         => __( 'Connect to OptiMole Service', 'optimole-wp' ),
			'disconnect_btn'      => __( 'Disconnect', 'optimole-wp' ),
			'api_key_placeholder' => __( 'API Key', 'optimole-wp' ),
			'invalid_key'         => __( 'Invalid API Key', 'optimole-wp' ),
			'status'              => __( 'Status', 'optimole-wp' ),
			'connected'           => __( 'Connected', 'optimole-wp' ),
			'not_connected'       => __( 'Not connected', 'optimole-wp' ),
			'usage'               => __( 'Monthly Usage', 'optimole-wp' ),
			'quota'               => __( 'Monthly Quota', 'optimole-wp' ),
			'logged_in_as'        => __( 'Logged in as', 'optimole-wp' ),
			'private_cdn_url'     => __( 'Private CDN url', 'optimole-wp' ),
			'options'             => __( 'Options', 'optimole-wp' ),
			'account_needed'      => sprintf(
				__( 'In order to get access to free image optimization service you will need an account on %s. You will get access to our image optimization and CDN service for free in the limit of 1GB traffic per month.', 'optimole-wp' ),
				' <a href="https://dashboard.optimole.com/register" target="_blank">optimole.com</a>'
			),
			'options_strings'     => array(
				'toggle_ab_item'       => __( 'Admin bar status', 'optimole-wp' ),
				'enable_image_replace' => __( 'Enable image replace', 'optimole-wp' ),
				'show'                 => __( 'Show', 'optimole-wp' ),
				'hide'                 => __( 'Hide', 'optimole-wp' ),
				'enabled'              => __( 'Enabled', 'optimole-wp' ),
				'disabled'             => __( 'Disabled', 'optimole-wp' ),
			),
			'latest_images'       => array(
				'image'               => __( 'Image', 'optimole-wp' ),
				'compression'         => __( 'Optimization', 'optimole-wp' ),
				'last'                => __( 'Last', 'optimole-wp' ),
				'optimized_images'    => __( 'optimized images', 'optimole-wp' ),
				'same_size'           => __( '🙉 We couldn\'t do better, this image is already optimized at maximum. ', 'optimole-wp' ),
				'small_optimization'  => __( '😬 Not that much, just <strong>{ratio}</strong> smaller.', 'optimole-wp' ),
				'medium_optimization' => __( '🤓 We are on the right track, <strong>{ratio}</strong> squeezed.', 'optimole-wp' ),
				'big_optimization'    => __( '❤️❤️❤️ Our moles just nailed it, this one is <strong>{ratio}</strong> smaller.  ', 'optimole-wp' ),
			)
		);
	}

	/**
	 * Add top admin bar notice of traffic quota/usage.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar resource.
	 */
	public function add_traffic_node( $wp_admin_bar ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$settings = new Optml_Settings();
		if ( ! $settings->is_connected() ) {
			return;
		}
		$should_load = $settings->get( 'admin_bar_item' );

		$service_data = $this->settings->get( 'service_data' );
		if ( empty( $service_data ) ) {
			return;
		}
		$args = array(
			'id'    => 'optml_image_quota',
			'title' => 'Optimole' . __( ' Image Traffic', 'optimole-wp' ) . ': ' . number_format( floatval( ( $service_data['usage'] / 1000 ) ), 3 ) . ' / ' . number_format( floatval( ( $service_data['quota'] / 1000 ) ), 0 ) . 'GB',
			'href'  => 'https://dashboard.optimole.com/',
			'meta'  => array(
				'target' => '_blank',
				'class'  => $should_load !== 'enabled' ? 'hidden' : ''
			)
		);
		$wp_admin_bar->add_node( $args );
	}
}
