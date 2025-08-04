<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\service\fc3d\Fc3dValidationService;
use app\service\fc3d\Fc3dCalculationService;

class TestXingtai extends Command
{
    protected function configure()
    {
        $this->setName('test:xingtai')
            ->setDescription('测试和值大小、和值单双的中奖验证和奖金计算');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('=== 测试和值大小 ===');
        
        // 模拟订单数据
        $order = [
            'order_no' => 'TEST001',
            'bet_content' => '{"type_key":"hezhi_daxiao","type_name":"和值大小","numbers":["大"],"note":"1","money":"10","multiplier":"1","calculated_amount":10}',
            'bet_amount' => 1000, // 10元 = 1000分
            'multiple' => 1,
            'lottery_type_id' => 14,
            'bet_type' => 'hezhi_daxiao'
        ];
        
        // 测试开奖号码：8,9,7 (和值=24，属于"大")
        $drawNumbers = '8,9,7';
        
        // 同时测试不中奖的情况
        $orderLose = [
            'order_no' => 'TEST001_LOSE',
            'bet_content' => '{"type_key":"hezhi_daxiao","type_name":"和值大小","numbers":["小"],"note":"1","money":"10","multiplier":"1","calculated_amount":10}',
            'bet_amount' => 1000, // 10元 = 1000分
            'multiple' => 1,
            'lottery_type_id' => 14,
            'bet_type' => 'hezhi_daxiao'
        ];
        
        $output->writeln('订单信息：');
        $output->writeln('投注内容：' . $order['bet_content']);
        $output->writeln('开奖号码：' . $drawNumbers);
        $output->writeln('和值：' . array_sum(explode(',', $drawNumbers)));
        
        // 测试中奖验证
        $validationService = new Fc3dValidationService();
        $betContent = json_decode($order['bet_content'], true);
        $winResult = $validationService->checkWin('hezhi_daxiao', $betContent, $drawNumbers);
        
        $output->writeln('');
        $output->writeln('中奖验证结果：');
        $output->writeln('是否中奖：' . ($winResult['is_win'] ? '是' : '否'));
        $output->writeln('中奖注数：' . $winResult['win_count']);
        if (isset($winResult['win_details'])) {
            $output->writeln('选择：' . json_encode($winResult['win_details']['selected']));
            $output->writeln('实际：' . $winResult['win_details']['actual']);
        }
        
        // 测试奖金计算
        $calculationService = new Fc3dCalculationService();
        $winAmountResult = $calculationService->calculateWinAmount('hezhi_daxiao', $betContent, $drawNumbers, $order['bet_amount'], $order['multiple']);
        $winAmount = $winAmountResult['success'] ? $winAmountResult['data']['win_amount'] : 0;
        $output->writeln('');
        $output->writeln('计算的中奖金额：' . $winAmount . '分 (' . ($winAmount/100) . '元)');
        
        // 测试不中奖情况
        $output->writeln('');
        $output->writeln('=== 测试不中奖情况（选择"小"但开奖和值18属于"大"）===');
        $betContentLose = json_decode($orderLose['bet_content'], true);
        $winResultLose = $validationService->checkWin('hezhi_daxiao', $betContentLose, $drawNumbers);
        $output->writeln('是否中奖：' . ($winResultLose['is_win'] ? '是' : '否'));
        $winAmountLoseResult = $calculationService->calculateWinAmount('hezhi_daxiao', $betContentLose, $drawNumbers, $orderLose['bet_amount'], $orderLose['multiple']);
        $winAmountLose = $winAmountLoseResult['success'] ? $winAmountLoseResult['data']['win_amount'] : 0;
        $output->writeln('计算的中奖金额：' . $winAmountLose . '分 (' . ($winAmountLose/100) . '元)');
        
        // 测试和值单双
        $output->writeln('');
        $output->writeln('=== 测试和值单双 ===');
        
        $order2 = [
            'order_no' => 'TEST002',
            'bet_content' => '{"type_key":"hezhi_danshuang","type_name":"和值单双","numbers":["双"],"note":"1","money":"10","multiplier":"1","calculated_amount":10}',
            'bet_amount' => 1000, // 10元 = 1000分
            'multiple' => 1,
            'lottery_type_id' => 14,
            'bet_type' => 'hezhi_danshuang'
        ];
        
        $output->writeln('订单信息：');
        $output->writeln('投注内容：' . $order2['bet_content']);
        $output->writeln('开奖号码：' . $drawNumbers);
        $output->writeln('和值：' . array_sum(explode(',', $drawNumbers)));
        
        // 测试中奖验证
        $betContent2 = json_decode($order2['bet_content'], true);
        $winResult2 = $validationService->checkWin('hezhi_danshuang', $betContent2, $drawNumbers);
        
        $output->writeln('');
        $output->writeln('中奖验证结果：');
        $output->writeln('是否中奖：' . ($winResult2['is_win'] ? '是' : '否'));
        $output->writeln('中奖注数：' . $winResult2['win_count']);
        if (isset($winResult2['win_details'])) {
            $output->writeln('选择：' . json_encode($winResult2['win_details']['selected']));
            $output->writeln('实际：' . $winResult2['win_details']['actual']);
        }
        
        // 测试奖金计算
        $winAmount2Result = $calculationService->calculateWinAmount('hezhi_danshuang', $betContent2, $drawNumbers, $order2['bet_amount'], $order2['multiple']);
        $winAmount2 = $winAmount2Result['success'] ? $winAmount2Result['data']['win_amount'] : 0;
        $output->writeln('');
        $output->writeln('计算的中奖金额：' . $winAmount2 . '分 (' . ($winAmount2/100) . '元)');
        
        $output->writeln('');
        $output->writeln('测试完成！');
        
        return 0;
    }
}