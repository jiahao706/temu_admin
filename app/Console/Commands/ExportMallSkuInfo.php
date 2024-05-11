<?php


namespace App\Console;

use App\Export\SkuInfoExport;
use App\Model\TemuGoodsSku;
use App\Model\TemuMalls;
use App\Service\SpiderService;
use App\Service\TemuDataStatisticsService;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Input\InputOption;

class ExportMallSkuInfo extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:malls:sku:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出店铺sku信息';


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
        ini_set("memory_limit",0);
        $malls = TemuMalls::Where(["type"=>0])->groupByRaw("mall_id")->get();
        $mallIds = array_column($malls->toArray(),"mall_id");
        ini_set("default_socket_timeout",120);
        $data = [];
        foreach ($mallIds as $mallId){
            $mallSkuInfo = TemuGoodsSku::join("temu_goods","temu_goods_sku.goods_id","=","temu_goods.goods_id")
                ->whereRaw("temu_goods_sku.mall_id='$mallId'")
                ->groupByRaw("product_sku_id")->get();
            if(!empty($mallSkuInfo)){
                foreach ($mallSkuInfo as $_skuInfo){
                    $_tmp = [];
                    //skc 图片
                    $imgPath = public_path("images/skus/").pathinfo($_skuInfo->img)["basename"];
                    /*$options = [
                        'http' => [
                            'timeout' => 60, // 设置超时时间为5秒
                        ]
                    ];
                    $context = stream_context_create($options);*/
                    if(!file_exists($imgPath)){
                        file_put_contents($imgPath,file_get_contents($_skuInfo->img));
                    }
                    $_tmp["img"] = $imgPath;

                    //skc 标题
                    $_tmp["title"] = $_skuInfo->title;
                    //skc 类目
                    $_tmp["category"] = $_skuInfo->category;
                    //skc
                    $_tmp["skc"] = $_skuInfo->skc;
                    //spu
                    $_tmp["spu"] = $_skuInfo->spu;
                    //skc 货号
                    $_tmp["sku_article_number"] = $_skuInfo->sku_article_number;
                    //sku名称
                    $_tmp["sku_name"] = $_skuInfo->sku_name;
                    //sku 货号
                    $_tmp["sku_ext_code"] = $_skuInfo->sku_ext_code;
                    //销售价格
                    $_tmp["supplier_price"] = is_numeric($_skuInfo->supplier_price)?$_skuInfo->supplier_price/100:"";
                    //成本价格
                    $_tmp["cost_price"] = $_skuInfo->cost_price;

                    //仓内可用库存
                    $_tmp["ware_house_inventory_num"] = $_skuInfo->ware_house_inventory_num;
                    //仓内暂时不可用库存
                    $_tmp["unavailable_warehouse_inventory_num"] = $_skuInfo->unavailable_warehouse_inventory_num;
                    //已发货库存
                    $_tmp["wait_receive_num"] = $_skuInfo->wait_receive_num;
                    //已下单待发货库存
                    $_tmp["wait_delivery_inventory_num"] = $_skuInfo->wait_delivery_inventory_num;
                    //待审核备货库存
                    $_tmp["wait_approve_inventory_num"] = $_skuInfo->wait_approve_inventory_num;
                    //近30日销量
                    $_tmp["last_thirty_days_sale_volume"] = $_skuInfo->last_thirty_days_sale_volume;
                    //sku 不合理库存
                    $_tmp["unreasonable_inventory"] = $_skuInfo->unreasonable_inventory;
                    //不合理库存总成本
                    $_tmp["unreasonable_inventory_total_cost_price"] = $_skuInfo->unreasonable_inventory_total_cost_price;

                    $data[$_skuInfo->mall_id][] = $_tmp;
                }
            }
            dump($mallId);
        }

        if(!empty($data)){
            foreach ($data as $mallId=>$skus){
                $mallInfo = TemuMalls::where(["mall_id"=>$mallId])->first();
                $filename = "skuinfo/".$mallInfo->mall_name.".xlsx";
                dump($filename);
                Excel::store(new SkuInfoExport($skus),$filename,"export");
            }
            dump("done");
        }
    }

}
