<script setup lang="ts">
// Copilot - pending review
import Card from '@/components/shared/Card.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import ShowWrapper from '../shared/ShowWrapper.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import { Building2, Edit3Icon, Home, Users } from 'lucide-vue-next';

defineProps<{
  unit_type: ApiResource<UnitType>;
}>();
</script>

<template>
  <Head :title="unit_type.name" />

  <Breadcrumbs>
    <Breadcrumb route="unit-types.index" text="Unit Types" />
    <Breadcrumb route="unit-types.show" :params="unit_type.id" :text="unit_type.name" />
  </Breadcrumbs>

  <MaybeModal>
    <ShowWrapper :resource-id="unit_type.id">
      <Card class="size-full">
        <template v-slot:header>
          <div class="flex items-center gap-4">
            <div class="rounded-lg bg-surface-sunken p-3">
              <Building2 class="h-8 w-8 text-text-subtle" />
            </div>
            <div class="flex-1">
              <h2 class="text-2xl font-semibold text-text">{{ unit_type.name }}</h2>
              <p v-if="unit_type.description" class="mt-1 text-sm text-text-muted">
                {{ unit_type.description }}
              </p>
            </div>
          </div>
        </template>

        <div class="mt-6 space-y-6 px-3 py-5">
          <!-- Stats Grid -->
          <div class="grid gap-4 @md:grid-cols-2">
            <!-- Units Count -->
            <div class="rounded-lg border border-border bg-surface-sunken p-4">
              <div class="flex items-center gap-3">
                <div class="rounded-md bg-interactive/10 p-2">
                  <Home class="h-5 w-5 text-interactive" />
                </div>
                <div>
                  <div class="text-sm font-medium text-text-subtle">{{ _('Units') }}</div>
                  <div class="text-2xl font-semibold text-text">{{ unit_type.units_count || 0 }}</div>
                </div>
              </div>
            </div>

            <!-- Families Count -->
            <div class="rounded-lg border border-border bg-surface-sunken p-4">
              <div class="flex items-center gap-3">
                <div class="rounded-md bg-interactive/10 p-2">
                  <Users class="h-5 w-5 text-interactive" />
                </div>
                <div>
                  <div class="text-sm font-medium text-text-subtle">{{ _('Families') }}</div>
                  <div class="text-2xl font-semibold text-text">{{ unit_type.families_count || 0 }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Metadata -->
          <div class="border-t border-border pt-4 text-sm text-text-subtle">
            <span>{{ _('Created') }}</span>: {{ unit_type.created_ago }}
          </div>
        </div>

        <!-- Edit Link -->
        <ModalLink
          v-if="unit_type.allows?.update"
          class="flex items-center justify-end gap-2 border-t border-border pt-base text-text-link hover:text-text-link-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
          paddingClasses="p-8"
          :href="route('unit-types.edit', unit_type.id)"
        >
          {{ _('Edit') }} {{ _('Unit Type') }} <Edit3Icon class="h-4 w-4" />
        </ModalLink>
      </Card>
    </ShowWrapper>
  </MaybeModal>
</template>
