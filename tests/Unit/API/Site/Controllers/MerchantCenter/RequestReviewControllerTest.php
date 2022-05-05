<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\RequestReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Google\Exception;

/**
 * Test suit for RequestReviewController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group RequestReview
 */
class RequestReviewControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_GET_REQUEST = '/wc/gla/mc/review';
	protected const ROUTE_REQUEST_REVIEW = '/wc/gla/mc/request-review';
	private $middleware;
	private $transients;
	private $request_review_statuses;

	public function setUp(): void {
		parent::setUp();
		$this->middleware = $this->createMock( Middleware::class );
		$this->transients = $this->createMock( TransientsInterface::class );
		$this->request_review_statuses = new RequestReviewStatuses();
		$this->controller = new RequestReviewController( $this->server, $this->middleware, $this->request_review_statuses, $this->transients  );
		$this->controller->register();
	}

	public function test_get_status_route() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programType' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::WARNING,
								                 'reviewIssues'            => [ 'one', 'two' ],
							                 ]
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'                => RequestReviewStatuses::WARNING,
			'issues'                => [ 'one', 'two' ],
			'cooldown'              => 0,
			'reviewEligibleRegions' => []
		], $response->get_data() );
	}

	public function test_request_review_route() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'account_request_review' )
		                 ->willReturn(
			                 [
				                 'message' => 'A new review has been successfully requested'
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_REQUEST_REVIEW );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'message' => 'A new review has been successfully requested'
		], $response->get_data() );
	}

	public function test_request_review_route_in_cooldown() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'freeListingsProgram' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'regionCodes'                      => [ 'US' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "1651047106000" // 27/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'                      => [ 'MX' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'ELIGIBLE'
							                 ]
						                 ],
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_REQUEST_REVIEW );
		$this->assertEquals( 'Your account is under cool down period and cannot request a new review.', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_request_review_route_ineligible() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'freeListingsProgram' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'regionCodes'                      => [ 'US' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
							                 ]
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_REQUEST_REVIEW );
		$this->assertEquals( 'Your account is not eligible for a new request review.', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_merged_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
							                 ],
							                 [
								                 'regionCodes'             => [ 'US', 'CA' ],
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
						                 ]
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::WARNING,
								                 'reviewIssues'            => [ 'two' ]
							                 ],
							                 [
								                 'regionCodes'             => [ 'US', 'CA' ],
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::UNDER_REVIEW
							                 ],
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'                => RequestReviewStatuses::DISAPPROVED,
			'issues'                => [ 'one', 'two' ],
			'cooldown'              => 0,
			'reviewEligibleRegions' => [ 'US' ]
		], $response->get_data() );
	}

	public function test_no_offers_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
						                 ]
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::NO_OFFERS
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => 'ONBOARDING',
			'issues'   => [ 'one' ],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unexpected_state_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  "WEIRD"
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  "UNKNOWN"
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => null,
			'issues'   => [],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unexpected_state_and_good_states_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" => "WEIRD"
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState"    => RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
							                 ]
						                 ]
					                 ]
				                 ],
				                 'programTypeC' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" => "UNKNOWN"
					                 ]
				                 ],
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => RequestReviewStatuses::APPROVED,
			'issues'   => [],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unexpected_status_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState"    => RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'UNKNOWN',
								                 'eligibilityStatus'       => 'UNKNOWN',
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'WEIRD',
								                 'eligibilityStatus'       => 'WEIRD',
							                 ],
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => RequestReviewStatuses::DISAPPROVED,
			'issues'   => [ 'one' ],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unset_region_statuses() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState"    => RequestReviewStatuses::ENABLED,
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => null,
			'issues'   => [],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_cooldown() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'freeListingsProgram' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'regionCodes'                      => [ 'US' ],
								                 'eligibilityStatus'                => RequestReviewStatuses::DISAPPROVED,
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "2022-04-27T10:58:51Z" // 27/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'                      => [ 'NL' ],
								                 'eligibilityStatus'                => RequestReviewStatuses::DISAPPROVED,
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "2022-04-25T10:58:51Z" // 25/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'             => [ 'IT' ],
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
							                 ],
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => RequestReviewStatuses::DISAPPROVED,
			'issues'   => [],
			'cooldown' => 1651058331000, // 27/04/2022
			'reviewEligibleRegions' => [ 'IT' ]
		], $response->get_data() );
	}

	public function test_exception_in_routes() {
		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willThrowException( new Exception( 'error', 401 ) );

		$this->middleware->expects( $this->once() )
		                 ->method( 'account_request_review' )
		                 ->willThrowException( new Exception( 'error', 401 ) );


		$routes = [ self::ROUTE_GET_REQUEST, self::ROUTE_REQUEST_REVIEW ];

		foreach ( $routes as $route ) {
			$response = $this->do_request( $route );
			$this->assertEquals( 'error', $response->get_data()['message'] );
			$this->assertEquals( 401, $response->get_status() );
		}
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_GET_REQUEST, $this->server->get_routes() );
		$this->assertArrayHasKey( self::ROUTE_REQUEST_REVIEW, $this->server->get_routes() );
	}

}
