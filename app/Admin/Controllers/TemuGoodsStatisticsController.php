<?php
/**
 * User: jiahao.dong
 * Date: 2023/4/26
 * Time: 下午5:31
 */

namespace App\Admin\Controllers;


use App\Admin\Actions\InvitationCode\AddCode;
use App\Compoents\CustomFilterBetween;
use App\Compoents\CustomFilterEqual;
use App\Model\TemuGoodsSales;
use App\Model\TemuGoodsSku;
use App\Model\TemuMalls;
use App\Service\AdminLayoutContentService;
use App\Service\TemuDataStatisticsService;
use Encore\Admin\Admin;
use Encore\Admin\Auth\Database\OperationLog;
use Encore\Admin\Form;
use Encore\Admin\Form\Builder;
use Encore\Admin\Form\Field;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Arr;

class TemuGoodsStatisticsController extends AdminController
{

    /**
     * 畅销商品
     * User: jiahao.dong
     * Date: 2023/5/6
     * Time: 下午9:10
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function hotGoods(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺畅销商品")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        $offsetGet = request()->offsetGet('temu_goods_sales');
        if(empty($offsetGet["created_at"])){
            $offsetGet["created_at"] =[
                'start'=>date("Y-m-d 00:00:00"),
                'end'=>date("Y-m-d 23:59:59"),
            ];
        }
        if(empty($offsetGet["mall_id"])){
            $offsetGet["mall_id"] = array_column($allMall->toArray(),"mall_id")[0];
        }
        request()->offsetSet("temu_goods_sales",$offsetGet);
        $grid = new Grid(new TemuGoodsSales());
        $grid->model()->with(["goods","goodsAllSku"])->selectRaw("sum(temu_goods_sales.`today_sale_volume`) as sum_today_sale_volume,sum((temu_goods_sales.`today_sale_volume`+0)*((temu_goods_sales.`price`+0)/100)) as sum_sale_volume_price,
        sum((temu_goods_sales.`today_sale_volume`+0)*((temu_goods_sales.`price`+0)/100-temu_goods_sales.`cost_price`)) as sum_profit,
        temu_goods_sales.goods_id,
 case
 when sum((temu_goods_sales.`today_sale_volume`+0)*((temu_goods_sales.`price`+0)/100))>0 then (sum((temu_goods_sales.`today_sale_volume`+0)*((temu_goods_sales.`price`+0)/100-temu_goods_sales.`cost_price`))/sum((temu_goods_sales.`today_sale_volume`+0)*((temu_goods_sales.`price`+0)/100)))*100
 else 0
 end as sum_profit_margin
        ")
            //->join("temu_goods_sku","temu_goods_sku.goods_sku_id","=","temu_goods_sales.goods_sku_id")
            ->groupBy("temu_goods_sales.goods_id")->orderBy("sum_today_sale_volume","desc");

        $grid->column("商品信息")->display(function (){
            $joinSiteDuration= !empty($this->goods->join_site_duration)?$this->goods->join_site_duration:"-";
            $str = <<<html
                  <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{$this->goods->img}" style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{$this->goods->title}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{$this->goods->category}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{$this->goods->skc}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{$this->goods->spu}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{$this->goods->sku_article_number}</p>
                                  <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:'.{$joinSiteDuration}</p>
                  </div>
html;
            return $str;
        })->style("width:500px");
        $grid->column("sum_today_sale_volume","商品销售数量")->display(function (){
            return $this->sum_today_sale_volume;
        })->style("text-aligin:center;vertical-align:middle")->sortable();

        $grid->column("商品仓内可用库存")->display(function (){
            $sum_ware_house_inventory_num = 0;
            //库存合计成本
            $sum_ware_house_inventory_num_price =0;
            if(!empty($this->goodsAllSku)){
                foreach ($this->goodsAllSku as $sku){
                    $sum_ware_house_inventory_num+=$sku->ware_house_inventory_num;
                    //$sum_ware_house_inventory_num+=$sku->warehouse_available_sale_days;
                    $sum_ware_house_inventory_num_price+=$sku->ware_house_inventory_num*$sku->cost_price;
                }
            }
            $this->sum_ware_house_inventory_num = $sum_ware_house_inventory_num;
            $this->sum_ware_house_inventory_num_price = $sum_ware_house_inventory_num_price;
            return$sum_ware_house_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品仓内暂不可用库存")->display(function (){
            $sum_unavailable_warehouse_inventory_num =0;
            $sum_unavailable_warehouse_inventory_num_price =0;
            if(!empty($this->goodsAllSku)){
                foreach ($this->goodsAllSku as $sku){
                    $sum_unavailable_warehouse_inventory_num+=$sku->unavailable_warehouse_inventory_num;
                    $sum_unavailable_warehouse_inventory_num_price+=$sku->unavailable_warehouse_inventory_num*$sku->cost_price;
                }
            }
            $this->sum_unavailable_warehouse_inventory_num = $sum_unavailable_warehouse_inventory_num;
            $this->sum_unavailable_warehouse_inventory_num_price = $sum_unavailable_warehouse_inventory_num_price;
            return $sum_unavailable_warehouse_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("商品已发货库存")->display(function (){
            $sum_wait_receive_num =0;
            $sum_wait_receive_num_price =0;
            if(!empty($this->goodsAllSku)){
                foreach ($this->goodsAllSku as $sku){
                    $sum_wait_receive_num+=$sku->wait_receive_num;
                    $sum_wait_receive_num_price+=$sku->wait_receive_num*$sku->cost_price;
                }
            }
            $this->sum_wait_receive_num = $sum_wait_receive_num;
            $this->sum_wait_receive_num_price = $sum_wait_receive_num_price;
            return $sum_wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("合计库存")->display(function (){
            return $this->sum_ware_house_inventory_num+$this->sum_unavailable_warehouse_inventory_num+$this->sum_wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("库存合计成本")->display(function (){
            return $this->sum_ware_house_inventory_num_price+$this->sum_unavailable_warehouse_inventory_num_price+$this->sum_wait_receive_num_price;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("sum_sale_volume_price","销售额")->display(function (){
            return $this->sum_sale_volume_price;
        })->style("text-aligin:center;vertical-align:middle")->sortable();

        //todo 待核实如何计算,按照最新的成本计算利润 还是按历史的计算
        $grid->column("sum_profit","利润")->display(function (){
            return $this->sum_profit;
        })->style("text-aligin:center;vertical-align:middle")->sortable();

        $grid->column("库存可售天数")->display(function (){
            $sum_available_sale_days = 0;
            if(!empty($this->goodsAllSku)){
                foreach ($this->goodsAllSku as $sku){
                    $sum_available_sale_days+=(int)$sku->warehouse_available_sale_days;
                }
            }
            return $sum_available_sale_days;
        })->style("text-aligin:center;vertical-align:middle");

        //todo 待核实 是按七天计算还是按筛选的天数去计算 比如查询 今天和后天的排行榜，此时库存比这列算几天的
        $grid->column("七天库存比")->display(function (){
            $inventoryTotal =  $this->sum_ware_house_inventory_num+$this->sum_unavailable_warehouse_inventory_num+$this->sum_wait_receive_num;
            $sevendaySaleNum = 0;
            if(!empty($this->goodsAllSku)){
                foreach ($this->goodsAllSku as $sku){
                    $sevendaySaleNum+=(int)$sku->last_seven_days_sale_volume;
                }
            }
            $percent =0;
            if($sevendaySaleNum >0){
                $percent = sprintf(
                            "%.2f",
                    ($inventoryTotal/$sevendaySaleNum)*100
                    )."%";
            }
            return $percent;
        })->style("text-aligin:center;vertical-align:middle");

        //todo 待核实如何计算,按照最新的成本计算利润 还是按历史的计算
        $grid->column("sum_profit_margin","利润率")->display(function (){
           // return $this->sum_sale_volume_price>0?sprintf("%.2f",($this->sum_profit/$this->sum_sale_volume_price)*100)."%":0;
            return sprintf("%.2f",($this->sum_profit_margin))."%";
        })->style("text-aligin:center;vertical-align:middle")->sortable();

        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();
//        $grid->disableTools();
        $grid->disableRowSelector();
        $grid->disableExport();
//        $grid->disableFilter();
        $grid->expandFilter();

        Grid\Filter::extend("between",CustomFilterBetween::class);
        Grid\Filter::extend("equal",CustomFilterEqual::class);

        $grid->filter(function(Grid\Filter $filter)use($allMall){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_goods_sales.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));
            $filter->where(function ($query){
                $query->whereHas("goods",function ($query){
                    $goods_skc = request()->get("goods_skc");
                    if(!empty($goods_skc)){
                        $query->where("skc",$goods_skc);
                    }
                });
            },"商品skc","goods_skc");

            $filter->where(function ($query){
                $query->whereHas("goods",function ($query){
                    $goods_sku_article_number = request()->get("goods_sku_article_number");
                    if(!empty($goods_sku_article_number)){
                        $query->where("sku_article_number",$goods_sku_article_number);
                    }
                });
            },"商品skc货号","goods_sku_article_number");


            // 在这里添加字段过滤器
            $filter->between('temu_goods_sales.created_at', "销售时间范围")->datetime();

        });

        $grid->header(function (\Illuminate\Database\Eloquent\Builder $query) {
            $goodsIdsArr = $query->pluck("goods_id");
            $totalInventoryNumPrice = 0;
            if(!empty($goodsIdsArr)){
                $goodsIds = array_unique($goodsIdsArr->toArray());
                $res = TemuGoodsSku::selectRaw("sum((ware_house_inventory_num+0)*cost_price+(unavailable_warehouse_inventory_num+0)*cost_price+(wait_receive_num+0)*cost_price) as totalInventoryNumPrice")->whereIn("goods_id",$goodsIds)->first();
                $totalInventoryNumPrice = $res["totalInventoryNumPrice"];
            }
            return "<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">库存成本合计: $totalInventoryNumPrice</label></div>";

        });

        $js = <<<JS
        $(function (){
          /* $(window).scroll(function(){
                var theadTop =  $('.grid-table thead').offset().top;

                var scrollTop = $(window).scrollTop();

                if(theadTop-scrollTop<0 && typeof($('.grid-table thead').attr('style'))=='undefined'){

                    $('.grid-table thead').attr('style','position:fixed;display:table-header-group;top:80px;background-color:#fff;z-index:1000;');
                }else{
                    if($('.grid-table thead').offset().top < $('.grid-table').offset().top){
                        if(typeof($('.grid-table thead').attr('style'))=='string'){
                           $('.grid-table thead').removeAttr('style');
                        }
                    }
                }

            });*/
        });
JS;
        Admin::script($js);


        return $content
            ->title("temu店铺畅销商品")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }

    /**
     * 畅销sku
     * User: jiahao.dong
     * Date: 2023/5/6
     * Time: 下午9:10
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function hotSku(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺畅销sku")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        $offsetGet = request()->offsetGet('temu_goods_sales');
        if(empty($offsetGet["created_at"])){
            $offsetGet["created_at"] =[
                'start'=>date("Y-m-d 00:00:00"),
                'end'=>date("Y-m-d 23:59:59"),
            ];
        }
        if(empty($offsetGet["mall_id"])){
            $offsetGet["mall_id"] = array_column($allMall->toArray(),"mall_id")[0];
        }
        request()->offsetSet("temu_goods_sales",$offsetGet);
        $cost_price_type = request()->get("cost_price");

        $grid = new Grid(new TemuGoodsSales());
        $grid->model()->with(["skuInfo","goods"])->selectRaw("sum(`today_sale_volume`) as sum_today_sale_volume,sum((`today_sale_volume`+0)/100*(`price`+0)) as sum_sale_volume_price,
        sum((temu_goods_sales.`today_sale_volume`+0)*((temu_goods_sales.`price`+0)/100-temu_goods_sales.`cost_price`)) as sum_profit,goods_id,goods_sku_id,
        case
            when sum((`today_sale_volume`+0)/100*(`price`+0))>0 then sum((temu_goods_sales.`today_sale_volume`+0)*((temu_goods_sales.`price`+0)/100-temu_goods_sales.`cost_price`))/sum((`today_sale_volume`+0)/100*(`price`+0))*100
            else
                0
        end as sum_profit_margin
        ")
            ->groupBy("goods_sku_id")->orderBy("sum_today_sale_volume","desc")
           ->whereRaw("`goods_id`!='' and `goods_id`!=0");

        $grid->column("商品信息")->display(function (){
            $joinSiteDuration= !empty($this->goods->join_site_duration)?$this->goods->join_site_duration:"-";
            $str = <<<html
                  <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{$this->goods->img}" style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{$this->goods->title}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{$this->goods->category}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{$this->goods->skc}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{$this->goods->spu}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{$this->goods->sku_article_number}</p>
                                  <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:'.{$joinSiteDuration}</p>
                  </div>
html;
            return $str;
        })->width(300)->style("text-aligin:center;vertical-align:middle");
        $grid->tableID = "hotSKuList";
        $grid->column("热销SKU")->display(function (){
            return $this->skuInfo->sku_name;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("SKU货号")->display(function (){
            return $this->skuInfo->sku_ext_code;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("SKU销售价格")->display(function (){
            return intval($this->skuInfo->supplier_price)/100;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("sku_cost_price","SKU成本价格")->display(function (){
            return $this->skuInfo->cost_price;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("sum_today_sale_volume","商品销售数量")->display(function (){
            return $this->sum_today_sale_volume;
        })->style("text-aligin:center;vertical-align:middle")->sortable();
        $grid->column("商品近30日销售数量")->display(function (){
            return $this->skuInfo->last_thirty_days_sale_volume;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("商品仓内可用库存")->display(function (){
            return $this->skuInfo->ware_house_inventory_num;
           // return $this->skuInfo->warehouse_available_sale_days;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("商品仓内暂不可用库存")->display(function (){
            return $this->skuInfo->unavailable_warehouse_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("商品已发货库存")->display(function (){
            return $this->skuInfo->wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("合计库存")->display(function (){
            return TemuDataStatisticsService::getSkuTotalInventory($this->skuInfo);
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("库存合计成本")->display(function (){
            return $this->skuInfo->ware_house_inventory_num*$this->skuInfo->cost_price
                +$this->skuInfo->unavailable_warehouse_inventory_num*$this->skuInfo->cost_price
                +$this->skuInfo->wait_receive_num*$this->skuInfo->cost_price;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("不合理库存")->display(function (){
            //return TemuDataStatisticsService::getSkuUnreasonableInventory($this->skuInfo);
            return  $this->skuInfo->unreasonable_inventory;
        })->style("text-aligin:center;vertical-align:middle")->help(TemuDataStatisticsService::getSkuUnreasonableInventoryHelpDesc());

        $grid->column("不合理库存成本")->display(function (){
            //        $skuUnreasonableInventory = self::getSkuUnreasonableInventory($skuInfo);
//        $skuUnreasonableInventory = $skuInfo->unreasonable_inventory;
            return TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($this->skuInfo->unreasonable_inventory,$this->skuInfo->cost_price);
        })->style("text-aligin:center;vertical-align:middle");

        //todo 待核实如何计算,按照最新的成本计算利润 还是按历史的计算
        $grid->column("sum_sale_volume_price","销售额")->display(function (){
            return $this->sum_sale_volume_price;
        })->style("text-aligin:center;vertical-align:middle")->sortable();

        //todo 待核实如何计算,按照最新的成本计算利润 还是按历史的计算
        $grid->column("sum_profit","利润")->display(function (){
            return $this->sum_profit;
        })->style("text-aligin:center;vertical-align:middle")->sortable();

        $grid->column("库存可售天数")->display(function (){
            return $this->skuInfo->warehouse_available_sale_days;
        })->style("text-aligin:center;vertical-align:middle");

        //todo 待核实 是按七天计算还是按筛选的天数去计算 比如查询 今天和后天的排行榜，此时库存比这列算几天的
        $grid->column("七天库存比")->display(function (){
            $total_inventory_num = $this->skuInfo->ware_house_inventory_num+$this->skuInfo->unavailable_warehouse_inventory_num+$this->skuInfo->wait_receive_num;
            $percent = 0;
            if($this->skuInfo->last_seven_days_sale_volume>0){
                $percent = sprintf("%.2f",
                        $total_inventory_num/$this->skuInfo->last_seven_days_sale_volume*100)."%";
            }
            return $percent;
        })->style("text-aligin:center;vertical-align:middle");

        //todo 待核实如何计算,按照最新的成本计算利润 还是按历史的计算
        $grid->column("sum_profit_margin","利润率")->display(function (){
            return sprintf("%.2f",$this->sum_profit_margin)."%";
           /* if($this->sum_sale_volume_price>0){
                return sprintf("%.2f",($this->sum_profit/$this->sum_sale_volume_price)*100)."%";
            }else{
                return 0;
            }*/
        })->style("text-aligin:center;vertical-align:middle")->sortable();



        $grid->disableCreateButton();
        $grid->disableColumnSelector();
//        $grid->disableTools();
        $grid->disableRowSelector();
        $grid->disableExport();
//        $grid->disableFilter();
        $grid->expandFilter();

        if(\Admin::user()->isAdministrator() || \Admin::user()->can('cost.manage.permissons')){
            $grid->actions(function (Grid\Displayers\Actions $actions){
                $actions->disableDelete();
                $actions->disableView();
                $actions->disableEdit();
                $skuInfo = $actions->getAttribute("skuInfo");
               // if(!empty($skuInfo["sku_ext_code"])){
                    $goods_sku_id = $actions->getAttribute("goods_sku_id");
                    Admin::style(".column-__actions__ {text-align:center;vertical-align:middle !important;}");
                    $actions->append('<a href="javascript:void(0);" class="edit-sku edit-'.$goods_sku_id.'" data-toggle="modal"
                    data-target="#myModal" data-id="'.$goods_sku_id.'" data-sku-ext-code="'.$skuInfo["sku_ext_code"].'"
                    data-sku-info="'.$skuInfo["sku_name"].'" data-cost-price="'.$skuInfo["cost_price"].'">编辑</a>');
                //}
        });
        }else{
            $grid->disableActions();
        }

        $grid->tools(function (Grid\Tools $tools){
            $tools->disableBatchActions();
        });

        Grid\Filter::extend("between",CustomFilterBetween::class);
        Grid\Filter::extend("equal",CustomFilterEqual::class);
        $grid->filter(function(Grid\Filter $filter)use($allMall,$cost_price_type){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_goods_sales.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));

            // 在这里添加字段过滤器
            $filter->between('temu_goods_sales.created_at', "销售时间范围")->datetime();

            //成本是否录入筛选
            $filter->where(function ($query)use ($cost_price_type){
                if($cost_price_type == 2){
                    $query->where("cost_price","=",0);
                }elseif ($cost_price_type == 3){
                    $query->where("cost_price","!=",0);
                }
            },"sku成本是否录入","cost_price")->select([
                "1"=>"所有sku",
                "2"=>"未录入成本sku",
                "3"=>"已录入成本sku",
            ])->default("1");
            $filter->where(function ($query){
                $query->whereHas("skuInfo",function ($query){
                    $sku_ext_code = request()->get("sku_ext_code");
                    if(!empty($sku_ext_code)){
                        $query->where("sku_ext_code",$sku_ext_code);
                    }
                });
            },"sku货号","sku_ext_code");
            $filter->where(function ($query){
                $query->whereHas("goods",function ($query){
                    $goods_skc = request()->get("goods_skc");
                    if(!empty($goods_skc)){
                        $query->where("skc",$goods_skc);
                    }
                });
            },"商品skc","goods_skc");

            $filter->where(function ($query){
                $query->whereHas("goods",function ($query){
                    $goods_sku_article_number = request()->get("goods_sku_article_number");
                    if(!empty($goods_sku_article_number)){
                        $query->where("sku_article_number",$goods_sku_article_number);
                    }
                });
            },"商品skc货号","goods_sku_article_number");
        });

        $grid->header(function (\Illuminate\Database\Eloquent\Builder $query) use($grid,$offsetGet){
            //$skuIdsArr = $query->pluck("goods_sku_id");
            //$totalInventoryNumPrice = 0;
//            $skuUnreasonableInventoryTotalCostPrice=0;
            $mallId = $offsetGet["mall_id"];
            $res = TemuGoodsSku::selectRaw("sum((ware_house_inventory_num+0)*cost_price+(unavailable_warehouse_inventory_num+0)*cost_price+(wait_receive_num+0)*cost_price) as totalInventoryNumPrice")->where("mall_id",$mallId)->first();
            $totalInventoryNumPrice = $res["totalInventoryNumPrice"];
            $totalUnreasonableRes = TemuDataStatisticsService::getTotalUnreasonableInventory([$mallId]);
            $skuUnreasonableInventoryTotalCostPrice = $totalUnreasonableRes[$mallId]??0;
            /*if(!empty($skuIdsArr)){
                $skuIds = array_unique($skuIdsArr->toArray());
                $res = TemuGoodsSku::selectRaw("sum((ware_house_inventory_num+0)*cost_price+(unavailable_warehouse_inventory_num+0)*cost_price+(wait_receive_num+0)*cost_price) as totalInventoryNumPrice")->whereIn("goods_sku_id",$skuIds)->first();
                $totalInventoryNumPrice = $res["totalInventoryNumPrice"];

                $skus =TemuGoodsSku::whereIn("goods_sku_id",$skuIds)->get();
                foreach ($skus as $_sku)
                {
                    if(!empty($_sku->toArray())){
                        $skuUnreasonableInventoryTotalCostPrice+=TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($_sku->unreasonable_inventory,$_sku->cost_price);
                    }
                }
            }*/

            return "
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">库存成本合计: $totalInventoryNumPrice</label></div>
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">不合理库存成本合计: {$skuUnreasonableInventoryTotalCostPrice}</label></div>
";

        });

        $js = <<<JS
        $(function (){
           $(".column-sku_cost_price").each(function (){
              if(parseFloat($(this).text()) == 0){
                    $(this).parent("tr").css("background-color","goldenrod");
              }
           });
           $("#hotSKuList").parent("div").addClass("table-container");
           $("#hotSKuList thead").addClass("topThead");
           $(".edit-sku").on("click",function (){
               $("#edit-box-sku-ext-code").text($(this).attr("data-sku-ext-code"));
               $("#edit-box-sku-info").text($(this).attr("data-sku-info"));
               $("#edit-sku-cost-price").val($(this).attr("data-cost-price"));
               $("#edit-sku-id").val($(this).attr("data-id"));
           })
           function showError(msg){
            swal({
                    title: msg,
                    type: 'error',
                    showCancelButton: false,
                    showConfirmButton: false,
                    toast:true,
                    position:"top",
                    width: "300px",
                    padding: "10px",
                    timer:3000
                }
            );
           }

            $(".save-sku-edit").click(function (){
                var cost_price = $("#edit-sku-cost-price").val();
                var goods_sku_id = $("#edit-sku-id").val();
                $.ajax({
                    url:"/admin/statistics/temugoods/updatesku",
                    type:"post",
                    data:{cost_price:cost_price,goods_sku_id:goods_sku_id,_token:LA.token},
                    success:function (res){
                        if(res.status !=200){
                            showError(res.data);
                        }else{
                           swal({
                                title: "修改成功",
                                type: 'success',
                                showCancelButton: false,
                                showConfirmButton: false,
                                toast:true,
                                position:"top",
                                width: "300px",
                                padding: "10px",
                                timer:3000
                             });
                           $(".edit-"+goods_sku_id).attr("data-cost-price",cost_price);
                           $(".edit-"+goods_sku_id).parent().parent().children(".column-sku_cost_price").text(cost_price)
                            $("#myModal").modal("hide");
                        }
                    }
                })
            })

        });
JS;
        Admin::script($js);

        $css =<<<css
.table-container {
    max-height: 800px;
    overflow-y: scroll;

}

.topThead {
    display: table-header-group;
    position: sticky;
    top: 0;
    background-color: #ffffff;
    z-index: 1;
}
.topThead >th  {
 min-width:50px
}

/*#hotSKuList th, #hotSKuList td {
  padding: 8px;
  text-align: left;
  border-bottom: 1px solid #ddd;
  width: 50px;
}

#hotSKuList th {
  background-color: #f2f2f2;
 // position: sticky;
 // top: 0; !* 将表头固定在顶部 *!
 // z-index: 1; !* 确保表头在其他内容之上 *!
}*/
css;

        Admin::style($css);

        $html = <<<html
<!-- 模态框（Modal） -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="myModalLabel">
					编辑sku
				</h4>
			</div>
			<div class="modal-body">
			<div class="form-group">
                <label>sku货号</label>
                <span class="form-control" id="edit-box-sku-ext-code"></span>
            </div>
            <div class="form-group">
                <label>sku信息</label>
                <span class="form-control" id="edit-box-sku-info" ></span>
            </div>
            <div class="form-group">
                <label>sku成本价格</label>
                <input type="text" id="edit-sku-cost-price" value="1" class="form-control code_num action" placeholder="输入 sku成本价格" required="1">
            </div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">关闭
				</button>
				<button type="button" class="btn btn-primary save-sku-edit">
					保存修改
				</button>
			</div>
			<input type="hidden" value="" id="edit-sku-id">
		</div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
html;

        Admin::html($html);


        return $content
            ->title("temu店铺畅销sku")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }



    /** 今日热销商品榜
     * User: jiahao.dong
     * Date: 2023/4/26
     * Time: 下午9:40
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function todayHotGoods(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺今日畅销商品")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        if(empty(request()->temu_goods_sku)){
            request()->offsetSet('temu_goods_sku',[
                'mall_id'=>array_column($allMall->toArray(),"mall_id")[0]
            ]);
        }

        $grid = new Grid(new TemuGoodsSku());
        $grid->model()->selectRaw("sum(`today_sale_volume`) as sum_today_sale_volume,sum(`ware_house_inventory_num`) as sum_ware_house_inventory_num,
        sum(`unavailable_warehouse_inventory_num`) as sum_unavailable_warehouse_inventory_num,sum(`wait_receive_num`) as sum_wait_receive_num,goods_id")
        ->groupBy("goods_id")->orderBy("sum_today_sale_volume","desc");


        $grid->column("商品信息")->display(function (){
            $joinSiteDuration= !empty($this->goods->join_site_duration)?$this->goods->join_site_duration:"-";
            $str = <<<html
                  <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{$this->goods->img}" style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{$this->goods->title}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{$this->goods->category}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{$this->goods->skc}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{$this->goods->spu}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{$this->goods->sku_article_number}</p>
                                  <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:'.{$joinSiteDuration}</p>
                  </div>
html;
            return $str;
        })->style("width:500px");
        $grid->column("商品销售数量")->display(function (){
            return $this->sum_today_sale_volume;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品仓内可用库存")->display(function (){
            return $this->sum_ware_house_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品仓内暂不可用库存")->display(function (){
            return $this->sum_unavailable_warehouse_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品已发货库存")->display(function (){
            return $this->sum_wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("合计库存")->display(function (){
            return $this->sum_ware_house_inventory_num+$this->sum_unavailable_warehouse_inventory_num+$this->sum_wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");


        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();
//        $grid->disableTools();
        $grid->disableRowSelector();
        $grid->disableExport();
//        $grid->disableFilter();
        Grid\Filter::extend("equal",CustomFilterEqual::class);

        $grid->filter(function(Grid\Filter $filter)use($allMall){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_goods_sku.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));

        });

        $js = <<<JS
        $(function (){
           /* $(window).scroll(function(){
                var theadTop =  $('.grid-table thead').offset().top;

                var scrollTop = $(window).scrollTop();

                if(theadTop-scrollTop<0 && typeof($('.grid-table thead').attr('style'))=='undefined'){

                    $('.grid-table thead').attr('style','position:fixed;display:table-header-group;top:80px;background-color:#fff;z-index:1000;');
                }else{
                    if($('.grid-table thead').offset().top < $('.grid-table').offset().top){
                        if(typeof($('.grid-table thead').attr('style'))=='string'){
                           $('.grid-table thead').removeAttr('style');
                        }
                    }
                }

            });*/
        });
JS;
        Admin::script($js);

        return $content
            ->title("temu店铺今日畅销商品")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }


    /** 今日热销sku榜
     * User: jiahao.dong
     * Date: 2023/4/26
     * Time: 下午9:45
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function todayHotSku(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺今日畅销sku")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        if(empty(request()->temu_goods_sku)){
            request()->offsetSet('temu_goods_sku',[
                'mall_id'=>array_column($allMall->toArray(),"mall_id")[0]
            ]);
        }

        $grid = new Grid(new TemuGoodsSku());
/*        $grid->model()->selectRaw("today_sale_volume,sku_name,ware_house_inventory_num,
        unavailable_warehouse_inventory_num,wait_receive_num,goods_id,last_thirty_days_sale_volume,cost_price")*/
        $grid->model()
            ->selectRaw("temu_goods_sku.*");
        $sortColumnGet = request()->offsetGet('_sort');
        if(empty($sortColumnGet["column"])){
            $grid->model()->orderByRaw("`today_sale_volume`+0 desc");
        }


        $grid->column("商品信息")->display(function (){
            $joinSiteDuration= !empty($this->goods->join_site_duration)?$this->goods->join_site_duration:"-";
            $str = <<<html
                  <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{$this->goods->img}" style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{$this->goods->title}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{$this->goods->category}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{$this->goods->skc}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{$this->goods->spu}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{$this->goods->sku_article_number}</p>
                                  <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:'.{$joinSiteDuration}</p>
                  </div>
html;
            return $str;
        })->style("width:500px");
        $grid->column("热销SKU")->display(function (){
            return $this->sku_name;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("SKU销售价格")->display(function (){
            return intval($this->supplier_price)/100;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("sku_cost_price","SKU成本价格")->display(function (){
            return $this->cost_price;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("today_sale_volume","sku销售数量")->display(function (){
            return $this->today_sale_volume;
        })->style("text-aligin:center;vertical-align:middle")->sortable("UNSIGNED");

        $grid->column("sku近30日销售数量")->display(function (){
            return $this->last_thirty_days_sale_volume;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("商品仓内可用库存")->display(function (){
            return $this->ware_house_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品仓内暂不可用库存")->display(function (){
            return $this->unavailable_warehouse_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品已发货库存")->display(function (){
            return $this->wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("合计库存")->display(function (){
            return TemuDataStatisticsService::getSkuTotalInventory($this);
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("unreasonable_inventory","不合理库存")->display(function (){
//            return TemuDataStatisticsService::getSkuUnreasonableInventory($this);
            return $this->unreasonable_inventory;
        })->style("text-aligin:center;vertical-align:middle")->help(TemuDataStatisticsService::getSkuUnreasonableInventoryHelpDesc())->sortable();

        $grid->column("unreasonable_inventory_cost_price","不合理库存成本")->display(function (){
            return TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($this->unreasonable_inventory,$this->cost_price);
        })->style("text-aligin:center;vertical-align:middle");

        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableExport();
//        $grid->disableFilter();

        Grid\Filter::extend("equal",CustomFilterEqual::class);

        $grid->filter(function(Grid\Filter $filter)use($allMall){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_goods_sku.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));

        });

        $grid->header(function (\Illuminate\Database\Eloquent\Builder $query)use($grid) {

            $skuIdsArr = $query->pluck("goods_sku_id");
            $skuUnreasonableInventoryTotalCostPrice=0;
            $skuIds=[];
            if(!empty($skuIdsArr)){
                $skuIds=array_unique($skuIdsArr->toArray());
            }

            $skus =TemuGoodsSku::whereIn("goods_sku_id",$skuIds)->get();
            foreach ($skus as $_sku)
            {
                if(!empty($_sku->toArray())){
                    $skuUnreasonableInventoryTotalCostPrice+=TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($_sku->unreasonable_inventory,$_sku->cost_price);
                }
            }

            return "
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">不合理库存成本合计: {$skuUnreasonableInventoryTotalCostPrice}</label></div>
";

        });

        $js = <<<JS
        $(function (){
           $(".column-sku_cost_price").each(function (){
              if(parseFloat($(this).text()) == 0){
                    $(this).parent("tr").css("background-color","goldenrod");
              }
           });

           /*$(window).scroll(function(){
                var theadTop =  $('.grid-table thead').offset().top;

                var scrollTop = $(window).scrollTop();

                if(theadTop-scrollTop<0 && typeof($('.grid-table thead').attr('style'))=='undefined'){

                    $('.grid-table thead').attr('style','position:fixed;display:table-header-group;top:80px;background-color:#fff;z-index:1000;');
                }else{
                    if($('.grid-table thead').offset().top < $('.grid-table').offset().top){
                        if(typeof($('.grid-table thead').attr('style'))=='string'){
                           $('.grid-table thead').removeAttr('style');
                        }
                    }
                }

            });*/
        });
JS;

        Admin::script($js);

        return $content
            ->title("temu店铺今日畅销sku")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }


    /** 七日热销商品榜
     * User: jiahao.dong
     * Date: 2023/4/26
     * Time: 下午9:40
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function sevenDayHotGoods(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺今日畅销商品")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        if(empty(request()->temu_goods_sku)){
            request()->offsetSet('temu_goods_sku',[
                'mall_id'=>array_column($allMall->toArray(),"mall_id")[0]
            ]);
        }

        $grid = new Grid(new TemuGoodsSku());
        $grid->model()->selectRaw("sum(`last_seven_days_sale_volume`) as sum_last_seven_days_sale_volume,sum(`ware_house_inventory_num`) as sum_ware_house_inventory_num,
        sum(`unavailable_warehouse_inventory_num`) as sum_unavailable_warehouse_inventory_num,sum(`wait_receive_num`) as sum_wait_receive_num,goods_id")
            ->groupBy("goods_id")->orderBy("sum_last_seven_days_sale_volume","desc");


        $grid->column("商品信息")->display(function (){
            $joinSiteDuration= !empty($this->goods->join_site_duration)?$this->goods->join_site_duration:"-";
            $str = <<<html
                  <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{$this->goods->img}" style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{$this->goods->title}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{$this->goods->category}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{$this->goods->skc}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{$this->goods->spu}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{$this->goods->sku_article_number}</p>
                                  <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:'.{$joinSiteDuration}</p>
                  </div>
html;
            return $str;
        })->style("width:500px");
        $grid->column("商品销售数量")->display(function (){
            return $this->sum_last_seven_days_sale_volume;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品仓内可用库存")->display(function (){
            return $this->sum_ware_house_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品仓内暂不可用库存")->display(function (){
            return $this->sum_unavailable_warehouse_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品已发货库存")->display(function (){
            return $this->sum_wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("合计库存")->display(function (){
            return $this->sum_last_seven_days_sale_volume+$this->sum_unavailable_warehouse_inventory_num+$this->sum_wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();
//        $grid->disableTools();
        $grid->disableRowSelector();
        $grid->disableExport();
//        $grid->disableFilter();

        Grid\Filter::extend("equal",CustomFilterEqual::class);

        $grid->filter(function(Grid\Filter $filter)use($allMall){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_goods_sku.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));

        });

        $js = <<<JS
        $(function (){
           $(window).scroll(function(){
               /* var theadTop =  $('.grid-table thead').offset().top;

                var scrollTop = $(window).scrollTop();

                if(theadTop-scrollTop<0 && typeof($('.grid-table thead').attr('style'))=='undefined'){

                    $('.grid-table thead').attr('style','position:fixed;display:table-header-group;top:80px;background-color:#fff;z-index:1000;');
                }else{
                    if($('.grid-table thead').offset().top < $('.grid-table').offset().top){
                        if(typeof($('.grid-table thead').attr('style'))=='string'){
                           $('.grid-table thead').removeAttr('style');
                        }
                    }
                }*/

            });
        });
JS;

        Admin::script($js);

        return $content
            ->title("temu店铺今日畅销商品")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }

    public function sevenDayHotSku(AdminLayoutContentService $content)
    {

        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺七日畅销SKU")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        if(empty(request()->temu_goods_sku)){
            request()->offsetSet('temu_goods_sku',[
                'mall_id'=>array_column($allMall->toArray(),"mall_id")[0]
            ]);
        }

        $grid = new Grid(new TemuGoodsSku());
/*        $grid->model()->selectRaw("last_seven_days_sale_volume,sku_name,ware_house_inventory_num,
        unavailable_warehouse_inventory_num,wait_receive_num,goods_id")*/
        $grid->model()
            ->selectRaw("temu_goods_sku.*");
        $sortColumnGet = request()->offsetGet('_sort');
        if(empty($sortColumnGet["column"])){
            $grid->model()->orderByRaw("`last_seven_days_sale_volume`+0 desc");
        }


        $grid->column("商品信息")->display(function (){
            $joinSiteDuration= !empty($this->goods->join_site_duration)?$this->goods->join_site_duration:"-";
            $str = <<<html
                  <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{$this->goods->img}" style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{$this->goods->title}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{$this->goods->category}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{$this->goods->skc}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{$this->goods->spu}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{$this->goods->sku_article_number}</p>
                                  <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:'.{$joinSiteDuration}</p>
                  </div>
html;
            return $str;
        })->style("width:500px");
        $grid->column("热销SKU")->display(function (){
            return $this->sku_name;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("SKU销售价格")->display(function (){
            return intval($this->supplier_price)/100;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("sku_cost_price","SKU成本价格")->display(function (){
            return $this->cost_price;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("last_seven_days_sale_volume","sku销售数量")->display(function (){
            return $this->last_seven_days_sale_volume;
        })->style("text-aligin:center;vertical-align:middle")->sortable("UNSIGNED");
        $grid->column("商品仓内可用库存")->display(function (){
            return $this->ware_house_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品仓内暂不可用库存")->display(function (){
            return $this->unavailable_warehouse_inventory_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("商品已发货库存")->display(function (){
            return $this->wait_receive_num;
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("合计库存")->display(function (){
            return TemuDataStatisticsService::getSkuTotalInventory($this);
        })->style("text-aligin:center;vertical-align:middle");
        $grid->column("unreasonable_inventory","不合理库存")->display(function (){
//            return TemuDataStatisticsService::getSkuUnreasonableInventory($this);
            return $this->unreasonable_inventory;
        })->style("text-aligin:center;vertical-align:middle")->help(TemuDataStatisticsService::getSkuUnreasonableInventoryHelpDesc())->sortable();

        $grid->column("不合理库存成本")->display(function (){
            return TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($this->unreasonable_inventory,$this->cost_price);
        })->style("text-aligin:center;vertical-align:middle");

        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();
//        $grid->disableTools();
        $grid->disableRowSelector();
        $grid->disableExport();
//        $grid->disableFilter();

        Grid\Filter::extend("equal",CustomFilterEqual::class);

        $grid->filter(function(Grid\Filter $filter)use($allMall){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_goods_sku.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));

        });

        $grid->header(function (\Illuminate\Database\Eloquent\Builder $query)use($grid) {

            $skuIdsArr = $query->pluck("goods_sku_id");
            $skuUnreasonableInventoryTotalCostPrice=0;
            $skuIds=[];
            if(!empty($skuIdsArr)){
                $skuIds=array_unique($skuIdsArr->toArray());
            }

            $skus =TemuGoodsSku::whereIn("goods_sku_id",$skuIds)->get();
            foreach ($skus as $_sku)
            {
                if(!empty($_sku->toArray())){
                    $skuUnreasonableInventoryTotalCostPrice+=TemuDataStatisticsService::getSkuUnreasonableInventoryTotalCostPrice($_sku->unreasonable_inventory,$_sku->cost_price);
                }
            }

            return "
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">不合理库存成本合计: {$skuUnreasonableInventoryTotalCostPrice}</label></div>
";

        });

        $js = <<<JS
        $(function (){
           $(".column-sku_cost_price").each(function (){
              if(parseFloat($(this).text()) == 0){
                    $(this).parent("tr").css("background-color","goldenrod");
              }
           });

           /*$(window).scroll(function(){
                var theadTop =  $('.grid-table thead').offset().top;

                var scrollTop = $(window).scrollTop();

                if(theadTop-scrollTop<0 && typeof($('.grid-table thead').attr('style'))=='undefined'){

                    $('.grid-table thead').attr('style','position:fixed;display:table-header-group;top:80px;background-color:#fff;z-index:1000;');
                }else{
                    if($('.grid-table thead').offset().top < $('.grid-table').offset().top){
                        if(typeof($('.grid-table thead').attr('style'))=='string'){
                           $('.grid-table thead').removeAttr('style');
                        }
                    }
                }

            });*/
        });
JS;
        Admin::script($js);

        return $content
            ->title("temu店铺七日畅销SKU")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }

    public function changePrice()
    {
        $data = TemuGoodsSku::all();
        if(!empty($data)){
            foreach ($data as $val){
                TemuGoodsSales::where("goods_sku_id",$val->goods_sku_id)->update(["cost_price"=>$val->cost_price]);
            }
        }
    }

    public function edit($id, AdminLayoutContentService $content)
    {

        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->skuform($id));

    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function skuform($goodsSkuId=null)
    {
        $temuGoodsSalesModel = new TemuGoodsSku();
        $model = $temuGoodsSalesModel->where(["goods_sku_id"=>$goodsSkuId])->first();
        if(empty($model)){
            return admin_error("","sku货号不存在");
        }
        $form = new Form($model);

        $form->display('id', 'ID');
        $form->display('sku_ext_code','sku货号');

        $form->text("cost_price","sku成本价")
            ->creationRules(['numeric'],[
                'cost_price.numeric'=>'成本价必须为数字!',
            ])
            ->updateRules(['numeric'],[
                'cost_price.numeric'=>'成本价必须为数字!',
            ])
            ->default(0);

        $form->saved(function (Form $form){
            admin_toastr(trans('admin.save_succeeded'));
            return redirect("/admin/mallmanage/temumalls/malllist");
        });

        if(request()->method() == "GET"){
            $data = $model->toArray();
            $form->fields()->each(function (Field $field) use ($data) {
                $field->fill($data);
            });
        }
        $actionUrl = "/admin/statistics/temugoods/update/".$goodsSkuId;
        return $form->setAction($actionUrl);
    }

    public function updatesku()
    {
        $goodsSkuId = request()->offsetGet('goods_sku_id');
        $temuGoodsSalesModel = new TemuGoodsSku();
        $model = $temuGoodsSalesModel->where(["goods_sku_id"=>$goodsSkuId])->first();
        if(empty($model)){
            return $this->jsonError("sku货号不存在");
        }
        $cost_price = request()->get("cost_price",0);
        $skus = TemuGoodsSku::where([
            "goods_sku_id" => $goodsSkuId,
        ])->get();
        if (!empty($skus)) {
            foreach ($skus as $sku) {
                $sku->update(["cost_price" => $cost_price]);
                //修改销售记录
                //修改今天的历史记录 成本价
                TemuGoodsSales::where([
                    "goods_id" => $sku->goods_id,
                    "goods_sku_id" => $sku->goods_sku_id,
                    "product_sku_id" => $sku->product_sku_id,
                ])->whereRaw("DATE_FORMAT(`created_at`,'%Y-%m-%d')=?", [date("Y-m-d", time())])
                    ->update([
                        "cost_price" => $cost_price
                    ]);

                //如果之前没有录入成本则今天补录
                TemuGoodsSales::where([
                    "goods_id" => $sku->goods_id,
                    "goods_sku_id" => $sku->goods_sku_id,
                    "product_sku_id" => $sku->product_sku_id,
                ])
                    ->whereRaw("`cost_price`=0")
                    ->update([
                        "cost_price" => $cost_price
                    ]);
            }
        }

          TemuGoodsSales::where("goods_sku_id",$goodsSkuId)
            ->where("cost_price","=",0)->update(["cost_price"=>$cost_price]);
            TemuGoodsSku::where("goods_sku_id",$goodsSkuId)
                ->where("cost_price","=",0)->update(["cost_price"=>$cost_price]);
            return $this->jsonSuccess();

    }
}
