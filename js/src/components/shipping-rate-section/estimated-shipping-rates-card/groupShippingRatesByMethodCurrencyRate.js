/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Groups shipping rates based on method, currency and rate.
 *
 * Usage example:
 *
 * ```js
 * const shippingRates = [
 *     {
 *         id: "1",
 *         country: 'US',
 *         currency: 'USD',
 *         rate: 20,
 *         options: {},
 *     },
 *     {
 *         id: "2",
 *         country: 'AU',
 *         currency: 'USD',
 *         rate: 20,
 *         options: {},
 *     },
 *     {
 *         id: "3",
 *         country: 'CN',
 *         currency: 'USD',
 *         rate: 25,
 *         options: {},
 *     },
 *     {
 *         id: "4",
 *         country: 'BR',
 *         currency: 'BRL',
 *         rate: 20,
 *         options: {},
 *     },
 * ]
 *
 * const result = groupShippingRatesByMethodCurrencyRate( shippingRates );
 *
 * // result:
 * // [
 * //     {
 * //         countries: ['US', 'AU'],
 * //         currency: 'USD',
 * //         rate: 20,
 * //     },
 * //     {
 * //         countries: ['CN'],
 * //         currency: 'USD',
 * //         rate: 25,
 * //     },
 * //     {
 * //         countries: ['BR'],
 * //         currency: 'BRL',
 * //         rate: 20,
 * //     },
 * // ]
 * ```
 *
 * @param {Array<ShippingRate>} shippingRates Array of shipping rates.
 * @return {Array<ShippingRateGroup>} Array of shipping rate groups.
 */
const groupShippingRatesByMethodCurrencyRate = ( shippingRates ) => {
	const rateGroupMap = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { country, currency, rate } = shippingRate;
		const methodCurrencyRate = `${ currency } ${ rate } `;
		const group = rateGroupMap.get( methodCurrencyRate ) || {
			countries: [],
			currency,
			rate,
		};
		group.countries.push( country );
		rateGroupMap.set( methodCurrencyRate, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default groupShippingRatesByMethodCurrencyRate;
