<template>
    <!-- 对话框表单 -->
    <el-dialog
        class="ba-operate-dialog"
        top="10vh"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
        :destroy-on-close="true"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate ? t(baTable.form.operate) : '' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="config.layout.shrink ? '' : 'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    ref="formRef"
                    @submit.prevent=""
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                >
                    <el-form-item prop="name" label="等级名称">
                        <el-input
                            v-model="baTable.form.items!.name"
                            type="string"
                            placeholder="请输入等级名称"
                        ></el-input>
                    </el-form-item>
                    
                    <el-form-item prop="level" label="等级序号">
                        <el-input-number
                            v-model="baTable.form.items!.level"
                            :min="1"
                            :max="999"
                            placeholder="请输入等级序号"
                        ></el-input-number>
                    </el-form-item>
                    
                    <el-form-item prop="upgrade_condition" label="升级条件">
                        <el-input-number
                            v-model="baTable.form.items!.upgrade_condition"
                            :min="0"
                            :precision="2"
                            placeholder="请输入升级所需累计投注额"
                        ></el-input-number>
                        <div class="form-item-help">会员累计投注额达到此数值时自动升级到该等级</div>
                    </el-form-item>
                    
                    <el-form-item prop="min_bet_amount" label="最低投注额">
                        <el-input-number
                            v-model="baTable.form.items!.min_bet_amount"
                            :min="0"
                            :precision="2"
                            placeholder="请输入最低投注额"
                        ></el-input-number>
                    </el-form-item>
                    
                    <el-form-item prop="max_bet_amount" label="最高投注额">
                        <el-input-number
                            v-model="baTable.form.items!.max_bet_amount"
                            :min="0"
                            :precision="2"
                            placeholder="请输入最高投注额"
                        ></el-input-number>
                    </el-form-item>
                    
                    <el-form-item prop="bet_percentage" label="投注额度百分比">
                        <el-input-number
                            v-model="baTable.form.items!.bet_percentage"
                            :min="0"
                            :max="1000"
                            :precision="2"
                            placeholder="请输入投注额度百分比"
                        ></el-input-number>
                        <div class="form-item-help">100表示100%，105表示105%（提升5%）</div>
                    </el-form-item>
                    
                    <el-form-item prop="sort" label="排序">
                        <el-input-number
                            v-model="baTable.form.items!.sort"
                            :min="0"
                            placeholder="请输入排序值"
                        ></el-input-number>
                    </el-form-item>
                    
                    <el-form-item prop="description" label="等级描述">
                        <el-input
                            v-model="baTable.form.items!.description"
                            type="textarea"
                            :rows="3"
                            placeholder="请输入等级描述"
                        ></el-input>
                    </el-form-item>
                    
                    <FormItem
                        label="状态"
                        v-model="baTable.form.items!.status"
                        type="radio"
                        :data="{ content: { '0': t('Disable'), '1': t('Enable') }, childrenAttr: { border: true } }"
                    />
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm('')">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? t('Save and edit next item') : t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import type baTableClass from '/@/utils/baTable'
import type { FormInstance, FormItemRule } from 'element-plus'
import FormItem from '/@/components/formItem/index.vue'
import { buildValidatorData } from '/@/utils/validate'
import { useConfig } from '/@/stores/config'

const config = useConfig()
const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    name: [buildValidatorData({ name: 'required', title: '等级名称' })],
    level: [buildValidatorData({ name: 'required', title: '等级序号' })],
    upgrade_condition: [buildValidatorData({ name: 'required', title: '升级条件' })],
    min_bet_amount: [buildValidatorData({ name: 'required', title: '最低投注额' })],
    max_bet_amount: [buildValidatorData({ name: 'required', title: '最高投注额' })],
    bet_percentage: [buildValidatorData({ name: 'required', title: '投注额度百分比' })],
})
</script>

<style scoped lang="scss">
.form-item-help {
    font-size: 12px;
    color: #909399;
    margin-top: 4px;
}
</style>