<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  units: Unit[];
}>();
</script>

<template>
  <Head title="Units" />

  <Breadcrumbs>
    <Breadcrumb route="units.index" text="Units" />
  </Breadcrumbs>

  <MaybeModal>
    <div class="space-y-2">
      <div v-if="!units.length" class="flex items-center justify-center p-8">
        <p class="text-text-muted">{{ _('No units yet') }}</p>
      </div>

      <div v-else class="space-y-2">
        <Link
          v-for="unit in units"
          :key="unit.id"
          :href="route('units.show', unit.id)"
          class="block rounded-lg border border-border bg-surface-elevated p-3 transition-colors hover:border-border-interactive hover:bg-surface-interactive-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
        >
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1">
              <div class="font-semibold text-text">{{ _('Unit') }} {{ unit.number }}</div>
              <div v-if="unit.type" class="text-sm text-text-subtle">
                {{ unit.type.name }}
              </div>
            </div>
            <div v-if="unit.family" class="text-sm text-text-subtle">
              {{ unit.family.name }}
            </div>
          </div>
        </Link>
      </div>
    </div>
  </MaybeModal>
</template>
