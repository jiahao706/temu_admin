<?php
/**
 * User: jiahao.dong
 * Date: 2023/4/30
 * Time: 上午1:39
 */
namespace App\Compoents;

class Common
{
    /**
     * 公共curl方法
     * Post
     * return.
     */
    public static function CurlRequest($url, $data = '', $timeOut = 60)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $time = microtime(true);
        $resultData = curl_exec($curl);
        if (($time = microtime(true) - $time) > 1) {
            Log::warning("Slow curl \"POST {$url}\".", ['time' => number_format($time, 2)]);
        }

        if (curl_errno($curl)) {
            Log::warning("Curl failed \"POST {$url}\", " . curl_error($curl), ['time' => number_format($time, 2)]);

            curl_close($curl);
            return false;
        } else {
            curl_close($curl);

            return $resultData;
        }
    }

    /**
     * 公共curl方法
     * Put
     * return.
     */
    public static function CurlPutRequest($url, $data = '')
    {
        $curl = curl_init();
        $headers = array();
        $headers[] = 'Content-Type: application/json';

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt ( $curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 6);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );

        $time = microtime(true);
        $resultData = curl_exec($curl);
        if (($time = microtime(true) - $time) > 1) {
            Log::warning("Slow curl \"PUT {$url}\".", ['time' => number_format($time, 2)]);
        }

        // t.vhall.com -> test connect
        // echo $resultData;

        if (curl_errno($curl)) {
            Log::warning("Curl failed \"PUT {$url}\", " . curl_error($curl), ['time' => number_format($time, 2)]);

            curl_close($curl);
            return false;
        } else {
            curl_close($curl);
            return json_decode($resultData,true);
        }
    }



    /**
     * 公共curl方法
     * GET
     * return.
     */
    public static function curlGetRequest($url, $overtime=5)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $overtime);
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);


        $time = microtime(true);
        $resultData = curl_exec($curl);
        $curl_errno = curl_errno($curl);
        $curl_error = curl_error($curl);
        $httpInfo = curl_getinfo($curl);
        curl_close($curl);

        if (($time = microtime(true) - $time) > 1) {
            Log::warning("Slow curl \"GET {$url}\".", ['time' => number_format($time, 2)]);
        }

        if ($curl_errno > 0) {
            Log::warning("Curl failed \"GET {$url}\", " . $curl_error, ['time' => number_format($time, 2), 'info'=>$httpInfo]);

            //return $curl_error;
            return false;
        }

        $httpStatus = isset($httpInfo['http_code'])?$httpInfo['http_code']:0;

        if($httpStatus!=200 && $httpStatus>0){
            \Log::warning("Curl failed \"GET {$url}\", http code:" . $httpInfo['http_code'], ['info' => $httpInfo]);
            return false;
        }

        return $resultData;
    }

    /**
     * @param $url
     * @param int $overtime
     * @return bool
     * get 请求附带头部
     */
    public static function curlGetWithHeader($url, $overtime=10)
    {
        $curl = curl_init();
        $header[] = "Content-type:application/x-www-form-urlencoded";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;http:'.env('WEB_DOMAIN').')');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $overtime);

        $time = microtime(true);
        $resultData = curl_exec($curl);
        $curl_errno = curl_errno($curl);
        $curl_error = curl_error($curl);

        curl_close($curl);

        if (($time = microtime(true) - $time) > 1) {
            \Log::warning("Slow curl \"GET {$url}\".", ['time' => number_format($time, 2)]);
        }

        if ($curl_errno > 0) {
            \Log::warning("Curl failed \"GET {$url}\", " . $curl_error, ['time' => number_format($time, 2)]);

            //return $curl_error;
            return false;
        } else {

            return $resultData;
        }
    }

    /**
     * CURL POST请求
     * @param string $url
     * @param array $header
     * @param array $data
     * @param int $timeOut
     * @return bool
     */
    public static function curlPostWithCustomHeader($url, array $header = [], $data = [], $timeOut = 10)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        } else {
            curl_setopt($curl, CURLOPT_HEADER, false);
        }

        $time = microtime(true);
        $response = curl_exec($curl);
        $curl_errno = curl_errno($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if (($time = microtime(true) - $time) > 1) {
//            \Log::warning("Slow curl \"GET {$url}\".", ['time' => number_format($time, 2)]);
        }

        if ($curl_errno > 0) {
//            \Log::warning("Curl failed \"GET {$url}\", " . $curl_error, ['time' => number_format($time, 2)]);
            return false;
        } else {
            return $response;
        }
    }

    /**
     * CURL GET请求
     * @param string $url
     * @param array $header
     * @param int $timeOut
     * @return bool
     */
    public static function curlGetWithCustomHeader($url, array $header = [], $timeOut = 10)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        } else {
            curl_setopt($curl, CURLOPT_HEADER, false);
        }

        $time = microtime(true);
        $response = curl_exec($curl);
        $curl_errno = curl_errno($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if (($time = microtime(true) - $time) > 1) {
            \Log::warning("Slow curl \"GET {$url}\".", ['time' => number_format($time, 2)]);
        }

        if ($curl_errno > 0) {
            \Log::warning("Curl failed \"GET {$url}\", " . $curl_error, ['time' => number_format($time, 2)]);
            return false;
        } else {
            return $response;
        }
    }

    /**
     * 公共curl方法
     * GET 下载
     * return.
     */
    public static function curlDownGetRequest($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        $time = microtime(true);
        $resultData = curl_exec($curl);
        if (($time = microtime(true) - $time) > 1) {
            Log::warning("Slow curl \"GET {$url}\".", ['time' => number_format($time, 2)]);
        }

        if (curl_errno($curl)) {
            Log::warning("Curl failed \"GET {$url}\", " . curl_error($curl), ['time' => number_format($time, 2)]);

            curl_close($curl);
            return false;
        } else {
            curl_close($curl);

            return $resultData;
        }
    }

    public static function getWeeks($time="",$format="Y-m-d"){
        $time = $time!=""?$time:time();
        $date=[];
        for($i=1;$i<=7;$i++){
            $date[$i] = date($format,strtotime("+".($i-7)." days",$time));
        }
        return $date;
    }

    /** 杀掉目标进程及其子进程
     * User: jiahao.dong
     * Date: 2023/5/22
     * Time: 下午4:07
     * @param $pid
     */
    public static function killProcessAndChildren($pid)
    {
        // 获取子进程的PID
        $output = [];
        exec("pgrep -P $pid", $output);

        // 杀死目标进程
        exec("sudo kill -9 $pid");

        // 递归杀死子进程
        foreach ($output as $childPid) {
            self::killProcessAndChildren($childPid);
        }
    }

    /**
     * 获取毫秒级别时间戳
     * @return int
     */
    public static function getMsUnixTime()
    {
        list($microTime, $time) = explode(' ', microtime());
        $time = intval((floatval($time) + floatval($microTime)) * 1000);

        return $time;
    }

}
