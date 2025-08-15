<template>
    <el-dialog
        v-model="state.visible"
        :title="'代理返水配置 - ' + state.agentInfo.username"
        width="800px"
        :close-on-click-modal="false"
        @close="onClose"
    >
        <el-form
            ref="formRef"
            :model="state.form"
            :rules="rules"
            label-width="140px"
            v-loading="state.loading"
        >
            <el-row :gutter="20">
                <el-col :span="12">
                    <el-form-item label="竞彩不中奖返水" prop="sports_no_win_rate">
                        <el-input-number
                            v-model="state.form.sports_no_win_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="竞彩投注返水" prop="sports_bet_rate">
                        <el-input-number
                            v-model="state.form.sports_bet_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
            </el-row>
            
            <el-row :gutter="20">
                <el-col :span="12">
                    <el-form-item label="福彩不中奖返水" prop="welfare_no_win_rate">
                        <el-input-number
                            v-model="state.form.welfare_no_win_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="福彩投注返水" prop="welfare_bet_rate">
                        <el-input-number
                            v-model="state.form.welfare_bet_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
            </el-row>
            
            <el-row :gutter="20">
                <el-col :span="12">
                    <el-form-item label="单场不中奖返水" prop="sports_single_no_win_rate">
                        <el-input-number
                            v-model="state.form.sports_single_no_win_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="单场投注返水" prop="sports_single_bet_rate">
                        <el-input-number
                            v-model="state.form.sports_single_bet_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
            </el-row>
            
            <el-row :gutter="20">
                <el-col :span="12">
                    <el-form-item label="快彩不中奖返水" prop="quick_no_win_rate">
                        <el-input-number
                            v-model="state.form.quick_no_win_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="快彩投注返水" prop="quick_bet_rate">
                        <el-input-number
                            v-model="state.form.quick_bet_rate"
                            :min="0"
                            :max="100"
                            :precision="2"
                            controls-position="right"
                            style="width: 100%"
                        />
                        <span class="input-suffix">%</span>
                    </el-form-item>
                </el-col>
            </el-row>
            
            <el-row :gutter="20">
                <el-col :span="12">
                    <el-form-item label="返水方式" prop="rebate_type">
                        <el-select v-model="state.form.rebate_type" style="width: 100%">
                            <el-option label="盈利返水" value="profit" />
                            <el-option label="投注返水" value="bet" />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="结算周期" prop="settlement_cycle">
                        <el-select v-model="state.form.settlement_cycle" style="width: 100%">
                            <el-option label="1天" value="1" />
                            <el-option label="7天" value="7" />
                            <el-option label="30天" value="30" />
                            <el-option label="90天" value="90" />
                        </el-select>
                    </el-form-item>
                </el-col>
            </el-row>
            
            <el-row :gutter="20">
                <el-col :span="12">
                    <el-form-item label="结算时间" prop="settlement_time">
                        <el-time-picker
                            v-model="state.form.settlement_time"
                            format="HH:mm"
                            value-format="HH:mm"
                            style="width: 100%"
                        />
                    </el-form-item>
                </el-col>
                <el-col :span="12">
                    <el-form-item label="启用状态" prop="is_enabled">
                        <el-switch
                            v-model="state.form.is_enabled"
                            :active-value="1"
                            :inactive-value="0"
                        />
                    </el-form-item>
                </el-col>
            </el-row>
        </el-form>
        
        <template #footer>
            <div class="dialog-footer">
                <el-button @click="onClose">取消</el-button>
                <el-button type="primary" @click="onSave" :loading="state.saving">保存</el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import createAxios from '/@/utils/axios'

interface AgentInfo {
    id: number
    username: string
}

interface RebateConfig {
    agent_id: number
    sports_no_win_rate: number
    sports_bet_rate: number
    welfare_no_win_rate: number
    welfare_bet_rate: number
    sports_single_no_win_rate: number
    sports_single_bet_rate: number
    quick_no_win_rate: number
    quick_bet_rate: number
    rebate_type: string
    settlement_cycle: string
    settlement_time: string
    is_enabled: number
}

const formRef = ref()

const state = reactive({
    visible: false,
    loading: false,
    saving: false,
    agentInfo: {} as AgentInfo,
    form: {
        agent_id: 0,
        sports_no_win_rate: 0,
        sports_bet_rate: 0,
        welfare_no_win_rate: 0,
        welfare_bet_rate: 0,
        sports_single_no_win_rate: 0,
        sports_single_bet_rate: 0,
        quick_no_win_rate: 0,
        quick_bet_rate: 0,
        rebate_type: 'profit',
        settlement_cycle: '7',
        settlement_time: '23:45',
        is_enabled: 1,
    } as RebateConfig
})

const rules = {
    sports_no_win_rate: [{ required: true, message: '请输入竞彩不中奖返水比例', trigger: 'blur' }],
    sports_bet_rate: [{ required: true, message: '请输入竞彩投注返水比例', trigger: 'blur' }],
    welfare_no_win_rate: [{ required: true, message: '请输入福彩不中奖返水比例', trigger: 'blur' }],
    welfare_bet_rate: [{ required: true, message: '请输入福彩投注返水比例', trigger: 'blur' }],
    sports_single_no_win_rate: [{ required: true, message: '请输入单场不中奖返水比例', trigger: 'blur' }],
    sports_single_bet_rate: [{ required: true, message: '请输入单场投注返水比例', trigger: 'blur' }],
    quick_no_win_rate: [{ required: true, message: '请输入快彩不中奖返水比例', trigger: 'blur' }],
    quick_bet_rate: [{ required: true, message: '请输入快彩投注返水比例', trigger: 'blur' }],
    rebate_type: [{ required: true, message: '请选择返水方式', trigger: 'change' }],
    settlement_cycle: [{ required: true, message: '请选择结算周期', trigger: 'change' }],
    settlement_time: [{ required: true, message: '请选择结算时间', trigger: 'change' }],
}

const open = (agentInfo: AgentInfo) => {
    state.agentInfo = agentInfo
    state.form.agent_id = agentInfo.id
    state.visible = true
    loadConfig()
}

const loadConfig = async () => {
    state.loading = true
    try {
        const res = await createAxios({
            url: '/admin/user.AgentRebate/getConfig',
            method: 'GET',
            params: { agent_id: state.agentInfo.id }
        })
        if (res.code === 1 && res.data.config) {
            Object.assign(state.form, res.data.config)
        }
    } catch (error) {
        console.error('加载配置失败:', error)
        ElMessage.error('加载配置失败')
    } finally {
        state.loading = false
    }
}

const onSave = async () => {
    if (!formRef.value) return
    
    const valid = await formRef.value.validate().catch(() => false)
    if (!valid) return
    
    state.saving = true
    try {
        const res = await createAxios({
            url: '/admin/user.AgentRebate/saveConfig',
            method: 'POST',
            data: state.form
        }, {
            showSuccessMessage: true
        })
        if (res.code === 1) {
            ElMessage.success('保存成功')
            onClose()
        } else {
            ElMessage.error(res.msg || '保存失败')
        }
    } catch (error) {
        console.error('保存失败:', error)
        ElMessage.error('保存失败')
    } finally {
        state.saving = false
    }
}

const onClose = () => {
    state.visible = false
    formRef.value?.resetFields()
}

defineExpose({
    open
})
</script>

<style scoped>
.input-suffix {
    margin-left: 8px;
    color: #909399;
}

.dialog-footer {
    text-align: right;
}
</style>