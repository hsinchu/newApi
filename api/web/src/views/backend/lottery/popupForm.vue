<template>
  <el-dialog
    class="ba-operate-dialog"
    :close-on-click-modal="false"
    :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
    @close="baTable.toggleForm"
    width="50%"
  >
    <template #header>
      <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">{{ baTable.form.operate === 'Add' ? '添加' : '编辑' }}</div>
    </template>
    <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
      <div
        class="ba-operate-form"
        :class="'ba-' + baTable.form.operate + '-form'"
        :style="'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
      >
        <el-form
          ref="formRef"
          @keyup.enter="baTable.onSubmit(formRef)"
          :model="baTable.form.items"
          label-position="right"
          :label-width="baTable.form.labelWidth + 'px'"
          :rules="rules"
        >
          <FormItem label="彩种代码" type="string" v-model="baTable.form.items!.type_code" prop="type_code" placeholder="请输入彩种代码" />
          
          <FormItem label="彩种名称" type="string" v-model="baTable.form.items!.type_name" prop="type_name" placeholder="请输入彩种名称" />
          
          <el-form-item label="彩种分类" prop="category">
            <el-select v-model="baTable.form.items!.category" placeholder="请选择彩种分类" style="width: 100%">
              <el-option
                v-for="item in categoryOptions"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="彩票类型" prop="type_group">
            <el-select v-model="baTable.form.items!.type_group" placeholder="请选择彩票类型" style="width: 100%">
              <el-option
                v-for="item in typeGroupOptions"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-form-item>
          
          <FormItem label="彩种图标" type="image" v-model="baTable.form.items!.type_icon" />
          
          <!-- 奖池相关配置 -->
          <el-row :gutter="20">
            <el-col :span="8">
              <FormItem label="默认奖池" type="number" v-model="baTable.form.items!.default_pool" prop="default_pool" placeholder="默认奖池金额" :step="0.01" :min="0" />
            </el-col>
            <el-col :span="8">
              <FormItem label="单人占用奖池（%）" type="number" v-model="baTable.form.items!.max_pool_rate" prop="max_pool_rate" placeholder="最大占用奖池（%）" :step="0.1" :min="0" :max="100" />
            </el-col>
            <el-col :span="8">
              <FormItem label="平台服务费（%）" type="number" v-model="baTable.form.items!.bonus_system_rate" prop="bonus_system_rate" placeholder="平台服务费（%）" :step="0.1" :min="0" :max="50" />
            </el-col>
          </el-row>
          
          <!-- 投注金额配置 -->
          <el-row :gutter="20">
            <el-col :span="8">
              <FormItem label="最小投注金额" type="number" v-model="baTable.form.items!.min_bet_amount" prop="min_bet_amount" placeholder="最小投注金额" :step="0.01" :min="0.01" />
            </el-col>
            <el-col :span="8">
              <FormItem label="最大投注金额" type="number" v-model="baTable.form.items!.max_bet_amount" prop="max_bet_amount" placeholder="最大投注金额" :step="0.01" :min="0.01" />
            </el-col>
            <el-col :span="8">
              <FormItem label="每日限额" type="number" v-model="baTable.form.items!.daily_limit" prop="daily_limit" placeholder="每日投注限额" :step="0.01" :min="0" />
            </el-col>
          </el-row>
          
          <!-- 开关配置 -->
          <el-row :gutter="20">
            <el-col :span="8">
              <el-form-item label="自动开奖" prop="auto_draw">
                <el-switch
                  v-model="baTable.form.items!.auto_draw"
                  :active-value="1"
                  :inactive-value="0"
                />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <FormItem label="排序" type="number" v-model="baTable.form.items!.sort_order" prop="sort_order" placeholder="排序权重" :step="1" :min="0" />
            </el-col>
            <el-col :span="8">
              <el-form-item label="启用状态" prop="is_enabled">
                <el-switch
                  v-model="baTable.form.items!.is_enabled"
                  :active-value="1"
                  :inactive-value="0"
                />
              </el-form-item>
            </el-col>
          </el-row>
          
          <FormItem label="备注" type="textarea" v-model="baTable.form.items!.remark" prop="remark" placeholder="请输入备注" :rows="2" />
        </el-form>
      </div>
    </el-scrollbar>
    <template #footer>
      <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
        <el-button @click="baTable.toggleForm('')">取消</el-button>
        <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
          {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? '保存并编辑下一项' : '保存' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { FormInstance, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'

defineOptions({
  name: 'lottery/popupForm',
})

const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

const categoryOptions = [
  { label: '体育彩票', value: 'SPORTS' },
  { label: '福利彩票', value: 'WELFARE' },
  { label: '体育单场', value: 'SPORTS_SINGLE' },
  { label: '快彩', value: 'QUICK' }
]

const typeGroupOptions = [
  { label: 'BNB彩票', value: 'ware' },
  { label: 'BNB直播', value: 'live' },
  { label: 'BNB棋牌', value: 'chess' },
  { label: 'BNB电子', value: 'person' }
]

const enabledOptions = [
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 }
]

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  type_code: [buildValidatorData({ name: 'required', title: '彩种代码' })],
  type_name: [buildValidatorData({ name: 'required', title: '彩种名称' })],
  category: [buildValidatorData({ name: 'required', title: '彩种分类' })],
  type_group: [buildValidatorData({ name: 'required', title: '彩票类型' })],
  type_icon: [],
  // default_pool: [buildValidatorData({ name: 'number', title: '默认奖池' })],
  // bonus_pool: [buildValidatorData({ name: 'number', title: '奖金池' })],
  // bonus_system: [buildValidatorData({ name: 'number', title: '平台抽取' })],
  // max_pool_rate: [buildValidatorData({ name: 'number', title: '最大占用奖池（%）' })],
  // bonus_system_rate: [buildValidatorData({ name: 'number', title: '平台服务费（%）' })],
  // min_bet_amount: [
  //   buildValidatorData({ name: 'required', title: '最小投注金额' }),
  //   buildValidatorData({ name: 'number', title: '最小投注金额' })
  // ],
  // max_bet_amount: [
  //   buildValidatorData({ name: 'required', title: '最大投注金额' }),
  //   buildValidatorData({ name: 'number', title: '最大投注金额' })
  // ],
  // daily_limit: [buildValidatorData({ name: 'number', title: '每日限额' })],
  sort_order: [buildValidatorData({ name: 'integer', title: '排序' })],
  auto_draw: [],
  is_enabled: [],
  remark: []
})
</script>

<style scoped lang="scss">
.avatar-uploader {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    border-radius: var(--el-border-radius-small);
    box-shadow: var(--el-box-shadow-light);
    border: 1px dashed var(--el-border-color);
    cursor: pointer;
    overflow: hidden;
    width: 110px;
    height: 110px;
}
.avatar-uploader:hover {
    border-color: var(--el-color-primary);
}
.avatar {
    width: 110px;
    height: 110px;
    display: block;
}
</style>