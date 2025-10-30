<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { ModalLink } from '@inertiaui/modal-vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unit_types: UnitType[];
}>();
</script>

<template>
  <Head title="Unit Types" />

  <Breadcrumbs>
    <Breadcrumb route="unit-types.index" text="Unit Types" />
  </Breadcrumbs>

  <MaybeModal>
    <div class="space-y-3">
      <div v-if="!unit_types.length" class="flex items-center justify-center p-8">
        <p class="text-text-muted">{{ _('No units yet') }}</p>
      </div>

      <div v-else class="grid gap-3 @md:grid-cols-2 @2xl:grid-cols-3">
        <ModalLink
          v-for="unitType in unit_types"
          :key="unitType.id"
          :href="`/unit-types/${unitType.id}`"
          class="block rounded-lg border border-border bg-surface-elevated p-4 transition-all hover:border-border-interactive hover:shadow-md focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
        >
          <div class="flex flex-col gap-2">
            <div class="font-semibold text-lg text-text">{{ unitType.name }}</div>
            <div v-if="unitType.description" class="text-sm text-text-muted">
              {{ unitType.description }}
            </div>
            <div class="mt-2 flex gap-4 text-sm text-text-subtle">
              <div v-if="unitType.units_count !== undefined" class="flex items-center gap-1">
                <span class="font-medium text-text">{{ unitType.units_count }}</span>
                {{ unitType.units_count === 1 ? _('unit') : _('units') }}
              </div>
              <div v-if="unitType.families_count !== undefined" class="flex items-center gap-1">
                <span class="font-medium text-text">{{ unitType.families_count }}</span>
                {{ unitType.families_count === 1 ? _('family') : _('families') }}
              </div>
            </div>
          </div>
        </ModalLink>
      </div>
    </div>
  </MaybeModal>
</template>
