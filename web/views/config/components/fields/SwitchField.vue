<script setup lang="ts">
import { computed } from 'vue'

interface Field {
  key: string
  name: string
  tooltip?: string
  description?: string
}

const props = defineProps<{
  modelValue?: boolean | string
  field: Field
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const value = computed({
  get: () => {
    const v = props.modelValue
    if (typeof v === 'string') {
      return v === '1' || v.toLowerCase() === 'true'
    }
    return v ?? false
  },
  set: val => emit('update:modelValue', val),
})
</script>

<template>
  <el-form-item :label="field.name" :prop="field.key">
    <el-switch
      v-model="value"
      active-text="开启"
      inactive-text="关闭"
    />
    <div v-if="field.description || field.tooltip" class="mt-1 text-xs text-gray-400">
      {{ field.description || field.tooltip }}
    </div>
  </el-form-item>
</template>
