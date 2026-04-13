<script setup lang="ts">
import { computed } from 'vue'

interface Field {
  key: string
  name: string
  placeholder?: string
  tooltip?: string
  description?: string
}

const props = defineProps<{
  modelValue?: string
  field: Field
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const value = computed({
  get: () => props.modelValue ?? '',
  set: val => emit('update:modelValue', val),
})
</script>

<template>
  <el-form-item :label="field.name" :prop="field.key">
    <el-input
      v-model="value"
      type="textarea"
      :rows="4"
      :placeholder="field.placeholder || `请输入${field.name}`"
    />
    <div v-if="field.description || field.tooltip" class="mt-1 text-xs text-gray-400">
      {{ field.description || field.tooltip }}
    </div>
  </el-form-item>
</template>
