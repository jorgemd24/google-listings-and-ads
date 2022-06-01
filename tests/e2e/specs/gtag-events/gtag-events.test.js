/**
 * External dependencies
 */
import {
	shopper, // eslint-disable-line import/named
	createSimpleProduct,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	clearConversionID,
	saveConversionID,
} from '../../utils/connection-test-page';
import { getEventData, trackGtagEvent } from '../../utils/track-event';

let simpleProductID;

describe( 'GTag events', () => {
	beforeAll( async () => {
		await saveConversionID();
		simpleProductID = await createSimpleProduct();
	} );

	afterAll( async () => {
		await clearConversionID();
	} );

	it( 'Global GTag snippet appears on a frontend page', async () => {
		await shopper.goToShop();

		await expect(
			page.$$eval( 'head', ( elements ) =>
				elements.some( ( el ) =>
					el.innerHTML.includes( 'Global site tag (gtag.js)' )
				)
			)
		).resolves.toBeTruthy();
	} );

	it( 'Page view event is sent on a frontend page', async () => {
		const event = trackGtagEvent( 'page_view' );

		await shopper.goToShop();
		await expect( event ).resolves.toBeTruthy();
	} );

	it( 'View item event is sent on a single product page', async () => {
		const event = trackGtagEvent( 'view_item' );

		await shopper.goToProduct( simpleProductID );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'product' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );
} );
