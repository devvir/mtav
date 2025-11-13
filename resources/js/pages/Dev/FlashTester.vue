<script setup lang="ts">
import Head from '@/components/Head.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { _ } from '@/composables/useTranslations';
import { ref } from 'vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';

const successMessage = ref('This is a success message!');
const infoMessage = ref('This is an informational message.');
const warningMessage = ref('This is a warning message!');
const errorMessage = ref('This is an error message!');

const sendFlash = (type: string, message: string) => {
  router.post(route('dev.flash.send'), { type, message });
};
</script>

<template>
  <Head :title="_('Flash Messages Tester')" />

  <Breadcrumbs global>
    <Breadcrumb route="dev.dashboard">Dev</Breadcrumb>
    <Breadcrumb route="dev.flash" no-link>Flash Message Tester</Breadcrumb>
  </Breadcrumbs>

  <div class="container mx-auto max-w-4xl py-8">
    <Card>
      <CardHeader>
        <CardTitle>Flash Messages Tester</CardTitle>
        <CardDescription>
          Test all flash message types with custom messages
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Success -->
        <div class="space-y-2">
          <Label for="success">Success Message</Label>
          <div class="flex gap-2">
            <Input
              id="success"
              v-model="successMessage"
              placeholder="Enter success message"
            />
            <Button @click="sendFlash('success', successMessage)" variant="default" class="w-36">
              Send Success
            </Button>
          </div>
        </div>

        <!-- Info -->
        <div class="space-y-2">
          <Label for="info">Info Message</Label>
          <div class="flex gap-2">
            <Input
              id="info"
              v-model="infoMessage"
              placeholder="Enter info message"
            />
            <Button @click="sendFlash('info', infoMessage)" variant="secondary" class="w-36">
              Send Info
            </Button>
          </div>
        </div>

        <!-- Warning -->
        <div class="space-y-2">
          <Label for="warning">Warning Message</Label>
          <div class="flex gap-2">
            <Input
              id="warning"
              v-model="warningMessage"
              placeholder="Enter warning message"
            />
            <Button @click="sendFlash('warning', warningMessage)" variant="outline" class="w-36">
              Send Warning
            </Button>
          </div>
        </div>

        <!-- Error -->
        <div class="space-y-2">
          <Label for="error">Error Message</Label>
          <div class="flex gap-2">
            <Input
              id="error"
              v-model="errorMessage"
              placeholder="Enter error message"
            />
            <Button @click="sendFlash('error', errorMessage)" variant="error" class="w-36">
              Send Error
            </Button>
          </div>
        </div>

        <!-- Send All -->
        <div class="pt-4 border-t">
          <Button @click="router.get(route('dev.flash.all'))" variant="default" class="w-full">
            Send All Messages At Once
          </Button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
