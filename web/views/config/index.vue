<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getModuleTree, getModuleValues, updateModuleConfigValues } from '~/settings/api/config'
import ConfigForm from './components/ConfigForm.vue'

defineOptions({ name: 'settings:config' })

const route = useRoute()
const router = useRouter()

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

interface Group {
  id: number
  key: string
  name: string
  description?: string
  icon?: string
  items?: Item[]
}

interface Module {
  id: number
  key: string
  name: string
  description?: string
  icon?: string
  groups?: Group[]
}

const modules = ref<Module[]>([])
const activeModuleKey = ref<string>('')
const activeGroupKey = ref<string>('')
const configValues = ref<Record<string, any>>({})
const allFormData = ref<Record<string, Record<string, any>>>({})
const loading = ref(false)
const saving = ref(false)

// 右侧内容区 ref，用于滚动定位
const contentRef = ref<HTMLElement>()

// 当前展开的 collapse 面板
const expandedGroups = ref<string[]>([])

// 当前选中的 module
const activeModule = ref<Module | null>(null)

// 标志位：是否为内部导航（用于区分点击菜单和浏览器前进/后退）
const isInternalNavigation = ref(false)

// 加载配置模块
async function loadModules() {
  try {
    loading.value = true
    const { data } = await getModuleTree()
    modules.value = data
  }
  finally {
    loading.value = false
  }
}

// 导航到指定模块和分组（使用 query 参数，replace 避免触发新标签页）
function navigateTo(moduleKey: string, groupKey?: string) {
  router.replace({
    path: '/settings/config',
    query: {
      module: moduleKey,
      ...(groupKey ? { group: groupKey } : {}),
    },
  })
}

// 从路由参数初始化状态
async function initFromRoute() {
  const { module: moduleKey, group: groupKey } = route.query

  if (moduleKey && typeof moduleKey === 'string') {
    const mod = modules.value.find(m => m.key === moduleKey)
    if (mod) {
      activeModule.value = mod
      activeModuleKey.value = mod.key
      await loadModuleValues(mod.key)

      expandedGroups.value = mod.groups?.map(g => g.key) || []

      const targetGroup = groupKey && typeof groupKey === 'string'
        ? groupKey
        : mod.groups?.[0]?.key || ''

      if (targetGroup) {
        activeGroupKey.value = targetGroup
        if (!expandedGroups.value.includes(targetGroup)) {
          expandedGroups.value.push(targetGroup)
        }
        await nextTick()
        const el = document.getElementById(`group-panel-${targetGroup}`)
        el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }
  }
  else if (modules.value.length > 0) {
    // 无 URL 参数时，导航到第一个模块
    const firstModule = modules.value[0]
    navigateTo(firstModule.key, firstModule.groups?.[0]?.key)
  }
}

// 打开模块（仅加载数据，不改变 group 选择）
async function handleModuleOpen(module: Module) {
  activeModule.value = module
  activeModuleKey.value = module.key
  await loadModuleValues(module.key)
  expandedGroups.value = module.groups?.map(g => g.key) || []

  // 如果没有选中 group，默认选第一个
  if (!activeGroupKey.value || !module.groups?.find(g => g.key === activeGroupKey.value)) {
    const firstGroup = module.groups?.[0]?.key || ''
    activeGroupKey.value = firstGroup
    navigateTo(module.key, firstGroup)
  }
}

// 选择模块（用于刷新按钮等手动触发场景）
async function handleModuleSelect(module: Module) {
  activeModule.value = module
  activeModuleKey.value = module.key

  // 加载配置值
  await loadModuleValues(module.key)

  // 默认展开所有 group
  expandedGroups.value = module.groups?.map(g => g.key) || []
  const firstGroup = module.groups?.[0]?.key || ''

  // 更新 URL
  navigateTo(module.key, firstGroup)
  activeGroupKey.value = firstGroup
}

// 加载模块配置值
async function loadModuleValues(moduleKey: string) {
  try {
    loading.value = true
    const { data } = await getModuleValues(moduleKey)
    configValues.value = data
    allFormData.value = {}
  }
  finally {
    loading.value = false
  }
}

// 左侧菜单选择 group
async function handleMenuSelect(groupKey: string) {
  activeGroupKey.value = groupKey

  // 确保 collapse 已展开
  if (!expandedGroups.value.includes(groupKey)) {
    expandedGroups.value.push(groupKey)
  }

  // 标记为内部导航，防止 watch 重复滚动
  isInternalNavigation.value = true

  // 先滚动
  await nextTick()
  const el = document.getElementById(`group-panel-${groupKey}`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  // 再更新 URL（会触发 watch，但 watch 会跳过滚动）
  navigateTo(activeModuleKey.value, groupKey)

  // 重置标志位
  isInternalNavigation.value = false
}

// 子表单字段更新
function handleFieldUpdate(groupKey: string, fieldKey: string, value: any) {
  if (!allFormData.value[groupKey]) {
    allFormData.value[groupKey] = {}
  }
  allFormData.value[groupKey][fieldKey] = value
}

// 重置当前模块所有表单
function handleReset() {
  allFormData.value = {}
  if (activeModule.value) {
    loadModuleValues(activeModule.value.key)
  }
}

// 保存全部配置
async function handleSaveAll() {
  if (!activeModule.value) {
    return
  }

  try {
    saving.value = true

    // 合并原始值和修改值
    const mergedData: Record<string, any> = {}
    activeModule.value.groups?.forEach((group) => {
      const overrides = allFormData.value[group.key] || {}
      const original = configValues.value[group.key] || {}
      mergedData[group.key] = { ...original, ...overrides }
    })

    await updateModuleConfigValues(activeModule.value.key, mergedData)
    ElMessage.success('保存成功')
    await loadModuleValues(activeModule.value.key)
    allFormData.value = {}
  }
  finally {
    saving.value = false
  }
}

// 获取 group 的有效值（原始值 + 覆盖值）
function getGroupValues(groupKey: string) {
  const original = configValues.value[groupKey] || {}
  const overrides = allFormData.value[groupKey] || {}
  return { ...original, ...overrides }
}

// 监听路由参数变化（处理浏览器前进/后退）
watch(
  () => route.query,
  async (query) => {
    // 内部导航触发的 URL 变化，跳过滚动
    if (isInternalNavigation.value) {
      return
    }

    const { module: moduleKey, group: groupKey } = query

    if (moduleKey && typeof moduleKey === 'string') {
      // 模块变化时重新加载
      if (moduleKey !== activeModuleKey.value) {
        const mod = modules.value.find(m => m.key === moduleKey)
        if (mod) {
          activeModule.value = mod
          activeModuleKey.value = mod.key
          await loadModuleValues(mod.key)
          expandedGroups.value = mod.groups?.map(g => g.key) || []
        }
      }

      const targetGroup = groupKey && typeof groupKey === 'string'
        ? groupKey
        : activeModule.value?.groups?.[0]?.key || ''

      if (targetGroup && targetGroup !== activeGroupKey.value) {
        activeGroupKey.value = targetGroup
        if (!expandedGroups.value.includes(targetGroup)) {
          expandedGroups.value.push(targetGroup)
        }
        await nextTick()
        const el = document.getElementById(`group-panel-${targetGroup}`)
        el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }
  },
)

onMounted(async () => {
  await loadModules()
  await initFromRoute()
})
</script>

<template>
  <div class="config-page h-full flex flex-col">
    <!-- 标题栏 -->
    <div class="config-header">
      <div class="config-header-left">
        <h2 class="config-header-title">
          <el-icon class="mr-2">
            <i class="i-ep:setting" />
          </el-icon>
          系统配置
        </h2>
        <p class="config-header-desc">
          管理系统全局配置参数，包括订单、支付、通知等模块
        </p>
      </div>
      <div class="config-header-actions">
        <el-button @click="handleReset">
          重置
        </el-button>
        <el-button type="primary" :loading="saving" @click="handleSaveAll">
          保存全部
        </el-button>
        <el-tooltip content="刷新配置" placement="top">
          <el-button circle @click="activeModule && handleModuleSelect(activeModule)">
            <el-icon><i class="i-ep:refresh" /></el-icon>
          </el-button>
        </el-tooltip>
      </div>
    </div>

    <!-- 左侧模块+分组菜单 + 右侧内容 -->
    <div class="config-body flex flex-1 overflow-hidden">
      <!-- 左侧模块+分组菜单 -->
      <div class="config-sidebar">
        <el-menu
          :default-openeds="[activeModuleKey]"
          :default-active="activeGroupKey"
          :unique-opened="true"
          @select="handleMenuSelect"
          @open="(key: string) => { const m = modules.find(m => m.key === key); if (m) handleModuleOpen(m) }"
        >
          <el-sub-menu
            v-for="module in modules"
            :key="module.key"
            :index="module.key"
          >
            <template #title>
              <div class="module-title">
                <el-icon v-if="module.icon">
                  <i :class="module.icon" />
                </el-icon>
                <span>{{ module.name }}</span>
              </div>
            </template>

            <el-menu-item
              v-for="group in module.groups"
              :key="group.key"
              :index="group.key"
            >
              <span>{{ group.name }}</span>
            </el-menu-item>
          </el-sub-menu>
        </el-menu>
      </div>

      <!-- 右侧折叠面板内容 -->
      <div ref="contentRef" class="config-content flex-1 overflow-y-auto p-5">
        <div v-if="activeModule" class="config-content-inner">
          <el-collapse v-model="expandedGroups">
            <el-collapse-item
              v-for="group in activeModule.groups"
              :id="`group-panel-${group.key}`"
              :key="group.key"
              :name="group.key"
            >
              <template #title>
                <div class="collapse-title">
                  <span class="font-medium">{{ group.name }}</span>
                  <span v-if="group.description" class="ml-2 text-xs text-gray-400">{{ group.description }}</span>
                </div>
              </template>

              <ConfigForm
                :items="group.items || []"
                :values="getGroupValues(group.key)"
                @update="(key: string, value: any) => handleFieldUpdate(group.key, key, value)"
              />
            </el-collapse-item>
          </el-collapse>
        </div>

        <el-empty
          v-else
          description="请选择配置模块"
          :image-size="120"
        />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.config-page {
  background: var(--el-bg-color-page);
}

.config-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--el-bg-color);
  border-bottom: 1px solid var(--el-border-color);

  &-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--el-text-color-primary);
    display: flex;
    align-items: center;
    margin: 0;
  }

  &-desc {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    margin: 4px 0 0;
  }

  &-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }
}

.config-sidebar {
  width: 220px;
  min-width: 220px;
  background: var(--el-bg-color);
  border-right: 1px solid var(--el-border-color);
  overflow-y: auto;

  .module-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.3px;
  }

  :deep(.el-menu) {
    border-right: none;
    background: transparent;
  }

  // 父级菜单 (Module)
  :deep(.el-sub-menu__title) {
    height: 56px;
    line-height: 56px;
    color: var(--el-text-color-primary);
    font-weight: 600;
    transition: all 0.25s ease;

    &:hover {
      background-color: var(--el-fill-color-light);
    }
  }

  // 子级菜单 (Group)
  :deep(.el-menu-item) {
    height: 46px;
    line-height: 46px;
    padding-left: 60px !important;
    color: var(--el-text-color-regular);
    font-size: 13px;
    font-weight: 400;
    position: relative;
    transition: all 0.25s ease;

    // 竖线指示器
    &::before {
      content: '';
      position: absolute;
      left: 24px;
      top: 50%;
      transform: translateY(-50%);
      width: 2px;
      height: 20px;
      background-color: var(--el-border-color);
      border-radius: 1px;
      opacity: 0.5;
      transition: all 0.25s ease;
    }

    &:hover {
      background-color: var(--el-fill-color-light);

      &::before {
        opacity: 1;
        background-color: var(--el-color-primary-light-3);
      }
    }

    &.is-active {
      color: var(--el-color-primary);
      background-color: var(--el-color-primary-light-9);
      font-weight: 500;

      &::before {
        opacity: 1;
        background-color: var(--el-color-primary);
      }
    }
  }
}

.config-content {
  .config-content-inner {
    max-width: 900px;
  }

  :deep(.el-collapse) {
    border: none;
  }

  :deep(.el-collapse-item) {
    margin-bottom: 12px;
    background: var(--el-bg-color);
    border-radius: 6px;
    border: 1px solid var(--el-border-color-lighter);
    overflow: hidden;

    .el-collapse-item__header {
      padding: 0 20px;
      height: 48px;
      line-height: 48px;
      background: transparent;
      border-bottom: none;
      font-size: 14px;
    }

    .el-collapse-item__wrap {
      border-bottom: none;
    }

    .el-collapse-item__content {
      padding: 10px 20px 20px;
    }
  }
}

.collapse-title {
  display: flex;
  align-items: center;
  gap: 4px;
}

// 暗色模式适配 - 使用 :global 处理 html.dark 选择器
:global(html.dark) .config-sidebar {
  :deep(.el-menu-item) {
    &::before {
      background-color: var(--el-border-color-darker);
    }

    &:hover::before {
      background-color: var(--el-color-primary-light-5);
    }

    &.is-active::before {
      background-color: var(--el-color-primary);
    }
  }
}
</style>
