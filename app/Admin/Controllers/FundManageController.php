<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/4
 * Time: 下午10:39
 */

namespace App\Admin\Controllers;

use App\Compoents\CustomFilterBetween;
use App\Compoents\CustomFilterEqual;
use App\Model\TemuGoodsSales;
use App\Model\TemuMalls;
use App\Model\TemuMallsDeliveryRestrict;
use App\Model\TemuMallsDeliveryRestrictOrder;
use App\Model\TemuMallsGoodsRefundCost;
use App\Service\AdminLayoutContentService;
use Encore\Admin\Admin;
use Encore\Admin\Form\Tab;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Table;

class FundManageController extends AdminController{

    /**
     * 快递费限制金额
     * User: jiahao.dong
     * Date: 2023/5/4
     * Time: 下午10:58
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function deliveryFundRestrictList(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺快递费限制记录")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        $offsetGet = request()->offsetGet('temu_malls_delivery_restrict');
        if(empty($offsetGet["freeze_start_time"])){
            $offsetGet["freeze_start_time"] =[
                'start'=>date("Y-m-d 00:00:00"),
                'end'=>date("Y-m-d 23:59:59"),
            ];
        }
        if(empty($offsetGet["mall_id"])){
            $offsetGet["mall_id"] = array_column($allMall->toArray(),"mall_id")[0];
        }
        request()->offsetSet("temu_malls_delivery_restrict",$offsetGet);
        $grid = new Grid(new TemuMallsDeliveryRestrict());
        $grid->column("shipping_no","快递运单号")->style("vertical-align:middle;text-align:center");
        $grid->column('sub_purchase_order_sn', '快递订单号')->expand(function ($model) {
            $sub_purchase_order_sn_arr = explode(",",str_replace(["、"],",",$model->sub_purchase_order_sn));
            $data = TemuMallsDeliveryRestrictOrder::whereIn("sub_purchase_order_sn",$sub_purchase_order_sn_arr)->get();
            $resrows = [];
            if(!empty($data)){
                foreach ($data as $row){
                    $productInfoHtml = <<<html
<div style="padding:13px 0 13px 70px;position:relative;">
                                        <img src="{$row->product_skc_picture}"
                                            style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;">
                                        <p>快递单号:{$row->sub_purchase_order_sn}</p>
                                        <p>{$row->product_name}</p>
                                        <p >SKC:{$row->product_skc_id}</p>
                                        <p >SKC货号:{$row->skc_ext_code}</p>
                                    </div>
html;
                    $courierInfo = !empty($row->courier_name)?$row->courier_name." ,".$row->courier_phone:"";
                    $expressInfoHtml = <<<html
            <p >
            物流单号:{$row->express_company},<br>{$row->express_delivery_sn}
            </p>
            <p >
            联系方式:{$courierInfo}
            </p>
html;
                    $deliverInfoHtml = <<<html
            <p >
            {$row->receive_address_detail_address}:<br>
            {$row->deliver_skc_num}
            </p>
html;
                  $deliver_receive_package = $row->deliver_package_num."/".$row->receive_package_num;
                    $nodeTime = <<<html
<p >
            发货时间:{$row->deliver_time}
            </p>
            <p >
            收货时间:{$row->receive_time}
            </p>
<p >
            入库时间:{$row->inbound_time}
            </p>
html;

                    $resrows[] = [
                        $row["delivery_order_sn"],
                        $productInfoHtml,
                        $expressInfoHtml,
                        $deliverInfoHtml,
                        $row->package_total_deliver_skc_num,
                        $deliver_receive_package,
                        $nodeTime,
                        $row->status
                    ];
                }
            }

            $table = new Table(['发货单号', '商品信息', '物流信息',"发货数量","发货总件数","已发货/已收包裹数","节点时间","发货单状态"],$resrows,["expand_table"]);
            return $table;

        })->style("vertical-align:middle;text-align:center;width: 500px;max-width: 500px;word-wrap: break-word;word-break: normal;");

        $css = <<<css
           .expand_table tr td:nth-child(2)
            {
                 width:300px
            }
            .expand_table tr td:nth-child(4)
            {
                 width:300px
            }
            .expand_table tr td:nth-child(n+3){
                vertical-align:middle !important;
                /*text-align:center;*/
            }
            .expand_table tr td:nth-child(1){
                vertical-align:middle !important;
                text-align:center;
            }
            .expand_table tr th{
                /*text-align:center;*/
            }
css;
        Admin::style($css);

        $grid->column("freeze_start_time","资金限制开始时间")->style("vertical-align:middle;text-align:center");
        $grid->column("amount","快递单号限制金额")->style("vertical-align:middle;text-align:center");
        $grid->column("currency","快递限制金额币种")->style("vertical-align:middle;text-align:center");


        Grid\Filter::extend("between",CustomFilterBetween::class);
        Grid\Filter::extend("equal",CustomFilterEqual::class);

        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->expandFilter();

        $grid->filter(function(Grid\Filter $filter)use($allMall){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_malls_delivery_restrict.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));
            // 在这里添加字段过滤器
            $filter->between('temu_malls_delivery_restrict.freeze_start_time', "资金限制时间范围")->datetime();

        });
        $grid->header(function (\Illuminate\Database\Eloquent\Builder $query)use ($offsetGet) {
            $amount = $query->sum("amount");
            $mallInfo = TemuMalls::where(["mall_id"=> $offsetGet["mall_id"]])->first();
            $totalAmounts = !empty($mallInfo)?$mallInfo->delivery_restrict_amount:0;
            return "
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">筛选时间范围内快递单号限制金额合计: $amount</label></div>
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">快递单号总限制金额合计: $totalAmounts</label></div>
";

        });
        return $content
            ->title("temu店铺快递费限制记录")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }


    /**
     * 退货运费限制金额
     * User: jiahao.dong
     * Date: 2023/5/4
     * Time: 下午10:58
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function goodsRefundCostList(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("temu店铺退货运费限制记录")
                ->description($this->description['index'] ?? trans('admin.list'));
        }
        $offsetGet = request()->offsetGet('temu_malls_goods_refund_cost');
        if(empty($offsetGet["freeze_start_time"])){
            $offsetGet["freeze_start_time"] =[
                'start'=>date("Y-m-d 00:00:00"),
                'end'=>date("Y-m-d 23:59:59"),
            ];
        }
        if(empty($offsetGet["mall_id"])){
            $offsetGet["mall_id"] = array_column($allMall->toArray(),"mall_id")[0];
        }
        request()->offsetSet("temu_malls_goods_refund_cost",$offsetGet);
        $grid = new Grid(new TemuMallsGoodsRefundCost());
        $grid->column("shipping_no","快递运单号")->style("vertical-align:middle;text-align:center");


        $grid->column("freeze_start_time","资金限制开始时间")->style("vertical-align:middle;text-align:center");
        $grid->column("amount","限制金额")->style("vertical-align:middle;text-align:center");
        $grid->column("currency","限制金额币种")->style("vertical-align:middle;text-align:center");


        Grid\Filter::extend("between",CustomFilterBetween::class);
        Grid\Filter::extend("equal",CustomFilterEqual::class);

        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->expandFilter();

        $grid->filter(function(Grid\Filter $filter)use($allMall){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("temu_malls_goods_refund_cost.mall_id",'选择店铺')->select($allMall->pluck("mall_name","mall_id"));
            // 在这里添加字段过滤器
            $filter->between('temu_malls_goods_refund_cost.freeze_start_time', "资金限制时间范围")->datetime();

        });
        $grid->header(function (\Illuminate\Database\Eloquent\Builder $query)use($offsetGet) {
            $amount = $query->sum("amount");
            $mallInfo = TemuMalls::where(["mall_id"=> $offsetGet["mall_id"]])->first();
            $totalAmounts = !empty($mallInfo)?$mallInfo->goods_refund_cost:0;
            return "
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">筛选时间范围内退货运费限制金额合计: $amount</label></div>
<div style='padding: 10px;' class='pull-right'><label class=\"col-sm-12 control-label label-danger h4\">退货运费总限制金额合计: $totalAmounts</label></div>
";

        });
        return $content
            ->title("temu店铺退货运费限制记录")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }

}


