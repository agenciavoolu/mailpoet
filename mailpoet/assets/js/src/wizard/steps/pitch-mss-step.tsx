import {
  useRouteMatch,
  Routes,
  Route,
  useParams,
  useNavigate,
  useLocation,
} from 'react-router-dom';
import { useEffect } from 'react';
import { MSSStepFirstPart } from './pitch-mss-step/first-part';
import { MSSStepSecondPart } from './pitch-mss-step/second-part';
import { MSSStepThirdPart } from './pitch-mss-step/third-part';
import { navigateToPath } from '../navigate-to-path';

function WelcomeWizardPitchMSSStep(): JSX.Element {
  const { path } = useRouteMatch();
  const { step } = useParams<{ step: string }>();
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    if (!location.pathname.includes('part')) {
      navigateToPath(navigate, `/steps/${step}/part/1`, true);
    }
  }, [step, path, navigate, location]);

  return (
    <Routes>
      <Route path={`${path}/part/1`} element={<MSSStepFirstPart />} />
      <Route path={`${path}/part/2`} element={<MSSStepSecondPart />} />
      <Route path={`${path}/part/3`} element={<MSSStepThirdPart />} />
    </Routes>
  );
}

export { WelcomeWizardPitchMSSStep };
