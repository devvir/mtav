import { useEntityConfig } from './useEntityConfig';
import { useAccordionState } from './useAccordionState';
import { useHashNavigation } from './useHashNavigation';
import { useScrollBehavior } from './useScrollBehavior';

/**
 * Main orchestrator for the Entity Cards page behavior
 */
export const useEntityCardsController = (entityData: Record<string, ApiResource[]>) => {
  const { getAvailableEntities } = useEntityConfig();
  const { setHash, clearHash, processHash, getCurrentHash, onHashChange } = useHashNavigation();
  const { scrollToSectionAfterUpdate } = useScrollBehavior();

  // Get available entities based on data
  const entities = computed(() => getAvailableEntities(entityData));

  // Set up accordion state management
  const { expandedSections, toggleSection, expandAll, collapseAll, isExpanded } = useAccordionState(entities);

  /**
   * Handle section toggle with URL and scroll management
   */
  const handleSectionToggle = (entityKey: string) => {
    const wasExpanded = isExpanded(entityKey);
    toggleSection(entityKey);

    if (!wasExpanded) {
      // Section was just expanded
      setHash(entityKey);
      scrollToSectionAfterUpdate(entityKey);
    } else {
      // Section was just collapsed
      if (getCurrentHash() === entityKey) {
        clearHash();
      }
    }
  };

  /**
   * Handle expand all with URL management
   */
  const handleExpandAll = () => {
    expandAll();
    clearHash(); // Clear hash when expanding all
  };

  /**
   * Handle collapse all with URL management
   */
  const handleCollapseAll = () => {
    collapseAll();
    clearHash(); // Clear hash when collapsing all
  };

  /**
   * Handle hash-based navigation (direct URLs, browser back/forward)
   */
  const handleHashNavigation = (hash: string, validKeys: string[]) => {
    if (hash && validKeys.includes(hash)) {
      toggleSection(hash);
      if (isExpanded(hash)) {
        scrollToSectionAfterUpdate(hash);
      }
    }
  };

  /**
   * Initialize hash-based navigation
   */
  const initializeNavigation = () => {
    // Handle initial hash on mount
    const initialHash = processHash(getCurrentHash());
    const validKeys = entities.value.map((e: any) => e.key);
    handleHashNavigation(initialHash, validKeys);

    // Set up hash change listener
    const cleanup = onHashChange((hash) => {
      const validKeys = entities.value.map((e: any) => e.key);
      handleHashNavigation(hash, validKeys);
    });

    // Return cleanup function
    return cleanup;
  };

  return {
    entities,
    expandedSections,
    isExpanded,
    handleSectionToggle,
    handleExpandAll,
    handleCollapseAll,
    initializeNavigation,
  };
};