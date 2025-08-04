<?php

namespace app\api\controller;

use app\common\model\BetOrder;
use app\common\controller\Frontend;
use think\facade\Log;
use think\facade\Db;
use Throwable;

class Order extends Frontend
{

    public function initialize(): void
    {
        parent::initialize();
        
        // 验证用户是否为代理商
        if (!$this->auth->isLogin()) {
            $this->error(__('Please login first'));
        }
        
        if ($this->auth->is_agent != 0) {
            $this->error('您不是用户，无权访问此接口');
        }
    }

    /**
     * 获取本期投注,单个彩种
     */
    public function getThisOrders(){

    }

    /**
     * 获取订单列表
     */
    public function getOrders(): void
    {
        try {
            $userId = $this->auth->id;
            $params = $this->request->param(['page', 'limit', 'status', 'lottery_code', 'keyword']);
            
            $page = $params['page'] ?? 1;
            $limit = $params['limit'] ?? 10;
            $status = $params['status'] ?? '';
            $lotteryCode = $params['lottery_code'] ?? '';
            $keyword = $params['keyword'] ?? '';
            
            // 构建查询条件
            $where = [
                ['user_id', '=', $userId]
            ];
            
            // 彩种筛选
            if (!empty($lotteryCode)) {
                $where[] = ['lottery_code', '=', $lotteryCode];
            }
            
            // 状态筛选 - 支持前端传入的数字状态码和字符串状态
            if ($status !== '') {
                // 前端状态码映射到数据库状态
                $statusMap = [
                    '0' => 'CONFIRMED',    // 待开奖
                    '1' => 'WINNING',      // 待派奖
                    '2' => 'LOSING',       // 未中奖
                    '3' => 'PAID',         // 已派奖
                    '4' => 'CANCELLED',    // 已取消
                    '5' => 'REFUNDED'      // 已退款
                ];
                
                if (isset($statusMap[$status])) {
                    $where[] = ['status', '=', $statusMap[$status]];
                } else {
                    // 如果直接传入状态字符串，也支持
                    $validStatuses = ['CONFIRMED', 'WINNING', 'PAID', 'LOSING', 'CANCELLED', 'REFUNDED'];
                    if (in_array(strtoupper($status), $validStatuses)) {
                        if($status == 'WINNING'){
                            $where[] = ['status', 'in', ['WINNING', 'PAID']];
                        }else{
                            $where[] = ['status', '=', strtoupper($status)];
                        }
                    }
                }
            }
            
            // 关键词搜索（订单号、期号）
            if (!empty($keyword)) {
                $where[] = ['order_no|period_no', 'like', '%' . $keyword . '%'];
            }
            
            // 查询订单数据，使用关联查询获取彩种信息
            $orders = BetOrder::where($where)
                ->with(['lotteryType']) 
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]
            );
            
            // 转换数据格式，添加状态映射文本和彩种信息
            $list = [];
            foreach ($orders->items() as $order) {
                $item = $order->toArray();
                
                // 添加状态文本映射（使用模型访问器）
                $item['status_text'] = $order->status_text;
                
                // 添加彩种信息
                if ($order->lotteryType) {
                    $item['typename'] = $order->lotteryType->type_name;
                    $item['typeicon'] = $order->lotteryType->type_icon;
                } else {
                    // 如果没有关联数据，使用默认值
                    $item['typename'] = $this->getLotteryName($order->lottery_code);
                    $item['typeicon'] = '';
                }
                
                // 前端状态码映射
                $frontendStatusMap = [
                    'CONFIRMED' => '0', 
                    'WINNING' => '1',    // 待派奖
                    'PAID' => '3',       // 已派奖
                    'LOSING' => '2',
                    'CANCELLED' => '4',
                    'REFUNDED' => '5'
                ];
                $item['frontend_status'] = $frontendStatusMap[$order->status] ?? '0';
                
                // 格式化时间
                $item['create_time_formatted'] = date('Y-m-d H:i:s', $order->create_time);
                $item['update_time_formatted'] = date('Y-m-d H:i:s', $order->update_time);
                
                // 解析开奖结果
                if (!empty($order->draw_result)) {
                    $drawResult = $order->draw_result;
                    $item['draw_result_parsed'] = $drawResult;
                    if (is_array($drawResult)) {
                        $item['draw_numbers_display'] = implode(', ', $drawResult);
                    }
                }
                
                // 移除关联数据，避免冗余
                unset($item['lotteryType']);
                $list[] = $item;
            }
            
        } catch (\Throwable $e) {
            Log::error('获取订单列表失败: ' . $e->getMessage());
            $this->error('获取订单列表失败：' . $e->getMessage());
        }
            
        $this->success('获取订单列表成功', [
            'data' => $list,
            'total' => $orders->total(),
            'pages' => $orders->lastPage(),
            'current_page' => $page
        ]);
    }
    
    /**
     * 根据彩种代码获取彩种名称
     */
    private function getLotteryName($lotteryCode)
    {
        $lotteryNames = [
            'ff3d' => '福彩3D',
            'pl3' => '排列3',
            'pl5' => '排列5',
            'ssq' => '双色球',
            'dlt' => '大乐透',
            'fc3d' => '福彩3D',
            'qxc' => '七星彩',
            'qlc' => '七乐彩'
        ];
        
        return $lotteryNames[$lotteryCode] ?? $lotteryCode;
    }
}