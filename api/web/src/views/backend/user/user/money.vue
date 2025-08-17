<template>
    <!-- 对话框表单 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="baTable.form.operate === 'Money'"
        @close="baTable.toggleForm"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                余额管理
            </div>
        </template>
        <el-scrollbar class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="config.layout.shrink ? '' : 'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    ref="formRef"
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                    v-if="!baTable.form.loading"
                >
                    <el-form-item label="用户名">
                        <el-input v-model="state.userInfo.username" disabled></el-input>
                    </el-form-item>
                    <el-form-item label="昵称">
                        <el-input v-model="state.userInfo.nickname" disabled></el-input>
                    </el-form-item>
                    <el-form-item label="当前余额">
                        <el-input v-model="state.userInfo.money" disabled type="number"></el-input>
                    </el-form-item>
                    <el-form-item label="冻结金额">
                        <el-input v-model="state.userInfo.unwith_money" disabled type="number"></el-input>
                    </el-form-item>
                    <el-form-item label="可提现余额">
                        <el-input v-model="state.availableMoney" disabled type="number"></el-input>
                    </el-form-item>
                    <el-form-item prop="type" label="操作类型">
                        <el-select v-model="baTable.form.items!.type" placeholder="请选择操作类型">
                            <el-option label="充值" value="ADMIN_ADD"></el-option>
                            <el-option label="扣除" value="ADMIN_DEDUCT"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item prop="money" label="变动金额">
                        <el-input
                            @input="changeMoney"
                            v-model="baTable.form.items!.money"
                            type="number"
                            placeholder="请输入变动金额"
                        ></el-input>
                    </el-form-item>
                    <el-form-item label="变动后余额">
                        <el-input v-model="state.after" type="number" disabled></el-input>
                    </el-form-item>
                    <el-form-item prop="memo" label="备注">
                        <el-input
                            @keyup.enter.stop=""
                            @keyup.ctrl.enter="submitForm"
                            v-model="baTable.form.items!.memo"
                            type="textarea"
                            placeholder="请输入变动备注说明"
                        ></el-input>
                    </el-form-item>
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm('')">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="submitForm" type="primary">
                    保存
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, inject, watch, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import type baTableClass from '/@/utils/baTable'
import createAxios from '/@/utils/axios'
import FormItem from '/@/components/formItem/index.vue'
import type { FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'
import { useConfig } from '/@/stores/config'

const config = useConfig()
const { t } = useI18n()
const baTable = inject('baTable') as baTableClass
const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    type: [buildValidatorData({ name: 'required', message: '请选择操作类型' })],
    money: [
        buildValidatorData({ name: 'required', title: '变动金额' }),
        {
            validator: (rule: any, val: string, callback: Function) => {
                if (!val || parseFloat(val) <= 0) {
                    return callback(new Error('请输入正确的变动金额'))
                }
                return callback()
            },
            trigger: 'blur',
        },
    ],
    memo: [buildValidatorData({ name: 'required', title: '备注' })],
})

const formRef = useTemplateRef('formRef')

const state: {
    userInfo: anyObj
    after: number
    availableMoney: number
} = reactive({
    userInfo: {},
    after: 0,
    availableMoney: 0,
})

const getAdd = () => {
    if (!baTable.form.items!.user_id || parseInt(baTable.form.items!.user_id) <= 0) {
        return
    }
    createAxios({
         url: '/admin/user.User/setMoney',
         method: 'GET',
         params: { user_id: baTable.form.items!.user_id }
     }).then((res) => {
        state.userInfo = res.data.user
        state.after = res.data.user.money
        state.availableMoney = parseFloat((parseFloat(res.data.user.money) - parseFloat(res.data.user.unwith_money || 0)).toFixed(2))
    })
}

const changeMoney = (value: string) => {
    if (!state.userInfo || typeof state.userInfo == 'undefined' || !baTable.form.items!.type) {
        state.after = parseFloat(state.userInfo?.money || 0)
        return
    }
    let newValue = value == '' ? 0 : parseFloat(value)
    if (baTable.form.items!.type === 'ADMIN_ADD') {
        state.after = parseFloat((parseFloat(state.userInfo.money) + newValue).toFixed(2))
    } else {
        state.after = parseFloat((parseFloat(state.userInfo.money) - newValue).toFixed(2))
    }
}

const submitForm = () => {
    if (!formRef.value) return
    formRef.value.validate((valid: boolean) => {
        if (valid) {
            baTable.form.submitLoading = true
            const submitData = {
                user_id: baTable.form.items!.user_id,
                money: baTable.form.items!.money,
                type: baTable.form.items!.type,
                memo: baTable.form.items!.memo || ''
            }
            createAxios({
                 url: '/admin/user.User/setMoney',
                 method: 'POST',
                 data: submitData
             }, {
                 showSuccessMessage: true
             }).then((res) => {
                baTable.form.submitLoading = false
                baTable.onTableHeaderAction('refresh', {})
                baTable.toggleForm('')
                ElMessage.success(res.msg || '操作成功')
            }).catch(() => {
                baTable.form.submitLoading = false
            })
        }
    })
}

// 打开表单时刷新用户数据
watch(
    () => baTable.form.operate,
    (newValue) => {
        if (newValue) {
            getAdd()
        }
    }
)

// 监听操作类型变化，重新计算余额
watch(
    () => baTable.form.items?.type,
    () => {
        changeMoney(baTable.form.items?.money || '')
    }
)
</script>

<style scoped lang="scss">
.preview-img {
    width: 60px;
    height: 60px;
}
</style>