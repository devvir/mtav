<script setup lang="ts">
import { Avatar } from '@/components/avatar';
import EditButton from '@/components/EditButton.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import Card from '@/components/shared/Card.vue';
import { currentUser } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import ShowWrapper from '../shared/ShowWrapper.vue';
import Delete from './Crud/Delete.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

const props = defineProps<{
  member: ApiResource<Member>;
}>();

const projectLink = computed(() => {
  return props.member.project?.allows?.view
    ? route('projects.show', props.member.project.id)
    : route('dashboard');
});

const isCurrentUser = computed(() => currentUser.value?.id === props.member.id);
</script>

<template>
  <Head :title="member.name" no-translation />

  <Breadcrumbs>
    <Breadcrumb route="members.index" text="Members" />
    <Breadcrumb route="members.show" :params="member.id">{{ member.name }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal>
    <ShowWrapper>
      <Card class="size-full">
        <template v-slot:header>
          <div class="flex items-center-safe justify-between gap-4" :title="member.name">
            <div class="flex min-w-0 flex-1 items-center-safe gap-4">
              <Avatar :subject="member" size="lg" class="rounded-full ring-2 ring-border" />
              <div class="min-w-0 flex-1 text-sm leading-wide text-text">
                <div class="truncate text-2xl leading-12">{{ member.name }}</div>
              </div>
            </div>

            <EditButton v-if="!isCurrentUser" :resource="member" route-name="members.edit" />
          </div>
        </template>

        <div class="mt-6 mb-3 flex flex-col justify-between gap-6 px-3 py-5">
          <!-- Context: Where member belongs -->
          <div class="space-y-3">
            <ModalLink
              v-if="member.project?.name"
              :href="projectLink"
              class="flex items-center-safe gap-2 text-base text-text-link hover:text-text-link-hover focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-none"
            >
              <span class="text-text/70">{{ _('Project') }}:</span>
              <span class="font-medium">{{ member.project.name }}</span>
            </ModalLink>

            <ModalLink
              :href="route('families.show', member.family.id)"
              class="flex items-center-safe gap-2 text-base text-text-link hover:text-text-link-hover focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-none"
            >
              <span class="text-text/70">{{ _('Family') }}:</span>
              <span class="font-medium">{{ (member.family as Family).name }}</span>
            </ModalLink>
          </div>

          <!-- Bio -->
          <div class="rounded-lg bg-surface-sunken p-4">
            <div class="mb-2 text-sm font-medium text-text/70">{{ _('About me') }}</div>
            <p v-if="member.about" class="text-sm leading-relaxed text-text/90">
              {{ member.about }}
            </p>
            <p v-else class="text-sm text-text/50 italic">{{ _('Nothing written yet') }}</p>
          </div>

          <!-- Member properties: Contact & identity info -->
          <div class="space-y-2 border-t border-border pt-4 text-sm">
            <div class="flex items-center-safe gap-2 text-text/60">
              <span>{{ _('Email') }}:</span>
              <span class="text-text/80">{{ member.email }}</span>
            </div>

            <div class="flex items-center-safe gap-2 text-text/60">
              <span>{{ _('Phone') }}:</span>
              <span class="text-text/80">{{ member.phone ?? 'N/A' }}</span>
            </div>

            <div v-if="member.legal_id" class="flex items-center-safe gap-2 text-text/60">
              <span>{{ _('Legal ID') }}:</span>
              <span class="text-text/80">{{ member.legal_id }}</span>
            </div>
          </div>

          <!-- Admin-only: verification status -->
          <div
            v-if="member.is_verified !== undefined"
            class="space-y-2 border-t border-border pt-4 text-sm"
          >
            <div class="flex items-center-safe gap-2 text-text/60">
              <span>{{ _('Email verification') }}:</span>
              <span :class="member.is_verified ? 'text-success' : 'text-warning'">
                {{ member.is_verified ? _('Verified') : _('Not verified') }}
              </span>
            </div>

            <div class="text-text/60" :title="member.created_at">
              {{ _('Created') }}: {{ member.created_ago }}
            </div>
          </div>
        </div>

        <Delete v-if="member.allows?.delete" :member class="border-t border-border px-3 py-5" />
      </Card>
    </ShowWrapper>
  </MaybeModal>
</template>
