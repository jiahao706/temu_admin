<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/2
 * Time: 下午8:37
 */
namespace App\Admin\Controllers;

use App\Model\AdminUser;
use App\Model\TemuMalls;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Hash;

class UserController extends \Encore\Admin\Controllers\UserController
{
    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($this->form());
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);
        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        $form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));
        $form->multipleSelect('mall_permissions', "店铺授权")
            ->options(TemuMalls::where("mall_id","!=","")->get()->pluck('mall_name', 'mall_id'));
        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        return $form;
    }

    public function logOff(Content $content)
    {
        $userInfo = \Admin::user();
        if($userInfo->isAdministrator()){
            return $this->jsonError("管理员账号不能注销！");
        }else{
            $res = AdminUser::where(["id"=>$userInfo->id])->delete();
            if($res){
                return $this->jsonSuccess();
            }else{
                return $this->jsonError("账号注销失败！");
            }
        }
    }


    public function jsonSuccess($msg="success",$status=200,$data="")
    {
        return response()->json([
            "status"=>$status,
            "msg"=>$msg,
            "data"=>$data
        ]);
    }

    public function jsonError($data,$status=500,$msg="error")
    {
        return response()->json([
            "status"=>$status,
            "msg"=>$msg,
            "data"=>$data
        ]);
    }
}
