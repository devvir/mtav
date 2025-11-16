<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { _ } from '@/composables/useTranslations';
import { Camera, Loader2, X } from 'lucide-vue-next';

interface Props {
  subject: Subject;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  uploadRoute: string;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  size: 'lg',
  compact: false,
});

const emit = defineEmits<{
  success: [avatarUrl: string];
  error: [error: string];
}>();

const { getInitials } = useInitials();

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const isUploading = ref(false);
const errorMessage = ref<string | null>(null);

const displayAvatar = computed(() => {
  if (previewUrl.value) return previewUrl.value;
  if (props.subject.avatar) {
    // If the avatar already starts with http or /, use it as-is
    if (props.subject.avatar.startsWith('http') || props.subject.avatar.startsWith('/')) {
      return props.subject.avatar;
    }
    // Otherwise prepend /storage/
    return `/storage/${props.subject.avatar}`;
  }
  return null;
});

const avatarInitials = computed(() => getInitials(props.subject.name) || '?');

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'size-16';
    case 'md':
      return 'size-24';
    case 'lg':
      return 'size-32';
    case 'xl':
      return 'size-40';
    default:
      return 'size-32';
  }
});

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];

  if (!file) return;

  // Validate file type
  if (!file.type.startsWith('image/')) {
    errorMessage.value = _('Please select an image file');
    return;
  }

  // Validate file size (2MB)
  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = _('Image must be smaller than 2MB');
    return;
  }

  errorMessage.value = null;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string;
  };
  reader.readAsDataURL(file);

  // Upload immediately
  uploadAvatar(file);
};

const uploadAvatar = async (file: File) => {
  isUploading.value = true;
  errorMessage.value = null;

  const formData = new FormData();
  formData.append('avatar', file);

  try {
    const response = await fetch(route(props.uploadRoute), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN':
          document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: formData,
    });

    if (!response.ok) {
      throw new Error('Upload failed');
    }

    const data = await response.json();

    emit('success', data.avatar_url);
  } catch (_error) {
    errorMessage.value = _('Failed to upload avatar. Please try again.');
    previewUrl.value = null;
    emit('error', errorMessage.value);
  } finally {
    isUploading.value = false;
  }
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

const clearPreview = () => {
  previewUrl.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};
</script>

<template>
  <div
    :class="
      compact ? 'flex flex-row-reverse items-center gap-4' : 'flex flex-col items-center gap-4'
    "
  >
    <div class="relative flex-shrink-0">
      <Avatar :class="sizeClasses" size="lg">
        <AvatarImage v-if="displayAvatar" :src="displayAvatar" :alt="_('Profile picture')" />
        <AvatarFallback :class="sizeClasses">
          <span class="text-2xl font-semibold">{{ avatarInitials }}</span>
        </AvatarFallback>
      </Avatar>

      <!-- Upload overlay when hovering or uploading -->
      <div
        v-if="!isUploading"
        class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity hover:opacity-100"
        @click="triggerFileInput"
      >
        <Camera class="size-8 text-white" />
      </div>

      <!-- Loading overlay -->
      <div
        v-if="isUploading"
        class="absolute inset-0 flex items-center justify-center rounded-full bg-black/70"
      >
        <Loader2 class="size-8 animate-spin text-white" />
      </div>
    </div>

    <!-- Hidden file input -->
    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleFileSelect" />

    <!-- Info and cancel button (if previewing) -->
    <div
      :class="
        compact
          ? 'flex min-h-[3rem] flex-col items-start gap-2'
          : 'flex min-h-[3rem] flex-col items-center gap-4'
      "
    >
      <!-- Cancel button (only shown when previewing) -->
      <Button
        v-if="previewUrl && !isUploading"
        type="button"
        variant="ghost"
        size="sm"
        @click="clearPreview"
      >
        <X class="mr-2 size-4" />
        {{ _('Cancel') }}
      </Button>

      <!-- Error message -->
      <p
        v-if="errorMessage"
        class="text-sm text-destructive"
        :class="compact ? 'max-w-xs text-left' : 'text-center'"
      >
        {{ errorMessage }}
      </p>

      <!-- Helper text -->
      <p
        v-else-if="!previewUrl"
        class="text-xs text-text-muted/70"
        :class="compact ? 'max-w-xs text-left' : 'text-center'"
      >
        {{ _('Click to upload a profile picture') }}<br />
        {{ _('Max 2MB, JPG, PNG or GIF') }}
      </p>
    </div>
  </div>
</template>
