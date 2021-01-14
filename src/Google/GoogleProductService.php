<?php

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Google_Service_ShoppingContent as GoogleShoppingService;
use Google_Service_ShoppingContent_Error as GoogleError;
use Google_Service_ShoppingContent_Product as GoogleProduct;
use Google_Service_ShoppingContent_ProductsCustomBatchRequest as GoogleBatchRequest;
use Google_Service_ShoppingContent_ProductsCustomBatchRequestEntry as GoogleBatchRequestEntry;
use Google_Service_ShoppingContent_ProductsCustomBatchResponse as GoogleBatchResponse;
use Google_Service_ShoppingContent_ProductsCustomBatchResponseEntry as GoogleBatchResponseEntry;

/**
 * Class GoogleProductService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class GoogleProductService implements Service {

	/**
	 * This is the maximum batch size recommended by Google
	 *
	 * @link https://developers.google.com/shopping-content/guides/how-tos/batch
	 */
	public const BATCH_SIZE = 1000;

	protected const METHOD_DELETE = 'delete';
	protected const METHOD_GET    = 'get';
	protected const METHOD_INSERT = 'insert';

	/**
	 * @var GoogleShoppingService
	 */
	protected $shopping_service;

	/**
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * GoogleProductService constructor.
	 *
	 * @param GoogleShoppingService $shopping_service
	 * @param Merchant              $merchant
	 */
	public function __construct( GoogleShoppingService $shopping_service, Merchant $merchant ) {
		$this->shopping_service = $shopping_service;
		$this->merchant         = $merchant;
	}

	/**
	 * @param string $product_id Google product ID.
	 *
	 * @return GoogleProduct
	 */
	public function get( string $product_id ): GoogleProduct {
		$merchant_id = $this->merchant->get_id();

		return $this->shopping_service->products->get( $merchant_id, $product_id );
	}

	/**
	 * @param GoogleProduct $product
	 *
	 * @return GoogleProduct
	 */
	public function insert( GoogleProduct $product ): GoogleProduct {
		$merchant_id = $this->merchant->get_id();

		return $this->shopping_service->products->insert( $merchant_id, $product );
	}

	/**
	 * @param string $product_id Google product ID.
	 */
	public function delete( string $product_id ) {
		$merchant_id = $this->merchant->get_id();

		$this->shopping_service->products->delete( $merchant_id, $product_id );
	}

	/**
	 * @param BatchProductRequestEntry[] $products
	 *
	 * @return BatchGetProductResponse
	 *
	 * @throws InvalidValue If any of the provided products are invalid.
	 */
	public function get_batch( array $products ): BatchGetProductResponse {
		if ( empty( $products ) ) {
			return new BatchGetProductResponse( [], [] );
		}
		return $this->custom_batch( $products, self::METHOD_GET );
	}

	/**
	 * @param BatchProductRequestEntry[] $products
	 *
	 * @return BatchUpdateProductResponse
	 *
	 * @throws InvalidValue If any of the provided products are invalid.
	 */
	public function insert_batch( array $products ): BatchUpdateProductResponse {
		if ( empty( $products ) ) {
			return new BatchUpdateProductResponse( [], [] );
		}
		return $this->custom_batch( $products, self::METHOD_INSERT );
	}

	/**
	 * @param BatchProductRequestEntry[] $products
	 *
	 * @return BatchDeleteProductResponse
	 *
	 * @throws InvalidValue If any of the provided products are invalid.
	 */
	public function delete_batch( array $products ): BatchDeleteProductResponse {
		if ( empty( $products ) ) {
			return new BatchDeleteProductResponse( [], [] );
		}
		return $this->custom_batch( $products, self::METHOD_DELETE );
	}

	/**
	 * @param BatchProductRequestEntry[] $products
	 * @param string                     $method
	 *
	 * @return BatchProductResponse
	 *
	 * @throws InvalidValue If any of the products' type is invalid for the batch method.
	 */
	protected function custom_batch( array $products, string $method ): BatchProductResponse {
		$merchant_id     = $this->merchant->get_id();
		$request_entries = [];
		foreach ( $products as $product_entry ) {
			$this->validate_batch_method_product( $method, $product_entry->get_product() );

			$request_entry = new GoogleBatchRequestEntry(
				[
					'batchId'    => $product_entry->get_wc_product_id(),
					'merchantId' => $merchant_id,
					'method'     => $method,
				]
			);

			$product_key                   = self::METHOD_INSERT === $method ? 'product' : 'product_id';
			$request_entry[ $product_key ] = $product_entry->get_product();
			$request_entries[]             = $request_entry;
		}

		$responses = $this->shopping_service->products->custombatch( new GoogleBatchRequest( [ 'entries' => $request_entries ] ) );

		return $this->parse_batch_responses( $responses, $method );
	}

	/**
	 * @param GoogleBatchResponse $responses
	 * @param string              $method
	 *
	 * @return BatchProductResponse
	 */
	protected function parse_batch_responses( GoogleBatchResponse $responses, string $method ): BatchProductResponse {
		$result_products = [];
		$errors          = [];

		/**
		 * @var GoogleBatchResponseEntry $response
		 */
		foreach ( $responses as $response ) {
			// WooCommerce product ID is provided and returned as batchId
			$wc_product_id = $response->batchId;

			if ( empty( $response->getErrors() ) ) {
				$result_products[] = new BatchProductEntry( $wc_product_id, $response->getProduct() );
			} else {
				$errors[] = new BatchInvalidProductEntry( $wc_product_id, self::get_batch_response_error_messages( $response ) );
			}
		}

		switch ( $method ) {
			case self::METHOD_INSERT:
				$response = new BatchUpdateProductResponse( $result_products, $errors );
				break;
			case self::METHOD_DELETE:
				$response = new BatchDeleteProductResponse( $result_products, $errors );
				break;
			default:
				$response = new BatchGetProductResponse( $result_products, $errors );
		}

		return $response;
	}

	/**
	 * @param string               $method
	 * @param GoogleProduct|string $product
	 *
	 * @throws InvalidValue If the product type is invalid for the batch method.
	 */
	protected function validate_batch_method_product( string $method, $product ) {
		if ( self::METHOD_INSERT === $method && ! $product instanceof GoogleProduct ) {
			throw InvalidValue::not_instance_of( GoogleProduct::class, 'product' );
		} elseif ( in_array( $method, [ self::METHOD_GET, self::METHOD_DELETE ], true ) && ! is_string( $product ) ) {
			throw InvalidValue::not_string( 'product' );
		}
	}

	/**
	 * @param GoogleBatchResponseEntry $batch_response_entry
	 *
	 * @return array
	 */
	protected static function get_batch_response_error_messages( GoogleBatchResponseEntry $batch_response_entry ): array {
		return array_map(
			function ( GoogleError $error ) {
				return $error->getMessage();
			},
			$batch_response_entry->getErrors()->getErrors()
		);
	}
}
