<?php

namespace App\Console\Commands;

use App\Compoents\Common;
use App\Model\TemuMalls;
use App\Model\TemuMallsDeliveryRestrictOrder;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Chrome\ChromeDriverService;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputOption;

class SpiderTemuDeliveryRestrict extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:temu:deliver:restrict';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '爬虫temu管理后台资金限制信息';


    private $driverHost = "http://localhost";

    /**
     * @var WebDriver
     */
    private $driver;

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
    private $orderReciverUserInfoApiUrl ="https://kuajing.pinduoduo.com/bgSongbird-api/supplier/deliverGoods/management/queryCourier";

    /**
     * 订单包裹详情接口
     * @var string
     */
    private $orderPackageDetailApiUrl = "https://kuajing.pinduoduo.com/bgSongbird-api/supplier/deliverGoods/management/queryDeliveryOrderPackageDetailInfo";

    /**
     * 已发货订单列表
     * @var string
     */
    private $orderListUrl = "https://kuajing.pinduoduo.com/main/order-manager/shipping-list";


    private $chromeService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        //注册启动参数
        $this->addOption("driver-path", null, InputOption::VALUE_REQUIRED, "必填项,浏览器驱动绝对路径");
        $this->addOption("driver-port", null, InputOption::VALUE_REQUIRED, "必填项,浏览器驱动启动时要使用的端口");
        $this->addOption("browser-path", null, InputOption::VALUE_REQUIRED, "必填项,浏览器绝对路径");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $driverPath = $this->option("driver-path");
        $driverPort = $this->option("driver-port");
        $browserPath = $this->option("browser-path");
        $this->validateOptions();
        $this->reloadChromeDriver($driverPath, $driverPort);
        //$this->startChrome($driverPort, $browserPath);
        $this->startSpider($driverPort,$browserPath);

        return;
    }

    /** 验证启动参数
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 下午2:52
     */
    public function validateOptions()
    {
        if (empty($this->option("driver-path"))) {
            $this->error("--driver-path 参数不能为空");
            Artisan::call($this->signature . " -h", [], $this->output);
            exit();
        } elseif (empty($this->option("driver-port"))) {
            $this->error("--driver-port 参数不能为空");
            Artisan::call($this->signature . " -h", [], $this->output);
            exit();
        } elseif (empty($this->option("driver-port"))) {
            $this->error("--browser-path 参数不能为空");
            Artisan::call($this->signature . " -h", [], $this->output);
            exit();
        }
    }

    /**
     * User: jiahao.dong
     * Date: 2023/5/21
     * Time: 下午11:01
     * @return ChromeDriverService
     */
    public function getChromeService()
    {
        return $this->chromeService;
    }

    /**
     * 获取父进程名称
     * User: jiahao.dong
     * Date: 2023/5/22
     * Time: 下午8:22
     * @return string
     */
    public function getMasterProcessName()
    {
        return $this->signature."[master]";
    }

    /**
     * 获取子进程名称
     * User: jiahao.dong
     * Date: 2023/5/22
     * Time: 下午8:22
     * @return string
     */
    public function getChildProcessName()
    {
        return $this->signature."[worker]";
    }

    /**
     * 获取服务器cpu核心数
     * User: jiahao.dong
     * Date: 2023/5/22
     * Time: 下午11:15
     * @return int
     */
    public function getServerCpuNum()
    {
        $cpuCores = shell_exec('nproc');
        $numProcesses = intval(trim($cpuCores));
        $numProcesses = 1;
        return $numProcesses;
    }

    /** 重启chromedriver
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 上午2:56
     */
    public function reloadChromeDriver($driverPath, $driverPort)
    {
        exec(base_path() . "/restartdriver.sh ${driverPath} $driverPort ");
        sleep(2);
        /*$this->chromeService = new ChromeDriverService($driverPath,$driverPort);
        $this->chromeService->start();*/
    }

    /**启动chrome
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 下午2:52
     * @param $driverPort
     * @param $browserPath
     */
    public function startChrome($driverPort, $browserPath,$username)
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->setBinary($browserPath);
        $chromeOptions->addArguments([
            '--user-agent=Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36',
            '--headless',
            '--user-data-dir=selenium/'.$username,
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
        //$this->driver = ChromeDriver::start($desiredCapabilities,$this->getChromeService());
        $this->driver = RemoteWebDriver::create($this->driverHost . ":" . $driverPort, $desiredCapabilities, 5000);
        //设置查找元素等待时长
        $this->driver->manage()->timeouts()->implicitlyWait(10);
        // 设置页面加载超时时间为 10 秒
        $this->driver->manage()->timeouts()->pageLoadTimeout(60);
    }

    /**
     * 开始采集
     * User: jiahao.dong
     * Date: 2023/4/24
     * Time: 上午5:06
     */
    public function startSpider($driverPort,$browserPath)
    {
//        $temuAccounts = explode("|",env("TEMU_ACCOUNTS"));
        $childPids = [];
        $serverCpuNum = $this->getServerCpuNum();
        $temuMalls = TemuMalls::all();
        if (!empty($temuMalls)) {
            foreach ($temuMalls as $k=>$accounts) {
                if (!empty($childPids) && (count($childPids) % $serverCpuNum == 0) ){
                    $this->waitChildPidsExit($childPids);
                    $childPids = [];
                }
                $pid = pcntl_fork();
                if($pid == -1){
                    dump("fork 子进程失败");
                    continue;
                }elseif ($pid > 0){
                    cli_set_process_title($this->getMasterProcessName());
                    $childPids[$pid] = $accounts->username;
                }else{
                    cli_set_process_title($this->getChildProcessName());
                    $this->startChrome($driverPort,$browserPath,$accounts->username);
                    $manage = $this->driver->manage();
                    $username = $accounts->username;
                    $password = $accounts->password;
                    $this->driver->get($this->loginUrl);
                    sleep(3);
                    //登录
                    $this->driver->findElement(WebDriverBy::id('usernameId'))->sendKeys($username);
                    $this->driver->findElement(WebDriverBy::id('passwordId'))->sendKeys($password);
                    $this->driver->findElement(WebDriverBy::xpath("//span[text()='登录']/parent::button"))->click();

                    sleep(3);

                    try {
                        $log = $manage->getLog("performance");
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
                        $this->info("账号:{$username}登录接口response");
                        $this->info($loginResponseInfo);
                        if (!empty($loginResponseInfo)) {
                            $r = json_decode($loginResponseInfo, true);
                            if (!empty($r["errorCode"]) && $r["errorCode"] != 1000000) {
                                $accounts->update([
                                    "spider_status_msg" => !empty($r) ? $r["errorMsg"] : "账号采集异常",
                                    "spider_status" => TemuMalls::SPIDER_ERROR,
                                ]);
                                continue;
                            }
                        } else {
                            $accounts->update([
                                "spider_status_msg" => "账号采集状态正常",
                                "spider_status" => TemuMalls::SPIDER_SUCCESS,
                            ]);
                        }
                        $cookieStr = $this->getCookies();
                        $this->info("登录cookie");
                        $this->info($cookieStr);
                        $loginUserInfo = $this->getLoginUserInfo($cookieStr);
                        $this->info("登录用户信息");
                        $this->info(json_encode($loginUserInfo));
                        $this->startDeliveryRestrictRequest($cookieStr, $loginUserInfo, $accounts);
                    } catch (\Exception $e) {
                        $this->error("异常信息");
                        $this->error($e->getMessage());
                    }
                    $this->driver->quit();
                    exit();
                }
            }
        }
        if (!empty($childPids)){
            $this->waitChildPidsExit($childPids);
        }
    }

    public function waitChildPidsExit($childPids)
    {
        if (!empty($childPids)){
            foreach ($childPids as $_childPid=>$_accountUname){
                pcntl_waitpid($_childPid,$status);
                dump("进程号:".$_childPid."退出,账号为:".$_accountUname);
            }
        }
    }

    public function getCookies()
    {
        $cookies = $this->driver->manage()->getCookies();
        $cookieStr = "";
        foreach ($cookies as $cookieV) {
            $cookieStr .= $cookieV["name"] . "=" . $cookieV["value"] . ";";
        }
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
     * 快递费资金限制详情
     * User: jiahao.dong
     * Date: 2023/5/4
     * Time: 下午5:08
     * @param $cookie
     * @param $loginUserInfo
     * @param $accounts
     */
    public function startDeliveryRestrictRequest($cookie, $loginUserInfo, $accounts)
    {
        if (!empty($loginUserInfo["result"])) {
            $mallId = $loginUserInfo["result"]["companyList"][0]["malInfoList"][0]["mallId"];
            //发货单列表
            $this->startDeliverOrderRequest($cookie,$mallId);
            dump("账号:{$accounts->username},快递费资金限制详情采集完成");
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

  var dom3 = document.querySelectorAll('.MDL_mask_5-52-0')
      var dom4 = document.querySelectorAll('.MDL_alert_5-52-0')
      var dom5 = document.querySelectorAll('.MDL_container_5-67-0')
  for (let i = 0; i < dom3.length; i++) {
    dom3[i].setAttribute('style', 'display:none');
  }
  for (let i = 0; i < dom4.length; i++) {
    dom4[i].setAttribute('style', 'display:none');
  }
  for (let i = 0; i < dom5.length; i++) {
      if(dom5[i].getAttribute('data-testid') == 'beast-core-modal-container'){
            dom5[i].setAttribute('style', 'display:none');
      }
  }

JS;
            $this->driver->get($this->orderListUrl);
            sleep(5);
            $this->driver->executeScript($clearModalJs);
            sleep(1);

        try {
            $alertElem = $this->driver->findElements(WebDriverBy::xpath("//div[@data-testid='beast-core-modal-mask']"));
            foreach ($alertElem as $elem)
            {
                $this->driver->executeScript("arguments[0].setAttribute('style','display:none;');", [$elem]);
            }

            $alertElem = $this->driver->findElements(WebDriverBy::xpath("//div[@data-testid='beast-core-modal']"));
            foreach ($alertElem as $elem)
            {
                $this->driver->executeScript("arguments[0].setAttribute('style','display:none;');", [$elem]);
            }
            $alertElem = $this->driver->findElements(WebDriverBy::xpath("//div[@data-testid='beast-core-modal-container']"));
            foreach ($alertElem as $elem)
            {
                $this->driver->executeScript("arguments[0].setAttribute('style','display:none;');", [$elem]);
            }
        } catch (\Exception $e) {
            dump($e->getMessage());
        }

        $this->driver->findElement(WebDriverBy::xpath("//div[@data-testid='beast-core-tab-itemLabel-wrapper']/div[text()='全部']"))->click();
            sleep(10);

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
//                               break;
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
                                   "mall_id" => $mallId,
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
                                       "package_total_deliver_skc_num" =>$_orderV["deliverSkcNum"]
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
                                       "package_total_deliver_skc_num" =>$_orderV["deliverSkcNum"]
                                   ]);
                               }
                           }


                           //抓取订单号包裹件数
                           /*try {
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

                                       $this->driver->findElement(WebDriverBy::xpath("//div[@class='index-module__title___1QfPM']/*[name()='svg']"))->click();
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
                                                       "mall_id" => $mallId,
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
                           }*/


                       }else{
                           dump($orderResponseInfo);
                       }
                   }
                   try {
                       $nextpageElem = $this->driver->findElement(WebDriverBy::xpath("//li[@data-testid='beast-core-pagination-next']"));
                   } catch (\Exception $e) {
                       dump("mallId:".$mallId);
                       dump($e->getMessage());
                       break;
                   }
                   $nextpageElemClass = $nextpageElem->getAttribute("class");
                    if(preg_match("/disabled/",$nextpageElemClass)){
                        break;
                    }else{
                        $this->driver->executeScript($clearModalJs);
                        $this->driver->executeScript("arguments[0].click();", [$nextpageElem]);
                        sleep(5);
                    }
               }

            //}
    }

}
