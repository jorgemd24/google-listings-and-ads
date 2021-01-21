<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

/**
 * Trait PluginHelper
 *
 * Helper functions that are useful throughout the plugin.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
trait PluginHelper {

	/**
	 * Get the root directory for the plugin.
	 *
	 * @return string
	 */
	protected function get_root_dir(): string {
		return dirname( __DIR__ );
	}

	/**
	 * Get the full path to the main plugin file.
	 *
	 * @return string
	 */
	protected function get_main_file(): string {
		return "{$this->get_root_dir()}/{$this->get_main_filename()}";
	}

	/**
	 * Get the main file for this plugin.
	 *
	 * @return string
	 */
	protected function get_main_filename(): string {
		return 'google-listings-and-ads.php';
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	protected function get_slug(): string {
		return 'gla';
	}

	/**
	 * Get the plugin URL, possibly with an added path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function get_plugin_url( string $path = '' ): string {
		return plugins_url( $path, $this->get_main_file() );
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	protected function get_version(): string {
		return '0.1.0';
	}
}
