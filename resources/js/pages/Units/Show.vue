<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { can } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import { Building2, Edit3Icon, Home, Users } from 'lucide-vue-next';
import ShowWrapper from '../shared/ShowWrapper.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
    unit: ApiResource<Unit>;
}>();
</script>

<template>
  <Head title="Unit" />

  <Breadcrumbs>
    <Breadcrumb v-if="can.viewAny('units')" route="units.index" text="Units" />
    <Breadcrumb route="units.show" :params="unit.id">{{ _('Unit') }} {{ unit.number }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal panelClasses="modalPanel backdrop-blur-lg">
    <ShowWrapper :resource-id="unit.id">
      <Card class="size-full">
        <template v-slot:header>
          <div class="flex items-center gap-4">
            <div class="rounded-lg bg-surface-sunken p-3">
              <Building2 class="h-8 w-8 text-text-subtle" />
            </div>
            <div class="flex-1">
              <h2 class="text-2xl font-semibold text-text">{{ _('Unit') }} {{ unit.number }}</h2>
              <p v-if="unit.type" class="mt-1 text-sm text-text-subtle">
                {{ unit.type.name }}
              </p>
            </div>
          </div>
        </template>

        <div class="mt-6 space-y-6 px-3 py-5">
          <!-- Info Grid -->
          <div class="grid gap-4 @md:grid-cols-2">
            <!-- Unit Type -->
            <div v-if="unit.type" class="rounded-lg border border-border bg-surface-sunken p-4">
              <div class="flex items-center gap-3">
                <div class="rounded-md bg-interactive/10 p-2">
                  <Home class="h-5 w-5 text-interactive" />
                </div>
                <div class="flex-1 overflow-hidden">
                  <div class="text-sm font-medium text-text-subtle">{{ _('Unit Type') }}</div>
                  <ModalLink
                    :href="route('unit-types.show', unit.type.id)"
                    class="truncate text-lg font-semibold text-text-link hover:text-text-link-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-1"
                  >
                    {{ unit.type.name }}
                  </ModalLink>
                </div>
              </div>
            </div>

            <!-- Family -->
            <div v-if="unit.family" class="rounded-lg border border-border bg-surface-sunken p-4">
              <div class="flex items-center gap-3">
                <div class="rounded-md bg-interactive/10 p-2">
                  <Users class="h-5 w-5 text-interactive" />
                </div>
                <div class="flex-1 overflow-hidden">
                  <div class="text-sm font-medium text-text-subtle">{{ _('Family') }}</div>
                  <ModalLink
                    :href="route('families.show', unit.family.id)"
                    class="truncate text-lg font-semibold text-text-link hover:text-text-link-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-1"
                  >
                    {{ unit.family.name }}
                  </ModalLink>
                </div>
              </div>
            </div>
          </div>

          <!-- Project Info -->
          <div v-if="unit.project" class="rounded-lg border border-border bg-surface-sunken p-4">
            <div class="text-sm font-medium text-text-subtle">{{ _('Project') }}</div>
            <div class="mt-1 text-lg font-semibold text-text">{{ unit.project.name }}</div>
          </div>

          <!-- Metadata -->
          <div class="border-t border-border pt-4 text-sm text-text-subtle" :title="unit.created_at">
            <span>{{ _('Created') }}</span>: {{ unit.created_ago }}
          </div>
        </div>

        <!-- Edit Link -->
        <ModalLink
          v-if="unit.allows?.update"
          class="flex items-center justify-end gap-2 border-t border-border pt-base text-text-link hover:text-text-link-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
          paddingClasses="p-8"
          :href="route('units.edit', unit.id)"
        >
          {{ _('Edit') }} {{ _('Unit') }} <Edit3Icon class="h-4 w-4" />
        </ModalLink>
      </Card>
    </ShowWrapper>
  </MaybeModal>
</template>
