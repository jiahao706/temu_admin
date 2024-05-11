<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/3
 * Time: 下午5:15
 */

namespace App\Admin\Controllers;

use App\Admin\Actions\InvitationCode\AddCode;
use App\Compoents\CustomFooter;
use App\Compoents\CustomForm;
use App\Model\InvitationCodeManage;
use App\Model\TemuMalls;
use App\Service\AdminLayoutContentService;
use App\Service\DashboardService;
use Encore\Admin\Actions\Action;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;

class InvitationCodeController extends AdminController
{

    public function codeList(AdminLayoutContentService $content)
    {
        $grid = new Grid(new InvitationCodeManage());
        $grid->column("code","邀请码");
        $grid->column("allow_times","允许使用次数");
        $grid->column("curr_times","当前使用次数");
        $grid->disableCreateButton();
        $grid->disableExport();
//        $grid->disableRowSelector();
        $grid->disableColumnSelector();

        $grid->tools(function (\Encore\Admin\Grid\Tools $tools){
            $tools->append(new AddCode());
        });
        $grid->actions(function (Grid\Displayers\Actions $action){
            $action->disableEdit();
            $action->disableView();
        });
        $grid->filter(function (Grid\Filter $filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->equal("code","邀请码");
        });

        return $content
            ->title("邀请码信息")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }

    public function form()
    {
        $model = new InvitationCodeManage();
        return new Form($model);
    }
}
