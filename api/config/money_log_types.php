<?php

/**
 * 资金变动类型统一配置
 * 用于前端和后端统一调用，避免重复定义
 */

return [
    // 数据库类型到前端类型的映射
    'db_to_frontend' => [
        'RECHARGE_ADD' => 'recharge',
        'WITHDRAW_DEDUCT' => 'withdraw',
        'BET_DEDUCT' => 'bet',
        'PRIZE_ADD' => 'win',
        'COMMISSION_ADD' => 'commission',
        'RED_PACKET_SEND' => 'redpacket',
        'RED_PACKET_RECEIVE' => 'redpacket',
        'RED_PACKET_CANCEL' => 'redpacket',
        'ADMIN_ADD' => 'other',
        'ADMIN_DEDUCT' => 'other',
        'AGENT_ADD_TO_USER' => 'agent_add',
        'AGENT_DEDUCT_FROM_USER' => 'agent_deduct',
        'ACTIVITY_REWARD_ADD' => 'activity_reward',
        'BONUS_ADD' => 'bonus',
        'PROMOTION_INCOME' => 'promotion',
        'WITHDRAW_REFUND_ADD' => 'withdraw_refund',
        'BET_REFUND_ADD' => 'bet_refund'
    ],

    // 前端类型到数据库类型的映射
    'frontend_to_db' => [
        'recharge' => ['RECHARGE_ADD', 'RECHARGE_GIFT_ADD'],
        'withdraw' => 'WITHDRAW_DEDUCT',
        'bet' => 'BET_DEDUCT',
        'win' => 'PRIZE_ADD',
        'rebate' => 'COMMISSION_ADD', // 兼容旧版本
        'commission' => 'COMMISSION_ADD',
        'redpacket' => ['RED_PACKET_SEND', 'RED_PACKET_RECEIVE', 'RED_PACKET_CANCEL'],
        'agent_add' => 'AGENT_ADD_TO_USER',
        'agent_deduct' => 'AGENT_DEDUCT_FROM_USER',
        'activity_reward' => 'ACTIVITY_REWARD_ADD',
        'bonus' => 'BONUS_ADD',
        'promotion' => 'PROMOTION_INCOME',
        'withdraw_refund' => 'WITHDRAW_REFUND_ADD',
        'bet_refund' => 'BET_REFUND_ADD',
        'other' => [
            'ADMIN_ADD',
            'ADMIN_DEDUCT',
            'AGENT_ADD_TO_USER',
            'AGENT_DEDUCT_FROM_USER',
            'ACTIVITY_REWARD_ADD',
            'BONUS_ADD',
            'PROMOTION_INCOME',
            'WITHDRAW_REFUND_ADD',
            'BET_REFUND_ADD',
            'RECHARGE_GIFT_DEDUCT'
        ]
    ],

    // 类型分组配置（用于前端筛选）
    'type_groups' => [
        'recharge' => ['RECHARGE_ADD', 'RECHARGE_GIFT_ADD'],
        'withdraw' => ['WITHDRAW_DEDUCT'],
        'bet' => ['BET_DEDUCT'],
        'win' => ['PRIZE_ADD'],
        'commission' => ['COMMISSION_ADD'],
        'redpacket' => ['RED_PACKET_SEND', 'RED_PACKET_RECEIVE', 'RED_PACKET_CANCEL'],
        'agent_add' => ['AGENT_ADD_TO_USER'],
        'agent_deduct' => ['AGENT_DEDUCT_FROM_USER'],
        'activity_reward' => ['ACTIVITY_REWARD_ADD'],
        'bonus' => ['BONUS_ADD'],
        'promotion' => ['PROMOTION_INCOME'],
        'withdraw_refund' => ['WITHDRAW_REFUND_ADD'],
        'bet_refund' => ['BET_REFUND_ADD'],
        'other' => [
            'ADMIN_ADD',
            'ADMIN_DEDUCT',
            'AGENT_ADD_TO_USER',
            'AGENT_DEDUCT_FROM_USER',
            'ACTIVITY_REWARD_ADD',
            'BONUS_ADD',
            'PROMOTION_INCOME',
            'WITHDRAW_REFUND_ADD',
            'BET_REFUND_ADD',
            'RECHARGE_GIFT_DEDUCT'
        ]
    ],

    // 类型中文名称
    'type_names' => [
        'recharge' => '充值',
        'withdraw' => '提现',
        'bet' => '投注',
        'win' => '中奖',
        'commission' => '佣金',
        'redpacket' => '红包',
        'agent_add' => '代理商加款',
        'agent_deduct' => '代理商扣款',
        'activity_reward' => '活动奖励',
        'bonus' => '奖金',
        'promotion' => '推广收益',
        'withdraw_refund' => '提现退款',
        'bet_refund' => '投注退款',
        'other' => '其他'
    ],

    // 类型图标配置
    'type_icons' => [
        'recharge' => 'plus-circle',
        'withdraw' => 'minus-circle',
        'bet' => 'play-circle',
        'win' => 'trophy',
        'commission' => 'gift',
        'redpacket' => 'red-packet',
        'agent_add' => 'arrow-up-circle',
        'agent_deduct' => 'arrow-down-circle',
        'activity_reward' => 'star',
        'bonus' => 'medal',
        'promotion' => 'share',
        'withdraw_refund' => 'reload',
        'bet_refund' => 'undo',
        'other' => 'more-circle'
    ],

    // 类型样式类配置
    'type_classes' => [
        'recharge' => 'icon-income',
        'withdraw' => 'icon-expense',
        'bet' => 'icon-expense',
        'win' => 'icon-income',
        'commission' => 'icon-income',
        'redpacket' => 'icon-income',
        'agent_add' => 'icon-income',
        'agent_deduct' => 'icon-expense',
        'activity_reward' => 'icon-income',
        'bonus' => 'icon-income',
        'promotion' => 'icon-income',
        'withdraw_refund' => 'icon-income',
        'bet_refund' => 'icon-income',
        'other' => 'icon-neutral'
    ]
];