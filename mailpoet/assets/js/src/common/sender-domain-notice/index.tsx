import { escapeHTML } from '@wordpress/escape-html';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { InlineNotice } from 'common/notices/inline-notice';
import { SenderDomainNoticeBody } from './sender-domain-notice-body';
import { SenderActions } from './sender-domain-notice-actions';

type SenderDomainInlineNoticeProps = {
  authorizeAction: (e) => void;
  emailAddress: string;
  emailAddressDomain: string;
  subscribersCount: number;
  isFreeDomain: boolean;
  isPartiallyVerifiedDomain: boolean;
};

function SenderEmailRewriteInfo({ emailAddress = '' }): JSX.Element {
  const rewrittenEmail = `${emailAddress.replace(
    '@',
    '=',
  )}@replies.sendingservice.net`;

  return (
    <p>
      {createInterpolateElement(
        __('Will be sent as: <rewrittenFromEmail/>', 'mailpoet'),
        {
          rewrittenFromEmail: <strong>{escapeHTML(rewrittenEmail)}</strong>,
        },
      )}
    </p>
  );
}

function SenderDomainInlineNotice({
  emailAddress,
  emailAddressDomain,
  authorizeAction,
  subscribersCount,
  isFreeDomain,
  isPartiallyVerifiedDomain,
}: SenderDomainInlineNoticeProps) {
  let showRewrittenEmail = false;
  const showAuthorizeButton = !isFreeDomain;
  let isAlert = true;

  const LOWER_LIMIT = window.mailpoet_sender_restrictions?.lowerLimit || 500;

  const isSmallSender = subscribersCount <= LOWER_LIMIT;

  if (isSmallSender || isPartiallyVerifiedDomain) {
    isAlert = false;
  }

  if (isSmallSender && !isPartiallyVerifiedDomain) {
    showRewrittenEmail = true;
  }

  return (
    <div key="authorizeSenderDomain">
      <InlineNotice
        status={isAlert ? 'alert' : 'info'}
        topMessage={
          showRewrittenEmail ? (
            <SenderEmailRewriteInfo emailAddress={emailAddress} />
          ) : undefined
        }
        actions={
          <SenderActions
            showAuthorizeButton={showAuthorizeButton}
            authorizeAction={authorizeAction}
            isFreeDomain={isFreeDomain}
            isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
          />
        }
      >
        <SenderDomainNoticeBody
          emailAddressDomain={emailAddressDomain}
          isFreeDomain={isFreeDomain}
          isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
          isSmallSender={isSmallSender}
        />
      </InlineNotice>
    </div>
  );
}

export { SenderDomainInlineNotice };
