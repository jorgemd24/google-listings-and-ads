<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V6\Resources\Campaign;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ads
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Ads {

	/**
	 * The container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * The ads account ID.
	 *
	 * @var PositiveInteger
	 */
	protected $id;

	/**
	 * Ads constructor.
	 *
	 * @param ContainerInterface $container
	 * @param PositiveInteger    $id
	 */
	public function __construct( ContainerInterface $container, PositiveInteger $id ) {
		$this->container = $container;
		$this->id        = $id;
	}

	/**
	 * Get the ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id->get();
	}

	/**
	 * @return Campaign[]
	 */
	public function get_campaigns(): array {
		/** @var GoogleAdsClient $client */
		$client = $this->container->get( GoogleAdsClient::class );
		$args   = [ 'headers' => $this->container->get( 'headers' ) ];
		$query  = 'SELECT campaign.id, campaign.name FROM campaign ORDER BY campaign.id';
		$rows   = $client->getGoogleAdsServiceClient()->search( $this->get_id(), $query, $args );
		$return = [];

		foreach ( $rows->iterateAllElements() as $row ) {
			$return[] = $row->getCampaign();
		}

		return $return;
	}
}
