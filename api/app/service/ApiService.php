<?php

declare(strict_types=1);

namespace app\service;

/**
 * 彩票服务类
 */
class ApiService
{

    /**
     * 获取游戏信息
     * @param string $name 彩种名称
     * @param string $dayStart 开始日期
     * @return array
     */
    public function GetWelfareKJ(string $name, string $dayStart = ''): array
    {
        $url = 'https://www.cwl.gov.cn/cwl_admin/front/cwlkj/search/kjxx/findDrawNotice?name=' . $name . '&dayStart=' . $dayStart;
        $header = [
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.93 Safari/537.36',
            'cookie:HMF_CI=e4bc95c8aa30902a761f03a098a8b16348ce6b83d7c1e02b798b563cd0e20ece37; 21_vq=24'
        ];
        $res = $this->http_get($url, $header);
        return $res;
    }

    public function getSportKj(string $name, int $pageLimit = 7): array
    {
        $url = 'https://webapi.sporttery.cn/gateway/lottery/getHistoryPageListV1.qry?gameNo=' . $name . '&provinceId=0&pageSize=' . $pageLimit . '&isVerify=1&pageNo=1';
        $header = [
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.93 Safari/537.36',
            'cookie:HMF_CI=e4bc95c8aa30902a761f03a098a8b16348ce6b83d7c1e02b798b563cd0e20ece37; 21_vq=24'
        ];
        $res = $this->http_get($url, $header);
        return $res;
    }

    protected function http_get($url, $header = [])
    {
        if (empty($header)) {
            $header = [
                "Content-type:application/json;charset=UTF-8",
                "Accept:application/json, text/javascript, */*; q=0.01",
                "X-Requested-With:XMLHttpRequest",
            ];
        }


        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_COOKIE, 'BAIDUID=A7281E0926CB37D791AD464CDD646CF2:FG=1; BIDUPSID=A7281E0926CB37D791AD464CDD646CF2');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);

        return $response;
    }
}