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
        echo 3;
    }
}