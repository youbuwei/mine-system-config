/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { ResponseStruct } from '#/global'

export interface ConfigGroupVo {
  id: number
  key: string
  name: string
  description?: string
  icon?: string
  sort: number
  children?: ConfigGroupVo[]
  fields?: ConfigFieldVo[]
}

export interface ConfigFieldVo {
  id: number
  key: string
  name: string
  description?: string
  type: string
  options?: { label: string, value: any }[]
  validation?: Record<string, any>
  default_value?: any
  placeholder?: string
  tooltip?: string
  sort: number
  is_encrypted: number
}

export interface ConfigValueVo {
  path: string
  value: any
}

/**
 * 获取配置分组树
 */
export function getConfigGroups(): Promise<ResponseStruct<ConfigGroupVo[]>> {
  return useHttp().get('/admin/config/groups')
}

/**
 * 获取分组下的字段
 */
export function getGroupFields(groupId: number): Promise<ResponseStruct<ConfigFieldVo[]>> {
  return useHttp().get(`/admin/config/groups/${groupId}/fields`)
}

/**
 * 获取配置值
 */
export function getConfigValues(group: string, scope = 'default'): Promise<ResponseStruct<Record<string, any>>> {
  return useHttp().get('/admin/config/values', {
    params: { group, scope },
  })
}

/**
 * 获取单个配置值
 */
export function getConfigValue(path: string, scope = 'default'): Promise<ResponseStruct<ConfigValueVo>> {
  return useHttp().get(`/admin/config/values/${path}`, {
    params: { scope },
  })
}

/**
 * 更新单个配置值
 */
export function updateConfigValue(path: string, value: any, scope = 'default'): Promise<ResponseStruct<null>> {
  return useHttp().put(`/admin/config/values/${path}`, {
    value,
    scope,
  })
}

/**
 * 批量更新配置值
 */
export function batchUpdateConfigValues(data: Record<string, any>, scope = 'default'): Promise<ResponseStruct<null>> {
  return useHttp().post('/admin/config/values/batch', {
    data,
    scope,
  })
}

/**
 * 通过分组批量更新配置值
 */
export function updateGroupConfigValues(groupId: number, data: Record<string, any>, scope = 'default'): Promise<ResponseStruct<null>> {
  return useHttp().post(`/admin/config/groups/${groupId}/values`, {
    data,
    scope,
  })
}

/**
 * 获取配置变更日志
 */
export function getConfigLogs(fieldId: number, scope = 'default', limit = 50): Promise<ResponseStruct<any[]>> {
  return useHttp().get(`/admin/config/logs/${fieldId}`, {
    params: { scope, limit },
  })
}

/**
 * 刷新配置缓存
 */
export function refreshConfigCache(): Promise<ResponseStruct<null>> {
  return useHttp().post('/admin/config/cache/refresh')
}

// ========== 3级配置系统新增接口 ==========

export interface ConfigModuleVo {
  id: number
  key: string
  name: string
  description?: string
  icon?: string
  sort: number
  groups?: ConfigGroupVo[]
}

/**
 * 获取配置模块树（完整3级结构）
 */
export function getModuleTree(): Promise<ResponseStruct<ConfigModuleVo[]>> {
  return useHttp().get('/admin/config/modules')
}

/**
 * 获取模块下的分组
 */
export function getModuleGroups(moduleKey: string): Promise<ResponseStruct<ConfigGroupVo[]>> {
  return useHttp().get(`/admin/config/modules/${moduleKey}/groups`)
}

/**
 * 获取模块下所有配置值
 */
export function getModuleValues(moduleKey: string, scope = 'default'): Promise<ResponseStruct<Record<string, any>>> {
  return useHttp().get(`/admin/config/modules/${moduleKey}/values`, {
    params: { scope },
  })
}

/**
 * 通过模块批量更新配置值
 */
export function updateModuleConfigValues(moduleKey: string, data: Record<string, any>, scope = 'default'): Promise<ResponseStruct<null>> {
  return useHttp().post(`/admin/config/modules/${moduleKey}/values`, {
    data,
    scope,
  })
}

/**
 * 同步配置定义
 */
export function syncConfig(): Promise<ResponseStruct<{ modules: number, groups: number, items: number }>> {
  return useHttp().post('/admin/config/sync')
}
