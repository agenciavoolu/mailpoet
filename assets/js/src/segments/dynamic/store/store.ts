/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { registerStore } from '@wordpress/data';
import * as selectors from './selectors';
import { createReducer } from './reducer';

import {
  StateType,
  SegmentFormDataWindow,
} from '../types';

declare let window: SegmentFormDataWindow;

const STORE = 'mailpoet-dynamic-segments-form';
export const createStore = (): void => {
  const defaultState: StateType = {
    products: window.mailpoet_products,
    subscriptionProducts: window.mailpoet_subscription_products,
    productCategories: window.mailpoet_product_categories,
    newslettersList: window.mailpoet_newsletters_list,
    wordpressRoles: window.wordpress_editable_roles_list,
    canUseWooSubscriptions: window.mailpoet_can_use_woocommerce_subscriptions,
    wooCurrencySymbol: window.mailpoet_woocommerce_currency_symbol,
    wooCountries: window.mailpoet_woocommerce_countries,
    customFieldsList: window.mailpoet_custom_fields,
  };

  const config = {
    selectors,
    reducer: createReducer(defaultState),
    resolvers: {},
  };

  registerStore(STORE, config);
};
