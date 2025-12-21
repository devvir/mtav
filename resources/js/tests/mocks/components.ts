// Copilot - Pending review

/**
 * Mock UI component stubs to reduce test boilerplate
 * These are third-party components that we don't want to test
 */
export function mockUIComponents() {
  return {
    // Headless UI / Dialog components
    Dialog: { template: '<div><slot /></div>' },
    DialogContent: { template: '<div><slot /></div>' },
    DialogHeader: { template: '<div><slot /></div>' },
    DialogFooter: { template: '<div><slot /></div>' },
    DialogTitle: { template: '<div><slot /></div>' },
    DialogDescription: { template: '<div><slot /></div>' },
    DialogClose: { template: '<button><slot /></button>' },

    // Reka UI components
    Button: {
      template: '<button :class="$attrs.class" :disabled="disabled"><slot /></button>',
      props: ['disabled', 'variant', 'size'],
    },
    Input: {
      template: '<input v-model="modelValue" :class="$attrs.class" :type="type" :placeholder="placeholder" />',
      props: ['modelValue', 'type', 'placeholder'],
      emits: ['update:modelValue'],
    },
    Label: { template: '<label><slot /></label>' },

    // Lucide icons - stub all as simple divs
    CheckCircle2: { template: '<div class="icon-check" />' },
    AlertTriangle: { template: '<div class="icon-alert" />' },
    AlertCircle: { template: '<div class="icon-error" />' },
    Info: { template: '<div class="icon-info" />' },
    X: { template: '<button class="icon-close"><span>×</span></button>' },
    ChevronDown: { template: '<div class="icon-chevron" />' },
    ChevronUp: { template: '<div class="icon-chevron" />' },
    ChevronLeft: { template: '<div class="icon-chevron" />' },
    ChevronRight: { template: '<div class="icon-chevron" />' },
    Search: { template: '<div class="icon-search" />' },
    Plus: { template: '<div class="icon-plus" />' },
    Trash2: { template: '<div class="icon-trash" />' },
    Edit: { template: '<div class="icon-edit" />' },
    Eye: { template: '<div class="icon-eye" />' },
    EyeOff: { template: '<div class="icon-eye-off" />' },
    Copy: { template: '<div class="icon-copy" />' },
    ExternalLink: { template: '<div class="icon-external-link" />' },
    Menu: { template: '<div class="icon-menu" />' },
    Settings: { template: '<div class="icon-settings" />' },
    Clock: { template: '<div class="icon-clock" />' },
    Calendar: { template: '<div class="icon-calendar" />' },
    MapPin: { template: '<div class="icon-map-pin" />' },
    User: { template: '<div class="icon-user" />' },
    Users: { template: '<div class="icon-users" />' },
    Mail: { template: '<div class="icon-mail" />' },
    Download: { template: '<div class="icon-download" />' },
    Upload: { template: '<div class="icon-upload" />' },
    Zap: { template: '<div class="icon-zap" />' },
    Lock: { template: '<div class="icon-lock" />' },
    Unlock: { template: '<div class="icon-unlock" />' },
    Shield: { template: '<div class="icon-shield" />' },
    AlertOctagon: { template: '<div class="icon-alert-octagon" />' },
    HelpCircle: { template: '<div class="icon-help" />' },
    MoreHorizontal: { template: '<div class="icon-more-h" />' },
    MoreVertical: { template: '<div class="icon-more-v" />' },
  };
}
