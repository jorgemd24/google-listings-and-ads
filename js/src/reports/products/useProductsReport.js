/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { mapReportFieldsToPerformance } from '.~/data/utils';
import useUrlQuery from '.~/hooks/useUrlQuery';

const category = 'products';
const emptyData = {
	products: [],
	intervals: [],
	totals: {},
};

/**
 * Get products report data by source of program type.
 * Query parameters will be parsed from the URL by this hook.
 *
 * @param  {string} type Data source of program type, 'free' or 'paid'.
 * @return {ProductsReportSchema} The fetched products report data and its status.
 */
export default function useProductsReport( type ) {
	const query = useUrlQuery();

	return useSelect(
		( select ) => {
			const { getReport } = select( STORE_KEY );

			const primary = getReport( category, type, query, 'primary' );
			const secondary = getReport( category, type, query, 'secondary' );
			const loaded = primary.loaded && secondary.loaded;

			let data = emptyData;

			if ( loaded ) {
				data = {
					products: primary.data.products || emptyData.products,
					intervals: primary.data.intervals || emptyData.intervals,
					totals: mapReportFieldsToPerformance(
						primary.data.totals,
						secondary.data.totals
					),
				};
			}

			return { data, loaded };
		},
		[ type, query ]
	);
}

/**
 * Schema of the `useProductsReport` hook
 *
 * @typedef {Object} ProductsReportSchema
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {ProductsReportData} data Fetched products report data.
 */

/**
 * @typedef {Object} ProductsReportData
 * @property {Array<ProductsData>} products Products data.
 * @property {Array<IntervalsData>} intervals Intervals data.
 * @property {PerformanceData} totals Performance data.
 */

/**
 * @typedef {Object} ProductsData
 * @property {number} id Product ID.
 * @property {TotalsData} subtotals Performance data.
 */

/**
 * @typedef {Object} IntervalsData
 * @property {string} interval ID of this report segment.
 * @property {TotalsData} subtotals Performance data.
 */

/**
 * @typedef { import(".~/data/utils").ReportFieldsSchema } TotalsData
 * @typedef { import(".~/data/utils").PerformanceData } PerformanceData
 */
