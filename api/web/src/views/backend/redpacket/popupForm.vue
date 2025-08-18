<template>
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="baTable.form.operate === 'Add'"
        @close="baTable.toggleForm"
        width="50%"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']">添加红包</div>
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
                        label="发放对象类型"
                        type="radio"
                        v-model="baTable.form.items!.target_type"
                        prop="target_type"
                        :input-attr="{
                            content: [
                                { label: '全部', value: '0' },
                                { label: '代理商', value: '1' },
                                { label: '用户', value: '2' }
                            ]
                        }"
                    />
                    <FormItem
                        label="红包标题"
                        type="string"
                        v-model="baTable.form.items!.title"
                        prop="title"
                        placeholder="请输入红包标题"
                    />
                    <FormItem
                        label="祝福语"
                        type="textarea"
                        v-model="baTable.form.items!.blessing"
                        prop="blessing"
                        placeholder="请输入祝福语"
                        :input-attr="{
                            rows: 3
                        }"
                    />
                    <FormItem
                        label="红包类型"
                        type="radio"
                        v-model="baTable.form.items!.type"
                        prop="type"
                        :input-attr="{
                            content: [
                                { label: '随机红包（金额随机分配）', value: 'RANDOM' },
                                { label: '固定红包（每个金额相同）', value: 'FIXED' }
                            ]
                        }"
                    />
                    <el-row :gutter="20">
                        <el-col :span="12">
                            <FormItem
                                label="红包总金额"
                                type="number"
                                v-model="baTable.form.items!.total_amount"
                                prop="total_amount"
                                placeholder="输入总金额，如：100"
                            >
                                <template #append>元</template>
                            </FormItem>
                        </el-col>
                        <el-col :span="12">
                            <FormItem
                                label="红包总个数"
                                type="number"
                                v-model="baTable.form.items!.total_count"
                                prop="total_count"
                                placeholder="输入个数，如：10"
                            >
                                <template #append>个</template>
                            </FormItem>
                        </el-col>
                    </el-row>
                    <div v-if="avgAmountTip" class="avg-amount-tip">
                        <el-alert :title="avgAmountTip" type="info" :closable="false" show-icon />
                    </div>
                    <FormItem
                        label="领取条件"
                        type="radio"
                        v-model="baTable.form.items!.condition_type"
                        prop="condition_type"
                        :input-attr="{
                            content: getConditionOptions()
                        }"
                    />
                    <FormItem
                        v-if="baTable.form.items!.condition_type !== 'NONE'"
                        :label="baTable.form.items!.condition_type === 'USER_LEVEL' ? '最低用户等级' : '最低投注金额'"
                        type="number"
                        v-model="baTable.form.items!.condition_value"
                        prop="condition_value"
                        :placeholder="baTable.form.items!.condition_type === 'USER_LEVEL' ? '输入等级，如：3' : '输入金额，如：100'"
                        :input-attr="{
                            min: baTable.form.items!.condition_type === 'USER_LEVEL' ? 1 : 1,
                            step: baTable.form.items!.condition_type === 'USER_LEVEL' ? 1 : 1,
                            precision: baTable.form.items!.condition_type === 'USER_LEVEL' ? 0 : 2,
                            'controls-position': 'right'
                        }"
                    >
                        <template v-if="baTable.form.items!.condition_type === 'MIN_BET'" #append>元</template>
                        <template v-else #append>级</template>
                    </FormItem>
                    <FormItem
                        label="过期时间"
                        type="datetime"
                        v-model="baTable.form.items!.expire_time"
                        prop="expire_time"
                        placeholder="请选择过期时间"
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
import { reactive, ref, inject, computed } from 'vue'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { FormInstance, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'

const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

// 设置表单默认值
if (baTable.form.operate === 'Add') {
    // 设置默认值
    baTable.form.items!.target_type = 'AGENT'
    baTable.form.items!.type = 'RANDOM'
    baTable.form.items!.condition_type = 'NONE'
}

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    target_type: [buildValidatorData({ name: 'required', title: '发放对象类型' })],
    target_id: [buildValidatorData({ name: 'required', title: '发放对象' })],
    title: [buildValidatorData({ name: 'required', title: '红包标题' })],
    type: [buildValidatorData({ name: 'required', title: '红包类型' })],
    total_amount: [
        buildValidatorData({ name: 'required', title: '红包总金额' }),
        buildValidatorData({ name: 'number', title: '红包总金额' }),
        {
            validator: (rule: any, value: any, callback: any) => {
                if (value <= 0) {
                    callback(new Error('红包总金额必须大于0'))
                } else {
                    callback()
                }
            },
            trigger: 'blur'
        }
    ],
    total_count: [
        buildValidatorData({ name: 'required', title: '红包总个数' }),
        buildValidatorData({ name: 'integer', title: '红包总个数' }),
        {
            validator: (rule: any, value: any, callback: any) => {
                if (value < 2) {
                    callback(new Error('红包个数最少2个'))
                } else if (value > 1000) {
                    callback(new Error('红包个数不能超过1000个'))
                } else {
                    callback()
                }
            },
            trigger: 'blur'
        }
    ],
    condition_type: [buildValidatorData({ name: 'required', title: '领取条件' })],
    condition_value: [
        {
            validator: (rule: any, value: any, callback: any) => {
                if (baTable.form.items!.condition_type !== 'NONE' && !value) {
                    const fieldName = baTable.form.items!.condition_type === 'USER_LEVEL' ? '最低用户等级' : '最低投注金额'
                    callback(new Error(`请输入${fieldName}`))
                } else {
                    callback()
                }
            },
            trigger: 'blur'
        }
    ]
})

// 获取条件选项（根据发放对象类型）
const getConditionOptions = () => {
    const baseOptions = [{ label: '无条件领取', value: 'NONE' }]
    
    // 只有当发放对象为用户(值为'2')时，才显示额外的条件选项
    if (baTable.form.items!.target_type === '2') {
        baseOptions.push(
            { label: '需要当日最低投注额', value: 'MIN_BET' },
            // { label: '需要用户等级', value: 'USER_LEVEL' }
        )
    }
    
    return baseOptions
}

// 计算平均金额提示
const avgAmountTip = computed(() => {
    const totalAmount = baTable.form.items!.total_amount
    const totalCount = baTable.form.items!.total_count
    if (totalAmount && totalCount && totalAmount > 0 && totalCount > 0) {
        const avg = (totalAmount / totalCount).toFixed(2)
        return `平均每个红包: ${avg}元`
    }
    return ''
})
</script>

<style scoped lang="scss">
.avg-amount-tip {
    margin: 15px 0;
    
    :deep(.el-alert) {
        border-radius: 6px;
        
        .el-alert__title {
            font-size: 13px;
            color: #409eff;
        }
    }
}
</style>