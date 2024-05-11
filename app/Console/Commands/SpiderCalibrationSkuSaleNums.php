<?php


namespace App\Console;

use App\Compoents\Common;
use App\Model\TemuGoodsSales;
use App\Model\TemuGoodsSku;
use App\Model\TemuMalls;
use App\Service\SpiderService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class SpiderCalibrationSkuSaleNums extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:calibration:sku:nums';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '校准temu店铺之前的销量';

    protected $historySalesUrl = "https://kuajing.pinduoduo.com/oms/bg/venom/api/supplier/sales/management/querySkuSalesNumber";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(){
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
//        $malls = TemuMalls::all();
        ini_set("memory_limit",0);
        $mallId = "5165679290184";

        $malls = TemuMalls::where(["mall_id"=>strval($mallId)])->get();

        if(!empty($malls)){
            foreach ($malls as $_mall){
                //$cookie = file_get_contents(storage_path("temu_cookies/$mallId.txt"));
                //$cookie = "api_uid=CmkhpWUZaV5JaABTJZ1aAg==; _nano_fp=XpEbn5gjn0gbXpTJn9_jlKf1GwO1ZBxzS2oJYUrg; _bee=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; _f77=afe8fcf1-995f-4ff6-a115-88ce47f5db67; _a42=68acb5e6-d359-4060-bf2d-667234db92aa; rckk=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; ru1k=afe8fcf1-995f-4ff6-a115-88ce47f5db67; ru2k=68acb5e6-d359-4060-bf2d-667234db92aa; SUB_PASS_ID=eyJ0IjoidC9SL0ZibkpnNzRzV2xpWFQwclJDajNQSkpXemthQWQ4aUhROEtOUjJZQXFuNWs3QUwvUFdpaGhvZVNsRzUxMyIsInYiOjEsInMiOjEwMDAwLCJ1Ijo0MjcwNDExMTU1MzcxfQ==";
                 $cookie = "api_uid=CmixCWXUZoOpVgBTqiJFAg==; _nano_fp=Xpmol0Tal0EbXpTal9_777MBPnA9yGwlIV6sP~xi; _bee=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; _f77=70bd6232-8e14-4d29-910f-7cfe6ea14b6a; _a42=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; rckk=PD7IWDenbdaBGn0bPwwg1zQwjVZ90v5i; ru1k=70bd6232-8e14-4d29-910f-7cfe6ea14b6a; ru2k=c496c18a-e8ca-43d2-bfc0-6d2b38ea8763; SUB_PASS_ID=eyJ0IjoiZmhIaS90Q0ZiU3NiMWpDQXR5WjNYdnpRajYxRmJNZU11cmx6Q3BuQ3BIT05sZUlMQlBSRGNqTjNXbEZodzZhVCIsInYiOjEsInMiOjEwMDAwLCJ1Ijo1MTY1Njc5MjkwMTg0fQ==";

                $cookie = str_replace("\n","",$cookie);
                $this->carlibrationSkuSaleNums($_mall->mall_id,$cookie);
            }
        }
    }

    public function carlibrationSkuSaleNums($mallId,$cookie)
    {
        $startDate = "2024-02-01";
        $startTime = strtotime($startDate);
        $endDate = date("Y-m-d",time());
        $endTime = strtotime($endDate);


        $total = TemuGoodsSku::where(["mall_id"=>strval($mallId)])
//            ->whereRaw("id>=10410")
            ->count();
        $limit = 20;
        if($total>0){
            $allPage = ceil($total/$limit);
            for($i=0;$i<$allPage;$i++){
                $skuIds = TemuGoodsSku::where(["mall_id"=>strval($mallId)])
//                    ->whereRaw("id>10410")
                    ->orderBy("id","asc")
                    ->take($limit)->skip($i*$limit)->pluck("product_sku_id");
                $skuIdsArr = $skuIds->toArray();
                if(!empty($skuIdsArr)){
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

//                        foreach ($listRes as $_dateSaleInfo){
//                            $historySaleData = TemuGoodsSales::where(["mall_id"=>$mallId,"product_sku_id"=>$_dateSaleInfo["prodSkuId"]])
//                                ->whereRaw("DATE_FORMAT(`created_at`,'%Y-%m-%d')=?", [$_dateSaleInfo["date"]])->first();
//
//
//                            //如果表中没有这条记录，则进行新增
//                            if(empty($historySaleData)){
//                                $cost_price = 0;
//                                $price = 0;
//                                $beforeDateCostPriceData = TemuGoodsSales::where(["mall_id"=>$mallId,"product_sku_id"=>$_dateSaleInfo["prodSkuId"]])
//                                    ->whereRaw("`created_at`<".$_dateSaleInfo["date"])
//                                    ->orderBy("created_at","desc")->first();
//                                if(empty($beforeDateCostPriceData)){
//                                    $afterDateCostPriceData = TemuGoodsSales::where(["mall_id"=>$mallId,"product_sku_id"=>$_dateSaleInfo["prodSkuId"]])
//                                        ->whereRaw("`created_at`>".$_dateSaleInfo["date"])
//                                        ->orderBy("created_at","asc")->first();
//                                    if(!empty($afterDateCostPriceData)){
//                                        $cost_price = $afterDateCostPriceData->cost_price;
//                                        $price = $afterDateCostPriceData->price;
//                                    }
//                                }else{
//                                    $cost_price = $beforeDateCostPriceData->cost_price;
//                                    $price = $beforeDateCostPriceData->price;
//                                }
//                                $skuInfo = TemuGoodsSku::where(["mall_id"=>$mallId,"product_sku_id"=>$_dateSaleInfo["prodSkuId"]])->first();
//                                dump("==============create=============");
//                                dump([
//                                    "goods_id"=>!empty($skuInfo)?$skuInfo->goods_id:"",
//                                    "goods_sku_id"=>!empty($skuInfo)?$skuInfo->goods_sku_id:"",
//                                    "product_sku_id"=>$_dateSaleInfo["prodSkuId"],
//                                    "price"=>$price,
//                                    "today_sale_volume"=>$_dateSaleInfo["salesNumber"],
//                                    "cost_price"=>$cost_price,
//                                    "mall_id"=>$mallId,
//                                    "date"=>$_dateSaleInfo["date"]
//                                ]);
//                                TemuGoodsSales::create([
//                                   "goods_id"=>!empty($skuInfo)?$skuInfo->goods_id:"",
//                                   "goods_sku_id"=>!empty($skuInfo)?$skuInfo->goods_sku_id:"",
//                                   "product_sku_id"=>$_dateSaleInfo["prodSkuId"],
//                                   "price"=>$price,
//                                   "today_sale_volume"=>$_dateSaleInfo["salesNumber"],
//                                   "cost_price"=>$cost_price,
//                                   "mall_id"=>$mallId,
//                                    "created_at"=>$_dateSaleInfo["date"]
//                                ]);
//                            }else{
//                                dump("==============update=============");
//                                dump([
//                                    "goods_id"=>!empty($skuInfo)?$skuInfo->goods_id:"",
//                                    "goods_sku_id"=>!empty($skuInfo)?$skuInfo->goods_sku_id:"",
//                                    "product_sku_id"=>$_dateSaleInfo["prodSkuId"],
//                                    "price"=>$historySaleData->price,
//                                    "today_sale_volume"=>$_dateSaleInfo["salesNumber"],
//                                    "cost_price"=>$historySaleData->cost_price,
//                                    "mall_id"=>$mallId,
//                                    "date"=>$_dateSaleInfo["date"]
//                                ]);
//                                $historySaleData->update(["today_sale_volume"=>$_dateSaleInfo["salesNumber"]]);
//                            }
//
//                            //如果是今天的，则同时修改今日销售记录表
//                            if($_dateSaleInfo["date"] == date("Y-m-d")){
//                               /* TemuGoodsSku::where(["mall_id"=>$mallId,"product_sku_id"=>$_dateSaleInfo["prodSkuId"]])
//                                    ->update(["today_sale_volume"=>$_dateSaleInfo["salesNumber"]])->limit(1);*/
//                            }
//                        }
                    }else{
                        dump($res);
                    }
                }

            }
        }
    }


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

}
