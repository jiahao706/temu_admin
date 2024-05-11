<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/26
 * Time: 下午3:47
 */

namespace App\Service;

use App\Compoents\Common;
use App\Model\AdminUserMallPermissions;
use App\Model\TemuGoods;
use App\Model\TemuGoodsSales;
use App\Model\TemuGoodsSku;
use App\Model\TemuMalls;
use App\Model\TemuMallsDeliveryRestrict;
use App\Model\TemuMallsDeliveryRestrictOrder;
use App\Model\TemuMallsGoodsRefundCost;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;

class SpiderService
{
    private $driverHost = "http://127.0.0.1";

    /**
     * @var WebDriver
     */
    public $driver;

    /**
     * temu 后台 销售管理列表接口
     * @var string
     */
    private $temuSaleManageListApiUrl = "https://kuajing.pinduoduo.com/marvel-mms/cn/api/kiana/venom/sales/management/list";
    private $temuSaleManageListApiUrlNew = "https://seller.kuajingmaihuo.com/marvel-mms/cn/api/kiana/venom/sales/management/listWarehouse";

    /**
     * temu 后台登录成功后返回用户信息接口
     * @var string
     */
    private $loginUserInfoApiUrl = "https://kuajing.pinduoduo.com/bg/quiet/api/mms/userInfo";

    /**
     * 登录响应接口
     * @var string
     */
    private $loginResponseApiUrl = "https://kuajing.pinduoduo.com/bg/quiet/api/mms/login";

    /**
     * 资金限制详情接口
     * @var string
     */
    private $deliveryRestrictApiUrl = "https://kuajing.pinduoduo.com/api/merchant/fund/restrict/detail";

    /**
     * 登录页面地址
     * @var string
     */
    private $loginUrl = 'https://kuajing.pinduoduo.com/login?redirectUrl=https%3A%2F%2Fkuajing.pinduoduo.com%2F';

    /**
     * 订单详情接口
     * @var string
     */
    private $orderDetailApiUrl = "https://kuajing.pinduoduo.com/bgSongbird-api/supplier/deliverGoods/management/pageQueryDeliveryOrders";

    /**
     * 订单收件人信息接口
     * @var string
     */
    private $orderReciverUserInfoApiUrl = "https://kuajing.pinduoduo.com/bgSongbird-api/supplier/deliverGoods/management/queryCourier";

    /**
     * 订单包裹详情接口
     * @var string
     */
    private $orderPackageDetailApiUrl = "https://kuajing.pinduoduo.com/bgSongbird-api/supplier/deliverGoods/management/queryDeliveryOrderPackageDetailInfo";

    /**
     * 退货运费详情接口
     * @var string
     */
    private $goodsRefundCostApiUrl = "https://kuajing.pinduoduo.com/api/merchant/fund/restrict/detail";

    protected $driverPath;

    protected $driverPort;

    protected $debugPort;

    protected $browserPath;

    protected $spiderMallIds;

    private $statisticsInventorySkuIds = [];

    /**
     * sku 历史销量接口
     * @var string
     */
    protected $historySalesUrl = "https://kuajing.pinduoduo.com/oms/bg/venom/api/supplier/sales/management/querySkuSalesNumber";


    public function initConsoleSpider($driverPath, $driverPort,$browserPath)
    {
        $this->reloadChromeDriver($driverPath, $driverPort);

        $this->browserPath = $browserPath;
        $this->startChrome($driverPort, $browserPath);
    }

    /** 重启chromedriver
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 上午2:56
     */
    public function reloadChromeDriver($driverPath, $driverPort, $debugPort = 9527)
    {

        $this->driverPath = $driverPath;
        $this->driverPort = $driverPort;
        $this->debugPort = $debugPort;
        //exec(base_path() . "/restartdriver.sh ${driverPath} $driverPort $debugPort");
        exec(base_path() . "/restartdriver.sh ${driverPath} $driverPort $debugPort", $out);
        sleep(2);
    }

    public function stopChromeDriver(){
        exec(base_path() . "/stopdriver.sh", $out);
        sleep(2);
    }

    public function getChromeUserDataDir($driverPort)
    {
        $path = base_path() . "/selenium_fe_" . $driverPort;
        if (!is_dir($path)) {
            mkdir($path);
        }
        exec("chmod -R 777 " . $path);
        return $path;
    }

    /**启动chrome
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 下午2:52
     * @param $driverPort
     * @param $browserPath
     */
    public function startChrome($driverPort, $browserPath)
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->setBinary($browserPath);
        $chromeOptions->addArguments([
            '--user-agent=Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36',
            '--headless',
//            '--profile-directory=Default',
            '--user-data-dir=selenium',
            '--disable-infobars',
            '--sec-fetch-site=same-site',
            '--start-maximized',
            '--hide-scrollbars',
            '--disable-blink-features',
            '--no-sandbox',
            '--disable-blink-features=AutomationControlled',
            '--disable-dev-shm-usage',
            '--lang=zh_CN.UTF-8',
        ]);
        $chromeOptions->setExperimentalOption("useAutomationExtension", false);
        $chromeOptions->setExperimentalOption("excludeSwitches", ["enable-automation", "enable-logging"]);
        //关闭保存密码提示
        $chromeOptions->setExperimentalOption("prefs", ["credentials_enable_service" => false, "profile.password_manager_enabled" => false]);

        $desiredCapabilities = DesiredCapabilities::chrome();
        $desiredCapabilities->setCapability("browserName", "chrome");
        $desiredCapabilities->setCapability("goog:loggingPrefs", ["performance" => "ALL"]);

        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $this->driver = RemoteWebDriver::create($this->driverHost . ":" . $driverPort, $desiredCapabilities, 5000);
        //设置查找元素等待时长
        $this->driver->manage()->timeouts()->implicitlyWait(10);
    }


    /**
     * 开始采集
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 上午5:06
     */
    public function startSpider()
    {
//        $temuAccounts = explode("|",env("TEMU_ACCOUNTS"));
        dump("采集开始：".date("Y-m-d H:i:s",time()));
        //$temuMalls = TemuMalls::all();>
        $temuMalls = TemuMalls::where(["mall_id"=>"634418210812805"])->get();
        //$temuMalls = TemuMalls::where(["username"=>"13757993074","is_start_spider"=>TemuMalls::START_SPIDER])->get();
        if (!empty($temuMalls)) {
            foreach ($temuMalls as $index => $accounts) {
                /*                $uinfo = explode(",",$accounts);
                                if(!empty($uinfo)){*/
                $manage = $this->driver->manage();

                $username = $accounts->username;
                $password = $accounts->password;
                if(empty($username) || empty($password)){
                    continue;
                }
               /* $this->driver->get($this->loginUrl);
                sleep(10);
                //登录
                $this->driver->findElement(WebDriverBy::id('usernameId'))->sendKeys($username);
                $this->driver->findElement(WebDriverBy::id('passwordId'))->sendKeys($password);
                $this->driver->findElement(WebDriverBy::xpath("//span[text()='登录']/parent::button"))->click();*/

                sleep(10);

                try {
                    $log = $manage->getLog("performance");
                    $loginResponseInfo = "";
                    //获取列表接口response 数据
                    /*if (!empty($log)) {
                        foreach ($log as $k => $v) {
                            $msg = $v["message"];
                            $msgArr = json_decode($msg, true);
                            if ($msgArr["message"]["method"] == "Network.responseReceived" && $this->loginResponseApiUrl == $msgArr["message"]["params"]["response"]["url"]) {
                                $tool = new ChromeDevToolsDriver($this->driver);
                                try {
                                    $res = $tool->execute("Network.getResponseBody", ["requestId" => $msgArr["message"]["params"]["requestId"]]);
                                    $loginResponseInfo = $res["body"];
                                } catch (\Exception $e) {
                                }
                                break;
                            }
                        }
                    }
                    dump("账号:{$username}登录接口response");
                    dump($loginResponseInfo);
                    if (!empty($loginResponseInfo)) {
                        $r = json_decode($loginResponseInfo, true);
                        if (!empty($r["errorCode"]) && $r["errorCode"] != 1000000) {
                            $accounts->update([
                                "spider_status_msg" => !empty($r) ? $r["errorMsg"] : "账号采集异常",
                                "spider_status" => TemuMalls::SPIDER_ERROR,
                            ]);
                            continue;
                        }
                    }*//*else {
                        $accounts->update([
                            "spider_status_msg" => "账号采集状态正常",
                            "spider_status" => TemuMalls::SPIDER_SUCCESS,
                        ]);
                    }*/
                    $cookieStr = $this->getCookies();
                    $cookieStr = "api_uid=CmixCWXUZoOpVgBTqiJFAg==; _nano_fp=Xpmol0Tal0EbXpTal9_777MBPnA9yGwlIV6sP~xi; _bee=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; _f77=70bd6232-8e14-4d29-910f-7cfe6ea14b6a; _a42=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; rckk=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; ru1k=70bd6232-8e14-4d29-910f-7cfe6ea14b6a; ru2k=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; SUB_PASS_ID=eyJ0IjoiM28zYVpPL1QycDcrQ0RDbHYyc3A1ZVhuendtajl3SVdFMmVubERXL1Bhb3NtSktWeVRXdnArZVdwMklnV1h5QiIsInYiOjEsInMiOjEwMDAwLCJ1Ijo5NDA3MTg5NjkxMTQ1fQ==";
                    dump("登录cookie");
                    dump($cookieStr);
                    $loginUserInfo = $this->getLoginUserInfo($cookieStr);
                    dump("登录用户信息");

                    dump(json_encode($loginUserInfo));
                    if (!empty($loginUserInfo["result"])) {
                        $mallIds = $loginUserInfo["result"]["companyList"][0];
                        if(!empty($mallIds["malInfoList"])){
                            foreach ($mallIds["malInfoList"] as $_mallIdInfo){

                                $newAccounts = $this->saveMall($_mallIdInfo,$accounts);
                                $newAccounts->update([
                                    "spider_status_msg" => "账号采集状态正常",
                                    "spider_status" => TemuMalls::SPIDER_SUCCESS,
                                ]);
                                dump($newAccounts);
                                if(!isset($this->spiderMallIds[$_mallIdInfo['mallId']])){
                                    try {
                                        $this->startSaleRequest($cookieStr, $_mallIdInfo, $newAccounts);
                                        $this->startDeliveryRestrictRequest($cookieStr, $_mallIdInfo, $newAccounts);
                                        $this->startGoodsRefundCostRequest($cookieStr, $_mallIdInfo, $newAccounts);
                                        $this->spiderMallIds[$_mallIdInfo['mallId']] =1;
                                    } catch (\Exception $e) {
                                        dump($e->getMessage());
                                        continue;
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    dump("异常信息");
                    dump($e->getMessage());
                }

                /*if($index % 2 ==0){
                    dump($this->driverPath);
                    dump($this->driverPort);
                    dump($this->browserPath);
                    $this->initConsoleSpider($this->driverPath,$this->driverPort,$this->browserPath);
                }*/
//                }
            }
            try {
                $this->driver->quit();
                $this->stopChromeDriver();
//                $this->driver->close();
            } catch (\Exception $e) {
            }
        }
        dump("采集结束：".date("Y-m-d H:i:s",time()));

    }


    public function userTestLogin($username, $password, $userId)
    {
        $this->driver->get($this->loginUrl);
        sleep(3);
        //登录
        $this->driver->findElement(WebDriverBy::id('usernameId'))->sendKeys($username);
        $this->driver->findElement(WebDriverBy::id('passwordId'))->sendKeys($password);
        $this->driver->findElement(WebDriverBy::xpath("//span[text()='登录']/parent::button"))->click();

        sleep(3);

        try {
            $log = $this->driver->manage()->getLog("performance");
            $loginResponseInfo = "";
            //获取列表接口response 数据
            if (!empty($log)) {
                foreach ($log as $k => $v) {
                    $msg = $v["message"];
                    $msgArr = json_decode($msg, true);
                    if ($msgArr["message"]["method"] == "Network.responseReceived" && $this->loginResponseApiUrl == $msgArr["message"]["params"]["response"]["url"]) {
                        $tool = new ChromeDevToolsDriver($this->driver);
                        try {
                            $res = $tool->execute("Network.getResponseBody", ["requestId" => $msgArr["message"]["params"]["requestId"]]);
                            $loginResponseInfo = $res["body"];
                        } catch (\Exception $e) {
                        }
                        break;
                    }
                }
            }
            if (!empty($loginResponseInfo)) {
                $r = json_decode($loginResponseInfo, true);
                //账号登录成功
                return $r;
            } else {
                $cookieStr = $this->getCookies();
                $loginUserInfo = $this->getLoginUserInfo($cookieStr);
                $mallInfo = $loginUserInfo["result"]["companyList"][0]["malInfoList"][0];
                //添加用户店铺信息
                $oldMalls = TemuMalls::where([
                    "mall_id" => strval($mallInfo["mallId"]),
                    "type" => TemuMalls::ADD_FROM_FE,
                ])->get();
                if(!empty($oldMalls)){
                    $mall = null;
                    foreach ($oldMalls as $_oldMall){

                        $_oldMall->update([
                            "username" => $username,
                            "password" => $password,
                            "user_id" => $userId,
                            "mall_name" => $mallInfo["mallName"],
                            "mall_logo" => $mallInfo["logo"],
                            "last_spider_time" => date("Y-m-d H:i:s"),
                            "spider_status_msg" => "账号采集状态正常",
                            "spider_status" => TemuMalls::SPIDER_SUCCESS,
                        ]);
                        if(empty($mall)){
                            $mall = $_oldMall->syncChanges();
                        }
                    }
                }else{
                    $mall = TemuMalls::create([
                        "username" => $username,
                        "password" => $password,
                        "mall_id" => $mallInfo["mallId"],
                        "mall_name" => $mallInfo["mallName"],
                        "mall_logo" => $mallInfo["logo"],
                        "last_spider_time" => date("Y-m-d H:i:s"),
                        "user_id" => $userId,
                        "type" => TemuMalls::ADD_FROM_FE,
                        "spider_status_msg" => "账号采集状态正常",
                        "spider_status" => TemuMalls::SPIDER_SUCCESS,
                    ]);
                    //给用户分配店铺权限
                    AdminUserMallPermissions::create([
                        "user_id" => $userId,
                        "mall_id" => $mallInfo["mallId"]
                    ]);
                }

                $this->startSaleRequest($cookieStr, $loginUserInfo, $mall);
                $this->startDeliveryRestrictRequest($cookieStr, $loginUserInfo, $mall);
            }
        } catch (\Exception $e) {
        }
        return [];
    }

    public function getCookies()
    {
        $cookies = $this->driver->manage()->getCookies();
        $cookieStr = "";
        foreach ($cookies as $cookieV) {
            $cookieStr .= $cookieV["name"] . "=" . $cookieV["value"] . ";";
        }
        //$cookieStr ="api_uid=Cmk2x2SnzEchxABVnb92Ag==; _nano_fp=XpEJl0mal0Tol0X8n9_ufgqW7A7nYCChI58gEm8_; _f77=046a704a-3723-4bb9-8be9-47688dc808be; _a42=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; ru1k=046a704a-3723-4bb9-8be9-47688dc808be; ru2k=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; request_id=846bb43057584552acb850c27726f872; terminalFinger=k8odia5Nimp1SC3mikjreFjubyrkGMa1; _bee=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; rckk=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; jrpl=uVGx3oS2k0vQw9D4d7BDJs7xYbf3iWKw; njrpl=uVGx3oS2k0vQw9D4d7BDJs7xYbf3iWKw; dilx=8pqPcXF7rJ720XHIvO8lc; SUB_PASS_ID=eyJ0IjoidkM5TXBjSFlaTnlrc25BQjBjNGxKNk1mYllOSGVHLzhBQ0duRy9WZFdYZ1MxWlc0U3V2eTMrSE9HNG9iT1FkaiIsInYiOjEsInMiOjEwMDAwLCJ1IjozOTU0MDQ3MTY4NTQ1fQ==";
        return $cookieStr;
    }

    /**
     * 获取用户信息接口
     * User: jiahao.dong
     * Date: 2023/5/4
     * Time: 下午5:15
     * @param $cookie
     * @return mixed
     */
    public function getLoginUserInfo($cookie)
    {
        $res = Common::curlPostWithCustomHeader($this->loginUserInfoApiUrl, [
            "authority: kuajing.pinduoduo.com",
            "accept: */*",
            "accept-language: zh-CN,zh;q=0.9",
            "cache-control: max-age=0",
            'cookie:' . $cookie,
            'origin:https://kuajing.pinduoduo.com',
            'referer:https://kuajing.pinduoduo.com/settle/site-main?redirectUrl=https%3A%2F%2Fkuajing.pinduoduo.com%2Fsettle%2Fsite-main%3FredirectUrl%3Dhttps%253A%252F%252Fkuajing.pinduoduo.com%252Fmain%252Fsale-manage%252Fmain',
            'sec-ch-ua: \"Chromium\";v=\"112\", \"Google Chrome\";v=\"112\", \"Not:A-Brand\";v=\"99\"',
            "sec-ch-ua-mobile: ?0",
            'sec-ch-ua-platform: \"macOS\"',
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origin",
            "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
        ], json_encode([]));
        return json_decode($res, true);
    }

    /**
     * 销售管理
     * User: jiahao.dong
     * Date: 2023/5/4
     * Time: 下午5:16
     * @param $cookie
     * @param $mallIdInfo
     * @param $accounts
     */
    public function startSaleRequest($cookie, $mallIdInfo, $accounts)
    {
        $pageNo = 1;
        $pageSize = 40;
        $totalPage = null;
        $mallId = "";
        if (!empty($mallIdInfo["mallId"])) {
            $mallId = $mallIdInfo["mallId"];
        }
        $resetTimes = 1;
        while (1) {
            $headers = [
                "accept: */*",
                "cache-control: no-cache",
                "cookie:" . $cookie,
                "content-type: application/json",
                "mallid: " . $mallId,
                'sec-ch-ua: \"Chromium\";v=\"112\", \"Google Chrome\";v=\"112\", \"Not:A-Brand\";v=\"99\"',
                "sec-ch-ua-mobile: ?0",
                'sec-ch-ua-platform: \"macOS\"',
                "sec-fetch-dest: empty",
                "sec-fetch-mode: cors",
                "sec-fetch-site: same-origin",
                "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
            ];
            $res = Common::curlPostWithCustomHeader($this->temuSaleManageListApiUrlNew, $headers, json_encode([
                "pageNo" => $pageNo,
                "pageSize" => $pageSize,
                "isLack" => 0,
                "priceAdjustRecentDays" => 7,
                "selectStatusList"=>[12],
                "orderByParam"=>"todaySaleVolume",
                "orderByDesc"=>1,
                //"thirtyDaysSaleVolumMin"=>1
            ]));
//            dump($res);
            $resArr = json_decode($res, true);
            if (isset($resArr["result"]["total"])) {
                if (empty($totalPage)) {
                    $totalPage = ceil($resArr["result"]["total"] / $pageSize);
                }

                if (!empty($resArr["result"]["subOrderList"])) {
                    $subOrderList = $resArr["result"]["subOrderList"];
                    if (!empty($subOrderList)) {
                        foreach ($subOrderList as $value) {
                            /*if(!empty($value["skuQuantityTotalInfo"]) && $value["skuQuantityTotalInfo"]["todaySaleVolume"] ==0){
                                dump("动销链接统计完毕");
                                break;
                            }*/
                            $this->saveProductInfo($value, $mallId);
                            $this->saveProductSkuInfoNew($value, $mallId);
                        }
                    }
                }

                $pageNo++;
                if ($pageNo > $totalPage) {
                    $this->saveMall($mallIdInfo, $accounts);
                    break;
                }
            } else {
                if(isset($resArr["errorMsg"]) && !preg_match("/当前发货人数较多/",$resArr["errorMsg"])){
                    dump("break startSaleRequest");
                    dump($res);
                    break;
                }else{
                    sleep(1);
                    dump("当前发货人数较多,重新拉取列表");
                    $resetTimes++;
                    if($resetTimes>3){
                        break;
                    }
                }
            }
        }
    }

    /**
     * 快递费资金限制详情
     * User: jiahao.dong
     * Date: 2023/5/4
     * Time: 下午5:08
     * @param $cookie
     * @param $mallIdInfo
     * @param $accounts
     */
    public function startDeliveryRestrictRequest($cookie, $mallIdInfo, $accounts)
    {
        $pageNo = 1;
        $pageSize = 200;
        $totalPage = null;
        $mallId = "";
        if (!empty($mallIdInfo["mallId"])) {
            $mallId = $mallIdInfo["mallId"];
            //删除历史记录
            // TemuMallsDeliveryRestrict::Where("mall_id", $mallId)->delete();
        }
        $amount = 0;
        while (1) {
            $headers = [
                "accept: */*",
                "accept-language: zh-CN,zh;q=0.9",
                "content-type: application/json",
                "cookie: " . $cookie,
                "mallid: " . $mallId,
                "origin: https://kuajing.pinduoduo.com",
                "pragma: no-cache",
                "referer: https://kuajing.pinduoduo.com/labor/limited/detail?frozenType=delivery_by_jit",
                'sec-ch-ua: "Chromium";v="112", "Google Chrome";v="112", "Not:A-Brand";v="99"',
                "sec-ch-ua-mobile: ?0",
                'sec-ch-ua-platform: "macOS',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-origin',
                'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
            ];
            $res = Common::curlPostWithCustomHeader($this->deliveryRestrictApiUrl, $headers, json_encode([
                "pageNum" => $pageNo,
                "pageSize" => $pageSize,
                "frozenType" => "delivery_by_jit"
            ]));
            $resArr = json_decode($res, true);
            if (isset($resArr["result"]["total"])) {
                if (empty($totalPage)) {
                    $totalPage = ceil($resArr["result"]["total"] / $pageSize);
                }

                if (!empty($resArr["result"]["fundRestrictRuleDo"])) {
                    //店铺快递费限制金额
                    empty($amount) && $amount = (float)str_replace("￥", "", $resArr["result"]["fundRestrictRuleDo"]["amount"]);
                    $fundRestrictRecordDtoList = $resArr["result"]["fundRestrictRecordDtoList"];
                    if (!empty($fundRestrictRecordDtoList)) {
                        $this->saveFundRestrictRecord($fundRestrictRecordDtoList, $mallId);
                    }
                }

                $pageNo++;
                if ($pageNo > $totalPage) {
                    $accounts->update(["delivery_restrict_amount" => $amount]);
                    //发货单列表
                    //$this->startDeliverOrderRequest($cookie,$mallId);
                    break;
                }
            } else {
                break;
            }
        }
    }

    public function startGoodsRefundCostRequest($cookie, $mallIdInfo, $accounts)
    {
        $pageNo = 1;
        $pageSize = 200;
        $totalPage = null;
        $mallId = "";
        if (!empty($mallIdInfo["mallId"])) {
            $mallId = $mallIdInfo["mallId"];
            //删除历史记录
            // TemuMallsDeliveryRestrict::Where("mall_id", $mallId)->delete();
        }
        $amount = 0;
        while (1) {
            $headers = [
                "accept: */*",
                "accept-language: zh-CN,zh;q=0.9",
                "content-type: application/json",
                "cookie: " . $cookie,
                "mallid: " . $mallId,
                "origin: https://kuajing.pinduoduo.com",
                "pragma: no-cache",
                "referer: https://kuajing.pinduoduo.com/labor/limited/detail?frozenType=delivery_by_jit",
                'sec-ch-ua: "Chromium";v="112", "Google Chrome";v="112", "Not:A-Brand";v="99"',
                "sec-ch-ua-mobile: ?0",
                'sec-ch-ua-platform: "macOS',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-origin',
                'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
            ];
            $res = Common::curlPostWithCustomHeader($this->goodsRefundCostApiUrl, $headers, json_encode([
                "pageNum" => $pageNo,
                "pageSize" => $pageSize,
                "frozenType" => "goods_refund_cost"
            ]));
            $resArr = json_decode($res, true);
            if (isset($resArr["result"]["total"])) {
                if (empty($totalPage)) {
                    $totalPage = ceil($resArr["result"]["total"] / $pageSize);
                }

                if (!empty($resArr["result"]["fundRestrictRuleDo"])) {
                    //店铺快递费限制金额
                    empty($amount) && $amount = (float)str_replace("￥", "", $resArr["result"]["fundRestrictRuleDo"]["amount"]);
                    $fundRestrictRecordDtoList = $resArr["result"]["fundRestrictRecordDtoList"];
                    if (!empty($fundRestrictRecordDtoList)) {
                        $this->saveGoodsRefundCostRecord($fundRestrictRecordDtoList, $mallId);
                    }
                }

                $pageNo++;
                if ($pageNo > $totalPage) {
                    $accounts->update(["goods_refund_cost" => $amount]);
                    //发货单列表
                    //$this->startDeliverOrderRequest($cookie,$mallId);
                    break;
                }
            } else {
                break;
            }
        }
    }

    public function saveFundRestrictRecord($fundRestrictRecordDtoList, $mallId)
    {
        foreach ($fundRestrictRecordDtoList as $value) {
            $orderSnStr = str_replace(["[", "]", '"'], "", $value["extraMap"]["subPurchaseOrderSn"]);
            $row = TemuMallsDeliveryRestrict::where([
                "mall_id"=>strval($mallId),
                "shipping_no" => $value["extraMap"]["shippingNo"],
            ])->first();
            if(empty($row)){
                TemuMallsDeliveryRestrict::create([
                    "mall_id" => $mallId,
                    "shipping_no" => $value["extraMap"]["shippingNo"],
                    "sub_purchase_order_sn" => $orderSnStr,
                    "freeze_start_time" => date('Y-m-d H:i:s', (int)($value["extraMap"]["freezeStartTime"]/1000)),
                    "amount" => (float)str_replace("￥", "", $value["extraMap"]["amount"]),
                    "currency" => $value["extraMap"]["currency"],
                    "last_spider_time"=>date("Y-m-d H:i:s",time()),
                ]);
            }else{
                $row->update([
                        "sub_purchase_order_sn" => $orderSnStr,
                        "freeze_start_time" => date('Y-m-d H:i:s', (int)($value["extraMap"]["freezeStartTime"]/1000)),
                        "amount" => (float)str_replace("￥", "", $value["extraMap"]["amount"]),
                        "currency" => $value["extraMap"]["currency"],
                        "last_spider_time"=>date("Y-m-d H:i:s",time()),
                    ]
                );
            }

        }
    }

    public function saveGoodsRefundCostRecord($fundRestrictRecordDtoList, $mallId)
    {
        foreach ($fundRestrictRecordDtoList as $value) {
//            $orderSnStr = str_replace(["[", "]", '"'], "", $value["extraMap"]["subPurchaseOrderSn"]);
            $row = TemuMallsGoodsRefundCost::where([
                "mall_id"=>strval($mallId),
                "shipping_no" => $value["extraMap"]["shippingNo"],
            ])->first();
            if(empty($row)){
                TemuMallsGoodsRefundCost::create([
                    "mall_id" => $mallId,
                        "shipping_no" => $value["extraMap"]["shippingNo"],
//                    "sub_purchase_order_sn" => $orderSnStr,
                    "freeze_start_time" => date('Y-m-d H:i:s', (int)($value["extraMap"]["freezeStartTime"]/1000)),
                    "amount" => (float)str_replace("￥", "", $value["extraMap"]["amount"]),
                    "currency" => $value["extraMap"]["currency"],
                    "last_spider_time"=>date("Y-m-d H:i:s",time()),
                ]);
            }else{
                $row->update([
//                        "sub_purchase_order_sn" => $orderSnStr,
                        "freeze_start_time" => date('Y-m-d H:i:s', (int)($value["extraMap"]["freezeStartTime"]/1000)),
                        "amount" => (float)str_replace("￥", "", $value["extraMap"]["amount"]),
                        "currency" => $value["extraMap"]["currency"],
                        "last_spider_time"=>date("Y-m-d H:i:s",time()),
                    ]
                );
            }

        }
    }

    /**
     * 订单详情采集
     * User: jiahao.dong
     * Date: 2023/5/7
     * Time: 下午8:23
     * @param $cookie
     * @param $mallId
     * @param $orderSnArr
     */
    public function startDeliverOrderRequest($cookie,$mallId)
    {
        $clearModalJs = <<<JS
  var dom1 = document.querySelectorAll('.MDL_mask_5-54-0')
      var dom2 = document.querySelectorAll('.MDL_outerWrapper_5-54-0')
  for (let i = 0; i < dom1.length; i++) {
    dom1[i].setAttribute('style', 'display:none');
  }
  for (let i = 0; i < dom2.length; i++) {
    dom2[i].setAttribute('style', 'display:none');
  }
JS;
        $this->driver->get("https://kuajing.pinduoduo.com/main/order-manager/shipping-list");
        sleep(5);
        $this->driver->executeScript($clearModalJs);

        /*
        $newOrderSnArr = array_chunk($orderSnArr,19);
        foreach ($newOrderSnArr as $_orderArr){
            dump($_orderArr);
            dump("count:".count($_orderArr));
            $searchOrderStr = implode(",",$_orderArr);
            dump($searchOrderStr);
            $this->driver->findElement(WebDriverBy::xpath("//div[text()='订单号']/following-sibling::div[1]/div/div/div/div/div/input"));
            $this->driver->executeScript($clearModalJs);
            $this->driver->findElement(WebDriverBy::xpath("//div[text()='订单号']/following-sibling::div[1]/div/div/div/div/div/input"))->sendKeys($searchOrderStr);
            $this->driver->findElement(WebDriverBy::xpath("//button/span[text()='查询']"))->click();
            sleep(3);*/
        while (1){
            $log = $this->driver->manage()->getLog("performance");
            $orderResponseInfo = "";
            //获取列表接口response 数据
            if (!empty($log)) {
                foreach ($log as $k => $v) {
                    $msg = $v["message"];
                    $msgArr = json_decode($msg, true);
                    if ($msgArr["message"]["method"] == "Network.responseReceived" && $this->orderDetailApiUrl == $msgArr["message"]["params"]["response"]["url"]) {
                        $tool = new ChromeDevToolsDriver($this->driver);
                        try {
                            $res = $tool->execute("Network.getResponseBody", ["requestId" => $msgArr["message"]["params"]["requestId"]]);
                            $orderResponseInfo = $res["body"];
                        } catch (\Exception $e) {
                            dump($e->getMessage());
                        }
                        break;
                    }
                }
            }

            if(!empty($orderResponseInfo)){
                $resArr = json_decode($orderResponseInfo,true);
                if (isset($resArr["result"]["total"])) {
                    $list = $resArr["result"]["list"];
                    foreach ($list as $_orderV){
                        $status = "";
                        switch ($_orderV["status"]){
                            case 0:
                                $status = "待发货";
                                break;
                            case 1:
                                $status = "待收货";
                                break;
                            case 2:
                                $status = "已收货";
                                break;
                            case 3:
                                $status = "已入库";
                                break;
                            case 4:
                                $status = "已退货";
                                break;
                            case 5:
                                $status = "已取消";
                                break;
                            case 6:
                                $status = "部分收货";
                                break;
                        }
                        $orderRow = TemuMallsDeliveryRestrictOrder::where([
                            "mall_id" => strval($mallId),
                            "sub_purchase_order_sn"=>$_orderV["subPurchaseOrderBasicVO"]["subPurchaseOrderSn"],
                        ])->first();
                        if(empty($orderRow)){
                            //抓取收货人姓名和电话
                            $headers = [
                                "accept: */*",
                                "accept-language: zh-CN,zh;q=0.9",
                                "content-type: application/json",
                                "cookie: " . $cookie,
                                "mallid: " . $mallId,
                                "origin: https://kuajing.pinduoduo.com",
                                "pragma: no-cache",
                                "referer: https://kuajing.pinduoduo.com/main/order-manager/shipping-list",
                                'sec-ch-ua: "Chromium";v="112", "Google Chrome";v="112", "Not:A-Brand";v="99"',
                                "sec-ch-ua-mobile: ?0",
                                'sec-ch-ua-platform: "macOS',
                                'sec-fetch-dest: empty',
                                'sec-fetch-mode: cors',
                                'sec-fetch-site: same-origin',
                                'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
                            ];
                            $res = Common::curlPostWithCustomHeader($this->orderReciverUserInfoApiUrl, $headers, json_encode([
                                "deliveryOrderSn" => $_orderV["deliveryOrderSn"]
                            ]));
                            $courierName = "";
                            $courierPhone = "";
                            $resArr = json_decode($res, true);
                            if (!empty($resArr["result"])) {
                                $courierName = $resArr["result"]["courierName"];
                                $courierPhone = $resArr["result"]["courierPhone"];
                            }
                            TemuMallsDeliveryRestrictOrder::create([
                                "mall_id" => $mallId,
                                "sub_purchase_order_sn"=>$_orderV["subPurchaseOrderBasicVO"]["subPurchaseOrderSn"],
                                "delivery_order_sn"=>$_orderV["deliveryOrderSn"],
                                "product_name"=>$_orderV["subPurchaseOrderBasicVO"]["productName"],
                                "product_skc_picture"=>$_orderV["subPurchaseOrderBasicVO"]["productSkcPicture"],
                                "skc_ext_code"=>$_orderV["subPurchaseOrderBasicVO"]["skcExtCode"],
                                "product_skc_id"=>$_orderV["subPurchaseOrderBasicVO"]["productSkcId"],
                                "express_company"=>$_orderV["expressCompany"],
                                "express_company_id"=>$_orderV["expressCompanyId"],
                                "express_delivery_sn"=>$_orderV["expressDeliverySn"],
                                "courier_name"=>$courierName,
                                "courier_phone"=>$courierPhone,
                                "receive_address_province_name"=>$_orderV["receiveAddressInfo"]["provinceName"],
                                "receive_address_province_code"=>$_orderV["receiveAddressInfo"]["provinceCode"],
                                "receive_address_district_name"=>$_orderV["receiveAddressInfo"]["districtName"],
                                "receive_address_district_code"=>$_orderV["receiveAddressInfo"]["districtCode"],
                                "receive_address_city_name"=>$_orderV["receiveAddressInfo"]["cityName"],
                                "receive_address_city_code"=>$_orderV["receiveAddressInfo"]["cityCode"],
                                "receive_address_detail_address"=>$_orderV["receiveAddressInfo"]["detailAddress"],
                                "receive_address_phone"=>$_orderV["receiveAddressInfo"]["phone"],
                                "receive_address_receiver_name"=>$_orderV["receiveAddressInfo"]["receiverName"],
                                "deliver_skc_num"=>$_orderV["deliverSkcNum"],
                                "deliver_time"=>!empty($_orderV["deliverTime"])?date("Y-m-d H:i:s",$_orderV["deliverTime"]/1000):null,
                                "receive_time"=>!empty($_orderV["receiveTime"])?date("Y-m-d H:i:s",$_orderV["receiveTime"]/1000):null,
                                "inbound_time"=>!empty($_orderV["inbound_time"])?date("Y-m-d H:i:s",$_orderV["inbound_time"]/1000):null,
                                "deliver_package_num"=>$_orderV["deliverPackageNum"],
                                "receive_package_num"=>$_orderV["receivePackageNum"],
                                "status"=>$status,
                                "last_spider_time"=>date("Y-m-d H:i:s",time()),
                            ]);
                        }else{
                            $orderRow->update([
                                "deliver_time"=>!empty($_orderV["deliverTime"])?date("Y-m-d H:i:s",$_orderV["deliverTime"]/1000):null,
                                "receive_time"=>!empty($_orderV["receiveTime"])?date("Y-m-d H:i:s",$_orderV["receiveTime"]/1000):null,
                                "inbound_time"=>!empty($_orderV["inbound_time"])?date("Y-m-d H:i:s",$_orderV["inbound_time"]/1000):null,
                                "deliver_package_num"=>$_orderV["deliverPackageNum"],
                                "receive_package_num"=>$_orderV["receivePackageNum"],
                                "status"=>$status,
                                "last_spider_time"=>date("Y-m-d H:i:s",time()),
                            ]);
                        }
                    }


                    //抓取订单号包裹件数
                    try {
                        $elems = $this->driver->findElements(WebDriverBy::xpath("//span[contains(text(),'包裹详情')]"));
                        if (!empty($elems)) {
                            foreach ($elems as $elem) {
                                $this->driver->executeScript("arguments[0].click();", [$elem]);
                                sleep(2);
                                $log = $this->driver->manage()->getLog("performance");
                                $orderPackageDetailResponseInfo = "";
                                //获取列表接口response 数据
                                if (!empty($log)) {
                                    foreach ($log as $k => $v) {
                                        $msg = $v["message"];
                                        $msgArr = json_decode($msg, true);
                                        if ($msgArr["message"]["method"] == "Network.responseReceived" && $this->orderPackageDetailApiUrl == $msgArr["message"]["params"]["response"]["url"]) {
                                            $tool = new ChromeDevToolsDriver($this->driver);
                                            try {
                                                $res = $tool->execute("Network.getResponseBody", ["requestId" => $msgArr["message"]["params"]["requestId"]]);
                                                $orderPackageDetailResponseInfo = $res["body"];
                                            } catch (\Exception $e) {
                                                dump($e->getMessage());
                                            }
                                            break;
                                        }
                                    }
                                }

                                $this->driver->findElement(WebDriverBy::xpath("//div[contains(@class,'index-module__title_')]/*[name()='svg']"))->click();
                                if (!empty($orderPackageDetailResponseInfo)) {
                                    $resArr = json_decode($orderPackageDetailResponseInfo, true);
                                    if (!empty($resArr["result"])) {
                                        $packageTotalSkcNum = 0;
                                        $subPurchaseOrderSn = $resArr["result"]["subPurchaseOrderSn"];
                                        foreach ($resArr["result"]["deliveryOrderDetails"] as $_v) {
                                            $packageTotalSkcNum += $_v["deliverSkuNum"];
                                        }
                                        if ($packageTotalSkcNum > 0) {
                                            TemuMallsDeliveryRestrictOrder::where([
                                                "mall_id" => strval($mallId),
                                                "sub_purchase_order_sn" => $subPurchaseOrderSn
                                            ])->update([
                                                "package_total_deliver_skc_num" => $packageTotalSkcNum
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        dump($e->getMessage());
                    }


                }else{
                    dump($orderResponseInfo);
                }
            }
            try {
                $nextpageElem = $this->driver->findElement(WebDriverBy::xpath("//li[@data-testid='beast-core-pagination-next']"));
            } catch (\Exception $e) {
                break;
            }
            $nextpageElemClass = $nextpageElem->getAttribute("class");
            if(preg_match("/disabled/",$nextpageElemClass)){
                break;
            }else{
                $this->driver->executeScript($clearModalJs);
                $nextpageElem->click();
                sleep(8);
            }
        }

        //}
    }

    /**
     * 保存店铺信息
     * User: jiahao.dong
     * Date: 2023/5/4
     * Time: 下午5:05
     * @param $mallInfo
     * @param TemuMalls $accounts
     */
    public function saveMall($mallInfo, TemuMalls $accounts)
    {

//        //保存店铺信息数据
//        $oldMall = TemuMalls::where(["mall_id"=>$mallInfo["mallId"]])->first();
//        if (empty($oldMall)){
//            if(empty($accounts->mall_id)){
//                //通过账号密码添加后第一次采集
//                $accounts->update([
//                    "mall_id" => $mallInfo["mallId"],
//                    "mall_name" => $mallInfo["mallName"],
//                    "mall_logo" => $mallInfo["logo"],
//                    "last_spider_time" => date("Y-m-d H:i:s"),
//                ]);
//                return $accounts->syncChanges();
//            }else{
//                //子店铺第一次采集
//                return TemuMalls::create([
//                    "mall_id"=>$mallInfo["mallId"],
//                    "mall_name"=>$mallInfo["mallName"],
//                    "mall_logo"=>$mallInfo["logo"],
//                    "last_spider_time"=>date("Y-m-d H:i:s"),
//                    "username"=>$accounts->username,
//                    "password"=>$accounts->password,
//                ]);
//            }
//        }else{
//            //之前采集过
//            $oldMall->update([
//                "mall_id" => $mallInfo["mallId"],
//                "mall_name" => $mallInfo["mallName"],
//                "mall_logo" => $mallInfo["logo"],
//                "last_spider_time" => date("Y-m-d H:i:s"),
//                "username"=>$accounts->username,
//                "password"=>$accounts->password,
//            ]);
//            return $oldMall->syncChanges();
//        }
//


        //-------------------------------------------------------

        if(empty($accounts->mall_id)){
            //通过账号密码添加后第一次采集

            dump("========update new accounts =========");
            $accounts->update([
                "mall_id" => $mallInfo["mallId"],
                "mall_name" => $mallInfo["mallName"],
                "mall_logo" => $mallInfo["logo"],
                "last_spider_time" => date("Y-m-d H:i:s"),
            ]);
            return $accounts->syncChanges();
        }else{
            $oldMalls = TemuMalls::where([
                "mall_id"=>strval($mallInfo["mallId"]),
                "username"=>strval($accounts->username),
            ])->count();
            if(empty($oldMalls)){
                dump("========create new accounts =========");
                //子店铺第一次采集
                return TemuMalls::create([
                    "mall_id"=>$mallInfo["mallId"],
                    "mall_name"=>$mallInfo["mallName"],
                    "mall_logo"=>$mallInfo["logo"],
                    "last_spider_time"=>date("Y-m-d H:i:s"),
                    "username"=>$accounts->username,
                    "password"=>$accounts->password,
                ]);
            }else{
                TemuMalls::where([
                    "mall_id"=>strval($mallInfo["mallId"]),
                    "username"=>strval($accounts->username),
                ])->update([
                    "mall_name" => $mallInfo["mallName"],
                    "mall_logo" => $mallInfo["logo"],
                    "last_spider_time" => date("Y-m-d H:i:s"),
                ]);
                return TemuMalls::where([
                    "mall_id"=>strval($mallInfo["mallId"]),
                    "username"=>strval($accounts->username),
                ])->first();
            }
        }

    }

    /** 保存商品信息
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 下午3:26
     * @param $value
     * @param $mallid
     */
    public function saveProductInfo($value, $mallid)
    {
        //保存商品信息数据
        $oldGoods = TemuGoods::where(["skc" => strval($value["productSkcId"]),"mall_id"=>strval($mallid)])->first();
        if (empty($oldGoods)) {
            TemuGoods::create([
                "goods_id" => trim($value["goodsId"]),
                "title" => trim($value["productName"]),
                "img" => trim($value["productSkcPicture"]),
                "skc" => trim($value["productSkcId"]),
                "spu" => trim($value["productId"]),
                "sku_article_number" => trim($value["skcExtCode"]),
                "join_site_duration" => trim($value["onSalesDurationOffline"]),
                "category" => trim($value["category"]),
                //"purchase_config" => trim($value["purchaseConfig"] ?? ""),
                "mall_id" => $mallid,
            ]);
        } else {
            if (
                $value["productName"] != $oldGoods["title"] ||
                $value["productSkcPicture"] != $oldGoods["img"] ||
                $value["productSkcId"] != $oldGoods["skc"] ||
                $value["productId"] != $oldGoods["spu"] ||
                $value["skcExtCode"] != $oldGoods["sku_article_number"] ||
                $value["onSalesDurationOffline"] != $oldGoods["join_site_duration"] ||
                $value["category"] != $oldGoods["category"] ||
               // $value["purchaseConfig"] != $oldGoods["purchase_config"] ||
                $value["goodsId"] != $oldGoods["goods_id"]
            ) {
                $oldGoods->update([
                    "goods_id"=>trim($value["goodsId"]),
                    "title" => trim($value["productName"]),
                    "img" => trim($value["productSkcPicture"]),
                    "skc" => trim($value["productSkcId"]),
                    "spu" => trim($value["productId"]),
                    "sku_article_number" => trim($value["skcExtCode"]),
                    "join_site_duration" => trim($value["onSalesDurationOffline"]),
                    "category" => trim($value["category"]),
                    //"purchase_config" => trim($value["purchaseConfig"]),
                    "mall_id" => $mallid
                ]);
            }
        }
    }

    /** 保存商品sku 信息
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 下午3:56
     * @param $value
     * @param $mallid
     * @param $fromStatisticsInventory 是否来自库存统计的功能，默认false
     */
    public function saveProductSkuInfo($value, $mallid,$fromStatisticsInventory=false,$isWriteDb=true)
    {
        //保存商品sku 信息
        if (!empty($value["skuQuantityDetailList"])) {
            foreach ($value["skuQuantityDetailList"] as $skuDetail) {
                if($fromStatisticsInventory){
                    $this->statisticsInventorySkuIds[] = trim($skuDetail["productSkuId"]);
                    if(!$isWriteDb){
                        continue;
                    }
                }
                $isVerifyPrice = $skuDetail["isVerifyPrice"] ? "核价通过" : "";
                $isAdjusted = $skuDetail["isAdjusted"] ? "调价成功" : "";
                $oldGoodsSku = TemuGoodsSku::where([
//                    "goods_id" => trim($value["goodsId"]),
                    "goods_sku_id" => trim($skuDetail["goodsSkuId"]),
                    "product_sku_id" => trim($skuDetail["productSkuId"]),
                    "mall_id"=>strval($mallid)
                ])->first();


                //不合理库存,暂时注释掉，春节备货逻辑有变化，采用新的逻辑，过了春节可能会重新启用
               /* $unreasonable_inventory = TemuDataStatisticsService::getSkuUnreasonableInventory(
                    intval($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"])+intval($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                    intval($skuDetail["lastThirtyDaysSaleVolume"])
                );*/

                //备货逻辑
                $purchaseConfig = $skuDetail["purchaseConfig"];
                //平台建议备货天数
                $purchaseConfigDay = 0;
                if(preg_match("/\+/",$purchaseConfig)){
                    $purchaseConfigArr = explode("+",$purchaseConfig);
                    $purchaseConfigDay = (int)trim($purchaseConfigArr[0])+(int)trim($purchaseConfigArr[1]);
                }
                //春节备货不合理库存计算
                $unreasonable_inventory = TemuDataStatisticsService::getSpringFestivalSkuUnreasonableInventory(
                    intval($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"])+intval($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                    intval($skuDetail["lastThirtyDaysSaleVolume"]),
                    $purchaseConfigDay
                );

                if (empty($oldGoodsSku)) {
                    TemuGoodsSku::create([
                        "goods_id" => trim($value["goodsId"]),
                        "goods_sku_id" => trim($skuDetail["goodsSkuId"]),
                        "product_sku_id" => trim($skuDetail["productSkuId"]),
                        "sku_name" => trim($skuDetail["className"]),
                        "sku_currency_type" => trim($skuDetail["currencyType"] ?? ""),
                        "supplier_price" => trim($skuDetail["supplierPrice"] ?? ""),
                        "sku_ext_code" => trim($skuDetail["skuExtCode"] ?? ""),
                        "is_verify_price" => $isVerifyPrice,
                        "is_adjusted" => $isAdjusted,
                        "lack_quantity" => trim($skuDetail["lackQuantity"] ?? ""),
                        "advice_quantity" => trim($skuDetail["adviceQuantity"] ?? ""),
                        "available_sale_days" => trim($skuDetail["availableSaleDays"]),
                        "available_sale_days_from_inventory" => trim($skuDetail["availableSaleDaysFromInventory"]),
                        "warehouse_available_sale_days" => trim($skuDetail["warehouseAvailableSaleDays"]),
                        "in_cart_number_7d" => trim($skuDetail["inCartNumber7d"]),
                        "in_card_number" => trim($skuDetail["inCardNumber"]),
                        "nomsg_subs_cnt_cnt_sth" => trim($skuDetail["nomsgSubsCntCntSth"]),
                        "today_sale_volume" => trim($skuDetail["todaySaleVolume"]),
                        "last_seven_days_sale_volume" => trim($skuDetail["lastSevenDaysSaleVolume"]),
                        "last_thirty_days_sale_volume" => trim($skuDetail["lastThirtyDaysSaleVolume"]),
                        "ware_house_inventory_num" => trim($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"]),
                        "unavailable_warehouse_inventory_num" => trim($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                        "wait_receive_num" => trim($skuDetail["inventoryNumInfo"]["waitReceiveNum"]),
                        "wait_delivery_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitDeliveryInventoryNum"]),
                        "wait_approve_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitApproveInventoryNum"]),
                        "wait_delivery_num" => trim($skuDetail["vmiOrderInfo"]["waitDeliveryNum"]),
                        "transportation_num" => trim($skuDetail["vmiOrderInfo"]["transportationNum"]),
                        "delivery_delay_num" => trim($skuDetail["vmiOrderInfo"]["deliveryDelayNum"]),
                        "arrival_delay_num" => trim($skuDetail["vmiOrderInfo"]["arrivalDelayNum"]),
                        "not_vmi_wait_delivery_num" => trim($skuDetail["notVmiOrderInfo"]["waitDeliveryNum"]),
                        "not_vmi_transportation_num" => trim($skuDetail["notVmiOrderInfo"]["transportationNum"]),
                        "not_vmi_delivery_delay_num" => trim($skuDetail["notVmiOrderInfo"]["deliveryDelayNum"]),
                        "not_vmi_arrival_delay_num" => trim($skuDetail["notVmiOrderInfo"]["arrivalDelayNum"]),
                        "mall_id" => $mallid,
                        //$skuInfo->ware_house_inventory_num + $skuInfo->unavailable_warehouse_inventory_num;
                        "unreasonable_inventory" =>$unreasonable_inventory
                    ]);
                } else {
                    //不合理库存总成本
                   /* $skuUnreasonableInventoryTotalCostPrice = TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($unreasonable_inventory,$oldGoodsSku["cost_price"]);
                    if($skuUnreasonableInventoryTotalCostPrice>0){
                        $oldGoodsSku->update([
                            "unreasonable_inventory_total_cost_price" =>$skuUnreasonableInventoryTotalCostPrice,
                        ]);
                    }*/

                    if (
                        $value["goodsId"] != $oldGoodsSku["goods_id"] ||
                        $skuDetail["className"] != $oldGoodsSku["sku_name"] ||
                        $skuDetail["currencyType"] != $oldGoodsSku["sku_currency_type"] ||
                        $skuDetail["supplierPrice"] != $oldGoodsSku["supplier_price"] ||
                        $skuDetail["skuExtCode"] != $oldGoodsSku["sku_ext_code"] ||
                        $isVerifyPrice != $oldGoodsSku["is_verify_price"] ||
                        $isAdjusted != $oldGoodsSku["is_adjusted"] ||
                        $skuDetail["lackQuantity"] != $oldGoodsSku["lack_quantity"] ||
                        $skuDetail["adviceQuantity"] != $oldGoodsSku["advice_quantity"] ||
                        $skuDetail["availableSaleDays"] != $oldGoodsSku["available_sale_days"] ||
                        $skuDetail["availableSaleDaysFromInventory"] != $oldGoodsSku["available_sale_days_from_inventory"] ||
                        $skuDetail["warehouseAvailableSaleDays"] != $oldGoodsSku["warehouse_available_sale_days"] ||
                        $skuDetail["inCartNumber7d"] != $oldGoodsSku["in_cart_number_7d"] ||
                        $skuDetail["inCardNumber"] != $oldGoodsSku["in_card_number"] ||
                        $skuDetail["nomsgSubsCntCntSth"] != $oldGoodsSku["nomsg_subs_cnt_cnt_sth"] ||
                        $skuDetail["todaySaleVolume"] != $oldGoodsSku["today_sale_volume"] ||
                        $skuDetail["lastSevenDaysSaleVolume"] != $oldGoodsSku["last_seven_days_sale_volume"] ||
                        $skuDetail["lastThirtyDaysSaleVolume"] != $oldGoodsSku["last_thirty_days_sale_volume"] ||
                        $skuDetail["inventoryNumInfo"]["warehouseInventoryNum"] != $oldGoodsSku["ware_house_inventory_num"] ||
                        $skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"] != $oldGoodsSku["unavailable_warehouse_inventory_num"] ||
                        $skuDetail["inventoryNumInfo"]["waitReceiveNum"] != $oldGoodsSku["wait_receive_num"] ||
                        $skuDetail["inventoryNumInfo"]["waitDeliveryInventoryNum"] != $oldGoodsSku["wait_delivery_inventory_num"] ||
                        $skuDetail["inventoryNumInfo"]["waitApproveInventoryNum"] != $oldGoodsSku["wait_approve_inventory_num"] ||
                        $skuDetail["vmiOrderInfo"]["waitDeliveryNum"] != $oldGoodsSku["wait_delivery_num"] ||
                        $skuDetail["vmiOrderInfo"]["transportationNum"] != $oldGoodsSku["transportation_num"] ||
                        $skuDetail["vmiOrderInfo"]["deliveryDelayNum"] != $oldGoodsSku["delivery_delay_num"] ||
                        $skuDetail["vmiOrderInfo"]["arrivalDelayNum"] != $oldGoodsSku["arrival_delay_num"] ||

                        $skuDetail["notVmiOrderInfo"]["waitDeliveryNum"] != $oldGoodsSku["not_vmi_wait_delivery_num"] ||
                        $skuDetail["notVmiOrderInfo"]["transportationNum"] != $oldGoodsSku["not_vmi_transportation_num"] ||
                        $skuDetail["notVmiOrderInfo"]["deliveryDelayNum"] != $oldGoodsSku["not_vmi_delivery_delay_num"] ||
                        $skuDetail["notVmiOrderInfo"]["arrivalDelayNum"] != $oldGoodsSku["not_vmi_arrival_delay_num"] ||
                        $unreasonable_inventory != $oldGoodsSku['unreasonable_inventory']

                    ) {
                        $oldGoodsSku->update([
                            "goods_id" => trim($value["goodsId"]),
                            "sku_name" => trim($skuDetail["className"]),
                            "sku_currency_type" => trim($skuDetail["currencyType"] ?? ""),
                            "supplier_price" => trim($skuDetail["supplierPrice"] ?? ""),
                            "sku_ext_code" => trim($skuDetail["skuExtCode"] ?? ""),
                            "is_verify_price" => $isVerifyPrice,
                            "is_adjusted" => $isAdjusted,
                            "lack_quantity" => trim($skuDetail["lackQuantity"] ?? ""),
                            "advice_quantity" => trim($skuDetail["adviceQuantity"] ?? ""),
                            "available_sale_days" => trim($skuDetail["availableSaleDays"]),
                            "available_sale_days_from_inventory" => trim($skuDetail["availableSaleDaysFromInventory"]),
                            "warehouse_available_sale_days" => trim($skuDetail["warehouseAvailableSaleDays"]),
                            "in_cart_number_7d" => trim($skuDetail["inCartNumber7d"]),
                            "in_card_number" => trim($skuDetail["inCardNumber"]),
                            "nomsg_subs_cnt_cnt_sth" => trim($skuDetail["nomsgSubsCntCntSth"]),
                            "today_sale_volume" => trim($skuDetail["todaySaleVolume"]),
                            "last_seven_days_sale_volume" => trim($skuDetail["lastSevenDaysSaleVolume"]),
                            "last_thirty_days_sale_volume" => trim($skuDetail["lastThirtyDaysSaleVolume"]),
                            "ware_house_inventory_num" => trim($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"]),
                            "unavailable_warehouse_inventory_num" => trim($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                            "wait_receive_num" => trim($skuDetail["inventoryNumInfo"]["waitReceiveNum"]),
                            "wait_delivery_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitDeliveryInventoryNum"]),
                            "wait_approve_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitApproveInventoryNum"]),
                            "wait_delivery_num" => trim($skuDetail["vmiOrderInfo"]["waitDeliveryNum"]),
                            "transportation_num" => trim($skuDetail["vmiOrderInfo"]["transportationNum"]),
                            "delivery_delay_num" => trim($skuDetail["vmiOrderInfo"]["deliveryDelayNum"]),
                            "arrival_delay_num" => trim($skuDetail["vmiOrderInfo"]["arrivalDelayNum"]),
                            "not_vmi_wait_delivery_num" => trim($skuDetail["notVmiOrderInfo"]["waitDeliveryNum"]),
                            "not_vmi_transportation_num" => trim($skuDetail["notVmiOrderInfo"]["transportationNum"]),
                            "not_vmi_delivery_delay_num" => trim($skuDetail["notVmiOrderInfo"]["deliveryDelayNum"]),
                            "not_vmi_arrival_delay_num" => trim($skuDetail["notVmiOrderInfo"]["arrivalDelayNum"]),
                            "mall_id" => $mallid,
                            "unreasonable_inventory" =>$unreasonable_inventory,
                            "unreasonable_inventory_total_cost_price"=>TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($unreasonable_inventory,$oldGoodsSku["cost_price"])
                        ]);
                    }

                }
                //保存销售信息
                $this->saveProductSkuSaleInfo($skuDetail, $value, $mallid);
            }
        }
    }

    /** 保存商品sku 信息
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 下午3:56
     * @param $value
     * @param $mallid
     * @param $fromStatisticsInventory 是否来自库存统计的功能，默认false
     */
    public function saveProductSkuInfoNew($value, $mallid,$fromStatisticsInventory=false,$isWriteDb=true)
    {
        //保存商品sku 信息
        if (!empty($value["skuQuantityDetailList"])) {
            foreach ($value["skuQuantityDetailList"] as $skuDetail) {
                if($fromStatisticsInventory){
                    $this->statisticsInventorySkuIds[] = trim($skuDetail["productSkuId"]);
                    if(!$isWriteDb){
                        continue;
                    }
                }
                $isVerifyPrice = $skuDetail["isVerifyPrice"] ? "核价通过" : "";
                $isAdjusted = $skuDetail["isAdjusted"] ? "调价成功" : "";
                $oldGoodsSku = TemuGoodsSku::where([
//                    "goods_id" => trim($value["goodsId"]),
                    "goods_sku_id" => trim($skuDetail["goodsSkuId"]),
                    "product_sku_id" => trim($skuDetail["productSkuId"]),
                    "mall_id"=>strval($mallid)
                ])->first();


                //不合理库存,暂时注释掉，春节备货逻辑有变化，采用新的逻辑，过了春节可能会重新启用
                /* $unreasonable_inventory = TemuDataStatisticsService::getSkuUnreasonableInventory(
                     intval($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"])+intval($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                     intval($skuDetail["lastThirtyDaysSaleVolume"])
                 );*/
                //备货逻辑
                $purchaseConfig = $skuDetail["purchaseConfig"];
                //平台建议备货天数
                $purchaseConfigDay = 0;
                if(preg_match("/\+/",$purchaseConfig)){
                    $purchaseConfigArr = explode("+",$purchaseConfig);
                    $purchaseConfigDay = (int)trim($purchaseConfigArr[0])+(int)trim($purchaseConfigArr[1]);
                }
                //春节备货不合理库存计算
                $unreasonable_inventory = TemuDataStatisticsService::getSpringFestivalSkuUnreasonableInventory(
                    intval($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"])+intval($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                    intval($skuDetail["lastThirtyDaysSaleVolume"]),
                    $purchaseConfigDay
                );

                if (empty($oldGoodsSku)) {
                    TemuGoodsSku::create([
                        "goods_id" => trim($value["goodsId"]),
                        "goods_sku_id" => trim($skuDetail["goodsSkuId"]),
                        "product_sku_id" => trim($skuDetail["productSkuId"]),
                        "sku_name" => trim($skuDetail["className"]),
                        "sku_currency_type" => trim($skuDetail["currencyType"] ?? ""),
                        "supplier_price" => trim($skuDetail["supplierPrice"] ?? ""),
                        "sku_ext_code" => trim($skuDetail["skuExtCode"] ?? ""),
                        "is_verify_price" => $isVerifyPrice,
                        "is_adjusted" => $isAdjusted,
                        "lack_quantity" => trim($skuDetail["lackQuantity"] ?? ""),
                        "advice_quantity" => trim($skuDetail["adviceQuantity"] ?? ""),
                        "available_sale_days" => trim($skuDetail["availableSaleDays"]),
                        "available_sale_days_from_inventory" => trim($skuDetail["availableSaleDaysFromInventory"]),
                        "warehouse_available_sale_days" => trim($skuDetail["warehouseAvailableSaleDays"]),
                        "in_cart_number_7d" => trim($skuDetail["inCartNumber7d"]),
                        "in_card_number" => trim($skuDetail["inCardNumber"]),
                        "nomsg_subs_cnt_cnt_sth" => trim($skuDetail["nomsgSubsCntCntSth"]),
                        "today_sale_volume" => trim($skuDetail["todaySaleVolume"]),
                        "last_seven_days_sale_volume" => trim($skuDetail["lastSevenDaysSaleVolume"]),
                        "last_thirty_days_sale_volume" => trim($skuDetail["lastThirtyDaysSaleVolume"]),
                        "ware_house_inventory_num" => trim($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"]),
                        "unavailable_warehouse_inventory_num" => trim($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                        "wait_receive_num" => trim($skuDetail["inventoryNumInfo"]["waitReceiveNum"]),
                        "wait_delivery_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitDeliveryInventoryNum"]),
                        "wait_approve_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitApproveInventoryNum"]),
                        "mall_id" => $mallid,
                        //$skuInfo->ware_house_inventory_num + $skuInfo->unavailable_warehouse_inventory_num;
                        "unreasonable_inventory" =>$unreasonable_inventory
                    ]);
                } else {
                    //不合理库存总成本
                    /* $skuUnreasonableInventoryTotalCostPrice = TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($unreasonable_inventory,$oldGoodsSku["cost_price"]);
                     if($skuUnreasonableInventoryTotalCostPrice>0){
                         $oldGoodsSku->update([
                             "unreasonable_inventory_total_cost_price" =>$skuUnreasonableInventoryTotalCostPrice,
                         ]);
                     }*/
                    if (
                        $value["goodsId"] != $oldGoodsSku["goods_id"] ||
                        $skuDetail["className"] != $oldGoodsSku["sku_name"] ||
                        $skuDetail["currencyType"] != $oldGoodsSku["sku_currency_type"] ||
                        $skuDetail["supplierPrice"] != $oldGoodsSku["supplier_price"] ||
                        $skuDetail["skuExtCode"] != $oldGoodsSku["sku_ext_code"] ||
                        $isVerifyPrice != $oldGoodsSku["is_verify_price"] ||
                        $isAdjusted != $oldGoodsSku["is_adjusted"] ||
                        $skuDetail["lackQuantity"] != $oldGoodsSku["lack_quantity"] ||
                        $skuDetail["adviceQuantity"] != $oldGoodsSku["advice_quantity"] ||
                        $skuDetail["availableSaleDays"] != $oldGoodsSku["available_sale_days"] ||
                        $skuDetail["availableSaleDaysFromInventory"] != $oldGoodsSku["available_sale_days_from_inventory"] ||
                        $skuDetail["warehouseAvailableSaleDays"] != $oldGoodsSku["warehouse_available_sale_days"] ||
                        $skuDetail["inCartNumber7d"] != $oldGoodsSku["in_cart_number_7d"] ||
                        $skuDetail["inCardNumber"] != $oldGoodsSku["in_card_number"] ||
                        $skuDetail["nomsgSubsCntCntSth"] != $oldGoodsSku["nomsg_subs_cnt_cnt_sth"] ||
                        $skuDetail["todaySaleVolume"] != $oldGoodsSku["today_sale_volume"] ||
                        $skuDetail["lastSevenDaysSaleVolume"] != $oldGoodsSku["last_seven_days_sale_volume"] ||
                        $skuDetail["lastThirtyDaysSaleVolume"] != $oldGoodsSku["last_thirty_days_sale_volume"] ||
                        $skuDetail["inventoryNumInfo"]["warehouseInventoryNum"] != $oldGoodsSku["ware_house_inventory_num"] ||
                        $skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"] != $oldGoodsSku["unavailable_warehouse_inventory_num"] ||
                        $skuDetail["inventoryNumInfo"]["waitReceiveNum"] != $oldGoodsSku["wait_receive_num"] ||
                        $skuDetail["inventoryNumInfo"]["waitDeliveryInventoryNum"] != $oldGoodsSku["wait_delivery_inventory_num"] ||
                        $skuDetail["inventoryNumInfo"]["waitApproveInventoryNum"] != $oldGoodsSku["wait_approve_inventory_num"] ||

                        //平台新接口中没有以下字段
                        /*$skuDetail["vmiOrderInfo"]["waitDeliveryNum"] != $oldGoodsSku["wait_delivery_num"] ||
                        $skuDetail["vmiOrderInfo"]["transportationNum"] != $oldGoodsSku["transportation_num"] ||
                        $skuDetail["vmiOrderInfo"]["deliveryDelayNum"] != $oldGoodsSku["delivery_delay_num"] ||
                        $skuDetail["vmiOrderInfo"]["arrivalDelayNum"] != $oldGoodsSku["arrival_delay_num"] ||

                        $skuDetail["notVmiOrderInfo"]["waitDeliveryNum"] != $oldGoodsSku["not_vmi_wait_delivery_num"] ||
                        $skuDetail["notVmiOrderInfo"]["transportationNum"] != $oldGoodsSku["not_vmi_transportation_num"] ||
                        $skuDetail["notVmiOrderInfo"]["deliveryDelayNum"] != $oldGoodsSku["not_vmi_delivery_delay_num"] ||
                        $skuDetail["notVmiOrderInfo"]["arrivalDelayNum"] != $oldGoodsSku["not_vmi_arrival_delay_num"] ||*/
                        $unreasonable_inventory != $oldGoodsSku['unreasonable_inventory']

                    ) {
                        $oldGoodsSku->update([
                            "goods_id" => trim($value["goodsId"]),
                            "sku_name" => trim($skuDetail["className"]),
                            "sku_currency_type" => trim($skuDetail["currencyType"] ?? ""),
                            "supplier_price" => trim($skuDetail["supplierPrice"] ?? ""),
                            "sku_ext_code" => trim($skuDetail["skuExtCode"] ?? ""),
                            "is_verify_price" => $isVerifyPrice,
                            "is_adjusted" => $isAdjusted,
                            "lack_quantity" => trim($skuDetail["lackQuantity"] ?? ""),
                            "advice_quantity" => trim($skuDetail["adviceQuantity"] ?? ""),
                            "available_sale_days" => trim($skuDetail["availableSaleDays"]),
                            "available_sale_days_from_inventory" => trim($skuDetail["availableSaleDaysFromInventory"]),
                            "warehouse_available_sale_days" => trim($skuDetail["warehouseAvailableSaleDays"]),
                            "in_cart_number_7d" => trim($skuDetail["inCartNumber7d"]),
                            "in_card_number" => trim($skuDetail["inCardNumber"]),
                            "nomsg_subs_cnt_cnt_sth" => trim($skuDetail["nomsgSubsCntCntSth"]),
                            "today_sale_volume" => trim($skuDetail["todaySaleVolume"]),
                            "last_seven_days_sale_volume" => trim($skuDetail["lastSevenDaysSaleVolume"]),
                            "last_thirty_days_sale_volume" => trim($skuDetail["lastThirtyDaysSaleVolume"]),
                            "ware_house_inventory_num" => trim($skuDetail["inventoryNumInfo"]["warehouseInventoryNum"]),
                            "unavailable_warehouse_inventory_num" => trim($skuDetail["inventoryNumInfo"]["unavailableWarehouseInventoryNum"]),
                            "wait_receive_num" => trim($skuDetail["inventoryNumInfo"]["waitReceiveNum"]),
                            "wait_delivery_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitDeliveryInventoryNum"]),
                            "wait_approve_inventory_num" => trim($skuDetail["inventoryNumInfo"]["waitApproveInventoryNum"]),
                            "wait_delivery_num" => 0,
                            "transportation_num" => 0,
                            "delivery_delay_num" => 0,
                            "arrival_delay_num" => 0,
                            "not_vmi_wait_delivery_num" => 0,
                            "not_vmi_transportation_num" => 0,
                            "not_vmi_delivery_delay_num" => 0,
                            "not_vmi_arrival_delay_num" => 0,
                            "mall_id" => $mallid,
                            "unreasonable_inventory" =>$unreasonable_inventory,
                            "unreasonable_inventory_total_cost_price"=>TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($unreasonable_inventory,$oldGoodsSku["cost_price"])
                        ]);
                    }

                }
                //保存销售信息
                $this->saveProductSkuSaleInfo($skuDetail, $value, $mallid);
            }
        }
    }

    /**
     * 保存商品今日销售信息
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 下午9:04
     * @param $skuDetail
     * @param $goodsInfo
     */
    public function saveProductSkuSaleInfo($skuDetail, $goodsInfo, $mallid)
    {
        //保存商品今日销售信息
        $oldSkuTodaySaleInfo = TemuGoodsSales::where([
            //"goods_id" => strval($goodsInfo["goodsId"]),
            "goods_sku_id" => strval($skuDetail["goodsSkuId"]),
            "product_sku_id" => strval($skuDetail["productSkuId"]),
            "mall_id"=>strval($mallid)
        ])->whereRaw("DATE_FORMAT(`created_at`,'%Y-%m-%d')=?", [date("Y-m-d", time())])->first();

        $skuData = TemuGoodsSku::where([
            //"goods_id" => strval($goodsInfo["goodsId"]),
            "goods_sku_id" => strval($skuDetail["goodsSkuId"]),
            "product_sku_id" => strval($skuDetail["productSkuId"]),
            "mall_id"=>strval($mallid)
        ])->first();
        if (empty($oldSkuTodaySaleInfo)) {
            TemuGoodsSales::create([
                "goods_id" => trim($goodsInfo["goodsId"]),
                "goods_sku_id" => trim($skuDetail["goodsSkuId"]),
                "product_sku_id" => trim($skuDetail["productSkuId"]),
                "price" => trim($skuDetail["supplierPrice"]),
                "today_sale_volume" => trim($skuDetail["todaySaleVolume"]),
                "last_seven_days_sale_volume" => trim($skuDetail["lastSevenDaysSaleVolume"]),
                "last_thirty_days_sale_volume" => trim($skuDetail["lastThirtyDaysSaleVolume"]),
                "cost_price" => !empty($skuData) ? $skuData->cost_price : 0.00,
                "mall_id" => $mallid
            ]);
        } else {
            $oldSkuTodaySaleInfo->update([
                "goods_id"=>trim($goodsInfo["goodsId"]),
                "price" => trim($skuDetail["supplierPrice"]),
                "today_sale_volume" => trim($skuDetail["todaySaleVolume"]),
                "last_seven_days_sale_volume" => trim($skuDetail["lastSevenDaysSaleVolume"]),
                "last_thirty_days_sale_volume" => trim($skuDetail["lastThirtyDaysSaleVolume"]),
                "cost_price" => !empty($skuData) ? $skuData->cost_price : 0.00,
                "mall_id" => $mallid
            ]);
        }
        TemuGoodsSales::where([
            "mall_id"=>strval($mallid),
//            "goods_id" => strval($goodsInfo["goodsId"]),
            "goods_sku_id" => strval($skuDetail["goodsSkuId"]),
            "product_sku_id" => strval($skuDetail["productSkuId"]),
            "price"=>"0"
        ])->update([
            "price"=>$skuDetail["supplierPrice"]
        ]);
    }

    /**
     * 不合理库存统计
     * @param $cookie
     * @return void
     */
    public function statisticsRemainingInventoryExistsSkus($cookie)
    {
        $loginUserInfo = $this->getLoginUserInfo($cookie);
        dump("登录用户信息");
        dump(json_encode($loginUserInfo));
        if (!empty($loginUserInfo["result"])) {
            $mallIds = $loginUserInfo["result"]["companyList"][0];
            if(!empty($mallIds["malInfoList"])){
                foreach ($mallIds["malInfoList"] as $_mallIdInfo){
                    try {
                        $this->startRemainingInventoryExistsSkusRequest($cookie, $_mallIdInfo);
//                        $this->startDeliveryRestrictRequest($cookieStr, $_mallIdInfo, $newAccounts);
//                        $this->startGoodsRefundCostRequest($cookieStr, $_mallIdInfo, $newAccounts);
//                        $this->spiderMallIds[$_mallIdInfo['mallId']] =1;
                    } catch (\Exception $e) {
                        dump($e->getMessage());
                        continue;
                    }
                }
            }
        }
    }

    public function startRemainingInventoryExistsSkusRequest($cookie,$mallIdInfo)
    {
        $pageNo = 1;
        $pageSize = 40;
        $totalPage = null;
        $mallId = "";
        $isWriteProductAndSkuInfo = true;
        if (!empty($mallIdInfo["mallId"])) {
            $mallId = $mallIdInfo["mallId"];
        }
        $resetTimes = 1;
        \DB::listen(function($query) {
            $bindings = $query->bindings;
            $sql = $query->sql;
            foreach ($bindings as $replace){
                $value = is_numeric($replace) ? $replace : "'".$replace."'";
                $sql = preg_replace('/\?/', $value, $sql, 1);
            }
            file_put_contents("./debug.sql",$sql.PHP_EOL,FILE_APPEND);
            dump($sql);
        });
        while (1) {
            $headers = [
                "accept: */*",
                "cache-control: no-cache",
                "cookie:" . $cookie,
                "content-type: application/json",
                "mallid: " . $mallId,
                'sec-ch-ua: \"Chromium\";v=\"112\", \"Google Chrome\";v=\"112\", \"Not:A-Brand\";v=\"99\"',
                "sec-ch-ua-mobile: ?0",
                'sec-ch-ua-platform: \"macOS\"',
                "sec-fetch-dest: empty",
                "sec-fetch-mode: cors",
                "sec-fetch-site: same-origin",
                "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
            ];
            $res = Common::curlPostWithCustomHeader($this->temuSaleManageListApiUrlNew, $headers, json_encode([
                "pageNo" => $pageNo,
                "pageSize" => $pageSize,
                "isLack" => 0,
                "priceAdjustRecentDays" => 7,
                "maxRemanentInventoryNum"=>1
            ]));
//            dump($res);
            $resArr = json_decode($res, true);
            if (isset($resArr["result"]["total"])) {
                if (empty($totalPage)) {
                    $totalPage = ceil($resArr["result"]["total"] / $pageSize);
                }

                if (!empty($resArr["result"]["subOrderList"])) {
                    $subOrderList = $resArr["result"]["subOrderList"];
                    if (!empty($subOrderList)) {
                        foreach ($subOrderList as $value) {
                            //保存sku信息
                            $isWriteProductAndSkuInfo && $this->saveProductInfo($value, $mallId);
                            $this->saveProductSkuInfoNew($value, $mallId,true,$isWriteProductAndSkuInfo);
                        }
                    }
                }

                $pageNo++;
                if ($pageNo > $totalPage) {
                    break;
                }
            } else {
                if(isset($resArr["errorMsg"]) && !preg_match("/当前发货人数较多/",$resArr["errorMsg"])){
                    dump("break startRemainingInventoryExistsSkusRequest");
                    dump($res);
                    break;
                }else{
                    sleep(1);
                    dump("当前发货人数较多,重新拉取列表");
                    $resetTimes++;
                    if($resetTimes>3){
                        break;
                    }
                }
            }
        }
        dump("店铺名称:".$mallIdInfo["mallName"].",存在库存的sku统计完毕");
        dump($this->statisticsInventorySkuIds);

        if(!empty($this->statisticsInventorySkuIds)){


            //将此店铺中其它的 sku库存批量设置为0
            TemuGoodsSku::whereNotIn("product_sku_id",$this->statisticsInventorySkuIds)
                ->where([
                "mall_id"=>$mallId
            ])->update([
                "sales_inventory_num"=>0,
                "unavailable_warehouse_inventory_num"=>0,
                "wait_receive_num"=>0,
                "wait_delivery_inventory_num"=>0,
                "wait_delivery_num"=>0,
                "transportation_num"=>0,
                "delivery_delay_num" => 0,
                "arrival_delay_num" => 0,
                "not_vmi_wait_delivery_num" => 0,
                "not_vmi_transportation_num" => 0,
                "not_vmi_delivery_delay_num" => 0,
                "not_vmi_arrival_delay_num" => 0,
                "unreasonable_inventory"=>0,
                "ware_house_inventory_num"=>0,
                "unreasonable_inventory_total_cost_price"=>0,


            ]);
            $this->statisticsInventorySkuIds =[];

            dump("店铺名称:".$mallIdInfo["mallName"].",没有库存的sku重置完毕");
        }
    }


    /**
     * 销量校准
     * @param $cookie
     * @param $startDate
     * @param $endDate
     * @return void
     */
    public function spiderCalibrateSalesVolume($cookie,$startDate,$endDate){
        $loginUserInfo = $this->getLoginUserInfo($cookie);
        dump("登录用户信息");
        dump(json_encode($loginUserInfo));
        if (!empty($loginUserInfo["result"])) {
            $mallIds = $loginUserInfo["result"]["companyList"][0];
            if(!empty($mallIds["malInfoList"])){
                foreach ($mallIds["malInfoList"] as $_mallIdInfo){
                    try {
                        $this->startThirtyDaysSalesSkusRequest($cookie, $_mallIdInfo,$startDate,$endDate);
                    } catch (\Exception $e) {
                        dump($e->getMessage());
                        continue;
                    }
                }
            }
        }
    }

    /**
     * 查找30 天有动销的sku
     * @param $cookie
     * @param $mallIdInfo
     * @param $startDate
     * @param $endDate
     * @return void
     */
    public function startThirtyDaysSalesSkusRequest($cookie,$mallIdInfo,$startDate,$endDate)
    {
        $pageNo = 1;
        $pageSize = 40;
        $totalPage = null;
        $mallId = "";
        $isWriteProductAndSkuInfo = true;
        if (!empty($mallIdInfo["mallId"])) {
            $mallId = $mallIdInfo["mallId"];
        }
        $resetTimes = 1;
        $skuIds = [];

        while (1) {
            $headers = [
                "accept: */*",
                "cache-control: no-cache",
                "cookie:" . $cookie,
                "content-type: application/json",
                "mallid: " . $mallId,
                'sec-ch-ua: \"Chromium\";v=\"112\", \"Google Chrome\";v=\"112\", \"Not:A-Brand\";v=\"99\"',
                "sec-ch-ua-mobile: ?0",
                'sec-ch-ua-platform: \"macOS\"',
                "sec-fetch-dest: empty",
                "sec-fetch-mode: cors",
                "sec-fetch-site: same-origin",
                "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
            ];
            $res = Common::curlPostWithCustomHeader($this->temuSaleManageListApiUrlNew, $headers, json_encode([
                "pageNo" => $pageNo,
                "pageSize" => $pageSize,
                "isLack" => 0,
                "priceAdjustRecentDays" => 7,
                "thirtyDaysSaleVolumMin"=>1
            ]));
//            dump($res);
            $resArr = json_decode($res, true);
            if (isset($resArr["result"]["total"])) {
                if (empty($totalPage)) {
                    $totalPage = ceil($resArr["result"]["total"] / $pageSize);
                }
                if (!empty($resArr["result"]["subOrderList"])) {
                    $subOrderList = $resArr["result"]["subOrderList"];
                    if (!empty($subOrderList)) {
                        foreach ($subOrderList as $value) {
                            if (!empty($value["skuQuantityDetailList"])) {
                                foreach ($value["skuQuantityDetailList"] as $skuDetail) {
                                    $skuIds[] = trim($skuDetail["productSkuId"]);
                                }
                            }
                        }
                    }
                }

                $pageNo++;
                if ($pageNo > $totalPage) {
                    break;
                }
            } else {
                if(isset($resArr["errorMsg"]) && !preg_match("/当前发货人数较多/",$resArr["errorMsg"])){
                    dump("break startThirtyDaysSalesSkusRequest");
                    dump($res);
                    break;
                }else{
                    sleep(1);
                    dump("当前发货人数较多,重新拉取列表");
                    $resetTimes++;
                    if($resetTimes>3){
                        break;
                    }
                }
            }
        }
        dump("店铺名称:".$mallIdInfo["mallName"].",30天存在销量的sku统计完毕");
        dump($skuIds);

        if(!empty($skuIds)){
            \DB::listen(function($query) {
                $bindings = $query->bindings;
                $sql = $query->sql;
                foreach ($bindings as $replace){
                    $value = is_numeric($replace) ? $replace : "'".$replace."'";
                    $sql = preg_replace('/\?/', $value, $sql, 1);
                }
                dump($sql);
            });

            //针对30天有动销的sku 进行销量校准
            $this->carlibrationSkuSaleNums($mallId,$cookie,$skuIds,$startDate,$endDate);
            $nowDate = date("Y-m-d",time());
            $last30dayDate = date("Y-m-d",strtotime("-30 days"));
            //近30 天没有销量的进行重置
            TemuGoodsSales::whereNotIn("product_sku_id",$skuIds)
                ->where([
                    "mall_id"=>strval($mallId)
                ])->whereRaw("`created_at`>='$last30dayDate' and `created_at`<'$nowDate'")->update([
                    "today_sale_volume"=>0
                ]);
            dump("店铺名称:".$mallIdInfo["mallName"].",没有销量的sku重置完毕");
        }
    }


    /**
     * 销量校准
     * @param $mallId
     * @param $cookie
     * @param $skuIds
     * @param $startDate
     * @param $endDate
     * @return void
     */
    public function carlibrationSkuSaleNums($mallId,$cookie,$skuIds,$startDate,$endDate)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $total = count($skuIds);
        $limit = 20;
        if($total>0){
            $chunkSkuIds = array_chunk($skuIds,$limit);
            foreach ($chunkSkuIds as $skuIdsArr){
                $res = $this->getHistorySalesNum($mallId,$cookie,$startDate,$endDate,$skuIdsArr);
                if(!empty($res["success"])){
                    $listRes = $res["result"];
                    $skuSalesRes = [];
                    foreach ($listRes as $_val){
                        $skuSalesRes[$_val["prodSkuId"]][$_val["date"]]=$_val;
                    }
                    foreach ($skuSalesRes as $prodSkuId=>$_valArr){
                        $start = $startTime;
                        $end = $endTime;
                        while ($start<=$end){
                            $date = date("Y-m-d",$start);
                            if (isset($_valArr[$date])){

                                $historySaleData = TemuGoodsSales::where(["mall_id"=>strval($mallId),"product_sku_id"=>strval($_valArr[$date]["prodSkuId"])])
                                    ->whereRaw("DATE_FORMAT(`created_at`,'%Y-%m-%d')=?", [$_valArr[$date]["date"]])->first();


                                //如果表中没有这条记录，则进行新增
                                if(empty($historySaleData)){
                                    $cost_price = 0;
                                    $price = 0;
                                    $beforeDateCostPriceData = TemuGoodsSales::where(["mall_id"=>strval($mallId),"product_sku_id"=>strval($_valArr[$date]["prodSkuId"])])
                                        ->whereRaw("`created_at`<'".$_valArr[$date]["date"]."'")
                                        ->whereRaw("`price`!=''")
                                        ->orderBy("created_at","desc")->first();

                                    if(empty($beforeDateCostPriceData)){
                                        $afterDateCostPriceData = TemuGoodsSales::where(["mall_id"=>strval($mallId),"product_sku_id"=>strval($_valArr[$date]["prodSkuId"])])
                                            ->whereRaw("`created_at`>'".$_valArr[$date]["date"]."'")
                                            ->whereRaw("`price`!=''")
                                            ->orderBy("created_at","asc")->first();
                                        if(!empty($afterDateCostPriceData)){
                                            $cost_price = $afterDateCostPriceData->cost_price;
                                            $price = $afterDateCostPriceData->price;
                                        }
                                    }else{
                                        $cost_price = $beforeDateCostPriceData->cost_price;
                                        $price = $beforeDateCostPriceData->price;
                                    }
                                    $skuInfo = TemuGoodsSku::where(["mall_id"=>strval($mallId),"product_sku_id"=>strval($_valArr[$date]["prodSkuId"])])
                                        ->whereRaw("`goods_id`!=''")->first();
                                    dump("==============create=============");
                                    dump([
                                        "goods_id"=>!empty($skuInfo)?$skuInfo->goods_id:"",
                                        "goods_sku_id"=>!empty($skuInfo)?$skuInfo->goods_sku_id:"",
                                        "product_sku_id"=>$_valArr[$date]["prodSkuId"],
                                        "price"=>$price,
                                        "today_sale_volume"=>$_valArr[$date]["salesNumber"],
                                        "cost_price"=>$cost_price,
                                        "mall_id"=>$mallId,
                                        "date"=>$_valArr[$date]["date"]
                                    ]);
                                    TemuGoodsSales::create([
                                        "goods_id"=>!empty($skuInfo)?$skuInfo->goods_id:"",
                                        "goods_sku_id"=>!empty($skuInfo)?$skuInfo->goods_sku_id:"",
                                        "product_sku_id"=>$_valArr[$date]["prodSkuId"],
                                        "price"=>$price,
                                        "today_sale_volume"=>$_valArr[$date]["salesNumber"],
                                        "cost_price"=>$cost_price,
                                        "mall_id"=>$mallId,
                                        "created_at"=>$_valArr[$date]["date"]
                                    ]);
                                }else{
                                    if(empty($historySaleData->price)){
                                        $beforeDateCostPriceData = TemuGoodsSales::where(["mall_id"=>strval($mallId),"product_sku_id"=>strval($_valArr[$date]["prodSkuId"])])
                                            ->whereRaw("`created_at`<'".$_valArr[$date]["date"]."'")
                                            ->whereRaw("`price`!=''")
                                            ->orderBy("created_at","desc")->first();
                                        if(empty($beforeDateCostPriceData)){
                                            $afterDateCostPriceData = TemuGoodsSales::where(["mall_id"=>strval($mallId),"product_sku_id"=>strval($_valArr[$date]["prodSkuId"])])
                                                ->whereRaw("`created_at`>'".$_valArr[$date]["date"]."'")
                                                ->whereRaw("`price`!=''")
                                                ->orderBy("created_at","asc")->first();
                                            if(!empty($afterDateCostPriceData)){
                                                $historySaleData->price = $afterDateCostPriceData->price;
                                            }
                                        }else{
                                            $historySaleData->price = $beforeDateCostPriceData->price;
                                        }
                                    }
                                    dump("==============update=============");
                                    dump([
                                        "goods_id"=>!empty($skuInfo)?$skuInfo->goods_id:"",
                                        "goods_sku_id"=>!empty($skuInfo)?$skuInfo->goods_sku_id:"",
                                        "product_sku_id"=>$_valArr[$date]["prodSkuId"],
                                        "price"=>$historySaleData->price,
                                        "today_sale_volume"=>$_valArr[$date]["salesNumber"],
                                        "cost_price"=>$historySaleData->cost_price,
                                        "mall_id"=>$mallId,
                                        "date"=>$_valArr[$date]["date"]
                                    ]);
                                    $historySaleData->update([
                                        "today_sale_volume"=>$_valArr[$date]["salesNumber"],
                                        "price"=>$historySaleData->price
                                    ]);
                                }
                            }else{
                                dump("==============delete=============");
                                dump([
                                    "goods_id"=>!empty($skuInfo)?$skuInfo->goods_id:"",
                                    "goods_sku_id"=>!empty($skuInfo)?$skuInfo->goods_sku_id:"",
                                    "product_sku_id"=>$prodSkuId,
//                                        "price"=>$historySaleData->price,
                                    "today_sale_volume"=>0,
//                                        "cost_price"=>$historySaleData->cost_price,
                                    "mall_id"=>$mallId,
                                    "date"=>$date
                                ]);
                                //如果不是今天的则删除
                                if($date != date("Y-m-d",time())){
                                    TemuGoodsSales::where(["mall_id"=>strval($mallId),"product_sku_id"=>strval($prodSkuId)])
                                        ->whereRaw("DATE_FORMAT(`created_at`,'%Y-%m-%d')=?", [$date])->delete();
                                }

                            }

                            $start+=86400;

                        }
                    }
                }else{
                    dump($res);
                }

            }
        }
    }

    /**
     * 调用平台接口查看sku历史销量
     * @param $mallId
     * @param $cookie
     * @param $startDate
     * @param $endDate
     * @param $productSkuIds
     * @return mixed
     */
    public function getHistorySalesNum($mallId,$cookie,$startDate,$endDate,$productSkuIds)
    {

        $data = json_encode([
            "startDate"=>$startDate,
            "endDate"=>$endDate,
            "productSkuIds"=>$productSkuIds
        ]);


        $headers = [
            // ":method: POST",
//            ":authority: kuajing.pinduoduo.com",
            // "host:kuajing.pinduoduo.com",
            "Accept: */*",
            //"Accept-Encoding: gzip, deflate, br",
            "Accept-Language: zh-CN,zh;q=0.9",
            "Anti-Content: 0aqWfxUkMwVeXKRPmWvOb-1k5S0sq4jO1dZ0suHyL3_ZFiuQvqS_Z-Lh4uqJ_x-MXui-W--tun_rh-XoyOk-SQQelKOejU2Qkn1sK_LEuDa45UJ1KxZQCAF1ci-0GhbW3-LqZFluEuqY_3SlXJAqH_LSlg4AYQdrStB_ixndBCtB_3YnYGA2-gndBptB_3qp86N4jG3SmBJVhgaiaCBhwvXRrUCXtLHenUfpefxmaE0nh3SUluIBGkqQ87NzNBaZCOfbrOtZOJuUxUpzd2ObGrQ8spfOfHrshQN_Vk1qEixFoJxFoQ5GeaY_FKt5IJyimnGgrXqNiGj4UGYvFHYVvVoldiEQYuxXyRpl0gSzduJX_NxQwvOan02l3QneTGofEeQl_nU4ynG9jXdvJnGPqX0vycATYnGPJnG_anG9JXUab9BZa58DdtYgqOYtaOkty1iNLOquJP_ivJj02TyC2ldXpntNKOYH2vNNy1_ux_99VHRaTKw4KeBNeDMNWDD4Kbs2dM1bD-fJ1F-RImB3SHBkVKDJZbZoUbL4UMk8DFtrISf8CMD4ObkwCM3ICIBZF2YTz9Zl0gcO_VdIIz2PLG9n_S4n0u1K54VPOuV1dPSf0r6K5rdY0BN84vYX_0zQtFKXdvKoDt0DfvdiQ467JsI_v-lIS3xkVFK30GYsFVb38erzW7B3sVFzWdL2UKsZw7SBYIM8iIh3nYB8t7yVeE39VX9BkCesKpZWOvp",
            "Cache-Control: max-age=0",
            "Content-Type: application/json",
            "Content-Length: ".strlen($data),
            // "Cookie: ".$cookie,
            "Cookie: ".$cookie,
            "Mallid: " . $mallId,
            "Origin: https://kuajing.pinduoduo.com",
            "Referer: https://kuajing.pinduoduo.com/main/sale-manage/main",
            'Sec-Ch-Ua: \"Chromium\";v=\"112\", \"Google Chrome\";v=\"112\", \"Not:A-Brand\";v=\"99\"',
            "Sec-Ch-Ua-Mobile: ?0",
            'Sec-Ch-Ua-Platform: \"macOS\"',
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
        ];
        $res = Common::curlPostWithCustomHeader($this->historySalesUrl, $headers, $data);

        return json_decode($res, true);
    }

    public function statisticsMallsFreightCost($cookie)
    {
        $loginUserInfo = $this->getLoginUserInfo($cookie);
        dump("登录用户信息");
        dump(json_encode($loginUserInfo));
        if (!empty($loginUserInfo["result"])) {
            $mallIds = $loginUserInfo["result"]["companyList"][0];
            if(!empty($mallIds["malInfoList"])){
                foreach ($mallIds["malInfoList"] as $_mallIdInfo){
                    try {
                        $this->startFreightCostRequest($cookie, $_mallIdInfo);
                    } catch (\Exception $e) {
                        dump($e->getMessage());
                        continue;
                    }
                }
            }
        }
    }
}
