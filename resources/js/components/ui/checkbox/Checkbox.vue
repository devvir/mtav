<script setup lang="ts">
import type { CheckboxRootEmits, CheckboxRootProps } from 'reka-ui'
import { cn } from '@/lib/utils'
import { Check } from 'lucide-vue-next'
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui'
import type { HTMLAttributes } from 'vue'

const props = defineProps<CheckboxRootProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<CheckboxRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <CheckboxRoot
    data-slot="checkbox"
    v-bind="forwarded"
    :class="
      cn('peer min-h-[44px] min-w-[44px] @md:min-h-[36px] @md:min-w-[36px] border-border data-[state=checked]:bg-interactive data-[state=checked]:text-white data-[state=checked]:border-interactive focus-visible:border-focus-ring focus-visible:ring-focus-ring/50 aria-invalid:ring-error/20 aria-invalid:border-error flex items-center justify-center rounded-[4px] border-2 shadow-xs transition-shadow outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50',
         props.class)"
  >
    <CheckboxIndicator
      data-slot="checkbox-indicator"
      class="flex items-center justify-center text-current transition-none"
    >
      <slot>
        <Check class="size-5 @md:size-4" />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
