import { useState } from 'react';
import { act, type Job } from '../../api';

/** Starts an action, its output then followed */
export type Start = (action: string, params?: Record<string, unknown>) => Promise<void>;

/** Whether an action of this kind is running */
export type Running = (kind: string) => boolean;

/**
 * What the page does: starting an action (and showing its output), whether one of a kind is
 * running, which job's output shows (the latest, unless another was picked), and the problem
 * when an action could not start
 */
export function useActions(jobs: Job[]) {
  const [shown, setShown] = useState<string | null>(null);
  const [problem, setProblem] = useState<string | null>(null);

  const running: Running = (kind) =>
    jobs.some((job) => job.kind === kind && job.state === 'running');
  const start: Start = async (action, params = {}) => {
    setProblem(null);
    try {
      setShown(await act(action, params));
    } catch (error) {
      setProblem(String((error as Error).message));
    }
  };

  return {
    start,
    running,
    shown: shown ?? jobs[0]?.id ?? null,
    show: setShown,
    problem,
    dismiss: () => setProblem(null),
  };
}
