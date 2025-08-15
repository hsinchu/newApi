<template>
  <el-dialog
    class="ba-operate-dialog"
    :close-on-click-modal="false"
    :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
    @close="baTable.toggleForm"
    width="70%"
  >
    <template #header>
      <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">
        {{ baTable.form.operate === 'Add' ? '添加轮播图' : '编辑轮播图' }}
      </div>
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
            label="轮播图标题" 
            type="string" 
            v-model="baTable.form.items!.title" 
            prop="title" 
            placeholder="请输入轮播图标题" 
            :input-attr="{ maxlength: 200, showWordLimit: true }"
          />
          
          <FormItem 
            label="轮播图片" 
            type="image" 
            v-model="baTable.form.items!.image" 
            prop="image" 
            placeholder="请上传轮播图片"
            :input-attr="{ 
              accept: 'image/*',
              limit: 1,
              fileSize: 5,
              returnFullUrl: true
            }"
          />
          
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="链接类型" prop="link_type">
                <el-select 
                  v-model="baTable.form.items!.link_type" 
                  placeholder="请选择链接类型"
                  style="width: 100%"
                >
                  <el-option label="无链接" :value="0" />
                  <el-option label="内部链接" :value="1" />
                  <el-option label="外部链接" :value="2" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <FormItem 
                label="链接地址" 
                type="string" 
                v-model="baTable.form.items!.link_url" 
                prop="link_url" 
                placeholder="请输入链接地址" 
                :input-attr="{ maxlength: 500, showWordLimit: true }"
                :disabled="baTable.form.items!.link_type === 0"
              />
            </el-col>
          </el-row>
          
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
          
          <!-- 链接地址提示 -->
          <el-row v-if="baTable.form.items!.link_type !== 0">
            <el-col :span="24">
              <el-alert
                :title="getLinkTypeHint()"
                type="info"
                show-icon
                :closable="false"
                style="margin-bottom: 20px;"
              />
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
import { reactive, ref, inject, computed } from 'vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { FormInstance, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'

defineOptions({
  name: 'other/banner/popupForm',
})

const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

// 获取链接类型提示信息
const getLinkTypeHint = () => {
  const linkType = baTable.form.items?.link_type
  if (linkType === 1) {
    return '内部链接示例：/lottery/index、/user/profile/index 等'
  } else if (linkType === 2) {
    return '外部链接示例：https://www.example.com、http://www.baidu.com 等'
  }
  return ''
}

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  title: [
    buildValidatorData({ name: 'required', title: '轮播图标题' }),
    buildValidatorData({ name: 'maxLength', title: '轮播图标题', attr: 200 })
  ],
  image: [
    buildValidatorData({ name: 'required', title: '轮播图片' })
  ],
  link_type: [
    buildValidatorData({ name: 'required', title: '链接类型' })
  ],
  link_url: [
    {
      validator: (rule: any, value: string, callback: Function) => {
        const linkType = baTable.form.items?.link_type
        if (linkType !== 0 && !value) {
          callback(new Error('当选择链接类型时，链接地址不能为空'))
        } else if (value && value.length > 500) {
          callback(new Error('链接地址不能超过500个字符'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
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
  
  .el-select {
    width: 100%;
  }
}
</style>