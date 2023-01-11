/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import MinimumOrderFormModal from './minimum-order-form-modal';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("../typedefs.js").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Display the add minimum order modal that is wrapped in a Form.
 *
 * When users submit the form, `props.onRequestClose` will be called first, and then followed by `props.onSubmit`.
 * If we were to call `props.onSubmit` first, it may cause some state change in the parent component and causing this component not to be rendered,
 * and when `props.onRequestClose` is called later, there would be a runtime React error because the component is no longer there.
 *
 * @param {Object} props Props.
 * @param {Array<CountryCode>} props.countryOptions Array of country codes options, to be used as options in SupportedCountrySelect.
 * @param {MinimumOrderGroup} props.initialValues Initial values for the form.
 * @param {(values: MinimumOrderGroup) => void} props.onSubmit Callback when the form is submitted, with the form value.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 */
const AddMinimumOrderFormModal = ( {
	countryOptions,
	initialValues,
	onSubmit,
	onRequestClose,
} ) => {
	return (
		<MinimumOrderFormModal
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
						key="save"
						isPrimary
						disabled={ ! isValidForm }
						onClick={ handleAddClick }
					>
						{ __( 'Add minimum order', 'google-listings-and-ads' ) }
					</AppButton>,
				];
			} }
			onSubmit={ onSubmit }
			onRequestClose={ onRequestClose }
		/>
	);
};

export default AddMinimumOrderFormModal;
