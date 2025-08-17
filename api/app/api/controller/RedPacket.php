<?php

namespace app\api\controller;


use think\facade\Db;
use think\facade\Log;
use app\common\controller\Frontend;
use app\service\RedPacketService;
use app\api\validate\RedPacket as RedPacketValidate;
use app\common\model\RedPacket as RedPacketModel;
use app\common\model\RedPacketRecord;
use Exception;
use think\exception\ValidateException;
use Throwable;

class RedPacket extends Frontend
{
    protected array $noNeedLogin = [];
    
    // 红包状态常量
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_FINISHED = 'FINISHED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_EXPIRED = 'EXPIRED';
    
    // 红包类型常量
    const TYPE_RANDOM = 'RANDOM';
    const TYPE_FIXED = 'FIXED';
    
    // 目标用户类型常量
    const TARGET_ALL = 0;
    const TARGET_AGENT = 1;
    const TARGET_USER = 2;
    
    // 领取条件类型常量
    const CONDITION_NONE = 'NONE';
    const CONDITION_MIN_BET = 'MIN_BET';
    
    // 分页限制
    const MAX_PAGE_SIZE = 100;
    const DEFAULT_PAGE_SIZE = 10;

    public function initialize(): void
    {
        parent::initialize();
        
        // 验证用户是否为代理商
        $userInfo = $this->auth->getUserInfo();
        if (!isset($userInfo['is_agent']) || !$userInfo['is_agent']) {
            Log::warning('非代理商用户尝试操作红包', [
                'user_id' => $this->auth->id ?? 0,
                'ip' => $this->request->ip()
            ]);
            $this->error('只有代理商才能操作红包');
        }
    }

    /**
     * 获取红包列表
     */
    public function index(): void
    {
        try {
            $params = $this->validateListParams();
            $agentId = $this->auth->id;
            
            $where = $this->buildListWhere($params, $agentId);
            
            $redPackets = RedPacketModel::where($where)
                ->field('*')
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $params['limit'],
                    'page' => $params['page']
                ]);
            
            $list = $this->formatRedPacketList($redPackets->items());
        } catch (ValidateException $e) {
            $this->error($e->getError());
        } catch (Exception $e) {
            Log::error('获取红包列表失败', [
                'user_id' => $this->auth->id ?? 0,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('获取红包列表失败，请稍后重试');
        }
            
        $this->success('获取红包列表成功', [
            'data' => $list,
            'total' => $redPackets->total(),
            'page' => $params['page'],
            'limit' => $params['limit']
        ]);
    }

    /**
     * 创建红包
     */
    public function create(): void
    {
        try {
            $data = $this->validateCreateData();
            
            // 检查用户权限和余额
            $this->checkCreatePermission($data);
            
            // 开启事务
            Db::startTrans();
            
            try {
                $service = new RedPacketService();
                $redPacket = $service->createRedPacket($data);
                
                Db::commit();
                
                // 记录操作日志
                Log::info('红包创建成功', [
                    'user_id' => $this->auth->id,
                    'red_packet_id' => $redPacket->id,
                    'amount' => $data['total_amount'],
                    'count' => $data['total_count']
                ]);
            } catch (Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Log::error('创建红包失败', [
                'user_id' => $this->auth->id ?? 0,
                'data' => $this->request->only([
                    'title', 'blessing', 'type', 'total_amount', 'total_count'
                ]),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('创建红包失败，请稍后重试');
        }
                
        $this->success('红包创建成功', [
            'id' => $redPacket->id,
            'title' => $redPacket->title
        ]);
    }

    /**
     * 获取红包详情
     */
    public function detail(): void
    {
        try {
            $id = $this->request->param('id');
            if (!$id) {
                $this->error('红包ID不能为空');
            }
            
            $agentId = $this->auth->id;
            
            $redPacket = RedPacketModel::where('id', $id)
                ->where('agent_id', $agentId)
                ->find();
            
            if (!$redPacket) {
                $this->error('红包不存在或无权限查看');
            }
            
            // 金额保持分为单位，前端处理显示
            $redPacket['progress'] = $redPacket['total_count'] > 0 ? round($redPacket['received_count'] / $redPacket['total_count'] * 100, 2) : 0;
            
            // 状态和类型文本
            $statusMap = [
                'ACTIVE' => '进行中',
                'FINISHED' => '已完成',
                'CANCELLED' => '已取消',
                'EXPIRED' => '已过期'
            ];
            $redPacket['status_text'] = $statusMap[$redPacket['status']] ?? '未知';
            
            $typeMap = [
                'RANDOM' => '随机红包',
                'FIXED' => '固定红包'
            ];
            $redPacket['type_text'] = $typeMap[$redPacket['type']] ?? '未知';
            
            // 目标类型文本
            $targetMap = [
                0 => '全部',
                1 => '代理商',
                2 => '用户民'
            ];
            $redPacket['target_type_text'] = $targetMap[$redPacket['target_type']] ?? '未知';
            
            // 条件类型文本
            $conditionMap = [
                'NONE' => '无条件',
                'MIN_BET' => '今日最低投注金额',
                'USER_LEVEL' => '用户等级限制'
            ];
            $redPacket['condition_type_text'] = $conditionMap[$redPacket['condition_type']] ?? '未知';
        } catch (Exception $e) {
            $this->error('获取红包详情失败：' . $e->getMessage());
        }
            
        $this->success('获取红包详情成功', $redPacket);
    }

    /**
     * 取消红包
     */
    public function cancel(): void
    {
        try {
            $id = $this->request->param('id');
            if (!$id) {
                $this->error('红包ID不能为空');
            }
            
            $agentId = $this->auth->id;
            
            // 验证红包是否属于当前代理商
            $redPacket = RedPacketModel::where('id', $id)
                ->where('agent_id', $agentId)
                ->find();
            
            if (!$redPacket) {
                $this->error('红包不存在或无权限操作');
            }
            
            $service = new RedPacketService();
            $result = $service->cancelRedPacket($id);
            
            if (!$result) {
                $this->error('红包取消失败');
            }
        } catch (Exception $e) {
            $this->error('取消红包失败：' . $e->getMessage());
        }
        $this->success('红包取消成功');
    }

    /**
     * 获取红包领取记录
     */
    public function records(): void
    {
        try {
            $redPacketId = $this->request->param('red_packet_id');
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 10);
            
            if (!$redPacketId) {
                $this->error('红包ID不能为空');
            }
            
            $agentId = $this->auth->id;
            
            // 验证红包是否属于当前代理商
            $redPacket = RedPacketModel::where('id', $redPacketId)
                ->where('agent_id', $agentId)
                ->find();
            
            if (!$redPacket) {
                $this->error('红包不存在或无权限查看');
            }
            
            $records = RedPacketRecord::alias('r')
                ->leftJoin('user u', 'r.user_id = u.id')
                ->where('r.red_packet_id', $redPacketId)
                ->field('r.id,r.amount,r.create_time,u.username,u.nickname,u.avatar')
                ->order('r.create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            $list = [];
            foreach ($records->items() as $item) {
                $list[] = [
                'id' => $item['id'],
                'amount' => $item['amount'], // 保持分为单位
                'create_time' => $item['create_time'],
                'username' => $item['username'],
                'nickname' => $item['nickname'],
                'avatar' => $item['avatar'],
                'display_name' => $item['nickname'] ?: $item['username']
            ];
            }
        } catch (Exception $e) {
            $this->error('获取领取记录失败：' . $e->getMessage());
        }
        $this->success('获取领取记录成功', [
            'data' => $list,
            'total' => $records->total(),
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * 获取红包统计数据
     */
    public function stats(): void
    {
        try {
            $agentId = $this->auth->id;
            
            // 总红包数
            $totalCount = RedPacketModel::where('agent_id', $agentId)
                ->count();
            
            // 进行中的红包数
            $activeCount = RedPacketModel::where('agent_id', $agentId)
                ->where('status', 'ACTIVE')
                ->count();
            
            // 已完成的红包数
            $finishedCount = RedPacketModel::where('agent_id', $agentId)
                ->where('status', 'FINISHED')
                ->count();
            
            // 总发放金额（保持分为单位）
            $totalAmount = RedPacketModel::where('agent_id', $agentId)
                ->sum('total_amount');
            
            // 已领取金额（保持分为单位）
            $receivedAmount = RedPacketModel::where('agent_id', $agentId)
                ->sum('received_amount');
            
            // 已领取红包总个数
            $receivedCount = RedPacketModel::where('agent_id', $agentId)
                ->sum('received_count');
            
            // 红包总个数（所有红包的个数之和）
            $totalPackets = RedPacketModel::where('agent_id', $agentId)
                ->sum('total_count');
            
            // 今日新增红包数
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayEnd = strtotime(date('Y-m-d 23:59:59'));
            $todayCount = RedPacketModel::where('agent_id', $agentId)
                ->where('create_time', 'between', [$todayStart, $todayEnd])
                ->count();
        } catch (Exception $e) {
            $this->error('获取统计数据失败：' . $e->getMessage());
        }
            
        $this->success('获取统计数据成功', [
            'total_count' => $totalCount,
            'active_count' => $activeCount,
            'finished_count' => $finishedCount,
            'total_amount' => $totalAmount,
            'received_amount' => $receivedAmount,
            'received_count' => $receivedCount,
            'total_packets' => $totalPackets,
            'today_count' => $todayCount
        ]);
    }

    /**
     * 领取红包（用户端）
     */
    public function receive(): void
    {
        try {
            $id = $this->request->param('id');
            if (!$id) {
                $this->error('红包ID不能为空');
            }
            
            $userId = $this->auth->id;
            $ip = $this->request->ip();
            $userAgent = $this->request->header('user-agent', '');
            
            $service = new RedPacketService();
            $result = $service->receiveRedPacket($id, $userId, $ip, $userAgent);
        } catch (Exception $e) {
            $this->error('领取红包失败：' . $e->getMessage());
        }
            
        $this->success('红包领取成功', $result);
    }

    /**
     * 获取我的红包记录（用户端）
     */
    public function my(): void
    {
        try {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 10);
            $userId = $this->auth->id;
            
            $records = RedPacketRecord::alias('r')
                ->leftJoin('red_packet rp', 'r.red_packet_id = rp.id')
                ->where('r.user_id', $userId)
                ->field('r.id,r.amount,r.create_time,rp.title,rp.blessing')
                ->order('r.create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            $list = $records->items();
            // 金额保持分为单位，前端处理显示
            
            // 统计总领取金额（保持分为单位）
            $totalAmount = RedPacketRecord::where('user_id', $userId)
                ->sum('amount');
        } catch (Exception $e) {
            Log::error('获取我的红包记录失败', [
                'user_id' => $this->auth->id ?? 0,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('获取我的红包记录失败，请稍后重试');
        }
            
        $this->success('获取我的红包记录成功', [
            'data' => $list,
            'total' => $records->total(),
            'total_amount' => $totalAmount,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    /**
     * 验证列表参数
     */
    private function validateListParams(): array
    {
        $page = $this->request->param('page', 1, 'intval');
        $limit = $this->request->param('limit', self::DEFAULT_PAGE_SIZE, 'intval');
        $status = $this->request->param('status', '', 'trim');
        $keyword = $this->request->param('keyword', '', 'trim');
        
        // 验证分页参数
        if ($page < 1) {
            throw new ValidateException('页码必须大于0');
        }
        
        if ($limit < 1 || $limit > self::MAX_PAGE_SIZE) {
            throw new ValidateException('每页数量必须在1-' . self::MAX_PAGE_SIZE . '之间');
        }
        
        // 验证状态参数
        if ($status && !in_array($status, [self::STATUS_ACTIVE, self::STATUS_FINISHED, self::STATUS_CANCELLED, self::STATUS_EXPIRED])) {
            throw new ValidateException('无效的状态参数');
        }
        
        // 验证关键词长度
        if ($keyword && mb_strlen($keyword) > 50) {
            throw new ValidateException('关键词长度不能超过50个字符');
        }
        
        return compact('page', 'limit', 'status', 'keyword');
    }
    
    /**
     * 构建列表查询条件
     */
    private function buildListWhere(array $params, int $agentId): array
    {
        $where = [['agent_id', '=', $agentId]];
        
        // 状态筛选
        if ($params['status']) {
            $where[] = ['status', '=', $params['status']];
        }
        
        // 关键词搜索
        if ($params['keyword']) {
            $where[] = ['title|blessing', 'like', '%' . $params['keyword'] . '%'];
        }
        
        return $where;
    }
    
    /**
     * 格式化红包列表数据
     */
    private function formatRedPacketList(array $list): array
    {
        $statusMap = [
            self::STATUS_ACTIVE => '进行中',
            self::STATUS_FINISHED => '已完成',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_EXPIRED => '已过期'
        ];
        
        $typeMap = [
            self::TYPE_RANDOM => '随机红包',
            self::TYPE_FIXED => '固定红包'
        ];
        
        foreach ($list as &$item) {
            // 金额保持分为单位，前端处理显示
            $item['progress'] = $item['total_count'] > 0 ? round($item['received_count'] / $item['total_count'] * 100, 2) : 0;
            $item['status_text'] = $statusMap[$item['status']] ?? '未知';
            $item['type_text'] = $typeMap[$item['type']] ?? '未知';
            
            // 目标类型文本
            $targetMap = [
                0 => '全部',
                1 => '代理商', 
                2 => '用户民'
            ];
            $item['target_type_text'] = $targetMap[$item['target_type']] ?? '未知';
            
            // 条件类型文本
            $conditionMap = [
                'NONE' => '无条件',
                'MIN_BET' => '今日最低投注金额',
                'USER_LEVEL' => '用户等级限制'
            ];
            $item['condition_type_text'] = $conditionMap[$item['condition_type']] ?? '未知';
        }
        
        return $list;
    }
    
    /**
     * 验证创建红包数据
     */
    private function validateCreateData(): array
    {
        $data = $this->request->only([
            'title', 'blessing', 'type', 'total_amount', 'total_count',
            'target_type', 'condition_type', 'condition_value', 'expire_time'
        ]);
        
        // 验证数据
        $validate = new RedPacketValidate();
        if (!$validate->scene('create')->check($data)) {
            throw new ValidateException($validate->getError());
        }
        
        // 添加代理商ID
        $data['agent_id'] = $this->auth->id;
        
        if (isset($data['total_amount']) && is_numeric($data['total_amount'])) {
            $data['total_amount'] = $data['total_amount'];
        }
        
        return $data;
    }
    
    /**
     * 检查创建权限
     */
    private function checkCreatePermission(array $data): void
    {
        // 检查用户状态
        $userInfo = $this->auth->getUserInfo();
        if (!$userInfo || $userInfo['status'] !== 1) {
            throw new ValidateException('账户状态异常，无法创建红包');
        }
        
        // 检查今日创建限制
        $todayCount = $this->getTodayCreateCount();
        if ($todayCount >= 10) { // 假设每日最多创建10个红包
            throw new ValidateException('今日创建红包数量已达上限');
        }
        
        // 检查金额限制
        $totalAmount = bcdiv($data['total_amount'], 100, 2);
        if (bccomp($totalAmount, '10000', 2) > 0) { // 假设单个红包最大10000元
            throw new ValidateException('红包金额不能超过10000元');
        }
    }
    
    /**
     * 获取今日创建数量
     */
    private function getTodayCreateCount(): int
    {
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        $todayEnd = strtotime(date('Y-m-d 23:59:59'));
        
        return RedPacketModel::where('agent_id', $this->auth->id)
            ->where('create_time', 'between', [$todayStart, $todayEnd])
            ->count();
    }
}