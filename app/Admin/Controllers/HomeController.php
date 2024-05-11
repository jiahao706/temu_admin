<?php

namespace App\Admin\Controllers;

use App\Compoents\Common;
use App\Http\Controllers\Controller;
use App\Model\AdminUser;
use App\Model\TemuGoodsSales;
use App\Model\TemuMalls;
use App\Model\TemuMallsDeliveryRestrict;
use App\Model\TemuMallsGoodsRefundCost;
use App\Service\AdminLayoutContentService;
use App\Service\DashboardService;
use App\Service\TemuDataStatisticsService;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;

class HomeController extends AdminController
{
    public function index(AdminLayoutContentService $content)
    {
        //$userAllowMalls = \Admin::user()->mall_permissions;
        $userInfo = \Admin::user();
        if($userInfo->source == AdminUser::REGISTER_FROM_ADMIN){
            if($userInfo->isAdministrator()){
                $userAllowMalls = TemuMalls::
                whereRaw("mall_id!=''")->groupBy("mall_id")->get();
            }else{
                $userAllowMalls = TemuMalls::
                where(["type"=>TemuMalls::ADD_FROM_ADMIN])->whereRaw("mall_id!=''")
                    ->groupBy("mall_id")->get();
            }
        }else{
            $userAllowMalls = TemuMalls::
            where(["user_id"=>$userInfo->id])->groupBy("mall_id")->get();
        }

        /*        $content = $content
                    ->row(DashboardService::title());*/
        if(!empty($userAllowMalls->toArray())){
            $content = $content->row(function (Row $row) use($userAllowMalls){
                $row->column(12, function (Column $column)use($userAllowMalls){
                    $r = DashboardService::temuSalesStatistics($userAllowMalls);
                    $column->append($r);
                });
            });
            /*$sortMall = DashboardService::getSortMall();
            foreach ($sortMall as $mallInfo){
                if(empty($mallInfo["mallName"])){continue;}
                $content = $content->row(function (Row $row)use($mallInfo) {
                    $row->column(6, function (Column $column)use($mallInfo) {
                        $column->append(DashboardService::TemuTodayBestsellingGoods($mallInfo));
                    });
                    $row->column(6, function (Column $column)use($mallInfo) {
                        $column->append(DashboardService::TemuTodayBestsellingSku($mallInfo));
                    });
                });
            }*/
            $css = <<<css
           .row:nth-child(1)
            {
                margin-top: -44px !important;
            }
css;
            Admin::style($css);
        }
        // return $content->title("主页");
        return $content;
    }

    public function latestDataStatistics()
    {
//        $userAllowMalls = \Admin::user()->mall_permissions;
        $userInfo = \Admin::user();
        if($userInfo->source == AdminUser::REGISTER_FROM_ADMIN){
            if($userInfo->isAdministrator()){
                $userAllowMalls = TemuMalls::
                whereRaw("mall_id!=''")->groupBy("mall_id")->get();
            }else{
                $userAllowMalls = TemuMalls::
                where(["type"=>TemuMalls::ADD_FROM_ADMIN])->whereRaw("mall_id!=''")
                    ->groupBy("mall_id")->get();
            }
        }else{
            $userAllowMalls = TemuMalls::
            where(["user_id"=>$userInfo->id])->groupBy("mall_id")->get();
        }
        $resonse = [];
        if(!empty($userAllowMalls)){
            $mallIds = $userAllowMalls->pluck("mall_id")->toArray();
            $requestMallIds = request()->get("mallids");
            $requestMallIdsArr = explode(",",$requestMallIds);
            foreach ($requestMallIdsArr as $mallId){
                if(!in_array($mallId ,$mallIds)){
                    abort(403);
                    return;
                }
            }
            if(!empty($requestMallIds)){
                $mallIds = $requestMallIdsArr;
            }
            $now = time();

            $startTime = request()->get("temu_malls_start_time");
            $endTime = request()->get("temu_malls_end_time");
            if(empty($startTime) && empty($endTime)){
                $allDay = Common::getWeeks($now);
            }elseif(empty($startTime)){
                $allDay = [date("Y-m-d",strtotime($endTime))];
            }elseif (empty($endTime)){
                $allDay = [date("Y-m-d",strtotime($startTime))];
            }else{
                $days = [];
                for($i=strtotime($startTime);$i<=strtotime($endTime);$i+=86400){
                    $days[] = date("Y-m-d",$i);
                }
                $allDay = array_unique($days);
            }

            $searchStartTimestamp = strtotime($allDay[0]);
            $searchEndTimestamp = strtotime($allDay[count($allDay)-1]);


            //获取七天内的销量
            //获取七天内的销售额
            //获取七天内的毛利润
            //获取七天内的发货运费
            //获取七天内的退货运费
            $latestSalesInfo = TemuGoodsSales::selectRaw("
            sum(`today_sale_volume`+0) as latest_sale_volume,mall_id,
            sum((today_sale_volume+0)*((price+0)/100)) as latest_sales_money,
            sum((today_sale_volume+0)*((price+0)/100-cost_price)) as latest_profit,
            DATE_FORMAT(`created_at`,'%Y-%m-%d') as date")
                ->whereIn("mall_id",$mallIds)
                ->whereRaw("`created_at` <='".date("Y-m-d 23:59:59",$searchEndTimestamp)."' AND `created_at`>='".date("Y-m-d 00:00:00",$searchStartTimestamp)."'")
                ->groupByRaw("mall_id,DATE_FORMAT(`created_at`,'%Y-%m-%d')")->get();

            $latestProfit = TemuGoodsSales::selectRaw("
            mall_id,
            sum((today_sale_volume+0)*((price+0)/100-cost_price)) as latest_profit,
            DATE_FORMAT(`created_at`,'%Y-%m-%d') as date")
                ->whereIn("mall_id",$mallIds)
                ->where("cost_price",">",0)
                ->whereRaw("`created_at` <='".date("Y-m-d 23:59:59",$searchEndTimestamp)."' AND `created_at`>='".date("Y-m-d 00:00:00",$searchStartTimestamp)."'")
                ->groupByRaw("mall_id,DATE_FORMAT(`created_at`,'%Y-%m-%d')")->get();

            $latestDeliveryRestrictInfo = TemuMallsDeliveryRestrict::selectRaw("sum(`amount`) as latest_amounts,mall_id,DATE_FORMAT(`freeze_start_time`,'%Y-%m-%d') as date")
                ->whereRaw("`freeze_start_time`<='".date("Y-m-d 23:59:59",$searchEndTimestamp)."' AND `freeze_start_time`>='".date("Y-m-d 00:00:00",$searchStartTimestamp)."'")
                ->whereIn("mall_id",$mallIds)
                ->groupByRaw("mall_id,DATE_FORMAT(`freeze_start_time`,'%Y-%m-%d')")
                ->get();

            $latestRefundCost = TemuMallsGoodsRefundCost::selectRaw("sum(`amount`) as latest_amounts,mall_id,DATE_FORMAT(`freeze_start_time`,'%Y-%m-%d') as date")
                ->whereRaw("`freeze_start_time`<='".date("Y-m-d 23:59:59",$searchEndTimestamp)."' AND `freeze_start_time`>='".date("Y-m-d 00:00:00",$searchStartTimestamp)."'")
                ->whereIn("mall_id",$mallIds)
                ->groupByRaw("mall_id,DATE_FORMAT(`freeze_start_time`,'%Y-%m-%d')")
                ->get();

            $res = [];
            foreach ($latestSalesInfo as $saleInfo){
                $res[$saleInfo->mall_id][$saleInfo->date]["latest_sale_volume"] =$saleInfo->latest_sale_volume;
                $res[$saleInfo->mall_id][$saleInfo->date]["latest_sales_money"] =$saleInfo->latest_sales_money;
                $res[$saleInfo->mall_id][$saleInfo->date]["latest_profit"] =$saleInfo->latest_profit;
            }

            //发货运费
            foreach ($latestDeliveryRestrictInfo as $deliveryRestrictInfo){
                $res[$deliveryRestrictInfo->mall_id][$deliveryRestrictInfo->date]["latest_amounts"] =$deliveryRestrictInfo->latest_amounts;
            }

            //退货运费
            foreach ($latestRefundCost as $refundCost){
                $res[$refundCost->mall_id][$refundCost->date]["refund_cost"] =$refundCost->latest_amounts;
            }

            //毛利润
            foreach ($latestProfit as $profitInfo){
                $dayLatestAmounts = 0;
                if(isset($res[$profitInfo->mall_id][$profitInfo->date]["latest_amounts"])){
                    $dayLatestAmounts = $res[$profitInfo->mall_id][$profitInfo->date]["latest_amounts"];
                }
                $dayRefundCost = 0;
                if(isset($res[$profitInfo->mall_id][$profitInfo->date]["refund_cost"])){
                    $dayRefundCost = $res[$profitInfo->mall_id][$profitInfo->date]["refund_cost"];
                }
                //要减掉发货运费和退货运费
               // $res[$profitInfo->mall_id][$profitInfo->date]["latest_profit"] =$profitInfo->latest_profit-$dayLatestAmounts-$dayRefundCost;
                $res[$profitInfo->mall_id][$profitInfo->date]["latest_profit"] =$profitInfo->latest_profit;
            }


            foreach ($allDay as $day){
                foreach ($mallIds as $mallId){
                    if(!isset($res[$mallId][$day]) ||!isset($res[$mallId][$day]["latest_sale_volume"])){
                        $res[$mallId][$day]["latest_sale_volume"]=0;
                    }
                    if(!isset($res[$mallId][$day]) ||!isset($res[$mallId][$day]["latest_sales_money"])){
                        $res[$mallId][$day]["latest_sales_money"] =0;
                    }
                    if(!isset($res[$mallId][$day]) ||!isset($res[$mallId][$day]["latest_profit"])){
                        $res[$mallId][$day]["latest_profit"]=0;
                    }
                    if(!isset($res[$mallId][$day]) || !isset($res[$mallId][$day]["latest_amounts"])){
                        $res[$mallId][$day]["latest_amounts"]=0;
                    }
                    if(!isset($res[$mallId][$day]) || !isset($res[$mallId][$day]["refund_cost"])){
                        $res[$mallId][$day]["refund_cost"]=0;
                    }

                   /* if($res[$mallId][$day]["latest_profit"]>0){
                        $mallInfo = TemuMalls::where("mall_id",$mallId)->first();
//                        $res[$mallId][$day]["net_profit"] =($res[$mallId][$day]["latest_profit"]-$res[$mallId][$day]["latest_amounts"]-$mallInfo->other_cost);
                        $res[$mallId][$day]["net_profit"] =($res[$mallId][$day]["latest_profit"]-$mallInfo->other_cost);
                    }else{
                        $res[$mallId][$day]["net_profit"] =0;
                    }*/
                }
            }
            $data = [];
            $total_statistics =[];
            foreach ($res as $mallId =>&$r){
               ksort($r);
             $data[$mallId]["latest_sale_volume"] = array_column($r,"latest_sale_volume");
             $data[$mallId]["latest_sales_money"] = array_column($r,"latest_sales_money");
             $data[$mallId]["latest_profit"] = array_column($r,"latest_profit");
             $data[$mallId]["latest_amounts"] = array_column($r,"latest_amounts");
             $data[$mallId]["refund_cost"] = array_column($r,"refund_cost");
//             $data[$mallId]["net_profit"] = array_column($r,"net_profit");
             $total_statistics[$mallId]["latest_sale_volume"] = sprintf("%.2f",array_sum($data[$mallId]["latest_sale_volume"]));
             $total_statistics[$mallId]["latest_sales_money"] = sprintf("%.2f",array_sum($data[$mallId]["latest_sales_money"]));
             $total_statistics[$mallId]["latest_profit"] = sprintf("%.2f",array_sum($data[$mallId]["latest_profit"]));
             $total_statistics[$mallId]["latest_amounts"] = sprintf("%.2f",array_sum($data[$mallId]["latest_amounts"]));
             $total_statistics[$mallId]["refund_cost"] = sprintf("%.2f",array_sum($data[$mallId]["refund_cost"]));

//             $total_statistics[$mallId]["net_profit"] = sprintf("%.2f",array_sum($data[$mallId]["net_profit"]));
            }
            $resonse["all_day"] = array_values($allDay);
            $resonse["mall_info"] = $data;
            $resonse["total_statistics"] = $total_statistics;
        }
        return response()->json([
            "status"=>200,
            "data"=>$resonse
        ]);
    }


    public function joinView()
    {
        $user = \Encore\Admin\Facades\Admin::user();
        if(empty($user)){
            return redirect("/admin/login");
        }

        if($user->is_complete_mall_info == 1){
            return redirect("/admin/");
        }
        return view("admin::promotion.join");
    }

    public function shouquan(AdminLayoutContentService $content)
    {
        config(["admin.layout"=>["sidebar-collapse"]]);
        $content = new AdminLayoutContentService();
        $form = new Form(new TemuMalls());
        $form->setTitle("绑定店铺");

        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function (Form\Footer $footer) {
            $footer->view = "admin::promotion.footer";
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
            $footer->disableReset();
        });

        $form->column(10,function ($form){
            // 在这一列中加入表单项
            $user = \Encore\Admin\Facades\Admin::user();
            $form->text('mall_name','店铺名称')->default($user->mall_name);
            $form->text('mall_category','店铺类目')->default($user->mall_category);
        });



        $content = $content->row(function (Row $row)use($form) {
            $row->class("bind_mall");
            $row->column(12, function (Column $column)use($form) {
                $column->row($form->render());
            });
        });
        $css = <<<css
            .bind_mall .row{
            /*margin-left: 16.7% !important;*/
            }
           .bind_mall form
            {
                margin-left: 25% !important;
                margin-top: 13% !important;
            }
            .bind_mall .box-info{
                height: 800px;
                background-image: url("/images/bg.png");
            }
css;
        Admin::style($css);


        $js = <<<JS
        $(".sidebar-toggle").hide();
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
        $("#go_sq").on("click",function() {
            var mall_name = $("#mall_name").val();
            if(mall_name == ""){
                showError("请输入店铺名称");
                return false;
            }
            var mall_category = $("#mall_category").val();
            if(mall_category == ""){
                showError("请输入店铺类目");
                return false;
            }
            $.ajax({
                url:"/admin/temumalls/bindmalls",
                type:"post",
                data:{
                    mall_name:mall_name,
                    mall_category:mall_category,
                    _token:$("input[name='_token']").val()
                },
                success:function (res) {
                    if(res.status !=200){
                        showError(res.data);
                    }else{
                        window.location.href="/admin/manage/invitationcode/join";
                    }
                }
            })
        })
JS;
        Admin::script($js);



        return $content;
    }

    public function thisMonthDataStatistics()
    {
        $userInfo = \Admin::user();
        if($userInfo->source == AdminUser::REGISTER_FROM_ADMIN){
            if($userInfo->isAdministrator()){
                $userAllowMalls = TemuMalls::
                whereRaw("mall_id!=''")->groupBy("mall_id")->get();
            }else{
                $userAllowMalls = TemuMalls::
                where(["type"=>TemuMalls::ADD_FROM_ADMIN])->whereRaw("mall_id!=''")
                    ->groupBy("mall_id")->get();
            }
        }else{
            $userAllowMalls = TemuMalls::
            where(["user_id"=>$userInfo->id])->groupBy("mall_id")->get();
        }
        $resonse = [];
        if(!empty($userAllowMalls)){
            $mallIds = $userAllowMalls->pluck("mall_id")->toArray();
            $requestMallIds = request()->get("mallids");
            $requestMallIdsArr = explode(",",$requestMallIds);
            foreach ($requestMallIdsArr as $mallId){
                if(!in_array($mallId ,$mallIds)){
                    abort(403);
                    return;
                }
            }
            if(!empty($requestMallIds)){
                $mallIds = $requestMallIdsArr;
            }
            $resonse = DashboardService::temuThisMonthSalesStatistics($mallIds);

        }
        return response()->json([
            "status"=>200,
            "data"=>$resonse
        ]);
    }

    public function getMallsHotSales(AdminLayoutContentService $content)
    {
        $userInfo = \Admin::user();
        if($userInfo->source == AdminUser::REGISTER_FROM_ADMIN){
            if($userInfo->isAdministrator()){
                $userAllowMalls = TemuMalls::
                whereRaw("mall_id!=''")->groupBy("mall_id")->get();
            }else{
                $userAllowMalls = TemuMalls::
                where(["type"=>TemuMalls::ADD_FROM_ADMIN])->whereRaw("mall_id!=''")
                    ->groupBy("mall_id")->get();
            }
        }else{
            $userAllowMalls = TemuMalls::
            where(["user_id"=>$userInfo->id])->groupBy("mall_id")->get();
        }
        if(!empty($userAllowMalls)){
            $mallIds = $userAllowMalls->pluck("mall_id")->toArray();
            $requestMallIds = request()->get("mallids");
            $requestMallIdsArr = explode(",",$requestMallIds);
            foreach ($requestMallIdsArr as $mallId){
                if(!in_array($mallId ,$mallIds)){
                    abort(403);
                    return;
                }
            }
            if(!empty($requestMallIds)){
                $mallIds = $requestMallIdsArr;
            }
            echo DashboardService::TemuTodayBestSellingGoodsAndSku($mallIds)->render();die;

        }else{
            echo "";die;
        }
    }
}
