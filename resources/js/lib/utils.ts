import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Capitalize the first letter of a string
 * @param str The string to capitalize
 * @returns The string with the first letter capitalized
 */
export function capitalize(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Set a cookie with 365-day expiration.
 */
export function setCookie(name: string, value: string, maxAge = 365 * 24 * 60 * 60) {
  document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
}

/**
 * Read a cookie value from document.
 */
export function getCookie(name: string): string | null {
  const match = document.cookie.match(`(^|;)\\s*${name}\\s*=\\s*([^;]+)`);
  return match ? match[2] : null;
}
