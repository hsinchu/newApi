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
          <FormItem label="彩票类型代码" type="string" v-model="baTable.form.items!.type_code" prop="type_code" placeholder="请输入彩票类型代码" />
          
          <FormItem label="彩票类型名称" type="string" v-model="baTable.form.items!.type_name" prop="type_name" placeholder="请输入彩票类型名称" />
          
          <el-form-item label="彩票分类" prop="category">
            <el-select v-model="baTable.form.items!.category" placeholder="请选择彩票分类" style="width: 100%">
              <el-option
                v-for="item in categoryOptions"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-form-item>
          
          <FormItem label="描述" type="textarea" v-model="baTable.form.items!.description" prop="description" placeholder="请输入描述" />
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem label="最小投注金额" type="number" v-model="baTable.form.items!.min_bet_amount" prop="min_bet_amount" placeholder="请输入最小投注金额" :step="0.01" :min="0.01" />
            </el-col>
            <el-col :span="12">
              <FormItem label="最大投注金额" type="number" v-model="baTable.form.items!.max_bet_amount" prop="max_bet_amount" placeholder="请输入最大投注金额" :step="0.01" :min="0.01" />
            </el-col>
          </el-row>
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem label="排序" type="number" v-model="baTable.form.items!.sort_order" prop="sort_order" placeholder="请输入排序" :step="1" :min="0" />
            </el-col>
            <el-col :span="12">
              <el-form-item label="启用状态" prop="is_enabled">
                <el-switch
                  v-model="baTable.form.items!.is_enabled"
                  :active-value="1"
                  :inactive-value="0"
                />
              </el-form-item>
            </el-col>
          </el-row>
          
          <FormItem label="开奖规则" type="textarea" v-model="baTable.form.items!.draw_rules" prop="draw_rules" placeholder="请输入开奖规则" :rows="3" />
          
          <FormItem label="投注规则" type="textarea" v-model="baTable.form.items!.bet_rules" prop="bet_rules" placeholder="请输入投注规则" :rows="3" />
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
  { label: '体育单场', value: 'SPORTS_SINGLE' }
]

const enabledOptions = [
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 }
]

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  type_code: [buildValidatorData({ name: 'required', title: '彩票类型代码' })],
  type_name: [buildValidatorData({ name: 'required', title: '彩票类型名称' })],
  category: [buildValidatorData({ name: 'required', title: '彩票分类' })],
  min_bet_amount: [
    buildValidatorData({ name: 'required', title: '最小投注金额' }),
  ],
  max_bet_amount: [
    buildValidatorData({ name: 'required', title: '最大投注金额' }),
  ],
  sort_order: [buildValidatorData({ name: 'integer', title: '排序' })]
})
</script>

<style scoped lang="scss"></style>