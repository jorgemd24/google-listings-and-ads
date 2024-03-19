/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	FlexItem,
	Card,
	CardHeader,
	CardBody,
	CardFooter,
} from '@wordpress/components';

import {
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useMCProductStatistics from '.~/hooks/useMCProductStatistics';
import ProductStatusHelpPopover from './product-status-help-popover';
import SyncStatus from '.~/product-feed/product-statistics/status-box/sync-status';
import SyncProductStatistics from '.~/product-feed/product-statistics/status-box/sync-product-statistics';
import FeedStatus from '.~/product-feed/product-statistics/status-box/feed-status';
import AccountStatus from '.~/product-feed/product-statistics/status-box/account-status';
import Text from '.~/components/app-text';
import './index.scss';

const ProductStatistics = () => {
	const { hasFinishedResolution, data, refreshStats } =
		useMCProductStatistics();

	if ( hasFinishedResolution && ! data ) {
		return __(
			'An error occurred while retrieving your product feed. Please try again later.',
			'google-listings-and-ads'
		);
	}

	const isLoading = ! hasFinishedResolution || data?.loading;

	return (
		<Card className="gla-product-statistics">
			<CardHeader justify="normal">
				<FlexItem>
					<Text variant="title-small" as="h2">
						{ __( 'Overview', 'google-listings-and-ads' ) }
					</Text>
				</FlexItem>
				<ProductStatusHelpPopover />
			</CardHeader>
			<CardBody
				className="gla-product-statistics__summaries"
				size={ null }
			>
				{ isLoading && <SummaryListPlaceholder numberOfItems={ 5 } /> }
				{ hasFinishedResolution && ! data?.loading && (
					<SummaryList>
						{ () => [
							<SummaryNumber
								key="active"
								label={ __(
									'Active',
									'google-listings-and-ads'
								) }
								value={ data.statistics.active }
							/>,
							<SummaryNumber
								key="expiring"
								label={ __(
									'Expiring',
									'google-listings-and-ads'
								) }
								value={ data.statistics.expiring }
							/>,
							<SummaryNumber
								key="pending"
								label={ __(
									'Pending',
									'google-listings-and-ads'
								) }
								value={ data.statistics.pending }
							/>,
							<SummaryNumber
								key="disapproved"
								label={ __(
									'Disapproved',
									'google-listings-and-ads'
								) }
								value={ data.statistics.disapproved }
							/>,
							<SummaryNumber
								key="not_synced"
								label={ __(
									'Not Synced',
									'google-listings-and-ads'
								) }
								value={ data.statistics.not_synced }
							/>,
						] }
					</SummaryList>
				) }
			</CardBody>
			<CardFooter gap={ 0 }>
				<FeedStatus />
				<SyncStatus />
				<AccountStatus />
				{ hasFinishedResolution && data?.error && (
					<SyncProductStatistics
						refreshStats={ refreshStats }
						error={ 'My error' }
					/>
				) }
			</CardFooter>
		</Card>
	);
};

export default ProductStatistics;
