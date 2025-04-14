<?php
/**
 * Main plugin class file.
 *
 * @package JSONExport
 */

namespace Calm\JSONExport;

/**
 * Core plugin class
 */
final class JSONExport {


	/**
	 * Singleton instance
	 *
	 * @var JSONExport|null
	 */
	private static $instance = null;

	/**
	 * Admin handler instance
	 *
	 * @var Admin|null
	 */
	private $admin = null;

	/**
	 * Get the singleton instance of this class
	 *
	 * @return JSONExport
	 */
	public static function get_instance(): JSONExport {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		$this->_init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function _init(): void {
		$this->admin = new Admin( CALM_JSON_EXPORT_TEXT_DOMAIN );
	}
}
