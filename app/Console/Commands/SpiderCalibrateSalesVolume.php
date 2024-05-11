<?php


namespace App\Console;

use App\Service\SpiderService;
use Illuminate\Console\Command;

class SpiderCalibrateSalesVolume extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:calibrate:sales:volume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '校准店铺30天销量';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        //注册启动参数
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cookie = "api_uid=CmixCWXUZoOpVgBTqiJFAg==; _nano_fp=Xpmol0Tal0EbXpTal9_777MBPnA9yGwlIV6sP~xi; _bee=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; _f77=70bd6232-8e14-4d29-910f-7cfe6ea14b6a; _a42=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; rckk=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; ru1k=70bd6232-8e14-4d29-910f-7cfe6ea14b6a; ru2k=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; SUB_PASS_ID=eyJ0IjoiUkFWOEFPa29NUWttY2h0SVpwQm0yd0ExZURNaXY3a21CU1hhZTBvY2VRdUVYSGI0U3lsbzFJOW1ieUF6ajR1MyIsInYiOjEsInMiOjEwMDAwLCJ1Ijo5NDA3MTg5NjkxMTQ1fQ==";
        $startDate = "2024-04-01";
        $endDate = date("Y-m-d",time());
        (new SpiderService())->spiderCalibrateSalesVolume($cookie,$startDate,$endDate);
    }

}
