<?php

namespace App\Admin\Controllers;

use App\Compoents\CustomFilterBetween;
use App\Compoents\CustomFilterEqual;
use App\Model\TemuCategory;
use App\Model\TemuGoodsSales;
use App\Model\TemuGoodsSku;
use App\Model\TemuMalls;
use App\Service\AdminLayoutContentService;
use App\Service\TemuDataStatisticsService;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Tree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends AdminController
{
    public function list(AdminLayoutContentService $content)
    {

        return $content->title('分类')
            ->description('列表')
            ->row(function (Row $row){
                // 显示分类树状图
                $row->column(12, $this->treeView()->render());

               /* $row->column(6, function (Column $column){
                    $form = new \Encore\Admin\Widgets\Form();
                    $form->action(admin_url('categories'));
                    $form->select('pid', __('Parent Category'))->options(CategoriesModel::selectOptions());
                    $form->text('cate_name', __('Category Name'))->required();
                    $form->number('sort', __('Asc Sort'))->default(99)->help('越小越靠前');
                    $form->hidden('_token')->default(csrf_token());
                    $column->append((new Box(__('category.new'), $form))->style('success'));
                });*/

            });
    }

    /**
     * 树状视图
     * @return Tree
     */
    protected function treeView()
    {
        return  TemuCategory::tree(function (Tree $tree){
            $tree->disableCreate(); // 关闭新增按钮
            $tree->disableRefresh();
            $tree->disableSave();
            $tree->setView([
                'tree'   => 'admin::tree',
                'branch' => 'admin::category.tree_branch',
            ]);
            $tree->branch(function ($branch) {
                return "<strong>{$branch['category_name']}</strong>"; // 标题添加strong标签
            });
        });
    }


    /**
     * 分类销量统计
     * User: jiahao.dong
     * Date: 2023/5/6
     * Time: 下午9:10
     * @param AdminLayoutContentService $content
     * @return AdminLayoutContentService
     */
    public function salesData(AdminLayoutContentService $content)
    {
        $allMall = TemuMalls::all();

        $grid = new Grid(new TemuGoodsSales());
        $grid->model()->with(["skuInfo","goods"])->selectRaw("sum(`today_sale_volume`) as sum_today_sale_volume,mall_id,category_id,
        sum((`today_sale_volume`+0)*number_of_price_diffrence) as sum_number_of_price_diffrence")
            ->groupByRaw("category_id,mall_id")->orderBy("sum_number_of_price_diffrence","desc")
            ->where("goods_id","!=","");

        $grid->column("店铺名称")->display(function (){
            return $this->mall->mall_name;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("分类名称")->display(function (){
            if($this->category_id == 0){
                return "其它分类";
            }else{
                return $this->category->category_name;
            }
            return $this->mall->mall_name;
        })->style("text-aligin:center;vertical-align:middle");

        $grid->column("sum_today_sale_volume","商品销售数量")->display(function (){
            return $this->sum_today_sale_volume;
        })->style("text-aligin:center;vertical-align:middle")->sortable();

        $grid->column("sum_number_of_price_diffrence","计算差价个数")->display(function (){
            return $this->sum_number_of_price_diffrence;
        })->style("text-aligin:center;vertical-align:middle")->sortable();




        $grid->disableCreateButton();
        $grid->disableColumnSelector();
//        $grid->disableTools();
        $grid->disableRowSelector();
        $grid->disableExport();
//        $grid->disableFilter();
        $grid->expandFilter();

        $grid->disableActions();
        $grid->tools(function (Grid\Tools $tools){
            $tools->disableBatchActions();
        });

        Grid\Filter::extend("between",CustomFilterBetween::class);
        Grid\Filter::extend("equal",CustomFilterEqual::class);
        $grid->filter(function(Grid\Filter $filter)use ($allMall){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->between('temu_goods_sales.created_at', "销售时间范围")->datetime();

            $filter->where(function ($query){
                    $categoryId = request()->get("goods_sku_category_id");
                    if($categoryId>0){
                        $childIds = TemuCategory::where(["pid"=>$categoryId])->pluck("id");
                        if(!empty($childIds->toArray())){
                            $catIds = array_merge($childIds->toArray(),[$categoryId]);
                            return $query->whereIn("category_id",$catIds);
                        }
                    }
                    return $query->where("category_id",$categoryId);
            },"商品分类","goods_sku_category_id")->select(TemuCategory::selectOptions());
          //  $filter->equal("temu_goods_sales.category_id",'商品分类')->select(TemuCategory::selectOptions());
            $filter->in("mall_id",'选择店铺')->multipleSelect($allMall->pluck("mall_name","mall_id"));

        });


        return $content
            ->title("temu 商品分类销量统计")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }

}
