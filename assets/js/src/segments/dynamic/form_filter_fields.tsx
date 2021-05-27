import React from 'react';

import {
  FilterValue,
  SegmentTypes,
} from './types';

import { EmailFields } from './dynamic_segments_filters/email';
import { SubscriberFields } from './dynamic_segments_filters/subscriber';
import { WooCommerceFields } from './dynamic_segments_filters/woocommerce';
import { WooCommerceSubscriptionFields } from './dynamic_segments_filters/woocommerce_subscription';

export interface FilterFieldsProps {
  segmentType: FilterValue;
}

const filterFieldsMap = {
  [SegmentTypes.Email]: EmailFields,
  [SegmentTypes.WooCommerce]: WooCommerceFields,
  [SegmentTypes.WordPressRole]: SubscriberFields,
  [SegmentTypes.WooCommerceSubscription]: WooCommerceSubscriptionFields,
};

export const FormFilterFields: React.FunctionComponent<FilterFieldsProps> = ({
  segmentType,
}) => {
  if (filterFieldsMap[segmentType.group] === undefined) return null;
  const Component = filterFieldsMap[segmentType.group];

  return (
    <Component />
  );
};
