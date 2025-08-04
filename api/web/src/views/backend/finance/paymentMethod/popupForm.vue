<template>
  <el-dialog
    class="ba-operate-dialog"
    :close-on-click-modal="false"
    :model-value="baTable.form.operate ? true : false"
    @close="baTable.toggleForm"
    width="50%"
  >
    <template #header>
      <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">{{ baTable.form.operate === 'Add' ? '添加支付方式' : '编辑支付方式' }}</div>
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
          <FormItem
            label="支付方式代码"
            type="string"
            v-model="baTable.form.items!.method_code"
            prop="method_code"
            placeholder="请输入支付方式代码"
          />
          <FormItem
            label="支付方式名称"
            type="string"
            v-model="baTable.form.items!.method_name"
            prop="method_name"
            placeholder="请输入支付方式名称"
          />
          <FormItem
            label="支付图标"
            type="image"
            v-model="baTable.form.items!.method_icon"
            prop="method_icon"
            placeholder="请上传支付图标"
          />
          <FormItem
            label="描述"
            type="textarea"
            v-model="baTable.form.items!.description"
            prop="description"
            placeholder="请输入描述"
          />
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
          <FormItem
            label="排序"
            type="number"
            v-model.number="baTable.form.items!.sort_order"
            prop="sort_order"
            placeholder="请输入排序值"
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
  method_code: [buildValidatorData({ name: 'required', title: '支付方式代码' })],
  method_name: [buildValidatorData({ name: 'required', title: '支付方式名称' })],
  is_enabled: [buildValidatorData({ name: 'required', title: '是否启用' })],
  sort_order: [buildValidatorData({ name: 'required', title: '排序' })],
})
</script>

<style scoped lang="scss"></style>