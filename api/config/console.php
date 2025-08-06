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
    ],
    
    // 命令执行配置
    'command_config' => [
        // 防重复执行锁配置
        'lock' => [
            'enable' => true,           // 启用锁机制
            'prefix' => 'cmd_lock_',    // 锁前缀
            'expire' => 300,            // 默认锁过期时间（秒）
        ],
        
        // 队列配置
        'queue' => [
            'payout_queue' => 'payout_queue',  // 派奖队列名称
            'max_retry' => 3,                  // 最大重试次数
            'retry_delay' => 60,               // 重试延迟（秒）
        ],
        
        // 日志配置
        'log' => [
            'enable' => true,           // 启用命令日志
            'level' => 'info',          // 日志级别
            'path' => 'command',        // 日志路径
        ],
    ],
];
