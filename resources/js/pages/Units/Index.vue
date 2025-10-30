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
        <p class="text-muted-foreground">{{ _('No units yet') }}</p>
      </div>

      <div v-else class="space-y-2">
        <Link
          v-for="unit in units"
          :key="unit.id"
          :href="route('units.show', unit.id)"
          class="block rounded-lg border p-3 transition-colors hover:bg-muted/50"
        >
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1">
              <div class="font-semibold">{{ _('Unit') }} {{ unit.number }}</div>
              <div v-if="unit.type" class="text-sm text-muted-foreground">
                {{ unit.type.name }}
              </div>
            </div>
            <div v-if="unit.family" class="text-sm text-muted-foreground">
              {{ unit.family.name }}
            </div>
          </div>
        </Link>
      </div>
    </div>
  </MaybeModal>
</template>
