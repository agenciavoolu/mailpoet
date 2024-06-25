import { Steps } from '../../../../../../../editor/components/automation/types';
import { Subscriber } from '../../../../store';

export type ActivityModalState = 'loading' | 'loaded' | 'error' | 'hidden';

export type Run = {
  id: number;
  automation_id: number;
  status: string;
};

export type Log = {
  id: number;
  automation_run_id: number;
  step_id: string;
  step_type: string;
  step_key: string;
  status: string;
  started_at: string;
  updated_at: string;
  run_number: number;
  data: string;
  error: string | null;
};

export type RunData = {
  run: Run;
  logs: Log[];
  steps: Steps;
  subscriber: Subscriber;
};
