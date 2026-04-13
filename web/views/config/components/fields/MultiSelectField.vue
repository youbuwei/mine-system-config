<script setup lang="ts">
import { computed } from 'vue'

interface Option {
  label: string
  value: string | number
}

interface Field {
  key: string
  name: string
  placeholder?: string
  tooltip?: string
  description?: string
  options?: Option[]
}

const props = defineProps<{
  modelValue?: (string | number)[]
  field: Field
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: (string | number)[]): void
}>()

const value = computed({
  get: () => {
    const v = props.modelValue
    if (Array.isArray(v)) {
      return v
    }
    if (typeof v === 'string' && v !== '') {
      try {
        const parsed = JSON.parse(v)
        return Array.isArray(parsed) ? parsed : []
      }
      catch {
        return []
      }
    }
    return []
  },
  set: val => emit('update:modelValue', val),
})

const options = computed(() => props.field.options ?? [])
</script>

<template>
  <el-form-item :label="field.name" :prop="field.key">
    <el-select
      v-model="value"
      :placeholder="field.placeholder || `请选择${field.name}`"
      multiple
      clearable
      collapse-tags
      collapse-tags-tooltip
      style="width: 100%"
    >
      <el-option
        v-for="opt in options"
        :key="opt.value"
        :label="opt.label"
        :value="opt.value"
      />
    </el-select>
    <div v-if="field.description || field.tooltip" class="mt-1 text-xs text-gray-400">
      {{ field.description || field.tooltip }}
    </div>
  </el-form-item>
</template>
