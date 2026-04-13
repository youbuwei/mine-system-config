<script setup lang="ts">
import { computed } from 'vue'

interface Group {
  id: number
  key: string
  name: string
  icon?: string
  children?: Group[]
}

const props = defineProps<{
  groups: Group[]
  activeKey?: string
}>()

const emit = defineEmits<{
  (e: 'select', group: Group): void
}>()

function handleSelect(group: Group) {
  emit('select', group)
}

// 扁平化分组树用于高亮（预留）
const _flatGroups = computed(() => {
  const result: Group[] = []
  const flatten = (items: Group[]) => {
    items.forEach((item) => {
      result.push(item)
      if (item.children?.length) {
        flatten(item.children)
      }
    })
  }
  flatten(props.groups)
  return result
})
</script>

<template>
  <div class="config-nav">
    <el-menu
      :default-active="activeKey"
      class="border-r-0"
    >
      <template v-for="group in groups" :key="group.key">
        <!-- 有子分组 -->
        <el-sub-menu v-if="group.children?.length" :index="group.key">
          <template #title>
            <el-icon v-if="group.icon">
              <i :class="group.icon" />
            </el-icon>
            <span>{{ group.name }}</span>
          </template>
          <el-menu-item
            v-for="child in group.children"
            :key="child.key"
            :index="child.key"
            @click="handleSelect(child)"
          >
            <el-icon v-if="child.icon">
              <i :class="child.icon" />
            </el-icon>
            <span>{{ child.name }}</span>
          </el-menu-item>
        </el-sub-menu>
        <!-- 无子分组 -->
        <el-menu-item v-else :index="group.key" @click="handleSelect(group)">
          <el-icon v-if="group.icon">
            <i :class="group.icon" />
          </el-icon>
          <span>{{ group.name }}</span>
        </el-menu-item>
      </template>
    </el-menu>
  </div>
</template>

<style scoped lang="scss">
.config-nav {
  height: 100%;
  background: var(--el-bg-color);

  :deep(.el-menu) {
    border-right: none;
  }
}
</style>
