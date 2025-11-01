/**
 * Flash Messages System
 *
 * Stack-based flash message system for displaying temporary notifications.
 * Each message is an independent instance with its own auto-dismiss timeout.
 *
 * Components:
 * - FlashMessages: Base container component, can be used anywhere
 * - FlashMessagesOverlay: Header-specific overlay wrapper with backdrop
 * - FlashMessage: Individual message component (internal use)
 *
 * Usage:
 *
 * In layouts or pages:
 * ```vue
 * import FlashMessages from '@/components/flash/FlashMessages.vue';
 *
 * // Default: auto-dismiss after 10 seconds
 * <FlashMessages />
 *
 * // Prevent auto-dismiss (e.g., for login/auth pages)
 * <FlashMessages no-dismiss />
 * ```
 *
 * In headers (with overlay):
 * ```vue
 * import FlashMessagesOverlay from '@/components/flash/FlashMessagesOverlay.vue';
 *
 * <FlashMessagesOverlay />
 * ```
 *
 * Programmatic usage (trigger messages from anywhere):
 * ```ts
 * import { useFlashMessages } from '@/components/flash/useFlashMessages';
 *
 * const { flash } = useFlashMessages();
 *
 * // Display flash messages (type defaults to 'success', timeout defaults to 10s)
 * flash('Operation completed!');
 * flash('Something went wrong', 'error');
 * flash('Please note...', 'info');
 *
 * // Custom timeout (in milliseconds) - use 0 for no auto-dismiss
 * flash('This will stay forever', 'warning', 0);
 * flash('Quick notification', 'info', 3000); // 3 seconds
 * ```
 *
 * Features:
 * - Auto-dismiss after 10 seconds by default (configurable per message)
 * - Error and warning messages from Inertia don't auto-dismiss (require manual close)
 * - Manual dismiss via X button
 * - Multiple messages of same type simultaneously
 * - Each message manages its own lifecycle
 * - Automatically reads from Inertia page.props.flash
 */

export { default as FlashMessages } from './FlashMessages.vue';
export { default as FlashMessagesOverlay } from './FlashMessagesOverlay.vue';
export { useFlashMessages } from './useFlashMessages';
export type { MessageType, FlashMessage } from './useFlashMessages';
