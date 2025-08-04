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
          <FormItem 
            label="彩票类型" 
            type="select" 
            v-model="baTable.form.items!.lottery_type_id" 
            prop="lottery_type_id" 
            :data="lotteryTypeOptions" 
            placeholder="请选择彩票类型" 
          />
          
          <FormItem 
            label="期号" 
            type="string" 
            v-model="baTable.form.items!.draw_no" 
            prop="draw_no" 
            placeholder="请输入期号" 
          />
          
          <FormItem 
            label="开奖时间" 
            type="datetime" 
            v-model="baTable.form.items!.draw_time" 
            prop="draw_time" 
            placeholder="请选择开奖时间" 
          />
          
          <FormItem 
            label="奖池金额" 
            type="number" 
            v-model="baTable.form.items!.prize_pool" 
            prop="prize_pool" 
            placeholder="请输入奖池金额" 
            :step="0.01" 
            :min="0" 
          />
          
          <FormItem 
            label="状态" 
            type="radio" 
            v-model="baTable.form.items!.status" 
            prop="status" 
            :data="statusOptions" 
          />
          
          <div v-if="baTable.form.operate === 'Edit' && baTable.form.items?.status !== 'PENDING'">
            <FormItem 
              label="开奖结果" 
              type="textarea" 
              v-model="drawResultText" 
              prop="draw_result_text" 
              placeholder="请输入开奖结果（JSON格式）" 
              :rows="4" 
              @input="updateDrawResult"
            />
          </div>
          
          <FormItem 
            label="备注" 
            type="textarea" 
            v-model="baTable.form.items!.remark" 
            prop="remark" 
            placeholder="请输入备注" 
            :rows="3" 
          />
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
import { reactive, ref, inject, computed, watch, onMounted } from 'vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { FormInstance, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'
import { baTableApi } from '/@/api/common'

defineOptions({
  name: 'lotterydraw/popupForm',
})

const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

const lotteryTypeOptions = ref<any[]>([])

const statusOptions = [
  { label: '待开奖', value: 'PENDING' },
  { label: '已开奖', value: 'DRAWN' },
  { label: '已结算', value: 'SETTLED' },
  { label: '已取消', value: 'CANCELLED' }
]

const drawResultText = ref('')

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
  lottery_type_id: [buildValidatorData({ name: 'required', title: '彩票类型' })],
  draw_no: [buildValidatorData({ name: 'required', title: '期号' })],
  draw_time: [buildValidatorData({ name: 'required', title: '开奖时间' })],
  prize_pool: [buildValidatorData({ name: 'number', title: '奖池金额' })],
  status: [buildValidatorData({ name: 'required', title: '状态' })],
  draw_result_text: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (baTable.form.items?.status !== 'PENDING' && value) {
          try {
            JSON.parse(value)
            callback()
          } catch (e) {
            callback(new Error('开奖结果格式错误'))
          }
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ]
})

const updateDrawResult = () => {
  if (drawResultText.value) {
    try {
      baTable.form.items!.draw_result = JSON.parse(drawResultText.value)
    } catch (e) {
      // 格式错误时不更新
    }
  }
}

// 监听表单项变化，同步开奖结果文本
watch(() => baTable.form.items?.draw_result, (newVal) => {
  if (newVal && typeof newVal === 'object') {
    drawResultText.value = JSON.stringify(newVal, null, 2)
  }
}, { deep: true, immediate: true })

// 监听彩种变化，自动生成期号
watch(() => baTable.form.items?.lottery_type_id, (newVal) => {
  if (newVal && baTable.form.operate === 'Add') {
    generateDrawNo(newVal)
  }
})

const generateDrawNo = (lotteryTypeId: number) => {
  const api = new baTableApi('/admin/lottery.LotteryDraw/')
  api.postData('generateDrawNo', { lottery_type_id: lotteryTypeId }).then((res) => {
    if (res.code === 1 && res.data.draw_no) {
      baTable.form.items!.draw_no = res.data.draw_no
    }
  })
}

const loadLotteryTypes = () => {
  const api = new baTableApi('/admin/lottery.LotteryType/')
  api.postData('getEnabledList').then((res) => {
    if (res.code === 1) {
      lotteryTypeOptions.value = Object.entries(res.data).map(([value, label]) => ({
        label: label as string,
        value: parseInt(value)
      }))
    }
  })
}

onMounted(() => {
  loadLotteryTypes()
})
</script>

<style scoped lang="scss"></style>