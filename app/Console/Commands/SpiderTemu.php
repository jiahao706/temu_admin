<?php

namespace App\Console\Commands;

use App\Compoents\Common;
use App\Model\TemuGoodsSales;
use App\Model\TemuGoods;
use App\Model\TemuGoodsSku;
use App\Model\TemuMalls;
use App\Model\TemuMallsDeliveryRestrict;
use App\Model\TemuMallsDeliveryRestrictOrder;
use App\Service\SpiderService;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputOption;

class SpiderTemu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:temu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '爬虫temu管理后台';

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

        $spiderService = new SpiderService();
        $spiderService->initConsoleSpider($driverPath, $driverPort,$browserPath);
        $spiderService->startSpider();

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

}
