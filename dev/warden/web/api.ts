import { useCallback, useEffect, useState } from 'react';

/** A source's last reading (server.mjs): when, what it found, or why it could not */
export interface Reading<T> {
  at: number | null;
  data: T | null;
  error: string | null;
}

export interface Container {
  name: string;
  state: string;
  status: string;
  healthy?: boolean;
  cpu?: number;
  memory?: string;
}

export interface Space {
  total: number;
  free: number;
}

export interface Release {
  slot: string;
  commit: string | null;
}

/** One of MTAV's servers on AWS, as AWS has it and, while running, as it says of itself */
export interface CloudServer {
  id: string;
  name: string;
  environments: string[];
  state: 'pending' | 'running' | 'stopping' | 'stopped';
  type: string;
  address: string | null;
  since: string;
  error?: string;
  cores?: number;
  load?: number[];
  memory?: Space;
  disk?: Space;
  data?: Space;
  sessions?: number;
  certificate?: string | null;
  backup?: string | null;
  live?: Record<string, Release>;
  previous?: Record<string, Release>;
  containers?: Container[];
}

export interface Readings {
  host: Reading<{ load: number[]; cores: number; memory: Space; disk: Space }>;
  stacks: Reading<Record<string, Container[]>>;
  repository: Reading<{
    branch: string;
    commit: string;
    changed: number;
    remote: { name: string; commit: string; at: number; when: string; merged: boolean }[];
  }>;
  pipelines: Reading<
    {
      workflow: string;
      branch: string;
      commit: string;
      status: string;
      conclusion: string | null;
      at: string;
      url: string;
    }[]
  >;
  sites: Reading<
    {
      name: string;
      url: string;
      leads: 'before' | 'aws' | null;
      status: number | null;
      ms?: number;
      error?: string;
    }[]
  >;
  server: Reading<{
    notSetUp?: boolean;
    servers?: {
      host: string;
      error?: string;
      cores?: number;
      load?: number[];
      memory?: Space;
      disk?: Space;
    }[];
    releases?: Record<string, { slot: string; commit: string; live: boolean }[]>;
  }>;
  app: Reading<Record<string, number | null>>;
  claude: Reading<{ account: string; [key: string]: unknown }[]>;
  cloud: Reading<CloudServer[]>;
  money: Reading<{
    plan: { type: string; credits: number | null; until: string } | null;
    month: { spent: number; forecast: number | null; limit: number } | null;
  }>;
  backups: Reading<
    Record<string, { server: string; day: string; at: string; files: Record<string, number> }>
  >;
}

export interface Job {
  id: string;
  kind: string;
  title: string;
  state: 'running' | 'done' | 'failed';
  started: number;
  ended: number | null;
  lines: string[];
  total: number;
  summary: string | null;
}

/** Everything the warden knows, read again every few seconds while the page is open */
export function useWarden(every = 5000) {
  const [state, setState] = useState<{ readings: Readings; jobs: Job[] } | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const response = await fetch('/api', { cache: 'no-store' });
      setState(await response.json());
      setError(null);
    } catch (problem) {
      setError(String(problem));
    }
  }, []);

  useEffect(() => {
    const first = window.setTimeout(() => void load(), 0);
    const timer = window.setInterval(() => void load(), every);
    return () => {
      window.clearTimeout(first);
      window.clearInterval(timer);
    };
  }, [load, every]);

  return { ...state, error, reload: load };
}

/** Asks the warden to do something; resolves with the job that does it */
export async function act(action: string, params: Record<string, unknown> = {}): Promise<string> {
  const response = await fetch(`/api/actions/${action}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Warden': '1' },
    body: JSON.stringify(params),
  });
  const answer = await response.json();
  if (!response.ok) throw new Error(answer.error ?? 'refused');
  return answer.id;
}

/** A job, followed: its lines as they come, until it ends */
export function useJob(id: string | null) {
  const [job, setJob] = useState<Job | null>(null);

  useEffect(() => {
    if (!id) return undefined;
    let lines: string[] = [];
    let stopped = false;
    const follow = async () => {
      if (stopped) return;
      const response = await fetch(`/api/jobs/${id}?from=${lines.length}`, { cache: 'no-store' });
      if (!response.ok) return;
      const found: Job = await response.json();
      lines = [...lines, ...found.lines];
      setJob({ ...found, lines });
      if (found.state === 'running') window.setTimeout(() => void follow(), 1500);
    };
    const first = window.setTimeout(() => void follow(), 0);
    return () => {
      stopped = true;
      window.clearTimeout(first);
    };
  }, [id]);

  // Another job asked for: the last one's lines never show under its name
  return job?.id === id ? job : null;
}
