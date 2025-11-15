/**
 * Composable for managing scroll behavior
 */
export const useScrollBehavior = () => {
  /**
   * Scroll to a specific element with smart offset calculation
   */
  const scrollToSection = (elementId: string) => {
    const element = document.getElementById(elementId);
    if (!element) return;

    // Get the element's position and calculate offset to keep header visible
    const elementRect = element.getBoundingClientRect();

    // Try to detect header height dynamically, or use a reasonable default
    const header = document.querySelector('header') || document.querySelector('[role="banner"]');
    const headerHeight = header ? header.getBoundingClientRect().height : 0;
    const additionalOffset = 20; // Extra padding for better visual spacing
    const offset = headerHeight + additionalOffset;

    const elementTop = elementRect.top + window.pageYOffset - offset;

    window.scrollTo({
      top: Math.max(0, elementTop), // Ensure we don't scroll above the page
      behavior: 'smooth'
    });
  };

  /**
   * Scroll to element after DOM update
   */
  const scrollToSectionAfterUpdate = (elementId: string) => {
    nextTick(() => {
      scrollToSection(elementId);
    });
  };

  return {
    scrollToSection,
    scrollToSectionAfterUpdate,
  };
};