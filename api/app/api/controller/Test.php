<?php

namespace app\api\controller;

use app\service\LotteryBonusService;
use think\facade\Db;
use think\facade\Request;
use think\facade\Log;
use app\common\controller\Frontend;
use think\exception\ValidateException;
use Exception;

class Test extends Frontend
{
    protected array $noNeedLogin = ['*'];

    public function initialize(): void
    {
        parent::initialize();
    }
    
    public function index(): void
    {
        $str = '{"dataFrom":"","emptyFlag":false,"errorCode":"0","errorMessage":"\u5904\u7406\u6210\u529f","success":true,"value":{"lastPoolDraw":{"lotteryDrawNum":"25200","lotteryDrawResult":"2 1 1","lotteryDrawTime":"2025-07-29","lotteryGameName":"\u6392\u52173","lotteryGameNum":"35","poolBalanceAfterdraw":"0","prizeLevelList":[{"awardType":0,"group":"10","lotteryCondition":"","prizeLevel":"\u76f4\u9009","sort":10,"stakeAmount":"1,040","stakeAmountFormat":"1040","stakeCount":"12,224","totalPrizeamount":"12,712,960"},{"awardType":0,"group":"20","lotteryCondition":"","prizeLevel":"\u7ec4\u90093","sort":20,"stakeAmount":"346","stakeAmountFormat":"346","stakeCount":"15,149","totalPrizeamount":"5,241,554"},{"awardType":0,"group":"30","lotteryCondition":"","prizeLevel":"\u7ec4\u90096","sort":30,"stakeAmount":"173","stakeAmountFormat":"173","stakeCount":"0","totalPrizeamount":"0"}]},"list":[{"drawFlowFund":"0","drawFlowFundRj":"","drawPdfUrl":"https:\/\/pdf.sporttery.cn\/28200\/25200\/25200.pdf","estimateDrawTime":"","isGetKjpdf":1,"isGetXlpdf":2,"lotteryDrawNum":"25200","lotteryDrawResult":"2 1 1","lotteryDrawStatus":20,"lotteryDrawStatusNo":"","lotteryDrawTime":"2025-07-29","lotteryEquipmentCount":0,"lotteryGameName":"\u6392\u52173","lotteryGameNum":"35","lotteryGamePronum":0,"lotteryNotice":1,"lotteryNoticeShowFlag":1,"lotteryPaidBeginTime":"2025-07-29 23:30:01","lotteryPaidEndTime":"2025-09-28 23:59:59","lotteryPromotionFlag":0,"lotteryPromotionFlagRj":0,"lotterySaleBeginTime":"2025-07-28 21:10:00","lotterySaleEndTimeUnix":0,"lotterySaleEndtime":"2025-07-29 21:00:00","lotterySuspendedFlag":0,"lotteryUnsortDrawresult":"2 1 1 2 9","matchList":[],"pdfType":1,"poolBalanceAfterdraw":"0","poolBalanceAfterdrawRj":"","prizeLevelList":[{"awardType":0,"group":"10","lotteryCondition":"","prizeLevel":"\u76f4\u9009","sort":10,"stakeAmount":"1,040","stakeAmountFormat":"1040","stakeCount":"12,224","totalPrizeamount":"12,712,960"},{"awardType":0,"group":"20","lotteryCondition":"","prizeLevel":"\u7ec4\u90093","sort":20,"stakeAmount":"346","stakeAmountFormat":"346","stakeCount":"15,149","totalPrizeamount":"5,241,554"},{"awardType":0,"group":"30","lotteryCondition":"","prizeLevel":"\u7ec4\u90096","sort":30,"stakeAmount":"173","stakeAmountFormat":"173","stakeCount":"0","totalPrizeamount":"0"}],"prizeLevelListRj":[],"ruleType":0,"surplusAmount":"","surplusAmountRj":"","termList":[],"termResultList":[],"totalSaleAmount":"46,599,904","totalSaleAmountRj":"","verify":1,"vtoolsConfig":[]}],"pageNo":1,"pageSize":1,"pages":7324,"total":7324}}';

        $data = json_decode($str, true);
        echo "<pre>"; print_r($data);
    }
}