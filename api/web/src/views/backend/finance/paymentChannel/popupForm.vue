<template>
  <el-dialog
    class="ba-operate-dialog"
    :close-on-click-modal="false"
    :model-value="baTable.form.operate ? true : false"
    @close="baTable.toggleForm"
    width="70%"
  >
    <template #header>
      <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">{{ baTable.form.operate === 'Add' ? '添加支付通道' : '编辑支付通道' }}</div>
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
                label="通道代码"
                type="string"
                v-model="baTable.form.items!.channel_code"
                prop="channel_code"
                placeholder="请输入通道代码"
              />
            </el-col>
          </el-row>
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem
                label="内部名称"
                type="string"
                v-model="baTable.form.items!.internal_name"
                prop="internal_name"
                placeholder="请输入内部名称"
              />
            </el-col>
            <el-col :span="12">
              <FormItem
                label="外部名称"
                type="string"
                v-model="baTable.form.items!.external_name"
                prop="external_name"
                placeholder="请输入外部名称"
              />
            </el-col>
          </el-row>
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem
                label="商户ID"
                type="string"
                v-model="baTable.form.items!.merchant_id"
                prop="merchant_id"
                placeholder="请输入商户ID"
              />
            </el-col>
            <el-col :span="12">
              <FormItem
                label="密钥"
                type="string"
                v-model="baTable.form.items!.secret_key"
                prop="secret_key"
                placeholder="请输入密钥"
              />
            </el-col>
          </el-row>
          
          <FormItem
            label="回调IP"
            type="string"
            v-model="baTable.form.items!.callback_ip"
            prop="callback_ip"
            placeholder="请输入回调IP"
          />
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem
                label="是否启用"
                type="radio"
                v-model="baTable.form.items!.is_enabled"
                prop="is_enabled"
                :data="{
                  content: {
                    0: '禁用',
                    1: '启用',
                  },
                }"
              />
            </el-col>
            <el-col :span="12">
              <FormItem
                label="排序"
                type="number"
                v-model.number="baTable.form.items!.sort_order"
                prop="sort_order"
                placeholder="请输入排序值"
              />
            </el-col>
          </el-row>
          
          <!-- 通道配置 -->
          <el-card class="channel-configs">
            <template #header>
              <div class="config-header">
                <span>通道编码配置</span>
                <el-button type="primary" size="small" @click="addChannelConfig">
                  <el-icon><Plus /></el-icon>
                  添加编码
                </el-button>
              </div>
            </template>
            <div v-for="(config, index) in channelConfigs" :key="index" class="config-item">
              <el-row :gutter="10">
                <el-col :span="6">
                  <el-select v-model="config.method_id" placeholder="选择支付方式">
                    <el-option
                      v-for="method in paymentMethods"
                      :key="method.id"
                      :label="method.method_name"
                      :value="method.id"
                    />
                  </el-select>
                </el-col>
                <el-col :span="4">
                  <el-input v-model="config.channel_code" placeholder="通道编码" />
                </el-col>
                <el-col :span="3">
                  <el-input-number v-model="config.min_amount" placeholder="最小充值" :min="1" style="width: 100%" clearable :controls="false" />
                </el-col>
                <el-col :span="3">
                  <el-input-number v-model="config.max_amount" placeholder="最大充值" :min="1" style="width: 100%" clearable :controls="false" />
                </el-col>
                <el-col :span="3">
                  <el-input-number v-model="config.fee_rate" placeholder="费率" :min="0" :max="100" :precision="2" style="width: 100%" clearable :controls="false" />
                </el-col>
                <el-col :span="2">
                  <el-switch v-model="config.is_enabled" active-text="" inactive-text="" />
                </el-col>
                <el-col :span="3">
                  <el-button 
                    type="danger" 
                    size="small" 
                    @click="removeChannelConfig(index)"
                    :disabled="channelConfigs.length <= 1"
                  >
                    <el-icon><Delete /></el-icon>
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
import { reactive, ref, inject, onMounted, computed, watch, nextTick } from 'vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { ElForm, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'
import { baTableApi } from '/@/api/common'
import { Plus, Delete } from '@element-plus/icons-vue'

const formRef = ref<InstanceType<typeof ElForm>>()
const baTable = inject('baTable') as baTableClass

const paymentMethods = ref([])
const paymentMethodOptions = ref({})
const channelConfigs = ref([
  {
    method_id: '',
    channel_code: '',
    min_amount: null,
    max_amount: null,
    fee_rate: null,
    is_enabled: true
  }
])

const enabledPaymentMethodOptions = computed(() => {
  const options: Record<string, string> = {}
  paymentMethods.value.forEach((method: any) => {
    if (method.status === 1) { // 只显示已开启的支付方式
      options[method.code] = method.name
    }
  })
  return options
})

const channelCodeOptions = computed(() => {
  const options: Record<string, string> = {}
  channelConfigs.value.forEach((config) => {
    if (config.channel_code) {
      options[config.channel_code] = config.channel_code
    }
  })
  return options
})

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  channel_code: [buildValidatorData({ name: 'required', title: '通道代码' })],
  internal_name: [buildValidatorData({ name: 'required', title: '内部名称' })],
  external_name: [buildValidatorData({ name: 'required', title: '外部名称' })],
  merchant_id: [buildValidatorData({ name: 'required', title: '商户ID' })],
  secret_key: [buildValidatorData({ name: 'required', title: '密钥' })],
  is_enabled: [buildValidatorData({ name: 'required', title: '是否启用' })],
  sort_order: [buildValidatorData({ name: 'required', title: '排序' })],
})

const addChannelConfig = () => {
  channelConfigs.value.push({
    method_id: '',
    channel_code: '',
    min_amount: null,
    max_amount: null,
    fee_rate: null,
    is_enabled: true
  })
}

const removeChannelConfig = (index: number) => {
  if (channelConfigs.value.length > 1) {
    channelConfigs.value.splice(index, 1)
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

// 防止循环更新的标志
const isUpdatingFromForm = ref(false)
const isUpdatingFromConfig = ref(false)

// 监听表单数据变化，确保编辑时数据正确填充
watch(
  () => baTable.form.items?.channel_params,
  (newVal) => {
    if (isUpdatingFromConfig.value) return // 防止循环调用
    
    if (newVal && baTable.form.operate === 'Edit' && Array.isArray(newVal)) {
      isUpdatingFromForm.value = true
      // 将后端返回的channel_params数组转换为前端需要的格式
      channelConfigs.value = newVal.map((param: any) => ({
        method_id: param.method_id ? Number(param.method_id) : '',
        channel_code: param.channel_code || '',
        min_amount: param.min_amount ? Number(param.min_amount) : null,
        max_amount: param.max_amount ? Number(param.max_amount) : null,
        fee_rate: param.fee_rate ? Number(param.fee_rate) : null,
        is_enabled: param.is_enabled === '1' || param.is_enabled === 1 || param.is_enabled === true
      }))
      nextTick(() => {
        isUpdatingFromForm.value = false
      })
    }
  },
  { deep: true }
)

// 监听通道配置变化，同步到表单数据
watch(
  () => channelConfigs.value,
  (newVal) => {
    if (isUpdatingFromForm.value || !baTable.form.operate) return // 防止循环调用和初始化时触发
    
    if (baTable.form.items && newVal && newVal.length > 0) {
      isUpdatingFromConfig.value = true
      baTable.form.items.channel_params = [...newVal] // 使用浅拷贝避免引用问题
      nextTick(() => {
        isUpdatingFromConfig.value = false
      })
    }
  },
  { deep: true }
)

const loadFormData = async () => {
  try {
    console.log('开始加载表单数据...')
    
    // 加载支付方式选项
    const methodApi = new baTableApi('/admin/finance.PaymentMethod/')
    console.log('调用支付方式API...')
    
    const methodRes = await methodApi.postData('getOptions', {})
    console.log('支付方式API响应:', methodRes)
    
    if (methodRes && methodRes.code === 1) {
      paymentMethods.value = methodRes.data || []
      paymentMethodOptions.value = {}
      console.log('支付方式数据加载成功:', paymentMethods.value)
    } else {
      console.warn('支付方式API返回异常:', methodRes)
      // 设置默认空数据，避免页面卡死
      paymentMethods.value = []
      paymentMethodOptions.value = {}
    }
    
    // 如果是添加模式，重置为默认配置
    if (baTable.form.operate === 'Add') {
      channelConfigs.value = [
        {
          method_id: '',
          channel_code: '',
          min_amount: null,
          max_amount: null,
          fee_rate: null,
          is_enabled: true
        }
      ]
    }
    
    console.log('表单数据加载完成')
  } catch (error) {
    console.error('加载表单数据失败:', error)
    // 设置默认空数据，避免页面卡死
    paymentMethods.value = []
    paymentMethodOptions.value = {}
    
    // 如果是添加模式，重置为默认配置
    if (baTable.form.operate === 'Add') {
      channelConfigs.value = [
        {
          method_id: '',
          channel_code: '',
          min_amount: null,
          max_amount: null,
          fee_rate: null,
          is_enabled: true
        }
      ]
    }
  }
}

onMounted(() => {
  if (baTable.form.operate) {
    loadFormData()
  }
})
</script>

<style scoped lang="scss">
.channel-configs {
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


</style>