/**
 * 资金变动类型统一配置
 * 用于前端统一调用，避免重复定义
 */

export const MONEY_LOG_TYPES = {
  // 类型中文名称
  TYPE_NAMES: {
    recharge: '充值',
    withdraw: '提现',
    bet: '投注',
    win: '中奖',
    commission: '佣金',
    redpacket: '红包',
    agent_add: '代理加款',
    agent_deduct: '代理扣款',
    activity_reward: '活动奖励',
    bonus: '奖金',
    promotion: '推广收益',
    withdraw_refund: '提现退款',
    bet_refund: '投注退款',
    other: '其他'
  },

  // 类型图标配置
  TYPE_ICONS: {
    recharge: 'plus-circle',
    withdraw: 'minus-circle',
    bet: 'play-circle',
    win: 'trophy',
    commission: 'gift',
    redpacket: 'red-packet',
    agent_add: 'arrow-up-circle',
    agent_deduct: 'arrow-down-circle',
    activity_reward: 'star',
    bonus: 'medal',
    promotion: 'share',
    withdraw_refund: 'reload',
    bet_refund: 'undo',
    other: 'more-circle'
  },

  // 类型样式类配置
  TYPE_CLASSES: {
    recharge: 'icon-income',
    withdraw: 'icon-expense',
    bet: 'icon-expense',
    win: 'icon-income',
    commission: 'icon-income',
    redpacket: 'icon-income',
    agent_add: 'icon-income',
    agent_deduct: 'icon-expense',
    activity_reward: 'icon-income',
    bonus: 'icon-income',
    promotion: 'icon-income',
    withdraw_refund: 'icon-income',
    bet_refund: 'icon-income',
    other: 'icon-neutral'
  },

  // 分类列表（用户端简化版）
  CATEGORY_LIST: [
    { name: '全部', value: '' },
    { name: '充值', value: 'recharge' },
    { name: '提现', value: 'withdraw' },
    { name: '投注', value: 'bet' },
    { name: '中奖', value: 'win' },
    { name: '佣金', value: 'commission' },
    { name: '红包', value: 'redpacket' },
    { name: '代理加款', value: 'agent_add' },
    { name: '代理扣款', value: 'agent_deduct' },
    { name: '其他', value: 'other' }
  ],

  // 分类到类型的映射（用户端）
  CATEGORY_TYPE_MAP: {
    '': '', // 全部
    'recharge': 'recharge',
    'withdraw': 'withdraw',
    'bet': 'bet',
    'win': 'win',
    'commission': 'commission',
    'redpacket': 'redpacket',
    'other': 'other'
  }
}

// 获取类型名称
export function getTypeName(type) {
  return MONEY_LOG_TYPES.TYPE_NAMES[type] || type
}

// 获取类型图标
export function getTypeIcon(type) {
  return MONEY_LOG_TYPES.TYPE_ICONS[type] || 'more-circle'
}

// 获取类型样式类
export function getTypeClass(type) {
  return MONEY_LOG_TYPES.TYPE_CLASSES[type] || 'icon-neutral'
}

// 根据分类获取类型
export function getTypeByCategory(category) {
  return MONEY_LOG_TYPES.CATEGORY_TYPE_MAP[category] || ''
}