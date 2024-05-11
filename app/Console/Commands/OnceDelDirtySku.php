<?php


namespace App\Console;

use App\Model\TemuGoodsSku;
use App\Service\TemuDataStatisticsService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class OnceDelDirtySku extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:del:dirty:sku';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除重复的sku';


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
        $dirtySkus = TemuGoodsSku::selectRaw("product_sku_id")
            //->where("mall_id","5894293131715")
            ->groupByRaw("product_sku_id")
            ->havingRaw("count(product_sku_id)>1")->get();


        if (!empty($dirtySkus)){
            $dirtySkusArr = $dirtySkus->toArray();
            foreach ($dirtySkusArr as $skuId){
                $t = TemuGoodsSku::where(["product_sku_id"=>$skuId])->orderBy("id","desc")->first();
                if(!empty($t)){
                    $tt = $t->toArray();
                    TemuGoodsSku::where(["product_sku_id"=>$skuId])->whereRaw("id<{$tt['id']}")->delete();
                }
            }
        }

        /*$limit = 20;
        $total = TemuGoodsSku::whereRaw("unreasonable_inventory>0")->count();
        if($total>0){
            $allPage = ceil($total/$limit);
            for($i=0;$i<=$allPage;$i++){
                $skus = TemuGoodsSku::whereRaw("unreasonable_inventory>0")
                    ->take($limit)->skip($i*$limit)->get();
                foreach ($skus as $_sku){
                    $unreasonable_inventory_total_cost_price = TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($_sku->unreasonable_inventory,$_sku->cost_price);
                    $_sku->update(["unreasonable_inventory_total_cost_price"=>$unreasonable_inventory_total_cost_price]);
                }
            }

        }*/
    }

}
