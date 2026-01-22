declare module '@inertiaui/modal-vue' {
  import type { Component, ComputedRef } from 'vue';

  // Core configuration interfaces
  export interface ModalTypeConfig {
    closeButton?: boolean;
    closeExplicitly?: boolean;
    maxWidth?: ModalWidth;
    paddingClasses?: string | boolean;
    panelClasses?: string | boolean;
    position?: string;
  }

  export interface Config {
    type?: 'modal' | 'slideover';
    navigate?: boolean;
    modal?: ModalTypeConfig;
    slideover?: ModalTypeConfig;
  }

  // Modal width types
  export type ModalWidth =
    | 'xs'
    | 'sm'
    | 'md'
    | 'lg'
    | 'xl'
    | '2xl'
    | '3xl'
    | '4xl'
    | '5xl'
    | '6xl'
    | '7xl';

  // Event listener types
  export type EventListener = (...args: any[]) => void;
  export type ComponentResolver = (name: string) => Component;

  // Modal instance interface
  export interface ModalInstance {
    id: string;
    index: ComputedRef<number>;
    isOpen: boolean;
    shouldRender: boolean;
    onTopOfStack: ComputedRef<boolean>;
    config: Record<string, any>;
    modalContext: any;

    // Methods
    close(): void;
    reload(props?: any, config?: any): Promise<void>;
    emit(event: string, ...args: any[]): void;
    on(event: string, callback: EventListener): void;
    afterLeave(): void;
    setOpen(open: boolean): void;
    getChildModal(): ModalInstance | null;
    getParentModal(): ModalInstance | null;
  }

  // Visit modal options
  export interface VisitModalOptions {
    method?: string;
    data?: Record<string, any>;
    headers?: Record<string, string>;
    config?: Record<string, any>;
    onClose?: () => void;
    onAfterLeave?: () => void;
    queryStringArrayFormat?: string;
    navigate?: boolean;
    listeners?: Record<string, EventListener>;
  }

  // Component props interfaces
  export interface ModalLinkProps {
    href: string;
    method?: string;
    data?: Record<string, any>;
    as?: string;
    headers?: Record<string, string>;
    queryStringArrayFormat?: string;
    navigate?: boolean;
    // Modal configuration props
    closeButton?: boolean;
    closeExplicitly?: boolean;
    maxWidth?: ModalWidth;
    paddingClasses?: string | boolean;
    panelClasses?: string | boolean;
    position?: string;
    slideover?: boolean;
    [key: string]: any;
  }

  export interface ModalProps {
    show?: boolean;
    closeButton?: boolean;
    closeExplicitly?: boolean;
    maxWidth?: ModalWidth;
    paddingClasses?: string | boolean;
    panelClasses?: string | boolean;
    position?: string;
    [key: string]: any;
  }

  // Emits interfaces
  export interface ModalLinkEmits {
    'after-leave': () => void;
    blur: () => void;
    close: () => void;
    error: (error: any) => void;
    focus: () => void;
    start: () => void;
    success: (response: any) => void;
  }

  export interface ModalEmits {
    'after-leave': () => void;
    blur: () => void;
    close: () => void;
    focus: () => void;
    success: (response: any) => void;
  }

  // Core functions
  export function useModal(): ModalInstance | null;

  export function getConfig(): Config;
  export function getConfig<T>(key: string): T;
  export function getConfig<T>(key: string, defaultValue: T): T;

  export function putConfig(config: Partial<Config>): void;
  export function resetConfig(): void;

  export function renderApp(App: Component, props: any): () => any;

  export function visitModal(url: string, options?: VisitModalOptions): Promise<ModalInstance>;

  export function initFromPageProps(pageProps: { resolveComponent?: ComponentResolver }): void;

  // Vue components
  export const ModalLink: Component<ModalLinkProps>;
  export const Modal: Component<ModalProps>;
  export const ModalRoot: Component;
  export const HeadlessModal: Component;
  export const Deferred: Component;
  export const WhenVisible: Component<{ threshold?: number }>;

  // Modal stack props (from modalStack.js)
  export const modalPropNames: string[];
}
