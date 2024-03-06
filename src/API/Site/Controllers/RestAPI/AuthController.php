<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AuthController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI
 */
class AuthController extends BaseController {

	/**
	 * @var OAuthService
	 */
	protected $oauth_service;

	/**
	 * Mapping between the client page name and its path.
	 * The first value is also used as a default,
	 * and changing the order of keys/values may affect things below.
	 *
	 * @var string[]
	 */
	private const NEXT_PATH_MAPPING = [
		'setup-mc'  => '/google/setup-mc',
		'reconnect' => '/google/settings',
	];

	/**
	 * AuthController constructor.
	 *
	 * @param RESTServer   $server
	 * @param OAuthService $oauth_service
	 */
	public function __construct( RESTServer $server, OAuthService $oauth_service ) {
		parent::__construct( $server );
		$this->oauth_service = $oauth_service;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$this->register_route(
			'rest-api/authorize',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_authorize_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_auth_params(),
				],
			]
		);
	}

	/**
	 * Get the callback function for the authorization request.
	 *
	 * @return callable
	 */
	protected function get_authorize_callback(): callable {
		return function ( Request $request ) {
			try {
				$next     = $request->get_param( 'next_page_name' );
				$path     = self::NEXT_PATH_MAPPING[ $next ];
				$auth_url = $this->oauth_service->get_auth_url( $path );

				$response = [
					'auth_url' => $auth_url,
				];

				return $this->prepare_item_for_response( $response, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the query params for the authorize request.
	 *
	 * @return array
	 */
	protected function get_auth_params(): array {
		return [
			'next_page_name' => [
				'description'       => __( 'Indicates the next page name mapped to the redirect URL when redirected back from Google WPCOM App authorization.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => array_key_first( self::NEXT_PATH_MAPPING ),
				'enum'              => array_keys( self::NEXT_PATH_MAPPING ),
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'auth_url' => [
				'type'        => 'string',
				'description' => __( 'The authorization URL for granting access to Google WPCOM App.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'rest_api_authorize';
	}
}
