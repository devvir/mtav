<script setup lang="ts">
import { ref } from 'vue';
import Head from '@/components/Head.vue';
import { Moon, Sun, Check, X, Info, AlertTriangle } from 'lucide-vue-next';
import FormInput from '@/components/forms/FormInput.vue';
import FormSelect from '@/components/forms/FormSelect.vue';
import FormSubmit from '@/components/forms/FormSubmit.vue';

const theme = ref<'light' | 'dark'>('dark');
const colorTheme = ref<string>('default');

// Form demo data
const formData = ref({
  name: '',
  email: 'user@example.com',
  country: ['US'],
  message: '',
  agreeTerms: false,
});

const formErrors = ref({
  email: 'Please enter a valid email address',
});

const countryOptions = {
  US: 'United States',
  UK: 'United Kingdom',
  CA: 'Canada',
  AU: 'Australia',
  FR: 'France',
  DE: 'Germany',
  ES: 'Spain',
  IT: 'Italy',
};

const themes = [
  { value: 'default', name: 'Default', desc: 'Cool blues & neutrals' },
  { value: 'ocean', name: 'Ocean', desc: 'Vibrant teals & blues' },
  { value: 'forest', name: 'Forest', desc: 'Earthy greens' },
  { value: 'sunset', name: 'Sunset', desc: 'Warm oranges & purples' },
  { value: 'mono', name: 'Monochrome', desc: 'High contrast B&W' },
];

const toggleTheme = () => {
  theme.value = theme.value === 'light' ? 'dark' : 'light';
  document.documentElement.classList.toggle('dark');
};

const setColorTheme = (newTheme: string) => {
  // Remove all theme classes
  document.documentElement.classList.remove('theme-ocean', 'theme-forest', 'theme-sunset', 'theme-mono');

  // Add new theme class if not default
  if (newTheme !== 'default') {
    document.documentElement.classList.add(`theme-${newTheme}`);
  }

  colorTheme.value = newTheme;
};const surfaceColors = [
  { name: 'Surface', var: 'surface', text: 'surface-foreground', desc: 'Base surface' },
  { name: 'Surface Elevated', var: 'surface-elevated', text: 'surface-elevated-foreground', desc: 'Cards, modals' },
  { name: 'Surface Sunken', var: 'surface-sunken', text: 'surface-sunken-foreground', desc: 'Recessed areas' },
  { name: 'Surface Interactive', var: 'surface-interactive', text: 'surface-foreground', desc: 'Hover state' },
];

const textColors = [
  { name: 'Text', var: 'text', desc: 'Primary body text', contrast: '7:1+' },
  { name: 'Text Muted', var: 'text-muted', desc: 'Secondary text', contrast: '4.5:1+' },
  { name: 'Text Subtle', var: 'text-subtle', desc: 'Tertiary text', contrast: '4.5:1' },
  { name: 'Text Link', var: 'text-link', desc: 'Hyperlinks', contrast: '4.5:1+' },
];

const interactiveColors = [
  { name: 'Interactive', var: 'interactive', text: 'interactive-foreground', desc: 'Primary buttons' },
  { name: 'Interactive Hover', var: 'interactive-hover', text: 'interactive-foreground', desc: 'Hover state' },
  { name: 'Interactive Secondary', var: 'interactive-secondary', text: 'interactive-secondary-foreground', desc: 'Secondary buttons' },
];

const semanticColors = [
  { name: 'Success', var: 'success', text: 'success-foreground', icon: Check },
  { name: 'Warning', var: 'warning', text: 'warning-foreground', icon: AlertTriangle },
  { name: 'Error', var: 'error', text: 'error-foreground', icon: X },
  { name: 'Info', var: 'info', text: 'info-foreground', icon: Info },
];

const borderColors = [
  { name: 'Border', var: 'border', desc: 'Default borders' },
  { name: 'Border Strong', var: 'border-strong', desc: 'Emphasized borders' },
  { name: 'Border Subtle', var: 'border-subtle', desc: 'Subtle dividers' },
  { name: 'Border Interactive', var: 'border-interactive', desc: 'Focus/active states' },
];
</script>

<template>
  <Head title="UI Design System Preview -  Colors" />

  <div class="min-h-screen bg-surface p-8">
    <!-- Header with Theme Toggles -->
    <div class="mb-8 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-text">UI Design System Preview</h1>
          <p class="mt-2 text-text-muted">Testing the color palette for accessibility</p>
        </div>
        <button
          @click="toggleTheme"
          class="flex min-h-[44px] @md:min-h-[36px] items-center gap-2 rounded-lg bg-interactive px-4 py-2 text-interactive-foreground transition-all hover:bg-interactive-hover focus:outline-0 focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset"
        >
          <Sun v-if="theme === 'dark'" class="h-5 w-5" />
          <Moon v-else class="h-5 w-5" />
          <span>{{ theme === 'light' ? 'Light' : 'Dark' }} Theme</span>
        </button>
      </div>

      <!-- Color Theme Selector -->
      <div class="rounded-lg bg-surface-elevated p-4 border border-border">
        <h3 class="mb-3 text-sm font-semibold text-text">Color Theme</h3>
        <div class="grid grid-cols-2 @md:grid-cols-5 gap-2">
          <button
            v-for="themeOption in themes"
            :key="themeOption.value"
            @click="setColorTheme(themeOption.value)"
            class="group relative min-h-[44px] @md:min-h-[36px] rounded-md border-2 p-3 text-left transition-all focus:outline-0 focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
            :class="colorTheme === themeOption.value
              ? 'border-interactive bg-interactive/10'
              : 'border-border bg-surface hover:border-border-strong hover:bg-surface-interactive-hover'"
          >
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm font-semibold text-text">{{ themeOption.name }}</div>
                <div class="text-xs text-text-subtle">{{ themeOption.desc }}</div>
              </div>
              <div v-if="colorTheme === themeOption.value" class="h-5 w-5 rounded-full bg-interactive text-interactive-foreground flex items-center justify-center">
                <Check class="h-3 w-3" />
              </div>
            </div>
          </button>
        </div>
      </div>
    </div>

    <div class="space-y-12 max-w-7xl">
      <!-- Surface Colors Section -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Surface Colors ()</h2>
        <div class="grid gap-4 @md:grid-cols-2 @xl:grid-cols-4">
          <div
            v-for="color in surfaceColors"
            :key="color.var"
            :style="{
              backgroundColor: `var(--${color.var})`,
              color: `var(--${color.text})`,
            }"
            class="rounded-lg p-6 shadow-sm ring-1 ring-border"
          >
            <div class="mb-2 text-sm font-medium opacity-70">{{ color.name }}</div>
            <div class="text-lg font-semibold">Aa</div>
            <div class="mt-3 space-y-1 text-xs opacity-60">
              <div>{{ color.desc }}</div>
              <div class="font-mono">--{{ color.var }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Text Colors Section -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Text Colors ()</h2>
        <div class="grid gap-4 @md:grid-cols-2 rounded-lg bg-surface-elevated p-6">
          <div v-for="textColor in textColors" :key="textColor.var" class="space-y-2">
            <div :style="{ color: `var(--${textColor.var})` }" class="text-xl font-semibold">
              {{ textColor.name }}
            </div>
            <p :style="{ color: `var(--${textColor.var})` }">
              The quick brown fox jumps over the lazy dog. This demonstrates readable text at various hierarchy levels.
            </p>
            <div class="flex items-center gap-3 text-xs text-text-muted">
              <span class="font-mono">--{{ textColor.var }}</span>
              <span class="rounded bg-success-subtle px-2 py-0.5 text-success-subtle-foreground">{{ textColor.contrast }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Interactive Colors Section -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Interactive Colors ()</h2>
        <div class="space-y-4">
          <div class="grid gap-4 @md:grid-cols-3">
            <div
              v-for="color in interactiveColors"
              :key="color.var"
              :style="{
                backgroundColor: `var(--${color.var})`,
                color: `var(--${color.text})`,
              }"
              class="rounded-lg p-6 shadow-sm"
            >
              <div class="text-lg font-semibold">{{ color.name }}</div>
              <p class="mt-2 text-sm opacity-90">{{ color.desc }}</p>
              <div class="mt-3 font-mono text-xs opacity-70">--{{ color.var }}</div>
            </div>
          </div>

          <div class="rounded-lg bg-surface-elevated p-6">
            <p class="mb-4 text-sm text-text-muted">Button Examples with Focus States (Tab to test)</p>
            <div class="flex flex-wrap gap-3">
              <button class="rounded-lg bg-interactive px-4 py-3 font-medium text-interactive-foreground transition-all hover:bg-interactive-hover active:bg-interactive-active focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset">
                Primary Action
              </button>
              <button class="rounded-lg bg-interactive-secondary px-4 py-3 font-medium text-interactive-secondary-foreground transition-all hover:bg-interactive-secondary-hover focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset">
                Secondary Action
              </button>
              <button class="rounded-lg border-2 border-border bg-surface-interactive px-4 py-3 font-medium text-text transition-all hover:bg-surface-interactive-hover focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset">
                Outline Button
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Semantic Colors Section -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Semantic Colors ()</h2>
        <div class="grid gap-4 @md:grid-cols-2">
          <div
            v-for="semantic in semanticColors"
            :key="semantic.var"
            class="space-y-3"
          >
            <!-- Bold version -->
            <div
              :style="{
                backgroundColor: `var(--${semantic.var})`,
                color: `var(--${semantic.text})`,
              }"
              class="flex items-start gap-3 rounded-lg p-4"
            >
              <component :is="semantic.icon" class="h-5 w-5 flex-shrink-0" />
              <div>
                <div class="font-semibold">{{ semantic.name }}</div>
                <div class="text-sm opacity-90">
                  High contrast variant for important alerts and feedback messages.
                </div>
              </div>
            </div>

            <!-- Subtle version -->
            <div
              :style="{
                backgroundColor: `var(--${semantic.var}-subtle)`,
                color: `var(--${semantic.var}-subtle-foreground)`,
                borderColor: `var(--${semantic.var})`,
              }"
              class="flex items-start gap-3 rounded-lg border p-4"
            >
              <component :is="semantic.icon" :style="{ color: `var(--${semantic.var})` }" class="h-5 w-5 flex-shrink-0" />
              <div>
                <div class="font-semibold">{{ semantic.name }} (Subtle)</div>
                <div class="text-sm">
                  Lower contrast variant for less critical information.
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Border Colors Section -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Border Colors ()</h2>
        <div class="grid gap-4 @md:grid-cols-2 @xl:grid-cols-4">
          <div
            v-for="border in borderColors"
            :key="border.var"
            :style="{ borderColor: `var(--${border.var})` }"
            class="rounded-lg border-2 bg-surface-elevated p-6"
          >
            <div class="font-semibold text-text">{{ border.name }}</div>
            <p class="mt-2 text-sm text-text-muted">{{ border.desc }}</p>
            <div class="mt-3 font-mono text-xs text-text-subtle">--{{ border.var }}</div>
          </div>
        </div>
      </section>

      <!-- Focus & Selection -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Focus & Selection ()</h2>
        <div class="space-y-4 rounded-lg bg-surface-elevated p-6">
          <div>
            <p class="mb-3 text-sm text-text-muted">Try selecting this text to see the selection color:</p>
            <p class="text-lg text-text">
              The quick brown fox jumps over the lazy dog. Pack my box with five dozen liquor jugs. Sphinx of black quartz, judge my vow.
            </p>
          </div>

          <div>
            <p class="mb-3 text-sm text-text-muted">Focus ring demonstration (Tab through these inputs):</p>
            <div class="grid gap-3 @md:grid-cols-2">
              <input
                type="text"
                placeholder="Focus me to see ring"
                class="rounded-lg border-2 border-border bg-surface px-4 py-3 text-text focus:border-border-interactive focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset"
              />
              <input
                type="text"
                placeholder="Tab here next"
                class="rounded-lg border-2 border-border bg-surface px-4 py-3 text-text focus:border-border-interactive focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- Accessibility Notes -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Accessibility Notes</h2>
        <div class="space-y-4 rounded-lg border-2 border-border-interactive bg-info-subtle p-6">
          <div class="flex items-start gap-3">
            <Info class="h-6 w-6 flex-shrink-0 text-info" />
            <div class="space-y-3 text-info-subtle-foreground">
              <p class="font-semibold">All  colors meet WCAG accessibility standards:</p>
              <ul class="list-disc space-y-1 pl-5">
                <li><strong>Primary text (text)</strong>: 7:1+ contrast ratio (AAA)</li>
                <li><strong>Secondary text (text-muted)</strong>: 4.5:1+ contrast ratio (AA)</li>
                <li><strong>Interactive elements</strong>: High contrast, visible focus states</li>
                <li><strong>Touch targets</strong>: Minimum 44px on mobile for all buttons/inputs</li>
                <li><strong>Semantic colors</strong>: Never rely on color alone (icons + text)</li>
                <li><strong>Focus rings</strong>: 2px+ width, high contrast</li>
              </ul>
              <p class="mt-4 rounded-lg bg-surface-elevated p-4 text-sm">
                <strong>Target audience:</strong> Designed for elderly users, people with visual impairments, and users on old devices.
                Large font sizes maintained, high contrast throughout, and generous spacing for easy interaction.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Form Elements Section -->
      <section>
        <h2 class="mb-6 text-2xl font-bold text-text">Form Elements</h2>
        <div class="space-y-6 rounded-lg bg-surface-elevated p-6">
          <p class="text-sm text-text-muted mb-4">
            Form fields with different states: empty (blue border on focus), filled valid (green border), invalid (red border with error message).
            Labels stand out with semibold weight. Focus ring only appears on the active field.
          </p>

          <div class="grid grid-cols-2 gap-4">
            <!-- Empty field (will show blue focus ring) -->
            <FormInput
              v-model="formData.name"
              name="name"
              label="Full Name"
              placeholder="Enter your name"
              required
            />

            <!-- Filled valid field (green border) -->
            <FormInput
              v-model="formData.email"
              name="email"
              label="Email"
              type="email"
              placeholder="your@email.com"
              :error="formErrors.email"
              required
            />

            <!-- Select dropdown -->
            <FormSelect
              v-model="formData.country"
              name="country"
              label="Country"
              :options="countryOptions"
              placeholder="Select a country"
            />

            <!-- Disabled field -->
            <FormInput
              model-value="Disabled Field"
              name="disabled"
              label="Disabled"
              disabled
            />
          </div>

          <div class="mt-6 flex justify-end">
            <FormSubmit label="Submit Form" />
          </div>

          <div class="mt-6 rounded-lg bg-info-subtle border border-info p-4">
            <div class="flex items-start gap-2">
              <Info class="h-5 w-5 flex-shrink-0 text-info mt-0.5" />
              <div class="text-sm text-info-subtle-foreground">
                <p class="font-semibold mb-1">Form Behavior:</p>
                <ul class="list-disc pl-5 space-y-1">
                  <li>Empty fields show subtle border, blue focus ring when clicked</li>
                  <li>Valid filled fields show green border (no ring needed)</li>
                  <li>Invalid fields show red border with error message below</li>
                  <li>Labels use semibold font to stand out without fatigue</li>
                  <li>Only ONE field at a time shows the focus ring</li>
                  <li>Dropdown appears smoothly with improved styling</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
