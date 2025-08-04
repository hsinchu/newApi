<template>
  <el-dialog
    class="ba-operate-dialog"
    :close-on-click-modal="false"
    :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
    @close="baTable.toggleForm"
    width="60%"
  >
    <template #header>
      <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">{{ baTable.form.operate === 'Add' ? '添加公告' : '编辑公告' }}</div>
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
            label="公告标题" 
            type="string" 
            v-model="baTable.form.items!.title" 
            prop="title" 
            placeholder="请输入公告标题" 
            :input-attr="{ maxlength: 200, showWordLimit: true }"
          />
          
          <FormItem 
            label="公告内容" 
            type="textarea" 
            v-model="baTable.form.items!.content" 
            prop="content" 
            placeholder="请输入公告内容" 
            :input-attr="{ 
              showWordLimit: true, 
              rows: 12  // 设置为6行高度，根据需要调整
            }"
          />
          
          <el-row :gutter="20">
            <el-col :span="12">
              <FormItem 
                label="排序序号" 
                type="number" 
                v-model="baTable.form.items!.sort_num" 
                prop="sort_num" 
                placeholder="请输入排序序号" 
                :step="1" 
                :min="0"
                :input-attr="{ controlsPosition: 'right' }"
              />
            </el-col>
            <el-col :span="12">
              <el-form-item label="显示状态" prop="status">
                <el-switch
                  v-model="baTable.form.items!.status"
                  :active-value="1"
                  :inactive-value="0"
                  active-text="正常显示"
                  inactive-text="已下架"
                />
              </el-form-item>
            </el-col>
          </el-row>
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
  name: 'other/dano/popupForm',
})

const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  title: [
    buildValidatorData({ name: 'required', title: '公告标题' }),
    buildValidatorData({ name: 'maxLength', title: '公告标题', attr: 200 })
  ],
  content: [
    buildValidatorData({ name: 'required', title: '公告内容' })
  ],
  sort_num: [
    buildValidatorData({ name: 'integer', title: '排序序号' })
  ],
  status: [
    buildValidatorData({ name: 'required', title: '显示状态' })
  ]
})
</script>

<style scoped lang="scss">
.ba-operate-form {
  .el-form-item {
    margin-bottom: 20px;
  }
  
  .el-textarea {
    :deep(.el-textarea__inner) {
      min-height: 120px;
    }
  }
}
</style>