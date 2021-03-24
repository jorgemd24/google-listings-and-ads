<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Psr\Container\ContainerInterface;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class BaseReportsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
abstract class BaseReportsController extends BaseController {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * BaseReportsController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
		parent::__construct( $container->get( RESTServer::class ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return [
			'context'  => $this->get_context_param( [ 'default' => 'view' ] ),
			'after'    => [
				'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'date',
				'default'           => '-7 days',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'before'   => [
				'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'date',
				'default'           => 'now',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'interval' => [
				'description'       => __( 'Time interval to use for buckets in the returned data.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'enum'              => [
					'day',
					'week',
					'month',
					'quarter',
					'year',
				],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'ids'      => [
				'description'       => __( 'Limit result to items with specified ids.', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'integer',
				],
			],
			'fields'   => [
				'description'       => __( 'Limit stats fields to the specified items.', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_slug_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param Request $request REST Request.
	 * @return array
	 */
	protected function prepare_query_arguments( Request $request ) {
		$params   = $this->get_collection_params();
		$defaults = wp_list_pluck( $params, 'default' );
		$args     = wp_parse_args( array_intersect_key( $request->get_query_params(), $params ), $defaults );

		$this->normalize_timezones( $args );
		return $args;
	}

	/**
	 * Converts input datetime parameters to local timezone.
	 *
	 * @param array $query_args Array of query arguments.
	 */
	protected function normalize_timezones( &$query_args ) {
		$local_tz = new \DateTimeZone( wc_timezone_string() );
		foreach ( [ 'before', 'after' ] as $query_arg_key ) {
			if ( isset( $query_args[ $query_arg_key ] ) && is_string( $query_args[ $query_arg_key ] ) ) {
				// Assume that unspecified timezone is a local timezone.
				$datetime = new \DateTime( $query_args[ $query_arg_key ], $local_tz );
				// In case timezone was forced by using +HH:MM, convert to local timezone.
				$datetime->setTimezone( $local_tz );
				$query_args[ $query_arg_key ] = $datetime;
			} elseif ( isset( $query_args[ $query_arg_key ] ) && is_a( $query_args[ $query_arg_key ], 'DateTime' ) ) {
				// In case timezone is in other timezone, convert to local timezone.
				$query_args[ $query_arg_key ]->setTimezone( $local_tz );
			}
		}
	}
}
