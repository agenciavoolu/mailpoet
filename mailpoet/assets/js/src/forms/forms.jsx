import { createRoot } from 'react-dom/client';
import { HashRouter, Route } from 'react-router-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices.jsx';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { Notices } from 'notices/notices.jsx';
import { registerTranslations, withBoundary } from 'common';
import { FormList } from './list.jsx';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
        <Notices />
        <MssAccessNotices />
        <Route path="*" render={withBoundary(FormList)} />
      </HashRouter>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('forms_container');

if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
