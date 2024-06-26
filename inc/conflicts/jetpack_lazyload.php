<?php

/**
 * Class Optml_Jetpack_Lazyload
 *
 * An example of a conflict.
 */
class Optml_Jetpack_Lazyload extends Optml_Abstract_Conflict {

	/**
	 * Optml_Jetpack_Lazyload constructor.
	 */
	public function __construct() {
		$this->severity = self::SEVERITY_MEDIUM;
		parent::__construct();
	}

	/**
	 * Set the message property
	 *
	 * @since   2.0.6
	 * @access  public
	 */
	public function define_message() {
		$this->message = sprintf( /* translators: 1 is the start of the bold tag, 2 is ending bold tag, 3 is new line tag, 4 is anchor tag start, 5 is ending anchor tag */__( 'It seems your are using %1$sJetpack%2$s with Lazy loading option ON. %3$s Optimole already provides a lazy loading mechanism by it\'s own which might conflict with this. If you would like to further use Optimole lazy loading feature, you can turn that off from %4$sJetpack -> Perfomance%5$s page. ', 'optimole-wp' ), '<b>', '</b>', '<br/>', '<a target="_blank" href="' . admin_url( 'admin.php?page=jetpack#/performance' ) . '">', '</a>' );
	}

	/**
	 * Determine if conflict is applicable.
	 *
	 * @return bool
	 * @since   2.0.6
	 * @access  public
	 */
	public function is_conflict_valid() {

		if ( ! is_plugin_active( 'jetpack/jetpack.php' ) ) {
			return false;
		}
		if ( ! class_exists( 'Jetpack', false ) ) {
			return false;
		}
		if ( ! class_exists( 'Jetpack', false ) ) {
			return false;
		}

		if ( ! Optml_Main::instance()->admin->settings->use_lazyload() ) {
			return false;
		}

		return Jetpack::is_module_active( 'lazy-images' );
	}
}
