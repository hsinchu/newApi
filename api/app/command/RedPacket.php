<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use app\common\model\RedPacket as RedPacketModel;
use app\common\model\RedPacketRecord;

class RedPacket extends Command
{
    protected function configure()
    {
        $this->setName('redpacket')
            ->setDescription('红包过期处理任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始处理红包状态...');
        
        try {
            // 处理过期红包
            $expiredCount = $this->handleExpiredRedPackets();
            $output->writeln("处理完成，共处理了 {$expiredCount} 个过期红包");
            
            // 处理已完成红包状态
            $finishedCount = $this->handleFinishedRedPackets();
            $output->writeln("状态修复完成，共修复了 {$finishedCount} 个已完成红包");
            
            Log::info("红包处理任务完成，过期红包: {$expiredCount}，状态修复: {$finishedCount}");
        } catch (\Exception $e) {
            $output->writeln('处理红包时发生错误: ' . $e->getMessage());
            Log::error('红包处理任务失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理过期红包
     * @return int 处理的红包数量
     */
    private function handleExpiredRedPackets(): int
    {
        $count = 0;
        
        // 开启事务
        Db::startTrans();
        
        try {
            // 查找所有过期的红包
            $expiredRedPackets = RedPacketModel::where('status', 'ACTIVE')
                ->where('expire_time', '<', time())
                ->select();
            
            foreach ($expiredRedPackets as $redPacket) {
                // 更新红包状态为已过期
                $redPacket->status = 'EXPIRED';
                $redPacket->save();
                
                // 计算剩余金额
                $totalAmount = $redPacket->total_amount;
                $receivedAmount = RedPacketRecord::where('red_packet_id', $redPacket->id)
                    ->sum('amount');
                $remainingAmount = $totalAmount - $receivedAmount;
                
                // 如果有剩余金额，退还给创建者
                if ($remainingAmount > 0) {
                    $this->refundRemainingAmount($redPacket, $remainingAmount);
                }
                
                $count++;
                Log::info("红包ID {$redPacket->id} 已过期，剩余金额 {$remainingAmount} 已退还");
            }
            
            // 提交事务
            Db::commit();
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw $e;
        }
        
        return $count;
    }
    
    /**
     * 处理已完成红包状态
     * @return int 处理的红包数量
     */
    private function handleFinishedRedPackets(): int
    {
        $count = 0;
        
        try {
            // 查找所有remaining_count为0但状态不是FINISHED的红包
            $redPackets = RedPacketModel::where('status', 'ACTIVE')
                ->where('remaining_count', '<=', 0)
                ->select();
            
            foreach ($redPackets as $redPacket) {
                // 重新计算remaining_count以确保准确性
                $amountListRaw = $redPacket->getData('amount_list');
                $amountList = is_string($amountListRaw) ? json_decode($amountListRaw, true) : $amountListRaw;
                $remainingCount = 0;
                
                if (!empty($amountList)) {
                    foreach ($amountList as $amountData) {
                        foreach ($amountData as $amount_val => $user_id) {
                            if ($user_id == 0) {
                                $remainingCount++;
                            }
                        }
                    }
                }
                
                // 如果确实没有剩余红包，更新状态为FINISHED
                if ($remainingCount <= 0) {
                    $redPacket->remaining_count = 0;
                    $redPacket->status = 'FINISHED';
                    $redPacket->save();
                    $count++;
                    
                    Log::info("红包ID {$redPacket->id} 状态已更新为FINISHED");
                }
            }
            
        } catch (\Exception $e) {
            Log::error('处理已完成红包状态失败: ' . $e->getMessage());
            throw $e;
        }
        
        return $count;
    }
    
    /**
     * 退还剩余金额给创建者
     * @param RedPacketModel $redPacket 红包模型
     * @param int $amount 退还金额（分）
     */
    private function refundRemainingAmount(RedPacketModel $redPacket, int $amount): void
    {
        // 根据红包类型确定退还对象
        if ($redPacket->agent_id > 0) {
            // 退还未领取金额给代理商
            $financeService = new \app\service\FinanceService();
            $financeService->adjustUserBalance($redPacket->agent_id, $amount, '红包过期退款', 'RED_PACKET_CANCEL');
        }
    }
    
}