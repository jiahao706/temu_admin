<?php

namespace App\Admin\Controllers;

use App\Compoents\CustomFilterEqual;
use App\Model\TemuGoods;
use App\Model\TemuMalls;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class TemusalesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Temu平台销售管理';


    public function index(Content $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("Temu平台销售管理")
                ->description($this->description['index'] ?? trans('admin.list'));
        }

        $goods = TemuGoods::with(["skus"])->whereIn("mall_id",$allMall->pluck("mall_id"))->paginate(10);
         return $content->title("Temu平台销售管理")
            ->description("列表")
            ->view("admin::sales.temusales",["goods"=>$goods]);
    }
}
