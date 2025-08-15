<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: '等级名称' })"
        />

        <!-- 表格 -->
        <Table ref="tableRef" />

        <!-- 表单 -->
        <PopupForm ref="formRef" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, provide } from 'vue'
import baTableClass from '/@/utils/baTable'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'
import { useI18n } from 'vue-i18n'

defineOptions({
    name: 'user/level',
})

const { t } = useI18n()
const tableRef = ref()
const formRef = ref()
const baTable = new baTableClass(
    new baTableApi('/admin/user.Level/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('Id'), prop: 'id', align: 'center', operator: '=', operatorPlaceholder: t('Id'), width: 70 },
            { label: '等级名称', prop: 'name', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query') },
            { label: '等级序号', prop: 'level', align: 'center', operator: '=', operatorPlaceholder: '等级序号', width: 100 },
            { label: '升级条件', prop: 'upgrade_condition', align: 'center', operator: 'RANGE', sortable: 'custom', width: 120 },
            { label: '最低投注额', prop: 'min_bet_amount', align: 'center', operator: 'RANGE', sortable: 'custom', width: 120 },
            { label: '最高投注额', prop: 'max_bet_amount', align: 'center', operator: 'RANGE', sortable: 'custom', width: 120 },
            { label: '投注额度(%)', prop: 'bet_percentage', align: 'center', operator: 'RANGE', sortable: 'custom', width: 120 },
            {
                label: '状态',
                prop: 'status',
                align: 'center',
                width: 100,
                render: 'switch',
                operator: 'eq',
                sortable: false,
            },
            { label: '排序', prop: 'sort', align: 'center', operator: 'RANGE', sortable: 'custom', width: 80 },
            { label: '描述', prop: 'description', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query'), showOverflowTooltip: true },
            { label: t('Create time'), prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: t('Operate'),
                align: 'center',
                width: '130',
                render: 'buttons',
                buttons: defaultOptButtons(['edit', 'delete']),
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: {
            status: '1',
            sort: 0,
            bet_percentage: 100.00,
        },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getIndex()
})
</script>

<style scoped lang="scss"></style>