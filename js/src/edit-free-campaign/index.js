/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import isEqual from 'lodash/isEqual';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import ChooseAudience from '.~/components/free-listings/choose-audience';
import useTargetAudience from '.~/hooks/useTargetAudience';
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import SetupFreeListings from './setup-free-listings';
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingTimes from '.~/hooks/useShippingTimes';

/**
 * Function use to allow the user to navigate between form steps without the prompt.
 *
 * @param {Object} location Location object given by the `getHistory().block` callback argument.
 * @return {boolean} `true` if given location is not another step of our form.
 */
function isNotOurStep( location ) {
	const allowList = new Set( [
		'/' + getNewPath( { pageStep: undefined } ),
		'/' + getNewPath( { pageStep: 1 } ),
		'/' + getNewPath( { pageStep: 2 } ),
	] );
	// TODO: Explore if we can make thich check cleaner given `history`'s API.
	const destination = location.pathname + location.search;
	return ! allowList.has( destination );
}

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Due to lack of single API for updating shipping data alltogether,
 * we need to send deletes and adds separately.
 * Also, we need to send upserts separately for each price.
 *
 * @param {(individualCountrySetting: ShippingRate) => Promise} upsertAction
 * @param {(countries: Array<CountryCode>) => Promise} deleteAction
 * @param {Array<ShippingRate>} oldData
 * @param {Array<ShippingRate>} newData
 */
function saveShippingData( upsertAction, deleteAction, oldData, newData ) {
	const mapCountryCode = ( rate ) => rate.countryCode;
	const currentCountries = new Set( newData.map( mapCountryCode ) );

	// Send upserts.
	const actions = newData.map( upsertAction );

	const deletedCountries = oldData
		.map( mapCountryCode )
		.filter( ( country ) => ! currentCountries.has( country ) );

	if ( deletedCountries.length ) {
		// Send delete.
		actions.concat( deleteAction( deletedCountries ) );
	}
	// TODO: implement better batched upsert that accpets an array of ShippingRates (with different prices)
	return actions;
}

/**
 * Page Component to edit free campaigns.
 * Provides two steps:
 *  - Choose your audience
 *  - Configure your free listings
 * Given the user is editing an existing campaign, both steps should be available.
 * The displayed step is driven by `pageStep` URL parameter, to make it easier to permalink and navigate back and forth.
 */
export default function EditFreeCampaign() {
	const { data: savedTargetAudience } = useTargetAudience();
	const { settings: savedSettings } = useSettings();
	const {
		saveTargetAudience,
		saveSettings,
		upsertShippingRate, // We need to use this one, as we serve non-aggregated ShippingRates
		deleteShippingRates,
	} = useAppDispatch();

	const [ targetAudience, updateTargetAudience ] = useState(
		savedTargetAudience
	);
	const [ settings, updateSettings ] = useState( savedSettings );

	const { data: savedShippingRates } = useShippingRates();
	const [ shippingRates, updateShippingRates ] = useState(
		savedShippingRates
	);

	const { data: savedShippingTimes } = useShippingTimes();
	const [ shippingTimes, updateShippingTimes ] = useState(
		savedShippingTimes
	);

	// TODO: Consider making it less repetitive.
	useEffect( () => {
		if ( savedTargetAudience ) {
			updateTargetAudience( savedTargetAudience );
		}
		if ( savedSettings ) {
			updateSettings( savedSettings );
		}
		if ( savedShippingRates ) {
			updateShippingRates( savedShippingRates );
		}
		if ( savedShippingTimes ) {
			updateShippingTimes( savedShippingTimes );
		}
	}, [
		savedTargetAudience,
		savedSettings,
		savedShippingRates,
		savedShippingTimes,
	] );

	const [ fetchSettingsSync ] = useApiFetchCallback( {
		path: `/wc/gla/mc/settings/sync`,
		method: 'POST',
	} );

	// Check what've changed to show prompt, and send requests only to save changed things.
	const didAudienceChanged = ! isEqual( targetAudience, savedTargetAudience );
	const didSettingsChanged = ! isEqual( settings, savedSettings );
	const didAnythingChanged = didAudienceChanged || didSettingsChanged;

	// Confirm leaving the page, if there are any changes and the user is navigating away from our stepper.
	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		didAnythingChanged,
		isNotOurStep
	);

	const { pageStep = '1' } = getQuery();
	const dashboardURL = getNewPath(
		// Clear the step we were at, but perserve programId to be able to highlight the program.
		{ pageStep: undefined },
		'/google/dashboard'
	);

	const handleChooseAudienceContinue = () => {
		getHistory().push( getNewPath( { pageStep: '2' } ) );
	};

	const handleSetupFreeListingsContinue = async () => {
		// TODO: Disable the form so the user won't be able to input any changes, which could be disregarded.
		//       Put Submit button in pending state.
		await Promise.allSettled( [
			saveTargetAudience( targetAudience ),
			saveSettings( settings ),
			...saveShippingData(
				upsertShippingRate,
				deleteShippingRates,
				savedShippingRates,
				shippingRates
			),
			// TODO: save batched shipping times
		] );
		// Sync data once our changes are saved, even partially succesfully.
		await fetchSettingsSync();
		// TODO notify errors.
		// TODO: Enable the submit button.

		recordEvent( 'gla_free_campaign_edited' );
		getHistory().push( dashboardURL );
	};

	const handleStepClick = ( key ) => {
		getHistory().push( getNewPath( { pageStep: key } ) );
	};
	// TODO: Wse ChooseAudience and SetupFreeListings customized for this page.
	return (
		<FullContainer>
			<TopBar
				title={ __( 'Edit free listings', 'google-listings-and-ads' ) }
				backHref={ dashboardURL }
			/>
			<Stepper
				className="gla-setup-stepper"
				currentStep={ pageStep }
				steps={ [
					{
						key: '1',
						label: __(
							'Choose your audience',
							'google-listings-and-ads'
						),
						content: (
							<ChooseAudience
								stepHeader={ __(
									'STEP ONE',
									'google-listings-and-ads'
								) }
								initialData={ targetAudience }
								onChange={ ( change, newTargetAudience ) =>
									updateTargetAudience( newTargetAudience )
								}
								onContinue={ handleChooseAudienceContinue }
							/>
						),
						onClick: handleStepClick,
					},
					{
						key: '2',
						label: __(
							'Configure your product listings',
							'google-listings-and-ads'
						),
						content: (
							<SetupFreeListings
								stepHeader={ __(
									'STEP TWO',
									'google-listings-and-ads'
								) }
								settings={ settings }
								onSettingsChange={ updateSettings }
								shippingRates={ shippingRates }
								onShippingRatesChange={ updateShippingRates }
								shippingTimes={ shippingTimes }
								onShippingTimesChange={ updateShippingTimes }
								onContinue={ handleSetupFreeListingsContinue }
							/>
						),
						onClick: handleStepClick,
					},
				] }
			/>
		</FullContainer>
	);
}
