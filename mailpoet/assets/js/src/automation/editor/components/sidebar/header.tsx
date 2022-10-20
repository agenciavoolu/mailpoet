import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { stepSidebarKey, storeName, workflowSidebarKey } from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/sidebar/settings-header/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/sidebar/settings-header/index.js

type Props = {
  sidebarKey: string;
};

export function Header({ sidebarKey }: Props): JSX.Element {
  const { openSidebar } = useDispatch(storeName);
  const openWorkflowSettings = () => openSidebar(workflowSidebarKey);
  const openStepSettings = () => openSidebar(stepSidebarKey);

  const [workflowAriaLabel, workflowActiveClass] =
    sidebarKey === workflowSidebarKey
      ? [__('Workflow (selected)', 'mailpoet'), 'is-active']
      : ['Workflow', ''];

  const [stepAriaLabel, stepActiveClass] =
    sidebarKey === stepSidebarKey
      ? [__('Step (selected)', 'mailpoet'), 'is-active']
      : ['Step', ''];

  return (
    <ul>
      <li>
        <Button
          onClick={openWorkflowSettings}
          className={`edit-site-sidebar__panel-tab ${workflowActiveClass}`}
          aria-label={workflowAriaLabel}
          data-label={__('Workflow', 'mailpoet')}
        >
          {__('Workflow', 'mailpoet')}
        </Button>
      </li>
      <li>
        <Button
          onClick={openStepSettings}
          className={`edit-site-sidebar__panel-tab ${stepActiveClass}`}
          aria-label={stepAriaLabel}
          data-label={__('Step', 'mailpoet')}
        >
          {__('Step', 'mailpoet')}
        </Button>
      </li>
    </ul>
  );
}
