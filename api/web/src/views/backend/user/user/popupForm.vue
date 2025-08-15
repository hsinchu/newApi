<template>
    <!-- 对话框表单 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :destroy-on-close="true"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate === 'Add' ? '添加用户' : '编辑用户' }}
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
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                    v-if="!baTable.form.loading"
                >
                
                    <FormItem
                        label="是否代理商"
                        v-model="baTable.form.items!.is_agent"
                        type="radio"
                        :input-attr="{
                            border: true,
                            content: { 0: '会员', 1: '代理商' },
                        }"
                    />
                    <FormItem
                        v-if="baTable.form.items!.is_agent == 0"
                        type="remoteSelect"
                        label="所属代理商"
                        v-model="baTable.form.items!.parent_id"
                        prop="parent_id"
                        placeholder="请选择所属代理商"
                        :input-attr="{
                            params: { search: [{ field: 'is_agent', val: '1', operator: 'eq' }, { field: 'status', val: 1, operator: 'eq' }] },
                            field: 'nickname',
                            remoteUrl: '/admin/user.User/select',
                        }"
                    />
                    <el-form-item prop="username" label="用户名">
                        <el-input
                            v-model="baTable.form.items!.username"
                            type="string"
                            placeholder="请输入用户名（登录账号）"
                        ></el-input>
                    </el-form-item>
                    <FormItem label="游戏选择" type="remoteSelects" v-model="baTable.form.items!.game_ids" prop="game_ids" :input-attr="{ pk: 'id', field: 'type_name', 'remote-url': '/admin/lottery.LotteryType/index', multiple: true }" placeholder="请选择游戏" />
                    <FormItem label="用户标签" type="remoteSelects" v-model="baTable.form.items!.user_tag" prop="user_tag" :input-attr="{ pk: 'user_tag.id', field: 'name', 'remote-url': '/admin/user.Tag/index', multiple: true }" placeholder="请选择用户标签" />
                    <el-form-item prop="password" label="密码">
                        <el-input
                            v-model="baTable.form.items!.password"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="
                                baTable.form.operate == 'Add'
                                    ? '请输入密码'
                                    : '不修改请留空'
                            "
                        ></el-input>
                    </el-form-item>
                    <el-form-item prop="pay_password" label="支付密码">
                        <el-input
                            v-model="baTable.form.items!.pay_password"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="
                                baTable.form.operate == 'Add'
                                    ? '请输入支付密码'
                                    : '不修改请留空'
                            "
                        ></el-input>
                    </el-form-item>
                    <el-form-item prop="nickname" label="昵称">
                        <el-input
                            v-model="baTable.form.items!.nickname"
                            type="string"
                            placeholder="请输入昵称"
                        ></el-input>
                    </el-form-item>
                    <FormItem label="头像" type="image" v-model="baTable.form.items!.avatar" />
                    <el-form-item prop="email" label="邮箱">
                        <el-input
                            v-model="baTable.form.items!.email"
                            type="email"
                            placeholder="请输入邮箱"
                        ></el-input>
                    </el-form-item>
                    <el-form-item prop="mobile" label="手机号">
                        <el-input
                            v-model="baTable.form.items!.mobile"
                            type="string"
                            placeholder="请输入手机号"
                        ></el-input>
                    </el-form-item>
                    <el-form-item prop="real_name" label="真实姓名">
                        <el-input
                            v-model="baTable.form.items!.real_name"
                            type="string"
                            placeholder="请输入真实姓名"
                        ></el-input>
                    </el-form-item>
                    <el-form-item prop="id_card" label="身份证号">
                        <el-input
                            v-model="baTable.form.items!.id_card"
                            type="string"
                            placeholder="请输入身份证号"
                        ></el-input>
                    </el-form-item>
                    <el-form-item v-if="baTable.form.items!.is_agent == 1" prop="rebate_rate" label="投注返佣">
                        <el-input
                            v-model="baTable.form.items!.rebate_rate"
                            type="string"
                            placeholder="请输入投注返佣比例"
                        ></el-input>
                    </el-form-item>
                    <el-form-item v-if="baTable.form.items!.is_agent == 1" prop="nowin_rate" label="不中奖返佣">
                        <el-input
                            v-model="baTable.form.items!.nowin_rate"
                            type="string"
                            placeholder="请输入不中奖返佣比例"
                        ></el-input>
                    </el-form-item>
                    <!-- <el-form-item prop="motto" label="个人签名">
                        <el-input
                            @keyup.enter.stop=""
                            @keyup.ctrl.enter="baTable.onSubmit(formRef)"
                            v-model="baTable.form.items!.motto"
                            type="textarea"
                            placeholder="请输入个人签名"
                        ></el-input>
                    </el-form-item> -->
                    <FormItem
                        label="身份认证"
                        v-model="baTable.form.items!.is_verified"
                        type="radio"
                        :input-attr="{
                            border: true,
                            content: { 0: '未认证', 1: '已认证', 2: '审核中' },
                        }"
                    />
                    <FormItem
                        label="状态"
                        v-model="baTable.form.items!.status"
                        type="radio"
                        :input-attr="{
                            border: true,
                            content: { 0: '审核中', 1: '启用', 2: '禁用' },
                        }"
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
import { reactive, inject, watch, useTemplateRef } from 'vue'
import type baTableClass from '/@/utils/baTable'
import { regularPassword } from '/@/utils/validate'
import type { FormItemRule } from 'element-plus'
import FormItem from '/@/components/formItem/index.vue'
import { buildValidatorData } from '/@/utils/validate'
import { useConfig } from '/@/stores/config'

const config = useConfig()
const formRef = useTemplateRef('formRef')
const baTable = inject('baTable') as baTableClass

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    username: [buildValidatorData({ name: 'required', title: '用户名' })],
    nickname: [buildValidatorData({ name: 'required', title: '昵称' })],
    email: [buildValidatorData({ name: 'email', title: '邮箱' })],
    mobile: [buildValidatorData({ name: 'mobile' })],
    real_name: [buildValidatorData({ name: 'varName', title: '真实姓名' })],
    id_card: [
        {
            validator: (rule: any, val: string, callback: Function) => {
                if (val && !/^[1-9]\d{5}(18|19|20)\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/.test(val)) {
                    return callback(new Error('请输入正确的身份证号'))
                }
                return callback()
            },
            trigger: 'blur',
        },
    ],
    password: [
        {
            validator: (rule: any, val: string, callback: Function) => {
                if (baTable.form.operate == 'Add') {
                    if (!val) {
                        return callback(new Error('请输入密码'))
                    }
                } else {
                    if (!val) {
                        return callback()
                    }
                }
                if (!regularPassword(val)) {
                    return callback(new Error('请输入正确的密码格式'))
                }
                return callback()
            },
            trigger: 'blur',
        },
    ],
    pay_password: [
        {
            validator: (rule: any, val: string, callback: Function) => {
                if (baTable.form.operate == 'Add') {
                    if (!val) {
                        return callback(new Error('请输入密码'))
                    }
                } else {
                    if (!val) {
                        return callback()
                    }
                }
                if (!regularPassword(val)) {
                    return callback(new Error('请输入正确的密码格式'))
                }
                return callback()
            },
            trigger: 'blur',
        },
    ],
})

watch(
    () => baTable.form.operate,
    (newVal) => {
        rules.password![0].required = newVal == 'Add'
        rules.pay_password![0].required = newVal == 'Add'
    }
)
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
.image-slot {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}
</style>
