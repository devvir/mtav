<script setup lang="ts">
import { Card, CardContent, CardFooter, CardHeader } from '@/components/card';
import { ContentHighlight, ContentDetail, ContentGrid } from '@/components/card/snippets';
import { _ } from '@/composables/useTranslations';
import { MailIcon, PhoneIcon, UsersIcon, HomeIcon, CalendarIcon } from 'lucide-vue-next';
import { iAmAdmin } from '@/composables/useAuth';

defineProps<{
  member: ApiResource<Member>;
}>();
</script>

<template>
  <Card :resource="member" entity="member" type="show">
    <CardHeader :title="member.name" avatar="lg" />

    <CardContent class="space-y-6">
      <!-- Family Information -->
      <div v-if="member.family" class="grid grid-cols-[auto_1fr] gap-3">
        <component :is="HomeIcon" class="h-5 w-5 mt-0.5 text-text-muted" />
        <div class="min-w-0">
          <div class="font-medium text-sm">{{ _('Family') }}</div>
          <div class="text-text-muted text-sm truncate">
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
        <ContentDetail
          :icon="MailIcon"
          :title="_('Email')"
          :content="member.email"
        />

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
          :content="member.email_verified_at"
        />

        <ContentDetail
          v-if="member.invitation_accepted_at"
          :icon="UsersIcon"
          :title="_('Member Since')"
          :content="member.invitation_accepted_at"
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
  </Card>
</template>
