/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import RateFormModal from './rate-form-modal.js';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("../typedefs.js").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Form to add a new rate for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Array of country codes, to be used as options in SupportedCountrySelect.
 * @param {ShippingRateGroup} props.initialValues Initial values for the form.
 * @param {(values: ShippingRateGroup) => void} props.onSubmit Called with submitted value.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 */
const AddRateFormModal = ( {
	countryOptions,
	initialValues,
	onSubmit,
	onRequestClose = noop,
} ) => {
	return (
		<RateFormModal
			countryOptions={ countryOptions }
			initialValues={ initialValues }
			renderButtons={ ( formProps ) => {
				const { isValidForm, handleSubmit } = formProps;

				const handleAddClick = () => {
					onRequestClose();
					handleSubmit();
				};

				return [
					<AppButton
						key="submit"
						isPrimary
						disabled={ ! isValidForm }
						onClick={ handleAddClick }
					>
						{ __( 'Add shipping rate', 'google-listings-and-ads' ) }
					</AppButton>,
				];
			} }
			onSubmit={ onSubmit }
			onRequestClose={ onRequestClose }
		/>
	);
};

export default AddRateFormModal;
