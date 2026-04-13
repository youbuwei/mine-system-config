<script setup lang="ts">
import { computed } from 'vue'

interface Field {
  key: string
  name: string
  placeholder?: string
  tooltip?: string
  description?: string
  validation?: {
    min?: number
    max?: number
    required?: boolean
  }
}

const props = defineProps<{
  modelValue?: number
  field: Field
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void
}>()

const value = computed({
  get: () => {
    const v = props.modelValue
    if (v === null || v === undefined) {
      return 0
    }
    return typeof v === 'number' ? v : Number(v)
  },
  set: val => emit('update:modelValue', val),
})

const min = computed(() => props.field.validation?.min ?? 0)
const max = computed(() => props.field.validation?.max ?? 999999)
</script>

<template>
  <el-form-item :label="field.name" :prop="field.key">
    <el-input-number
      v-model="value"
      :min="min"
      :max="max"
      :placeholder="field.placeholder"
      controls-position="right"
      style="width: 200px"
    />
    <div v-if="field.description || field.tooltip" class="mt-1 text-xs text-gray-400">
      {{ field.description || field.tooltip }}
    </div>
  </el-form-item>
</template>
