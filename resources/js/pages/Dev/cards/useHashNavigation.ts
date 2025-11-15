/**
 * Composable for managing URL hash state and navigation
 */
export const useHashNavigation = () => {
  /**
   * Get the current hash from URL
   */
  const getCurrentHash = (): string => {
    return window.location.hash.slice(1); // Remove the #
  };

  /**
   * Set the hash in the URL
   */
  const setHash = (hash: string) => {
    window.history.replaceState(null, '', `#${hash}`);
  };

  /**
   * Clear the hash from the URL
   */
  const clearHash = () => {
    window.history.replaceState(null, '', window.location.pathname + window.location.search);
  };

  /**
   * Normalize hash for legacy support (gallery -> media)
   */
  const normalizeHash = (hash: string): string => {
    return hash === 'gallery' ? 'media' : hash;
  };

  /**
   * Handle hash changes and update URL if normalized
   */
  const processHash = (hash: string): string => {
    const normalized = normalizeHash(hash);

    // Update URL if we normalized the hash
    if (hash === 'gallery' && normalized === 'media') {
      setHash(normalized);
    }

    return normalized;
  };

  /**
   * Set up hash change listener
   */
  const onHashChange = (callback: (hash: string) => void) => {
    const handleHashChange = () => {
      const hash = getCurrentHash();
      const processed = processHash(hash);
      callback(processed);
    };

    window.addEventListener('hashchange', handleHashChange);

    // Cleanup function
    return () => {
      window.removeEventListener('hashchange', handleHashChange);
    };
  };

  return {
    getCurrentHash,
    setHash,
    clearHash,
    normalizeHash,
    processHash,
    onHashChange,
  };
};