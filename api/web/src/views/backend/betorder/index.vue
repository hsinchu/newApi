<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索：订单号/期号'"
    />

    <!-- 表格 -->
    <Table ref="tableRef" />

    <!-- 订单详情弹窗 -->
    <el-dialog v-model="detailDialog.visible" title="订单详情" width="800px" :close-on-click-modal="false">
      <div v-loading="detailDialog.loading" class="order-detail">
        <!-- 基本信息 -->
        <div class="detail-section">
          <el-descriptions :column="2" border size="default">
            <el-descriptions-item label="订单号">
              <span class="order-no">{{ detailDialog.data.order_no }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="用户">
              <el-tag type="info">{{ detailDialog.data.user?.username }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="彩种">
               <el-tag type="primary">{{ detailDialog.data.lotteryType?.type_name }}</el-tag>
             </el-descriptions-item>
             <el-descriptions-item label="期号">
               <span class="period-no">{{ detailDialog.data.period_no }}</span>
             </el-descriptions-item>
             <el-descriptions-item label="状态">
               <el-tag :type="getStatusType(detailDialog.data.status)">
                 {{ detailDialog.data.status === 'PENDING' ? '待确认' : 
                    detailDialog.data.status === 'CONFIRMED' ? '待开奖' :
                    detailDialog.data.status === 'WINNING' ? '中奖' :
                    detailDialog.data.status === 'PAID' ? '已派奖' :
                    detailDialog.data.status === 'LOSING' ? '未中奖' :
                    detailDialog.data.status === 'CANCELLED' ? '已取消' :
                    detailDialog.data.status === 'REFUNDED' ? '已退款' : detailDialog.data.status }}
               </el-tag>
             </el-descriptions-item>
             <el-descriptions-item label="IP地址">
               <span class="ip-address">{{ detailDialog.data.ip }}</span>
             </el-descriptions-item>
             <el-descriptions-item label="代理商">
               <el-tag v-if="detailDialog.data.agent_id" type="success">ID: {{ detailDialog.data.agent_id }}</el-tag>
               <span v-else class="no-agent">无代理</span>
             </el-descriptions-item>
          </el-descriptions>
        </div>
        
        <!-- 金额信息 -->
        <div class="detail-section">
          <el-descriptions :column="2" border size="default">
            <el-descriptions-item label="投注金额">
              <span class="amount-text">¥{{ detailDialog.data.bet_amount }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="倍数">
              <span class="multiple-text">{{ detailDialog.data.multiple }}倍</span>
            </el-descriptions-item>
            <el-descriptions-item label="总金额">
              <span class="total-amount">¥{{ detailDialog.data.total_amount }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="中奖金额">
              <span class="win-amount" :class="{ 'has-win': parseFloat(detailDialog.data.win_amount || '0') > 0 }">
                ¥{{ detailDialog.data.win_amount || '0' }}
              </span>
            </el-descriptions-item>
            <el-descriptions-item label="投注时间">
               <span class="time-text">{{ formatTime(detailDialog.data.create_time) }}</span>
             </el-descriptions-item>
             <el-descriptions-item label="结算时间">
               <span class="time-text">{{ detailDialog.data.settle_time ?? '未结算' }}</span>
             </el-descriptions-item>
          </el-descriptions>
        </div>
        
        <div class="mt-4">
          <el-card class="mt-2">
            <div class="bet-content-display">
              <el-descriptions :column="1" border>
                <el-descriptions-item label="投注类型">
                  <el-tag type="primary">{{ detailDialog.data.bet_type_name }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="投注注数">
                  <span class="highlight-number">{{ detailDialog.data.bet_content?.note || 0 }}注</span>
                </el-descriptions-item>
                <el-descriptions-item label="单注金额">
                  <span class="highlight-money">¥{{ formatMoneyFromCents(detailDialog.data.bet_content?.money ? parseFloat(detailDialog.data.bet_content.money) * 100 : 0) }}</span>
                </el-descriptions-item>
                <el-descriptions-item label="投注号码">
                  <div class="bet-numbers">
                    <template v-if="detailDialog.data.bet_content?.numbers">
                      <div v-if="formatBetNumbers(detailDialog.data.bet_content.numbers)" class="numbers-display">
                        {{ formatBetNumbers(detailDialog.data.bet_content.numbers) }}
                      </div>
                      <div v-else class="raw-numbers">
                        <pre>{{ JSON.stringify(detailDialog.data.bet_content.numbers, null, 2) }}</pre>
                      </div>
                    </template>
                    <span v-else class="no-data">暂无数据</span>
                  </div>
                </el-descriptions-item>
              </el-descriptions>

            </div>
          </el-card>
        </div>
        
        <div class="mt-4">
          <el-card class="mt-2">
            <div class="draw-result-display">
              <template v-if="detailDialog.data.draw_result && detailDialog.data.draw_result.length > 0">
                <el-descriptions :column="1" border>
                  <el-descriptions-item label="开奖号码">
                    <span class="draw-numbers">{{ detailDialog.data.draw_result.join(' ') }}</span>
                  </el-descriptions-item>
                  <el-descriptions-item label="开奖时间">
                    {{ detailDialog.data.draw_time ?? '未开奖' }}
                  </el-descriptions-item>
                  <el-descriptions-item label="中奖状态">
                    <el-tag :type="detailDialog.data.status === 'WINNING' ? 'success' : 'info'">
                      {{ detailDialog.data.status === 'WINNING' ? '中奖' : detailDialog.data.status === 'PAID' ? '已派奖' : '未中奖' }}
                    </el-tag>
                  </el-descriptions-item>
                </el-descriptions>
              </template>
              <div v-else class="no-result">
                <text>暂未开奖</text>
              </div>
            </div>
          </el-card>
        </div>
      </div>
      
      <template #footer>
         <div class="dialog-footer">
           <div class="footer-actions">
             <el-button 
               v-if="canCancel(detailDialog.data)" 
               type="warning" 
               @click="cancelOrder(detailDialog.data.id)"
               :loading="detailDialog.loading"
             >
               取消订单
             </el-button>
             <el-button 
               v-if="canSettle(detailDialog.data)" 
               type="success" 
               @click="settleOrder(detailDialog.data.id)"
               :loading="detailDialog.loading"
             >
               手动结算
             </el-button>
            <el-button @click="detailDialog.visible = false">关闭</el-button>
           </div>
         </div>
       </template>
    </el-dialog>


  </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted, reactive } from 'vue'
import baTableClass from '/@/utils/baTable'
import { baTableApi } from '/@/api/common'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { ElMessage, ElMessageBox } from 'element-plus'

defineOptions({
  name: 'betorder/index',
})
const tableRef = ref()

const detailDialog = reactive({
  visible: false,
  loading: false,
  data: {} as any
})



const baTable = new baTableClass(
  new baTableApi('/admin/lottery.BetOrder/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
      { label: '订单号', prop: 'order_no', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 201 },
      { label: '用户', prop: 'user.username', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '期号', prop: 'period_no', align: 'center', operator: 'LIKE', width:105, operatorPlaceholder: '模糊查询' },
      { label: '彩种', prop: 'lotteryType.type_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '投注类型', prop: 'bet_type_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '投注金额', prop: 'bet_amount', align: 'center', operator: false },
      { label: '倍数', prop: 'multiple', align: 'center', operator: 'RANGE', width: 80 },
      { label: '总金额', prop: 'total_amount', align: 'center', operator: false },
      { label: '中奖金额', prop: 'win_amount', align: 'center', operator: false },
      { label: '代理商ID', prop: 'agent_id', align: 'center', operator: 'eq', width: 100 },
      { label: '状态', prop: 'status', align: 'center', operator: 'eq', replaceValue: {
        'PENDING': '待确认',
        'CONFIRMED': '待开奖',
        'WINNING': '中奖',
        'PAID': '已派奖',
        'LOSING': '未中奖',
        'CANCELLED': '已取消',
        'REFUNDED': '已退款'
      }, render: 'tag', custom: {
        'PENDING': 'warning',
        'CONFIRMED': 'primary',
        'WINNING': 'success',
        'PAID': 'success',
        'LOSING': 'info',
        'CANCELLED': 'danger',
        'REFUNDED': 'warning'
      }},
      { label: 'IP地址', prop: 'ip', align: 'center', operator: 'LIKE', width: 120 },
      { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
      {
          label: '操作',
          align: 'center',
          fixed: 'right',
          width: 70,
          render: 'buttons',
          buttons: [
            {
              render: 'tipButton',
              class: 'el-button el-button--primary is-link el-button--small',
              text: '详情',
              title: '详情',
              click: (row: any) => showDetail(row.id)
            }
          ],
          operator: false,
        },
    ],
    dblClickNotEditColumn: [undefined],
    defaultOrder: { prop: 'id', order: 'desc' },
  },
  {
    // 禁用添加、编辑、删除操作
  }
)

provide('baTable', baTable)

const getStatusType = (status: string) => {
  const types: Record<string, string> = {
    'PENDING': 'warning',
    'CONFIRMED': 'primary',
    'WINNING': 'success',
    'PAID': 'success',
    'LOSING': 'info',
    'CANCELLED': 'danger',
    'REFUNDED': 'warning'
  }
  return types[status] || 'info'
}

const canCancel = (row: any) => {
  return ['PENDING', 'CONFIRMED'].includes(row.status)
}

const canSettle = (row: any) => {
  return row.status === 'CONFIRMED'
}

const showDetail = (id: number) => {
  detailDialog.loading = true
  detailDialog.visible = true
  
  baTable.api.postData('info', { id }).then((res) => {
    if (res.code === 1) {
      detailDialog.data = res.data.order
    }
  }).finally(() => {
    detailDialog.loading = false
  })
}

const cancelOrder = (id: number) => {
  ElMessageBox.prompt('请输入取消原因', '取消订单', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    inputPattern: /.+/,
    inputErrorMessage: '请输入取消原因'
  }).then(({ value }) => {
    baTable.api.postData('cancel', { id, reason: value }).then((res) => {
      if (res.code === 1) {
        ElMessage.success(res.msg)
        baTable.onTableHeaderAction('refresh', {})
        if (detailDialog.visible) {
          showDetail(id)
        }
      }
    })
  })
}

const settleOrder = (id: number) => {
  ElMessageBox.confirm('确定要手动结算此订单吗？', '手动结算', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(() => {
    baTable.api.postData('settle', { id }).then((res) => {
      if (res.code === 1) {
        ElMessage.success(res.msg)
        baTable.onTableHeaderAction('refresh', {})
        if (detailDialog.visible) {
          showDetail(id)
        }
      }
    })
  })
}



// 格式化金额显示（原有函数，用于已经是元的金额）
const formatMoney = (amount: any) => {
  if (!amount) return '0.00'
  const num = parseFloat(amount.toString())
  return num.toFixed(2)
}

// 格式化金额显示（从分转换为元）
const formatMoneyFromCents = (amount: any) => {
  if (!amount) return '0.00'
  const num = parseFloat(amount.toString()) / 100
  return num.toFixed(2)
}

// 格式化时间显示
const formatTime = (timestamp: any) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// 格式化投注号码显示
const formatBetNumbers = (numbers: any) => {
  if (!numbers) return ''
  
  try {
    // 如果是字符串，直接返回
    if (typeof numbers === 'string') {
      return numbers
    }
    
    let result = ''
    
    // 处理二码定位类型 (positionCombo + numbers对象)
    if (numbers.positionCombo && numbers.numbers) {
      result += `位置组合: ${numbers.positionCombo}`
      
      const numbersObj = numbers.numbers
      if (typeof numbersObj === 'object' && numbersObj !== null) {
        const positionNames: Record<string, string> = {
          'bai': '百位',
          'shi': '十位', 
          'ge': '个位'
        }
        
        const positionResults: string[] = []
        Object.keys(numbersObj).forEach(position => {
          const nums = numbersObj[position]
          if (Array.isArray(nums) && nums.length > 0) {
            const positionName = positionNames[position] || position
            positionResults.push(`${positionName}: ${nums.join(', ')}`)
          }
        })
        
        if (positionResults.length > 0) {
          result += ` | ${positionResults.join(' | ')}`
        }
      }
      
      return result
    }
    
    // 处理一码定位类型 (position + numbers数组)
    if (numbers.position && numbers.numbers) {
      const positionNames: Record<string, string> = {
        'bai': '百位',
        'shi': '十位',
        'ge': '个位'
      }
      const positionName = positionNames[numbers.position] || numbers.position
      const nums = Array.isArray(numbers.numbers) ? numbers.numbers : []
      return `${positionName}: ${nums.join(', ')}`
    }
    
    // 处理一码不定位类型 (只有numbers数组)
    if (numbers.numbers && Array.isArray(numbers.numbers) && !numbers.position && !numbers.positionCombo) {
      return `号码: ${numbers.numbers.join(', ')}`
    }
    
    // 处理组三拖胆类型
    if (numbers.datadan && numbers.value) {
      const datadan = Array.isArray(numbers.datadan) ? numbers.datadan : []
      const value = Array.isArray(numbers.value) ? numbers.value : []
      
      if (datadan.length > 0) {
        result += `胆码: ${datadan.join(', ')}`
      }
      
      if (value.length > 0) {
        const valueStr = value.map(arr => {
          return Array.isArray(arr) ? arr.join(', ') : arr
        }).join(' | ')
        result += result ? ` | 拖码: ${valueStr}` : `拖码: ${valueStr}`
      }
      
      return result
    }
    
    // 处理其他格式的号码
    if (Array.isArray(numbers)) {
      return numbers.map(item => {
        if (Array.isArray(item)) {
          return item.join(', ')
        }
        return item
      }).join(' | ')
    }
    
    // 处理对象格式
    if (typeof numbers === 'object') {
      const keys = Object.keys(numbers)
      return keys.map(key => {
        const value = numbers[key]
        if (Array.isArray(value)) {
          return `${key}: ${value.join(', ')}`
        }
        return `${key}: ${value}`
      }).join(' | ')
    }
    
    return numbers.toString()
  } catch (error) {
    console.error('格式化投注号码失败:', error)
    return ''
  }
}

onMounted(() => {
  baTable.table.ref = tableRef.value
  baTable.mount()
  baTable.getIndex()?.then(() => {
    baTable.initSort()
    baTable.dragSort()
  })
})
</script>

<style scoped lang="scss">
.order-detail {
  .detail-section {
    margin-bottom: 24px;
    
    .section-title {
      margin: 0 0 12px 0;
      padding: 8px 12px;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      border-left: 4px solid #409eff;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 600;
      color: #303133;
    }
    
    .el-descriptions {
      margin-bottom: 0;
    }
  }
  
  .order-no {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #409eff;
  }
  
  .period-no {
    font-weight: bold;
    color: #67c23a;
  }
  
  .ip-address {
    font-family: 'Courier New', monospace;
    color: #909399;
  }
  
  .amount-text {
    color: #e6a23c;
    font-weight: bold;
    font-size: 15px;
  }
  
  .multiple-text {
    color: #409eff;
    font-weight: bold;
  }
  
  .total-amount {
    color: #f56c6c;
    font-weight: bold;
    font-size: 16px;
  }
  
  .win-amount {
    color: #909399;
    font-weight: bold;
    font-size: 15px;
    
    &.has-win {
      color: #67c23a;
    }
  }
  
  .commission-amount {
    color: #e6a23c;
    font-weight: bold;
    font-size: 14px;
  }
  
  .gift-ratio {
    color: #67c23a;
    font-weight: bold;
    font-size: 14px;
  }
  
  .no-agent {
    color: #909399;
    font-style: italic;
  }
  
  .time-text {
     color: #606266;
     font-size: 13px;
   }
   
   pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    font-size: 12px;
    max-height: 200px;
    overflow-y: auto;
  }
  
  .dialog-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    
    .footer-actions {
      display: flex;
      gap: 12px;
    }
  }
}

.bet-content-display {
  .highlight-number {
    color: #409eff;
    font-weight: bold;
    font-size: 16px;
  }
  
  .highlight-money {
    color: #f56c6c;
    font-weight: bold;
    font-size: 16px;
  }
  
  .bet-numbers {
    .numbers-display {
      background: #f0f9ff;
      border: 1px solid #e1f5fe;
      border-radius: 6px;
      padding: 12px;
      font-family: 'Courier New', monospace;
      font-size: 14px;
      color: #1976d2;
      line-height: 1.6;
      word-break: break-all;
    }
    
    .raw-numbers {
      pre {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        margin: 0;
        font-size: 12px;
        max-height: 150px;
      }
    }
    
    .no-data {
      color: #909399;
      font-style: italic;
    }
  }
  

 }
 
 .draw-result-display {
   .draw-numbers {
     font-family: 'Courier New', monospace;
     font-size: 16px;
     font-weight: bold;
     color: #e74c3c;
     letter-spacing: 2px;
   }
   
   .no-result {
     text-align: center;
     color: #909399;
   }
 }
 
</style>