<?php

namespace App\Http\Middleware;

use App\Model\AdminUser;
use App\Service\DashboardService;
use Encore\Admin\Auth\Permission as Checker;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class AdminRegisgerUserCheck
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param array                    $args
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, ...$args)
    {
        $user = Admin::user();

        $model = config('admin.database.users_model');
        /**
         * @var  AdminUser
         */
        $userModel = new $model;
        if(!empty($user) && !$request->ajax() && !$this->shouldPassThrough($request)){
            if($user->source == $userModel->getFeRegisterTag() && $user->is_complete_mall_info == 0){
                return redirect("/admin/manage/invitationcode/shouquan");
            }
        }

        return $next($request);
    }

    protected function shouldPassThrough($request)
    {
        // 下面这些路由不验证权限
        $excepts = array_merge(config('admin.auth.excepts', []), [
            'auth/login',
            'auth/logout',
            '_handle_action_',
            '_handle_form_',
            '_handle_selectable_',
            '_handle_renderable_',
        ]);

        return collect($excepts)
            ->map('admin_base_path')
            ->contains(function ($except) use ($request) {
                if ($except !== '/') {
                    $except = trim($except, '/');
                }

                return $request->is($except);
            });
    }
}
