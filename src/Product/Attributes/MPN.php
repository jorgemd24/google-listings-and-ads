<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class MPN
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class MPN extends AbstractAttribute {

	/**
	 * @return string
	 */
	public static function get_id(): string {
		return 'mpn';
	}

	/**
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Manufacturer Part Number (MPN)', 'google-listings-and-ads' );
	}

	/**
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'This code uniquely identifies the product to its manufacturer', 'google-listings-and-ads' );
	}

	/**
	 * Return an array of WooCommerce product types that this attribute can be applied to.
	 *
	 * @return array
	 */
	public static function get_applicable_product_types(): array {
		return [ 'simple', 'variation' ];
	}

}
