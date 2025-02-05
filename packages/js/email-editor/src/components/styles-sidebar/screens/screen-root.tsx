import {
	__experimentalVStack as VStack, // eslint-disable-line
	Card,
	CardBody,
	CardMedia,
	__experimentalItemGroup as ItemGroup, // eslint-disable-line
	__experimentalItem as Item, // eslint-disable-line
	__experimentalHStack as HStack, // eslint-disable-line
	__experimentalNavigatorButton as NavigatorButton, // eslint-disable-line
	Icon,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { typography, color, layout } from '@wordpress/icons';
import { Preview } from './preview';

export function ScreenRoot(): JSX.Element {
	return (
		<Card
			size="small"
			className="edit-site-global-styles-screen-root"
			variant="primary"
		>
			<CardBody>
				<VStack spacing={ 4 }>
					<Card>
						<CardMedia>
							<Preview />
						</CardMedia>
					</Card>
					<ItemGroup>
						<NavigatorButton path="/typography">
							<Item>
								<HStack justify="flex-start">
									<Icon icon={ typography } size={ 24 } />
									<FlexItem>
										{ __( 'Typography', 'mailpoet' ) }
									</FlexItem>
								</HStack>
							</Item>
						</NavigatorButton>
						<NavigatorButton path="/colors">
							<Item>
								<HStack justify="flex-start">
									<Icon icon={ color } size={ 24 } />
									<FlexItem>
										{ __( 'Colors', 'mailpoet' ) }
									</FlexItem>
								</HStack>
							</Item>
						</NavigatorButton>
						<NavigatorButton path="/layout">
							<Item>
								<HStack justify="flex-start">
									<Icon icon={ layout } size={ 24 } />
									<FlexItem>
										{ __( 'Layout', 'mailpoet' ) }
									</FlexItem>
								</HStack>
							</Item>
						</NavigatorButton>
					</ItemGroup>
				</VStack>
			</CardBody>
		</Card>
	);
}
