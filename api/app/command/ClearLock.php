<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Cache;

/**
 * 清除自动开奖锁定状态命令
 */
class ClearLock extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('clearlock')
            ->setDescription('清除自动开奖锁定状态')
            ->addArgument('lottery_code', Argument::OPTIONAL, '彩种代码', 'all')
            ->addOption('list', 'l', Option::VALUE_NONE, '列出所有锁定状态');
    }

    protected function execute(Input $input, Output $output)
    {
        $lotteryCode = $input->getArgument('lottery_code');
        $listOnly = $input->getOption('list');
        
        // 支持的彩种列表
        $supportedLotteries = ['3d', 'pl3'];
        
        if ($listOnly) {
            $output->writeln('检查锁定状态:');
            foreach ($supportedLotteries as $code) {
                $lockKey = "autodraw_lock_{$code}";
                $lockValue = Cache::get($lockKey);
                if ($lockValue) {
                    $lockTime = date('Y-m-d H:i:s', $lockValue);
                    $output->writeln("  {$code}: 已锁定 (锁定时间: {$lockTime})");
                } else {
                    $output->writeln("  {$code}: 未锁定");
                }
            }
            return;
        }
        
        if ($lotteryCode === 'all') {
            // 清除所有彩种的锁
            $clearedCount = 0;
            foreach ($supportedLotteries as $code) {
                $lockKey = "autodraw_lock_{$code}";
                if (Cache::get($lockKey)) {
                    Cache::delete($lockKey);
                    $output->writeln("已清除 {$code} 的锁定状态");
                    $clearedCount++;
                }
            }
            
            if ($clearedCount === 0) {
                $output->writeln('没有发现锁定状态');
            } else {
                $output->writeln("总共清除了 {$clearedCount} 个锁定状态");
            }
        } else {
            // 清除指定彩种的锁
            if (!in_array($lotteryCode, $supportedLotteries)) {
                $output->writeln("不支持的彩种: {$lotteryCode}");
                $output->writeln('支持的彩种: ' . implode(', ', $supportedLotteries));
                return;
            }
            
            $lockKey = "autodraw_lock_{$lotteryCode}";
            if (Cache::get($lockKey)) {
                Cache::delete($lockKey);
                $output->writeln("已清除 {$lotteryCode} 的锁定状态");
            } else {
                $output->writeln("{$lotteryCode} 没有锁定状态");
            }
        }
    }
}