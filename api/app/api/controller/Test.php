<?php

namespace app\api\controller;

use Throwable;
use app\common\library\Email;
use app\common\controller\Frontend;
use app\common\model\LotteryTime;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Test extends Frontend
{
    protected array $noNeedLogin = ['index', 'sendTestMail', 'getMailConfig'];

    public function initialize(): void
    {
        parent::initialize();
    }

    public function index(): void
    {
        $list = LotteryTime::where(['lottery_name'=>'pl3'])->select()->toArray();
        foreach($list as $k=>$v){
            LotteryTime::insert([
                'lottery_name'=>'day3d',
                'draw_date'=>$v['draw_date'],
                'draw_time_start'=>'21:30:00',
                'draw_time_end'=>'21:29:00',
                'closing_time'=>'21:28:30',
                'next_issue_start_time'=>'21:30:00',
                'current_issue_number'=>$v['current_issue_number'],
                'issue_time_interval'=>$v['issue_time_interval'],
                'status'=>$v['status'],
            ]);
        }
    }
}