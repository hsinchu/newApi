<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'快速搜索用户名/昵称/ID/真实姓名/邮箱/手机号'"
        >
        </TableHeader>

        <!-- 表格 -->
        <!-- 要使用`el-table`组件原有的属性，直接加在Table标签上即可 -->
        <Table ref="tableRef" />

        <!-- 表单 -->
        <PopupForm />
        <MoneyForm />
    </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted } from 'vue'
import baTableClass from '/@/utils/baTable'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'
import PopupForm from './popupForm.vue'
import MoneyForm from './money.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'

defineOptions({
    name: 'user/user',
})

const tableRef = ref()
const optButtons: OptButton[] = defaultOptButtons(['edit'])
optButtons.push({
    render: 'tipButton',
    name: 'info',
    title: '',
    text: '余额',
    type: 'danger',
    icon: '',
    click(row, field) {
        baTable.form.operate = 'Money'
        baTable.form.items = { user_id: row.id }
        baTable.toggleForm('Money')
    },
})

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/user.User/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false, width: 50 },
            { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'eq', sortable: 'custom' },
            { label: '用户名', prop: 'username', align: 'center', width: 120, operator: 'LIKE', operatorPlaceholder: '模糊查询', fixed: 'left' },
            {
                label: '会员类型',
                prop: 'is_agent',
                align: 'center',
                width: 90,
                render: 'tag',
                custom: { 0: 'success', 1: 'danger' },
                replaceValue: { 0: '会员', 1: '代理商' },
                operator: 'eq',
                sortable: false,
            },
            { label: '昵称', prop: 'nickname', align: 'center', width: 120, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '头像', prop: 'avatar', align: 'center', width: 80, render: 'image', operator: false },
            {
                label: '性别',
                prop: 'gender',
                align: 'center',
                width: 80,
                render: 'tag',
                custom: { 0: 'info', 1: 'primary', 2: 'danger' },
                replaceValue: { 0: '未知', 1: '男', 2: '女' },
                operator: false,
            },
            {
                label: '生日',
                prop: 'birthday',
                align: 'center',
                width: 120,
                operator: false,
            },
            { label: '上级代理', prop: 'parentUser.username', align: 'center', width: 120, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            {
                label: '状态',
                prop: 'status',
                align: 'center',
                width: 80,
                render: 'tag',
                custom: { 0: 'info', 1: 'success', 2: 'danger' },
                replaceValue: { 0: '审核中', 1: '启用', 2: '禁用' },
                operator: 'eq',
                sortable: false,
            },
            {
                label: '实名认证',
                prop: 'is_verified',
                align: 'center',
                width: 100,
                render: 'tag',
                custom: { 0: 'danger', 1: 'success' },
                replaceValue: { 0: '未认证', 1: '已认证' },
                operator: 'eq',
                sortable: false,
            },
            { label: '余额', prop: 'money', align: 'center', operator: false, sortable: 'custom', width: 100 },
            { label: '不可提现余额', prop: 'unwith_money', align: 'center', sortable: 'custom', operator: false, width: 100 },
            { label: '积分', prop: 'score', align: 'center', sortable: 'custom', operator: false, width: 100 },
            { label: '邀请码', prop: 'invite_code', align: 'center', operator: 'eq', operatorPlaceholder: '精确查询', width: 120 },
            { label: '真实姓名', prop: 'real_name', align: 'center', width: 100, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '身份证号', prop: 'id_card', align: 'center', width: 120, operator: false },
            { label: '手机号', prop: 'mobile', align: 'center', width: 120, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '邮箱', prop: 'email', align: 'center', width: 180, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '个人签名', prop: 'motto', align: 'center', width: 120, operator: false },
            { label: '最后登录IP', prop: 'last_login_ip', align: 'center', width: 130, operator: false },
            { label: '最后登录时间', prop: 'last_login_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160 },
            { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160 },
            {
                label: '操作',
                align: 'center',
                width: 100,
                render: 'buttons',
                buttons: optButtons,
                operator: false,
                fixed: 'right'
            },
        ],
        dblClickNotEditColumn: [undefined],
        defaultOrder: { prop: 'id', order: 'desc' },
    },
    {
        defaultItems: { group_id: 0, status: 'enable', is_agent: 0, parent_id: 0 },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getIndex()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
})
</script>

<style scoped lang="scss"></style>
