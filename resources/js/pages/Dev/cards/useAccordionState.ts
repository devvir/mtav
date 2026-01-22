import type { EntityDisplay } from './useEntityConfig';

/**
 * Composable for managing accordion expand/collapse state
 */
export const useAccordionState = (entities: Ref<EntityDisplay[]>) => {
  // Track expanded state for each section
  const expandedSections = ref<Record<string, boolean>>({});

  // Initialize expanded state when entities change
  watch(
    entities,
    (newEntities: EntityDisplay[]) => {
      const newState: Record<string, boolean> = {};
      newEntities.forEach((entity: EntityDisplay) => {
        newState[entity.key] = expandedSections.value[entity.key] ?? false;
      });
      expandedSections.value = newState;
    },
    { immediate: true },
  );

  /**
   * Toggle a specific section
   */
  const toggleSection = (entityKey: string) => {
    expandedSections.value[entityKey] = !expandedSections.value[entityKey];
  };

  /**
   * Expand all sections
   */
  const expandAll = () => {
    entities.value.forEach((entity: EntityDisplay) => {
      expandedSections.value[entity.key] = true;
    });
  };

  /**
   * Collapse all sections
   */
  const collapseAll = () => {
    entities.value.forEach((entity: EntityDisplay) => {
      expandedSections.value[entity.key] = false;
    });
  };

  /**
   * Check if a section is expanded
   */
  const isExpanded = (entityKey: string): boolean => {
    return expandedSections.value[entityKey] ?? false;
  };

  return {
    expandedSections: readonly(expandedSections),
    toggleSection,
    expandAll,
    collapseAll,
    isExpanded,
  };
};
