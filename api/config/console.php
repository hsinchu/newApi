<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'autobet' => 'app\command\AutoBet',   //自动投注
        'autodraw' => 'app\command\AutoDraw', //自动开奖，把待派奖的订单加入redis队列，把未中奖的订单执行未中奖佣金处理
        'autopaid' => 'app\command\AutoPaid', //自动派奖，从redis队列中取出待派奖订单，查询订单状态，更新订单状态为已派奖（同时使用adjustUserBalance方法更新用户余额）
        'clearlock' => 'app\command\ClearLock', //清除自动开奖锁定状态
        'rebate' => 'app\command\Rebate', //代理返水功能
        'autobak' => 'app\command\AutoBak', //自动备份数据库并发送邮件通知
        'redpacket' => 'app\command\RedPacket', //红包过期处理任务
    ],
    
];
