<script setup lang="ts">
import { ref, watch } from 'vue'
import TextField from './fields/TextField.vue'
import TextareaField from './fields/TextareaField.vue'
import NumberField from './fields/NumberField.vue'
import SwitchField from './fields/SwitchField.vue'
import SelectField from './fields/SelectField.vue'
import MultiSelectField from './fields/MultiSelectField.vue'

interface Item {
  id: number
  key: string
  name: string
  type: string
  options?: { label: string, value: any }[]
  validation?: Record<string, any>
  default_value?: any
  placeholder?: string
  tooltip?: string
  is_encrypted?: number
}

const props = defineProps<{
  items: Item[]
  values: Record<string, any>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: any): void
}>()

const formData = ref<Record<string, any>>({})

function convertValue(item: Item, value: any): any {
  if (value === null || value === undefined) {
    return item.default_value
  }

  switch (item.type) {
    case 'number':
      return Number(value)
    case 'switch':
      if (typeof value === 'string') {
        return value === '1' || value.toLowerCase() === 'true'
      }
      return Boolean(value)
    case 'multiSelect':
    case 'checkbox':
      if (typeof value === 'string') {
        try {
          const parsed = JSON.parse(value)
          return Array.isArray(parsed) ? parsed : []
        }
        catch {
          return value ? [value] : []
        }
      }
      return Array.isArray(value) ? value : []
    case 'select':
    case 'radio':
      if (item.options?.length && typeof item.options[0].value === 'number') {
        return Number(value)
      }
      return value
    default:
      return value
  }
}

watch(
  [() => props.items, () => props.values],
  ([items, values]) => {
    const data: Record<string, any> = {}
    items.forEach((item) => {
      const rawValue = values[item.key] ?? item.default_value
      data[item.key] = convertValue(item, rawValue)
    })
    formData.value = data
  },
  { immediate: true, deep: true },
)

function handleUpdate(key: string, value: any) {
  formData.value[key] = value
  emit('update', key, value)
}

defineExpose({ formData })
</script>

<template>
  <el-form :model="formData" label-width="140px">
    <template v-if="items.length">
      <template v-for="item in items" :key="item.key">
        <TextField
          v-if="item.type === 'text'"
          :model-value="formData[item.key]"
          :field="item"
          @update:model-value="(v: any) => handleUpdate(item.key, v)"
        />
        <TextareaField
          v-else-if="item.type === 'textarea'"
          :model-value="formData[item.key]"
          :field="item"
          @update:model-value="(v: any) => handleUpdate(item.key, v)"
        />
        <NumberField
          v-else-if="item.type === 'number'"
          :model-value="formData[item.key]"
          :field="item"
          @update:model-value="(v: any) => handleUpdate(item.key, v)"
        />
        <SwitchField
          v-else-if="item.type === 'switch'"
          :model-value="formData[item.key]"
          :field="item"
          @update:model-value="(v: any) => handleUpdate(item.key, v)"
        />
        <SelectField
          v-else-if="item.type === 'select' || item.type === 'radio'"
          :model-value="formData[item.key]"
          :field="item"
          @update:model-value="(v: any) => handleUpdate(item.key, v)"
        />
        <MultiSelectField
          v-else-if="item.type === 'multiSelect' || item.type === 'checkbox'"
          :model-value="formData[item.key]"
          :field="item"
          @update:model-value="(v: any) => handleUpdate(item.key, v)"
        />
        <TextField
          v-else
          :model-value="formData[item.key]"
          :field="item"
          @update:model-value="(v: any) => handleUpdate(item.key, v)"
        />
      </template>
    </template>
    <el-empty v-else description="暂无配置项" />
  </el-form>
</template>
