<?php


namespace App\Console;

use App\Model\TemuGoodsSku;
use App\Service\SpiderService;
use App\Service\TemuDataStatisticsService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class SpiderFreightCost extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:malls:FreightCost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算店铺当月运费';


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
        $cookie = "api_uid=CmgE3mXj63MWTwBV4WGtAg==; _nano_fp=XpmolpTqnpmjn0TxnT_cmlkQ3zfYP85Nsf6f7JPW; _bee=zqD0VDsFFgZakT58bLUAiISApm8tlamc; _f77=6b8ae6c7-7125-4435-9a5d-ac65b87f78d3; _a42=dde8ee12-a370-4927-8cf3-77a4102599c2; rckk=zqD0VDsFFgZakT58bLUAiISApm8tlamc; ru1k=6b8ae6c7-7125-4435-9a5d-ac65b87f78d3; ru2k=dde8ee12-a370-4927-8cf3-77a4102599c2; SUB_PASS_ID=eyJ0IjoiamUydkhhMGVnelFiS1hKSmpLT3hCNjNKTTM2ZW51RVFmT0huUmlvTEQrSXFIWUd3d3U1YWxPNVhERkFFTXZiWiIsInYiOjEsInMiOjEwMDAwLCJ1IjoxMDI2OTE5NjIwODk4Nn0=";
        (new SpiderService())->statisticsMallsFreightCost($cookie);
    }

}
