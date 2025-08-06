<?php

namespace app\api\controller;

use think\facade\Db;
use app\common\controller\Api;
use app\service\LotteryService;
use app\service\LotteryBonusService;
use app\service\ApiService;
use app\common\model\LotteryType;
use app\common\model\BetOrder;
use app\common\model\LotteryDraw;
use app\common\model\LotteryType as LotteryTime;

class Lottery extends Api
{
    /**
     * 获取彩种信息
     * @return \think\response\Json
     */
    public function getGameInfo()
    {
        try {
            // 获取彩种名称参数，默认为ff3d
            $lotteryName = $this->request->param('type', 'ff3d');
            
            // 调用LotteryService获取期号信息
            $lotteryService = new LotteryService();
            $result = $lotteryService->getGameInfo($lotteryName);
            
            if ($result['code'] != 1) {
                $this->error($result['msg']);
            }
            
            $lotteryBonusService = new LotteryBonusService();

            $result['data']['bonus_list'] = $lotteryBonusService->getBonusByTypeId($result['data']['id']);
            
        } catch (\Exception $e) {
            return $this->error('获取彩种信息失败：' . $e->getMessage());
        }
        $this->success($result['msg'], $result['data']);
    }

    /**
     * 获取当前期号信息
     * @return \think\response\Json
     */
    public function getCurrentPeriod()
    {
        try {
            // 获取彩种名称参数，默认为ff3d
            $lotteryName = $this->request->param('type', 'ff3d');

            $lotteryType = LotteryType::where('type_code', $lotteryName)->find();
            
            // 调用LotteryService获取期号信息
            $lotteryService = new LotteryService();

            if($lotteryType['category'] == 'QUICK'){
                $result = $lotteryService->getCurrentPeriod($lotteryName);
            }else{
                $result = $lotteryService->getCurrentPeriodOther($lotteryName);
            }
            
            if ($result['code'] != 1) {
                $this->error($result['msg']);
            }
            
        } catch (\Exception $e) {
            return $this->error('获取期号信息失败：' . $e->getMessage());
        }
        $this->success($result['msg'], $result['data']);
    }

    /**
     * 获取当前游戏玩法的赔率
     * @return \think\response\Json
     */
    public function getGameOdds()
    {
        try {
            // 获取参数
            $lottery_code = $this->request->param('type', 'ff3d');
            $bet_type = $this->request->param('bet_type', '');
            
            // 调用LotteryService获取赔率
            $lotteryService = new LotteryService();
            $result = $lotteryService->getGameOdds($lottery_code, $bet_type);
            
            if ($result['code'] != 1) {
                throw new \Exception($result['msg']);
            }
            
        } catch (\Exception $e) {
            return $this->error('获取赔率信息失败：' . $e->getMessage());
        }

        $this->success($result['msg'], $result['data']);
    }

    /**
     * 获取历史开奖记录
     */
    public function getHistoryDraw(){
        try {
            // 获取参数
            $lottery_code = $this->request->param('lottery_code', 'ff3d');
            $page = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 20);
            
            // 参数验证
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 20;
            // 计算偏移量
            $offset = ($page - 1) * $limit;
            
            // 获取总数
            $total = LotteryDraw::where('lottery_code', $lottery_code)
                ->count();
            
            // 获取分页数据
            $lotteryList = LotteryDraw::field('period_no,draw_numbers as open_code,draw_time')
                ->where('lottery_code', $lottery_code)
                ->order('period_no', 'desc')
                ->limit($offset, $limit)
                ->select();
            
            // 构造返回数据
            $result = [
                'list' => $lotteryList,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (\Exception $e) {
            $this->error('获取历史开奖记录失败：' . $e->getMessage());
        }
        $this->success('获取成功', $result);
    }
    
    /**
     * 获取彩种时间表
     * @return void
     */
    public function getLotteryList(): void
    {
        try {
            $lotteryList = LotteryTime::field('lottery_name')
                ->where('status', 'active')
                ->group('lottery_name')
                ->select();
            
        } catch (\Exception $e) {
            $this->error('获取彩种时间失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $lotteryList);
    }

    /**
     * 获取彩种类型列表
     * @return void
     */
    public function getLotteryTypes(): void
    {
        try {
            $lotteryTypes = LotteryType::field('type_code, type_name, category, type_icon')
                ->where('is_enabled', 1)
                ->order('sort_order desc')
                ->select();
            
        } catch (\Exception $e) {
            $this->error('获取彩种类型失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $lotteryTypes);
    }

    /**
     * 通过type_id获取所有已开启的赔率记录
     * @return void
     */
    public function getBonusByTypeId(): void
    {
        try {
            $typeId = $this->request->param('type_id', 0);
            
            if (!$typeId) {
                $this->error('彩种ID不能为空');
            }
            
            $bonusList = LotteryBonusService::getBonusByTypeId($typeId);
            
        } catch (\Exception $e) {
            $this->error('获取赔率记录失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $bonusList);
    }

    /**
     * 通过type_id和key获取bonus_json值
     * @return void
     */
    public function getBonusJson(): void
    {
        try {
            $typeId = $this->request->param('type_id', 0);
            $key = $this->request->param('key', '');
            
            if (!$typeId || !$key) {
                $this->error('彩种ID和键值不能为空');
            }
            
            $bonusJson = LotteryBonusService::getBonusJsonByTypeIdAndKey($typeId, $key);
            
            if (!$bonusJson) {
                $this->error('未找到对应的赔率配置');
            }
            
        } catch (\Exception $e) {
            $this->error('获取赔率配置失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $bonusJson);
    }

    /**
     * 验证bonus值是否存在
     * @return void
     */
    public function validateBonus(): void
    {
        try {
            $typeId = $this->request->param('type_id', 0);
            $key = $this->request->param('key', '');
            $bonus = $this->request->param('bonus', '');
            
            if (!$typeId || !$key || !$bonus) {
                $this->error('彩种ID、键值和bonus值不能为空');
            }
            
            $result = LotteryBonusService::validateBonus($typeId, $key, $bonus);
            
            if ($result['code'] == 0) {
                $this->error($result['msg']);
            }
            
        } catch (\Exception $e) {
            $this->error('验证bonus值失败：' . $e->getMessage());
        }
        
        $this->success('验证成功', $result['data']);
    }

    /**
     * 获取各彩种的最新开奖记录(1条)
     * @return void
     */
    public function getLatestDraw(): void
    {
        try {
            $lotteryCode = $this->request->param('type', '');
            
            if (empty($lotteryCode)) {
                $this->error('彩种代码不能为空');
            }
            
            // 获取指定彩种的最新一条开奖记录
            $latestDraw = LotteryDraw::field('period_no,draw_numbers as open_code,draw_time')
                ->where('lottery_code', $lotteryCode)
                ->order('period_no', 'desc')
                ->find();
            
            if (!$latestDraw) {
                $this->error('未找到该彩种的开奖记录');
            }
            
        } catch (\Exception $e) {
            $this->error('获取最新开奖记录失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $latestDraw);
    }
    
    /**
     * 获取所有开放彩种的最新开奖记录
     * @return void
     */
    public function getAllLatestDraw(): void
    {
        try {
            // 获取所有开放的彩种
            $lotteryTypes = LotteryType::where('is_enabled', 1)
                ->field('type_code,type_name')
                ->select();
            
            if ($lotteryTypes->isEmpty()) {
                $this->error('暂无开放的彩种');
            }
            
            $result = [];
            
            foreach ($lotteryTypes as $lotteryType) {
                // 获取每个彩种的最新开奖记录
                $latestDraw = LotteryDraw::field('period_no,draw_numbers as open_code,draw_time')
                    ->where('lottery_code', $lotteryType->type_code)
                    ->order('period_no', 'desc')
                    ->find();
                
                $result[] = [
                    'lottery_code' => $lotteryType->type_code,
                    'lottery_name' => $lotteryType->type_name,
                    'latest_draw' => $latestDraw ? $latestDraw->toArray() : null
                ];
            }
            
        } catch (\Exception $e) {
            $this->error('获取所有彩种最新开奖记录失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $result);
    }

    /**
     * 获取奖金池信息
     * @return void
     */
    public function getBonusPool(): void
    {
        try {
            $periodNo = $this->request->param('periodNo', '');
            $lotteryCode = $this->request->param('type', '');
            
            if (empty($periodNo) || empty($lotteryCode)) {
                $this->error('期号和彩种代码不能为空');
            }
            
            // 获取彩种信息
            $lotteryType = LotteryType::where('type_code', $lotteryCode)->find();
            if (!$lotteryType) {
                $this->error('彩种不存在');
            }
            
            // 获取累计奖金池（从bonus_pool字段）
            $accumulatedBonusPool = floatval($lotteryType->bonus_pool ?? 0);
            
            // 基础奖金池固定为10000（仅用于显示）
            $baseBonusPool = $lotteryType['default_pool'];
            
            // 总奖金池 = 基础奖金池 + 累计奖金池
            $totalBonusPool = $baseBonusPool + $accumulatedBonusPool;
            
            // 获取当期投注的总金额（用于显示）
            $totalAmount = BetOrder::where('lottery_code', $lotteryCode)
                ->where('period_no', $periodNo)
                ->sum('bet_amount');
            
            $result = [
                'period_no' => $periodNo,
                'lottery_code' => $lotteryCode,
                // 'base_bonus_pool' => number_format($baseBonusPool, 2),
                // 'accumulated_bonus_pool' => number_format($accumulatedBonusPool, 2),
                'total_bonus_pool' => number_format($totalBonusPool, 2),
                'total_bet_amount' => number_format($totalAmount, 2)
            ];
            
        } catch (\Exception $e) {
            $this->error('获取奖金池信息失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $result);
    }
}