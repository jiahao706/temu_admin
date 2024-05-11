<?php

namespace App\Admin\Controllers;


use App\Model\AdminUser;
use App\Model\TemuGoodsSales;
use App\Model\TemuMalls;
use App\Service\AdminLayoutContentService;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;

class PerformanceManageController extends AdminController
{
    public function monthBonusOfMalls(AdminLayoutContentService $content)
    {
        $userInfo = \Admin::user();
        if($userInfo->source != AdminUser::REGISTER_FROM_ADMIN){
            abort(403);
            return;
        }


        $grid = new Grid(new TemuMalls());
        $grid->model()->where(['type'=>TemuMalls::ADD_FROM_ADMIN]);

        $grid->filter(function(Grid\Filter $filter){
            // 在这里添加字段过滤器
                        $filter->between('temu_goods_sales.created_at', "销售时间范围")->datetime();

            //$filter->month("month","时间筛选");
//            $filter->between('temu_goods_sales.created_at', "销售时间范围")->datetime();
        });

       /* $grid->column("店铺名称",function (){

        });

        $grid->column("当月销量",function (){

        });

        $grid->column("当月营业额",function (){

        });

        $grid->column("当月毛利润",function (){

        });

        $grid->column("当月发货运费",function (){

        });

        $grid->column("当月退货运费",function (){

        });

        $grid->column("其它成本",function (){

        });

        $grid->column("奖金",function (){

        });*/


        return $content
            ->title('店铺月度奖金')
            ->body($grid);
    }
}
