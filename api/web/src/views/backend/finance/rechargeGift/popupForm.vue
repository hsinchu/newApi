<template>
  <el-dialog
    class="ba-operate-dialog"
    :close-on-click-modal="false"
    :model-value="baTable.form.operate ? true : false"
    @close="baTable.toggleForm"
    width="50%"
  >
    <template #header>
      <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">{{ baTable.form.operate === 'Add' ? '添加充值赠送' : '编辑充值赠送' }}</div>
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

            <FormItem
              label="最低充值金额"
              type="number"
              v-model="baTable.form.items!.charge_amount"
              prop="charge_amount"
              :input-attr="{
                step: 0.01,
                min: 0,
                precision: 2,
                placeholder: '请输入最低充值金额',
              }"
            />
            <FormItem
              label="赠送金额"
              type="number"
              v-model="baTable.form.items!.bonus_amount"
              prop="bonus_amount"
              :input-attr="{
                step: 0.01,
                min: 0,
                precision: 2,
                placeholder: '请输入赠送金额',
              }"
            />
            <FormItem
              label="生效开始时间"
              type="datetime"
              v-model="baTable.form.items!.start_time"
              prop="start_time"
              :input-attr="{
                placeholder: '请选择生效开始时间',
                format: 'YYYY-MM-DD HH:mm:ss',
                'value-format': 'YYYY-MM-DD HH:mm:ss',
              }"
            />
            <FormItem
              label="生效结束时间"
              type="datetime"
              v-model="baTable.form.items!.end_time"
              prop="end_time"
              :input-attr="{
                placeholder: '请选择生效结束时间',
                format: 'YYYY-MM-DD HH:mm:ss',
                'value-format': 'YYYY-MM-DD HH:mm:ss',
              }"
            />
            <FormItem
              label="是否启用"
              type="radio"
              v-model="baTable.form.items!.status"
              prop="status"
              :data="{
                content: {
                  0: '禁用',
                  1: '启用',
                },
              }"
            />
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
import { reactive, ref, inject } from 'vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { ElForm, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'

const formRef = ref<InstanceType<typeof ElForm>>()
const baTable = inject('baTable') as baTableClass

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  charge_amount: [
    buildValidatorData({ name: 'required', message: '请输入最低充值金额' }),
    buildValidatorData({ name: 'number', message: '最低充值金额必须是数字' }),
    {
      validator: (rule: any, value: any, callback: any) => {
        if (value <= 0) {
          callback(new Error('最低充值金额必须大于0'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ],
  bonus_amount: [
    buildValidatorData({ name: 'required', message: '请输入赠送金额' }),
    buildValidatorData({ name: 'number', message: '赠送金额必须是数字' }),
    {
      validator: (rule: any, value: any, callback: any) => {
        if (value < 0) {
          callback(new Error('赠送金额不能小于0'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ],
  start_time: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (value && baTable.form.items!.end_time && new Date(value) >= new Date(baTable.form.items!.end_time)) {
          callback(new Error('开始时间必须小于结束时间'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ],
  end_time: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (value && baTable.form.items!.start_time && new Date(value) <= new Date(baTable.form.items!.start_time)) {
          callback(new Error('结束时间必须大于开始时间'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ],
  status: [buildValidatorData({ name: 'required', message: '请选择状态' })],
})
</script>

<style scoped>
.ba-operate-form {
  padding: 20px;
}

.ba-table-form-scrollbar {
  max-height: 60vh;
}
</style>