<script setup lang="ts">
import { CardContent, CardFooter, CardHeader, EntityCard } from '@/components/card';
import { ContentDetail, ContentGrid, ContentHighlight } from '@/components/card/snippets';
import { iAmAdmin } from '@/composables/useAuth';
import { fromUTC } from '@/composables/useDates';
import { _ } from '@/composables/useTranslations';
import { CalendarIcon, HomeIcon, MailIcon, PhoneIcon, UsersIcon } from 'lucide-vue-next';

defineProps<{
  member: ApiResource<Member>;
}>();
</script>

<template>
  <EntityCard :resource="member" entity="member" type="show">
    <CardHeader :title="member.name" avatar="xl" />

    <CardContent class="space-y-6">
      <!-- Family Information -->
      <div v-if="member.family" class="grid grid-cols-[auto_1fr] gap-3">
        <component :is="HomeIcon" class="mt-0.5 h-5 w-5 text-text-muted" />
        <div class="min-w-0">
          <div class="text-sm font-medium">{{ _('Family') }}</div>
          <div class="truncate text-sm text-text-muted">
            <Link
              :href="route('families.show', member.family.id)"
              :modal="true"
              class="truncate text-text-link hover:text-text-link-hover"
            >
              {{ member.family.name }}
            </Link>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <ContentGrid>
        <ContentDetail :icon="MailIcon" :title="_('Email')" :content="member.email" />

        <ContentDetail
          v-if="member.phone"
          :icon="PhoneIcon"
          :title="_('Phone')"
          :content="member.phone"
        />
      </ContentGrid>

      <!-- Membership Details -->
      <ContentGrid>
        <ContentDetail
          v-if="iAmAdmin && member.email_verified_at"
          :icon="CalendarIcon"
          :title="_('Email Verified')"
          :content="fromUTC(member.email_verified_at)"
        />

        <ContentDetail
          v-if="member.invitation_accepted_at"
          :icon="UsersIcon"
          :title="_('Member Since')"
          :content="fromUTC(member.invitation_accepted_at)"
        />
      </ContentGrid>

      <!-- About Section -->
      <div v-if="member.about">
        <ContentHighlight :title="_('About me')">
          {{ member.about }}
        </ContentHighlight>
      </div>
    </CardContent>

    <CardFooter />
  </EntityCard>
</template>
