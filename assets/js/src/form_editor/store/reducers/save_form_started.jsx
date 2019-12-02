import MailPoet from 'mailpoet';

export default (state) => {
  // remove all form saving related notices
  const notices = state.notices.filter((notice) => !['save-form', 'missing-lists', 'missing-block'].includes(notice.id));
  const hasMissingLists = state.formErrors.includes('missing-lists');
  const sidebarOpenedPanels = [...state.sidebar.openedPanels];
  if (hasMissingLists) {
    notices.push({
      id: 'missing-lists',
      content: MailPoet.I18n.t('settingsPleaseSelectList'),
      isDismissible: true,
      status: 'error',
    });
    if (!sidebarOpenedPanels.includes('basic-settings')) {
      sidebarOpenedPanels.push('basic-settings');
    }
  }

  const hasMissingEmail = state.formErrors.includes('missing-email-input');
  const hasMissingSubmit = state.formErrors.includes('missing-submit');
  if (hasMissingEmail || hasMissingSubmit) {
    notices.push({
      id: 'missing-block',
      content: MailPoet.I18n.t('missingObligatoryBlock'),
      isDismissible: true,
      status: 'error',
    });
  }

  return {
    ...state,
    isFormSaving: !hasMissingLists,
    sidebar: {
      ...state.sidebar,
      activeTab: hasMissingLists ? 'form' : state.sidebar.activeTab,
      openedPanels: sidebarOpenedPanels,
    },
    notices,
  };
};
