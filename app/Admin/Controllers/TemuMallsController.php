<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/3
 * Time: 下午5:15
 */

namespace App\Admin\Controllers;

use App\Model\TemuMalls;
use App\Service\AdminLayoutContentService;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TemuMallsController extends AdminController
{
    public function mallsList(AdminLayoutContentService $content)
    {
        $grid = new Grid(new TemuMalls());
        $grid->column("id");
        $grid->column("username","店铺采集账号")->style("vertical-align:middle");

        $grid->column("spider_status_msg","店铺采集状态")->display(function (){
            if($this->spider_status == TemuMalls::SPIDER_DEFAULT){
                $style = "warning";
            }elseif ($this->spider_status == TemuMalls::SPIDER_SUCCESS){
                $style = "success";
            }else{
                $style = "danger";
            }
            return "<span class='label label-{$style}'>$this->spider_status_msg</span>";
        })->style("vertical-align:middle");

        $grid->column("mall_name","店铺名称")->style("vertical-align:middle");
        $grid->column("mall_logo","店铺LOGO")->image("",100,100);
        $grid->column("belongs_to_users","店铺负责人")->display(function (){
            if($this->belongs_to_users == ""){
                return "未填写";
            }else{
                return $this->belongs_to_users;
            }
        })->style("vertical-align:middle;text-align:center;");
        $grid->column("share_ratio","店铺分成比例")->display(function (){
            if($this->share_ratio == 0){
                return "暂无";
            }else{
                return $this->share_ratio."%";
            }
        })->style("vertical-align:middle;text-align:center;");
        $grid->column("other_cost","店铺其它固定成本")->display(function (){
            if($this->other_cost == 0){
                return "暂无";
            }else{
                return $this->other_cost;
            }
        })->style("vertical-align:middle;text-align:center;");
        $grid->column("other_cost_msg","店铺其它固定成本介绍")->display(function (){
            if($this->other_cost_msg == ""){
                return "暂无";
            }else{
                return str_replace(["\r\n","\n"],"<br/>",$this->other_cost_msg);
            }
        })->style("vertical-align:middle;max-width:300px;");
        $grid->column("is_start_spider","是否开启采集")->display(function (){
            if($this->is_start_spider == 0){
                return "暂停采集";
            }else{
                return "开启采集";
            }
        })->style("vertical-align:middle;text-align:center;");

        $grid->column("is_show_in_home","是否在首页展示")->display(function (){
            if($this->is_show_in_home == 1){
                return "展示";
            }else{
                return "不展示";
            }
        })->style("vertical-align:middle;text-align:center;");

        $grid->column("created_at","店铺添加时间")->style("vertical-align:middle");
/*        $grid->disableCreateButton();
        $grid->disableColumnSelector();
        $grid->disableActions();*/
//        $grid->disableTools();
/*        $grid->disableRowSelector();
        */
        $grid->disableExport();
        $grid->disableFilter();

        return $content
            ->title("temu店铺账号信息")
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);

    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(AdminLayoutContentService $content)
    {
        return $content
            ->title("新增店铺")
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($this->form());
    }

    public function edit($id, AdminLayoutContentService $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form($id)->edit($id));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form($id=null)
    {
        $temuMallModel = new TemuMalls();
        $form = new Form($temuMallModel);
        $temuMallTable = $temuMallModel->getTable();
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', "店铺采集账号")
            ->creationRules(['required', "unique:{$connection}.{$temuMallTable},username,NULL,id,deleted_at,NULL","phone"],[
                'username.phone'=>'手机号不合法!',
                'username.unique'=>'账号已经存在!',
            ])
            /*->updateRules(['required', "unique:{$connection}.{$temuMallTable},username,{{id}},id,deleted_at,NULL","phone"],[
                'username.phone'=>'手机号不合法!',
                'username.unique'=>'账号已经存在!',
            ])*/;

        $form->password('password', "店铺采集密码")->rules('required',['required'=>"密码不能为空"]);
        if(!$form->isCreating()){
            $form->display('spider_status_msg','店铺采集状态');
        }
        $form->text("belongs_to_users","店铺负责人");
        $form->text("other_cost","店铺其它固定成本")
            ->creationRules(['numeric'],[
                'other_cost.numeric'=>'成本必须为数字!',
            ])
            ->updateRules(['numeric'],[
                'other_cost.numeric'=>'成本必须为数字!',
            ])
            ->default(0);

        $form->text("share_ratio","店铺分成比例")
            ->creationRules(['numeric',"max:100","min:0"],[
                'share_ratio.numeric'=>'分成比例必须为数字!',
                'share_ratio.max'=>'分成比例最大为100!',
                'share_ratio.min'=>'分成比例最小为0!',
            ])
            ->updateRules(['numeric'],[
                'share_ratio.numeric'=>'分成比例必须为数字!',
                'share_ratio.max'=>'分成比例最大为100!',
                'share_ratio.min'=>'分成比例最小为0!',
            ])
            ->default(0)->append("%");

        $form->textarea("other_cost_msg","店铺其它固定成本介绍");


        $form->select('is_start_spider', "店铺是否采集")->options(TemuMalls::getSpiderStatus());
        $form->select('is_show_in_home', "是否在首页展示")->options(TemuMalls::getShowInHomeStatus());


        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));


        $form->saving(function (Form $form){
            if($form->isCreating()){
                $form->model()->spider_status_msg = "未开始采集";
            }
        });
        $form->saved(function (Form $form){
            admin_toastr(trans('admin.save_succeeded'));
            return redirect("/admin/mallmanage/temumalls/malllist");
        });
        $js = <<<JS
//显示密码
$(document).on("click",".fa-eye-slash",function() {
  $(this).removeClass("fa-eye-slash").addClass("fa-eye");
  $("#password").attr("type","text");
});
//隐藏密码
$(document).on("click",".fa-eye",function() {
    $(this).removeClass("fa-eye").addClass("fa-eye-slash");
    $("#password").attr("type","password");
});
JS;
        Admin::script($js);
        $actionUrl = $form->isCreating()?'/admin/mallmanage/temumalls/malllist/store':
            "/admin/mallmanage/temumalls/malllist/update/".$id;
        return $form->setAction($actionUrl);
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(TemuMalls::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('mall_name', '店铺名称');
        $show->field('mall_logo', '店铺LOGO')->image("",100,100);
        $show->field('username', "店铺采集账号");
        $show->field('password', "店铺采集密码");
        $show->field('spider_status_msg', "店铺采集状态");
        $show->field('belongs_to_users', "店铺所属者");
        $show->field('share_ratio', "店铺其分成比例")->unescape()->as(function ($share_ratio){
            if($share_ratio == 0){
                return "暂无";
            }else{
                return $share_ratio."%";
            }
        });
        $show->field('other_cost', "店铺其它固定成本")->unescape()->as(function ($other_cost){
            if($other_cost == 0){
                return "暂无";
            }else{
                return $other_cost;
            }
        });
        $show->other_cost_msg("店铺其它固定成本介绍")->unescape()->as(function ($other_cost_msg) {
            return str_replace(["\r\n","\n"],"<br/>",$other_cost_msg);
        });
        $show->field('is_start_spider',"店铺是否采集")->as(function ($is_spider){
            if($is_spider == 1){
                return "开始采集";
            }else{
                return "暂停采集";
            }
        });//is_show_in_home
        $show->field('is_show_in_home',"是否在首页展示")->as(function ($is_spider){
            if($is_spider == 1){
                return "展示";
            }else{
                return "不展示";
            }
        });
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    public function bindMallsInfo(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'mall_name'          => 'required',
            'mall_category'          => 'required',
        ],[
            "mall_name.required"=>"店铺名称不能为空!",
            "mall_category.required"=>"店铺类目不能为空!",
        ]);

        if($validate->fails()){
            return $this->jsonError($validate->errors()->first());
        }

        $user = \Auth::user();
        if(empty($user)){
            return $this->jsonError("账号未登录");
        }
        $mallName = $request->get("mall_name");
        $mallCategory = $request->get("mall_category");
        if(mb_strlen($mallName)>=50){
            return $this->jsonError("店铺名称不能超过50个字符");
        }

        if(mb_strlen($mallCategory)>=50){
            return $this->jsonError("店铺类目不能超过50个字符");
        }

        $user->update([
            "mall_name"=>$mallName,
            "mall_category"=>$mallCategory
        ]);

        return $this->jsonSuccess();
    }
}
