<?php
/**
 * User: jiahao.dong
 * Date: 2023/4/19
 * Time: 上午1:28
 */

namespace App\Service;

use App\Model\TemuMalls;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Arr;

class DashboardService{

    private static $sortMall;

    public static function getSortMall()
    {
        return self::$sortMall;
    }

    public static function title()
    {
        return view('admin::dashboard.custom_title');
    }

    public static function environment()
    {
        $envs = [
            ['name' => 'PHP version',       'value' => 'PHP/'.PHP_VERSION],
            ['name' => 'Laravel version',   'value' => app()->version()],
            ['name' => 'CGI',               'value' => php_sapi_name()],
            ['name' => 'Uname',             'value' => php_uname()],
            ['name' => 'Server',            'value' => Arr::get($_SERVER, 'SERVER_SOFTWARE')],

            ['name' => 'Cache driver',      'value' => config('cache.default')],
            ['name' => 'Session driver',    'value' => config('session.driver')],
            ['name' => 'Queue driver',      'value' => config('queue.default')],

            ['name' => 'Timezone',          'value' => config('app.timezone')],
            ['name' => 'Locale',            'value' => config('app.locale')],
            ['name' => 'Env',               'value' => config('app.env')],
            ['name' => 'URL',               'value' => config('app.url')],
        ];

        return view('admin::dashboard.custom_environment', compact('envs'));
    }

    public static function temuSalesStatistics($userAllowMalls)
    {
        if(!empty($userAllowMalls)){
            $mallIds = $userAllowMalls->pluck("mall_id")->toArray();

            $todaySalesNum = TemuDataStatisticsService::getTodaySalesNum($mallIds);

            $todayProfit = TemuDataStatisticsService::getTodayProfit($mallIds);
            // $thisMonthProfit = TemuDataStatisticsService::getThisMonthProfit($mallIds);
            $todaySalesVolume = TemuDataStatisticsService::getTodaySalesVolume($mallIds);
            $todayDeliveryRestrict = TemuDataStatisticsService::getTodayDeliveryRestrict($mallIds);
            // $thisMonthDeliveryRestrict = TemuDataStatisticsService::getThisMonthDeliveryRestrictNum($mallIds);
            $todayRefundCost = TemuDataStatisticsService::getTodayRefundCost($mallIds);
            //  $thisMonthRefundCost = TemuDataStatisticsService::getThisMonthRefundCostNum($mallIds);
            //不合理库存成本
            //$mallsUnreasonableInventoryCost = TemuDataStatisticsService::getTotalUnreasonableInventory($mallIds);
            $mallsUnreasonableInventoryCost = [];
            $sortres = [];
            $nosortRes = [];
            $res = [];
            foreach ($userAllowMalls as $mall){
                $_tmp = [];
                $_tmp["todaySalesNum"] = isset($todaySalesNum[$mall->mall_id])?intval($todaySalesNum[$mall->mall_id]):"";
                $_tmp["todaySalesVolume"] = isset($todaySalesVolume[$mall->mall_id])?sprintf("%.2f",$todaySalesVolume[$mall->mall_id]):"";
                //今日毛利
                $_tmp["todayProfit"] = isset($todayProfit[$mall->mall_id])?sprintf("%.2f",$todayProfit[$mall->mall_id]):0;
                //当月毛利
                // $_tmp["thisMonthProfit"] = isset($thisMonthProfit[$mall->mall_id])?sprintf("%.2f",$thisMonthProfit[$mall->mall_id]):"";
                //当日发货运费
                $_tmp["todayDeliveryRestrict"] = isset($todayDeliveryRestrict[$mall->mall_id])?sprintf("%.2f",$todayDeliveryRestrict[$mall->mall_id]):0;
                //当月发货运费
                // $_tmp["thisMonthDeliveryRestrict"] = isset($thisMonthDeliveryRestrict[$mall->mall_id])?sprintf("%.2f",$thisMonthDeliveryRestrict[$mall->mall_id]):0;

                //当日退货运费
                $_tmp["todayRefundCost"] = isset($todayRefundCost[$mall->mall_id])?sprintf("%.2f",$todayRefundCost[$mall->mall_id]):0;
                //当月退货运费
                //$_tmp["thisMonthRefundCost"] = isset($thisMonthRefundCost[$mall->mall_id])?sprintf("%.2f",$thisMonthRefundCost[$mall->mall_id]):0;
                //人工和其它成本
                $_tmp["otherCost"] = $mall->other_cost;

                $_tmp["lastSpiderTime"] = $mall->last_spider_time;
                $_tmp["mallName"] = $mall->mall_name;
                $_tmp["mallId"] = $mall->mall_id;
                $_tmp["mallDetail"] = $mall;
                //不合理库存
                $_tmp["unreasonableInventoryCost"] = isset($mallsUnreasonableInventoryCost[$mall->mall_id])?sprintf("%.2f",$mallsUnreasonableInventoryCost[$mall->mall_id]):0;

                //当天毛利润没减发货运费和退货运费
                // $_tmp["todayProfit"] = $_tmp["todayProfit"]-$_tmp["todayDeliveryRestrict"]-$_tmp["todayRefundCost"];
                $_tmp["todayProfit"] = $_tmp["todayProfit"];

                //本月预估净利润
                $_tmp["netProfit"] = null;
                if(!empty($_tmp["thisMonthProfit"])){
                    // $_tmp["netProfit"] = $_tmp["todayProfit"]-$_tmp["otherCost"]-$_tmp["todayDeliveryRestrict"];
                    //毛利润-发货运费-退货运费-不合理库存-人工
                    // $_tmp["netProfit"] = $_tmp["thisMonthProfit"]-$_tmp["thisMonthDeliveryRestrict"]-$_tmp["thisMonthRefundCost"]-$_tmp["unreasonableInventoryCost"]-$_tmp["otherCost"];
                    //$_tmp["netProfit"] = $_tmp["thisMonthProfit"];
                }else{
                    $_tmp["netProfit"] =0;
                }

                //本月预估奖金
                $_tmp["estimateCommission"] = $_tmp["netProfit"]*0.03;
                /* $_tmp["estimateCommission"] = null;
                 if(!empty($_tmp["netProfit"]) && $_tmp["netProfit"]>0){
                     $_tmp["estimateCommission"] = $_tmp["netProfit"]*0.03;
                 }else{
                     $_tmp["estimateCommission"] = 0;
                 }*/
                $res[] = $_tmp;

                /* if($_tmp["netProfit"] == 0 && $_tmp["todaySalesVolume"] ==0){
                     $nosortRes[] = $_tmp;
                 }else{
                     $sortres[] = $_tmp;
                 }*/
            }
            //按照今日销量排名
            array_multisort(
            //array_column($sortres,"netProfit"),SORT_DESC,
                array_column($res,"todayProfit"),SORT_DESC,
                $res);
            self::$sortMall = $res;
            return view('admin::dashboard.temu_sales_statistics', [
                "res"=>self::$sortMall,
            ]);
        }
    }

    public static function temuThisMonthSalesStatistics($mallIds)
    {
        $res = [];

        if(!empty($mallIds)){
            $mallsInfo = TemuMalls::whereIn("mall_id",$mallIds)->get();
            $thisMonthProfit = TemuDataStatisticsService::getThisMonthProfit($mallIds);
            $thisMonthDeliveryRestrict = TemuDataStatisticsService::getThisMonthDeliveryRestrictNum($mallIds);
            $thisMonthRefundCost = TemuDataStatisticsService::getThisMonthRefundCostNum($mallIds);
            //不合理库存成本
            $mallsUnreasonableInventoryCost = TemuDataStatisticsService::getTotalUnreasonableInventory($mallIds);
            foreach ($mallsInfo as $mall){
                $_tmp = [];
                //当月毛利
                $_tmp["thisMonthProfit"] = isset($thisMonthProfit[$mall->mall_id])?(float)sprintf("%.2f",$thisMonthProfit[$mall->mall_id]):"";
                //当月发货运费
                $_tmp["thisMonthDeliveryRestrict"] = isset($thisMonthDeliveryRestrict[$mall->mall_id])?(float)sprintf("%.2f",$thisMonthDeliveryRestrict[$mall->mall_id]):0;

                //当月退货运费
                $_tmp["thisMonthRefundCost"] = isset($thisMonthRefundCost[$mall->mall_id])?(float)sprintf("%.2f",$thisMonthRefundCost[$mall->mall_id]):0;

                //不合理库存
                $_tmp["unreasonableInventoryCost"] = isset($mallsUnreasonableInventoryCost[$mall->mall_id])?(float)sprintf("%.2f",$mallsUnreasonableInventoryCost[$mall->mall_id]):0;

                //人工和其它成本
                $_tmp["otherCost"] = $mall->other_cost;

                //本月预估净利润
                $_tmp["netProfit"] = null;
                if(!empty($_tmp["thisMonthProfit"])){
                    //毛利润-发货运费-退货运费-不合理库存-人工
                    $_tmp["netProfit"] = $_tmp["thisMonthProfit"]-$_tmp["thisMonthDeliveryRestrict"]-$_tmp["thisMonthRefundCost"]-$_tmp["unreasonableInventoryCost"]-$_tmp["otherCost"];
                    $_tmp["netProfit"] = (float)sprintf("%.2f",$_tmp["netProfit"]);
                }else{
                    $_tmp["netProfit"] =0;
                }

                //本月预估奖金
                $_tmp["estimateCommission"] = (float)sprintf("%.2f",$_tmp["netProfit"]*0.03);
                $res[$mall->mall_id] = $_tmp;

            }
        }
        return $res;
    }

    public static function TemuTodayBestSellingGoodsAndSku($mallId)
    {
        $mall = TemuMalls::where("mall_id",$mallId)->first()->toArray();
        $goodsSalesInfo = TemuDataStatisticsService::getTodayBestsellingGoods($mallId);
        $skusSalesInfo = TemuDataStatisticsService::getTodayBestsellingSku($mallId);

        return view('admin::dashboard.temu_today_best_selling_goods', [
            "goodsSalesInfo"=>$goodsSalesInfo,
            "skusSalesInfo"=>$skusSalesInfo,
            "mall"=>$mall,
        ]);
    }



    public static function TemuTodayBestsellingGoods($mall)
    {
        $goodsSalesInfo = TemuDataStatisticsService::getTodayBestsellingGoods($mall["mallId"]);
        return view('admin::dashboard.temu_today_best_selling_goods', [
            "salesInfo"=>$goodsSalesInfo,
            "mall"=>$mall["mallDetail"],
        ]);
    }

    public static function TemuTodayBestsellingSku($mall)
    {
        $goodsSalesInfo = TemuDataStatisticsService::getTodayBestsellingSku($mall["mallId"]);
        return view('admin::dashboard.temu_today_best_selling_sku', [
            "salesInfo"=>$goodsSalesInfo,
            "mall"=>$mall["mallDetail"]
        ]);
    }

    public static function Temu7dayBestsellingGoods()
    {
        $goodsSalesInfo = TemuDataStatisticsService::get7dayBestsellingGoods();
        return view('admin::dashboard.temu_7day_best_selling_goods', [
            "salesInfo"=>$goodsSalesInfo,
        ]);
    }
    public static function Temu7dayBestsellingSku()
    {
        $goodsSalesInfo = TemuDataStatisticsService::get7dayBestsellingSku();
        return view('admin::dashboard.temu_7day_best_selling_sku', [
            "salesInfo"=>$goodsSalesInfo,
        ]);
    }

    public static function TemuTodayUnsableGoods()
    {
        $goodsSalesInfo = TemuDataStatisticsService::getTodayUnsableGoods();
        return view('admin::dashboard.temu_today_unsable_goods', [
            "salesInfo"=>$goodsSalesInfo,
        ]);
    }

    public static function TemuTodayUnsableGoodsSku()
    {
        $goodsSalesInfo = TemuDataStatisticsService::getTodayUnsableSku();
        return view('admin::dashboard.temu_today_unsable_sku', [
            "salesInfo"=>$goodsSalesInfo,
        ]);
    }

    public static function Temu7dayUnsableGoods()
    {
        $goodsSalesInfo = TemuDataStatisticsService::get7dayUnsableGoods();
        return view('admin::dashboard.temu_7day_unsable_goods', [
            "salesInfo"=>$goodsSalesInfo,
        ]);
    }

    public static function showBindMallView()
    {
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
            $user = Admin::user();
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

}
