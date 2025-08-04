<template>
  <el-dialog
    class="ba-operate-dialog"
    :close-on-click-modal="false"
    :model-value="baTable.form.operate ? true : false"
    @close="baTable.toggleForm"
    width="70%"
  >
    <template #header>
      <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">
        {{ baTable.form.operate === 'Add' ? '添加彩票赔率' : '编辑彩票赔率' }}
      </div>
    </template>
    <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
      <div
        class="ba-operate-form"
        :class="'ba-' + baTable.form.operate + '-form'"
        :style="'width: calc(100% - ' + baTable.form.labelWidth / 2 + 'px)'"
      >
        <el-form
          ref="formRef"
          @keyup.enter="baTable.onSubmit(formRef)"
          :model="baTable.form.items"
          label-position="right"
          :label-width="baTable.form.labelWidth + 'px'"
          :rules="rules"
        >
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem
                type="remoteSelect"
                label="所属彩种"
                v-model="baTable.form.items!.lottery_id"
                prop="lottery_id"
                placeholder="请选择所属彩种"
                :input-attr="{
                    field: 'type_name',       
                    id: 'type_code', 
                    remoteUrl: '/admin/lottery.LotteryType/select',
                }"
            />
            </el-col>
            <el-col :span="12">
              <FormItem
                label="玩法名称"
                type="string"
                v-model="baTable.form.items!.type_name"
                prop="type_name"
                placeholder="请输入玩法名称"
              />
            </el-col>
          </el-row>
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem
                label="玩法键值"
                type="string"
                v-model="baTable.form.items!.type_key"
                prop="type_key"
                placeholder="请输入玩法键值"
              />
            </el-col>
            <el-col :span="12">
              <FormItem
                label="排序(大-小)"
                type="string"
                v-model="baTable.form.items!.weigh"
                prop="weigh"
                placeholder="请输入排序"
              />
            </el-col>
          </el-row>
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem
                label="最低购买金额"
                type="number"
                v-model.number="baTable.form.items!.min_price"
                prop="min_price"
                placeholder="请输入最低购买金额（0为不限制）"
              />
            </el-col>
            <el-col :span="12">
              <FormItem
                label="最高购买金额"
                type="number"
                v-model.number="baTable.form.items!.max_price"
                prop="max_price"
                placeholder="请输入最高购买金额（0为不限制）"
              />
            </el-col>
          </el-row>
          
          <!-- 赔率配置 -->
          <el-card class="bonus-configs">
            <template #header>
              <div class="config-header">
                <span>赔率配置</span>
                <el-button type="primary" size="small" @click="addBonusConfig">
                  <el-icon><Plus /></el-icon>
                  添加赔率
                </el-button>
              </div>
            </template>
            <div v-for="(config, index) in bonusJsonArray" :key="index" class="config-item">
              <el-row :gutter="10">
                <el-col :span="8">
                  <el-input 
                    v-model="config.key" 
                    placeholder="赔率键值" 
                    :class="{ 'error-input': config.keyError }"
                    @input="validateBonusValues"
                  />
                  <div v-if="config.keyError" class="error-message">{{ config.keyError }}</div>
                </el-col>
                <el-col :span="8">
                  <el-input-number 
                    v-model="config.value" 
                    placeholder="赔率值" 
                    :min="0" 
                    :precision="2" 
                    style="width: 100%" 
                    clearable 
                    :controls="false"
                    :class="{ 'error-input': config.valueError }"
                    @change="validateBonusValues"
                  />
                  <div v-if="config.valueError" class="error-message">{{ config.valueError }}</div>
                </el-col>
                <el-col :span="8">
                  <el-button 
                    type="danger" 
                    size="small" 
                    @click="removeBonusConfig(index)"
                    :disabled="bonusJsonArray.length <= 1"
                  >
                    <el-icon><Delete /></el-icon>
                    删除
                  </el-button>
                </el-col>
              </el-row>
            </div>
          </el-card>
        </el-form>
      </div>
    </el-scrollbar>
    <template #footer>
      <div :style="'width: calc(100% - ' + baTable.form.labelWidth / 1.8 + 'px)'">
        <el-button @click="baTable.toggleForm('')">取消</el-button>
        <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
          {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? '保存并编辑下一项' : '保存' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { reactive, ref, inject, onMounted, watch } from 'vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { ElForm, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'
import { baTableApi } from '/@/api/common'
import { Plus, Delete } from '@element-plus/icons-vue'

const formRef = ref<InstanceType<typeof ElForm>>()
const baTable = inject('baTable') as baTableClass

const lotteryTypeOptions = ref({})
const bonusJsonArray = ref([
  {
    key: '0',
    value: null,
    keyError: '',
    valueError: ''
  }
])

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  lottery_id: [buildValidatorData({ name: 'required', title: '彩种类型' })],
  type_name: [buildValidatorData({ name: 'required', title: '玩法名称' })],
  type_key: [buildValidatorData({ name: 'required', title: '键值' })],
  min_price: [buildValidatorData({ name: 'required', title: '最低购买金额' })],
  max_price: [buildValidatorData({ name: 'required', title: '最高购买金额' })],
})

const addBonusConfig = () => {
  const nextKey = bonusJsonArray.value.length.toString()
  bonusJsonArray.value.push({
    key: nextKey,
    value: null,
    keyError: '',
    valueError: ''
  })
  updateBonusJson()
}

const removeBonusConfig = (index: number) => {
  if (bonusJsonArray.value.length > 1) {
    bonusJsonArray.value.splice(index, 1)
    updateBonusJson()
  }
}

const validateBonusValues = () => {
  let hasError = false
  const keys = new Set()
  
  bonusJsonArray.value.forEach((config, index) => {
    // 重置错误信息
    config.keyError = ''
    config.valueError = ''
    
    // 验证key不能为空
    if (!config.key && config.key !== '0') {
      config.keyError = '键值不能为空'
      hasError = true
    }
    
    // 验证key不能重复
    if (config.key || config.key === '0') {
      if (keys.has(config.key)) {
        config.keyError = '键值不能重复'
        hasError = true
      } else {
        keys.add(config.key)
      }
    }
    
    // 验证value不能为空且必须大于1
    if (config.value === null || config.value === undefined || config.value === '') {
      config.valueError = '赔率值不能为空'
      hasError = true
    } else if (config.value <= 1) {
      config.valueError = '赔率值必须大于1'
      hasError = true
    }
  })
  
  updateBonusJson()
  return !hasError
}

const updateBonusJson = () => {
  if (baTable.form.items) {
    const bonusObj: Record<string, number> = {}
    bonusJsonArray.value.forEach(config => {
      if ((config.key || config.key === '0') && config.value !== null && config.value !== undefined) {
        bonusObj[config.key] = config.value
      }
    })
    // 避免递归更新，只在数据真正变化时更新
    const currentBonusJson = JSON.stringify(baTable.form.items.bonus_json)
    const newBonusJson = JSON.stringify(bonusObj)
    if (currentBonusJson !== newBonusJson) {
      baTable.form.items.bonus_json = bonusObj
    }
  }
}

const initFormData = () => {
  if (baTable.form.items && baTable.form.items.bonus_json) {
    const bonusJson = baTable.form.items.bonus_json
    let newBonusArray: any[] = []
    
    // 处理不同格式的bonus_json数据
    if (Array.isArray(bonusJson)) {
      // 标准数组格式: [{key: "大", value: 1.8}, {key: "小", value: 1.8}]
      if (bonusJson.length > 0 && typeof bonusJson[0] === 'object' && bonusJson[0].hasOwnProperty('key')) {
        newBonusArray = bonusJson.map((item: any) => ({
          key: String(item.key || ''),
          value: parseFloat(item.value) || null,
          keyError: '',
          valueError: ''
        }))
      }
      // 简单数组格式: ["2", "3"] 或 ["大", "小"]
      else {
        newBonusArray = bonusJson.map((item: any, index: number) => {
          // 如果数组项是数字字符串，尝试解析为赔率值
          const numValue = parseFloat(item)
          if (!isNaN(numValue) && numValue > 0) {
            return {
              key: String(index),
              value: numValue,
              keyError: '',
              valueError: ''
            }
          } else {
            // 如果不是数字，则作为key处理
            return {
              key: String(item || index),
              value: null,
              keyError: '',
              valueError: ''
            }
          }
        })
      }
    }
    // 对象格式: {"大": 1.8, "小": 1.8}
    else if (typeof bonusJson === 'object' && bonusJson !== null) {
      newBonusArray = Object.entries(bonusJson).map(([key, value]: [string, any]) => ({
        key: String(key),
        value: parseFloat(value) || null,
        keyError: '',
        valueError: ''
      }))
    }
    // JSON字符串格式
    else if (typeof bonusJson === 'string') {
      try {
        const parsed = JSON.parse(bonusJson)
        if (typeof parsed === 'object' && parsed !== null) {
          newBonusArray = Object.entries(parsed).map(([key, value]: [string, any]) => ({
            key: String(key),
            value: parseFloat(value) || null,
            keyError: '',
            valueError: ''
          }))
        }
      } catch (e) {
        console.error('解析bonus_json失败:', e)
        newBonusArray = [{
          key: '0',
          value: null,
          keyError: '',
          valueError: ''
        }]
      }
    }
    
    // 确保至少有一行数据
    if (newBonusArray.length === 0) {
      newBonusArray = [{
        key: '0',
        value: null,
        keyError: '',
        valueError: ''
      }]
    }
    
    bonusJsonArray.value = newBonusArray
  } else {
    // 默认至少保留一行
    bonusJsonArray.value = [
      {
        key: '0',
        value: null,
        keyError: '',
        valueError: ''
      }
    ]
  }
}

// 监听表单操作变化，加载相关数据
watch(
  () => baTable.form.operate,
  (newVal) => {
    if (newVal) {
      loadFormData()
    }
  }
)

// 监听表单数据变化，用于编辑模式
watch(
  () => baTable.form.items?.bonus_json,
  (newVal, oldVal) => {
    if (newVal && baTable.form.operate === 'Edit' && JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
      initFormData()
    }
  },
  { deep: true }
)

// 监听赔率配置变化，同步到表单数据
watch(
  () => bonusJsonArray.value,
  () => {
    updateBonusJson()
  },
  { deep: true, flush: 'post' }
)

const loadFormData = async () => {
  try {
    
    // 如果是编辑模式，初始化表单数据
    if (baTable.form.operate === 'Edit') {
      setTimeout(() => {
        initFormData()
      }, 100)
    } else if (baTable.form.operate === 'Add') {
      // 添加模式时重置为默认配置
      bonusJsonArray.value = [
        {
          key: '0',
          value: null,
          keyError: '',
          valueError: ''
        }
      ]
      updateBonusJson()
    }
  } catch (error) {
    console.error('加载表单数据失败:', error)
  }
}

onMounted(() => {
  if (baTable.form.operate) {
    loadFormData()
  }
})
</script>

<style scoped lang="scss">
.bonus-configs {
  margin: 20px 0;
  
  .config-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .config-item {
    padding: 5px;
    
    &:last-child {
      margin-bottom: 0;
    }
  }
}

.error-input {
  border-color: #f56c6c !important;
}

.error-message {
  color: #f56c6c;
  font-size: 12px;
  margin-top: 4px;
}
</style>