/** Figures as people read them */

export const bytes = (value: number | undefined | null) => {
  if (value == null || Number.isNaN(value)) return '—';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let size = value;
  let unit = 0;
  while (size >= 1024 && unit < units.length - 1) {
    size /= 1024;
    unit++;
  }
  return `${size >= 10 || unit === 0 ? Math.round(size) : size.toFixed(1)} ${units[unit]}`;
};

export const ago = (at: number | string | null | undefined) => {
  if (!at) return '—';
  const seconds = Math.max(0, Math.round((Date.now() - new Date(at).getTime()) / 1000));
  if (seconds < 60) return `${seconds}s ago`;
  if (seconds < 3600) return `${Math.round(seconds / 60)} min ago`;
  if (seconds < 86400) return `${Math.round(seconds / 3600)} h ago`;
  return `${Math.round(seconds / 86400)} d ago`;
};

export const short = (commit: string | null | undefined) => (commit ? commit.slice(0, 8) : '—');

export const money = (value: number | null | undefined) =>
  value == null ? '—' : `$${value.toFixed(2)}`;
