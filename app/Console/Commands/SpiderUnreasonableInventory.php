<?php


namespace App\Console;

use App\Model\TemuGoodsSku;
use App\Service\SpiderService;
use App\Service\TemuDataStatisticsService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class SpiderUnreasonableInventory extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:malls:unreasonable:inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算店铺不合理库存';


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
        $cookie = "api_uid=CmmE5mXpf0FF3ABb32AcAg==; _nano_fp=XpmolpCoXpPJX5dYlT_1JfnV~bUKO7Fjwsci6qjn; _bee=BKU1jjdju0iJfcawIAmxyAi8h40zcami; _f77=30eb74c7-5879-45a8-b7cc-50ce18cbb7ac; _a42=6e5d7104-89d4-4a06-be2c-5c69d5ba8dfc; rckk=BKU1jjdju0iJfcawIAmxyAi8h40zcami; ru1k=30eb74c7-5879-45a8-b7cc-50ce18cbb7ac; ru2k=6e5d7104-89d4-4a06-be2c-5c69d5ba8dfc; SUB_PASS_ID=eyJ0IjoiL3EvRmpBUWd6Z1ZUcHAwVHdqZ2M0cWZVWlF6RE43VkdSUWhoTy9Ic3lLbUNMaFhiZE1McCtyU05HYjNSN08xayIsInYiOjEsInMiOjEwMDAwLCJ1Ijo2NTE1MjcwMTg1MDA2fQ==";
        (new SpiderService())->statisticsRemainingInventoryExistsSkus($cookie);
    }

}
