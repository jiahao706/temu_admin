<?php

namespace App\Admin\Controllers;


use App\Compoents\Common;
use App\Model\AdminRoleUsers;
use App\Model\AdminUser;
use App\Model\InvitationCodeManage;
use App\Model\TemuMalls;
use App\Service\AdminLayoutContentService;
use App\Service\SpiderService;
use Encore\Admin\Auth\Database\Role;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * @var string
     */
//    protected $loginView = 'admin::login';
    protected $loginView = 'admin::custom_login.login';

    /**
     * Show the login page.
     *
     * @return \Illuminate\Contracts\View\Factory|Redirect|\Illuminate\View\View
     */
    public function getLogin()
    {
        if ($this->guard()->check()) {
            return redirect($this->redirectPath());
        }

        return view($this->loginView);
    }

    /**
     * Handle a login request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        $this->loginValidator($request->all())->validate();

        $credentials = $request->only([$this->username(), 'password']);
        $remember = $request->get('remember', false);

        if ($this->guard()->attempt($credentials, $remember)) {
            return $this->sendLoginResponse($request);
        }

        return back()->withInput()->withErrors([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    /**
     * Get a validator for an incoming login request.
     *
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function loginValidator(array $data)
    {
        return Validator::make($data, [
            $this->username()   => 'required',
            'password'          => 'required',
        ],[
            $this->username().".required"=>"用户名不能为空",
            "password.required"=>"密码不能为空",
        ]);
    }

    /**
     * User logout.
     *
     * @return Redirect
     */
    public function getLogout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect(config('admin.route.prefix'));
    }

    /**
     * User setting page.
     *
     * @param AdminLayoutContentService $content
     *
     * @return AdminLayoutContentService
     */
    public function getSetting(AdminLayoutContentService $content)
    {
        $form = $this->settingForm();
        $form->tools(
            function (Form\Tools $tools) {
                $tools->disableList();
                $tools->disableDelete();
                $tools->disableView();
            }
        );

        return $content
            ->title(trans('admin.user_setting'))
            ->body($form->edit(Admin::user()->id));
    }

    /**
     * Update user setting.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSetting()
    {
        return $this->settingForm()->update(Admin::user()->id);
    }

    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {
        $class = config('admin.database.users_model');

        $form = new Form(new $class());

        $form->display('username', trans('admin.username'));
        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('confirmed|required');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->setAction(admin_url('auth/setting'));

        $form->ignore(['password_confirmation']);

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        $form->saved(function () {
            admin_toastr(trans('admin.update_succeeded'));

            return redirect(admin_url('auth/setting'));
        });

        return $form;
    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getFailedLoginMessage()
    {
        return Lang::has('auth.failed')
            ? trans('auth.failed')
            : '账号密码错误';
    }

    /**
     * Get the post login redirect path.
     *
     * @return string
     */
    protected function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : config('admin.route.prefix');
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        admin_toastr(trans('admin.login_successful'));

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    protected function username()
    {
        return 'username';
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Admin::guard();
    }

    public function postAjaxLogin(Request $request)
    {
        $validate = $this->loginValidator($request->all());
        if($validate->fails()){
            return $this->jsonError($validate->errors()->first());
        }

        $credentials = $request->only([$this->username(), 'password']);
        $remember = $request->get('remember', false);

        if ($this->guard()->attempt($credentials, $remember)) {
            admin_toastr(trans('admin.login_successful'));

            $request->session()->regenerate();

            return $this->jsonSuccess();
        }
        return $this->jsonError($this->getFailedLoginMessage());
    }

    public function postAjaxRegister(Request $request)
    {
        $connection = config('admin.database.connection');
        $userTable = config('admin.database.users_table');

        $validate = Validator::make($request->all(), [
            $this->username()   => "required|unique:{$connection}.{$userTable},username",
            'password'          => 'required|between:6,24',
            'phone'          => 'required|phone',
            'invite_code'          => 'required',
        ],[
            $this->username().".required"=>"用户名不能为空!",
            $this->username().".unique"=>"用户名已经存在!",
            "password.required"=>"密码不能为空!",
            "password.between"=>"密码长度必须为6~24位!",
            "phone.required"=>"手机号码不能为空!",
            "phone.phone"=>"手机号码格式不正确!",
            "invite_code.required"=>"邀请码不能为空!",
        ]);

        if($validate->fails()){
            return $this->jsonError($validate->errors()->first());
        }
        $inviteCode = $request->get("invite_code");
        $inviteCodeInfo = InvitationCodeManage::where(["code"=>$inviteCode])->first();
        if(empty($inviteCodeInfo)){
            return $this->jsonError("邀请码不存在，请重新输入!");
        }
        if($inviteCodeInfo->curr_times+1>$inviteCodeInfo->allow_times){
            return $this->jsonError("邀请码使用次数已达上限,请更换邀请码!");
        }

        $model = config('admin.database.users_model');

        /**
         * @var  AdminUser
         */
        $userModel = new $model;
        $createUser = $userModel->create([
            "username"=>$request->get("username"),
            "password"=>Hash::make($request->get("password")),
            "name"=>$request->get("username"),
            "phone"=>$request->get("phone"),
            "source"=>$userModel->getFeRegisterTag(),
            "invite_code"=>$inviteCode,
        ]);
        if($createUser){
            $inviteCodeInfo->increment("curr_times");
        }
        return $this->jsonSuccess();
    }

    public function ajxLoginPdd(Request $request)
    {
        $user = Admin::user();
        if(empty($user)){
            return $this->jsonError("账号未登录!");
        }
        $validate = Validator::make($request->all(), [
             "username"   => "required|phone",
            'password'          => 'required',
        ],[
            "username.required"=>"手机号不能为空!",
            "username.phone"=>"手机号码格式不正确!",
            "password.required"=>"密码不能为空!",
        ]);

        if($validate->fails()){
            return $this->jsonError($validate->errors()->first());
        }
        $username = $request->get("username");
        $password = $request->get("password");
        $mall = TemuMalls::where([
            "username"=>$username,
            "user_id"=>$user->id,
        ])->first();
        if(!empty($mall)){
            return $this->jsonError("店铺已绑定其它账号!");
        }

        $driverPath = "/usr/bin/chromedriver";
        $browserPath = "/usr/bin/google-chrome";
        $driverStartPos = 20000;
        $driverPort = $driverStartPos+$user->id;
        $debugStartPos = 30000;
        $debugPort = $debugStartPos+$user->id;

        $spiderService = new SpiderService();
        exec("ps -ef|grep 'port=$driverPort'|awk '{print $2}'|xargs kill -9",$output);
        exec("sudo nohup $driverPath --port=$driverPort --allowed-ips='0.0.0.0' --allowed-origins=* --remote-debugging-port=$debugPort > /dev/null &");
        sleep(7);
        $spiderService->startChrome($driverPort, $browserPath);

        //尝试登录temu 后台看是否成功,并添加用户店铺账号信息
        $loginInfo = $spiderService->userTestLogin($username,$password,$user->id);
        try {
            $spiderService->driver->quit();
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
        if (!empty($loginInfo["errorCode"]) && $loginInfo["errorCode"] != 1000000) {
            return $this->jsonError($loginInfo["errorMsg"]);
        }else{
            //登录成功 设置用户权限
            $roleModelString = config("admin.database.roles_model");
            $roleModel = new $roleModelString;
            //给用户设置运营者权限
            $operateRoleInfo = $roleModel->where(["slug"=>"mall.operate.user.roles"])->first();
            if(!empty($operateRoleInfo)){
                AdminRoleUsers::create([
                   "role_id"=>$operateRoleInfo->id,
                    "user_id"=>$user->id
                ]);
            }
            //修改用户已完成注册标记
            $user->update(["is_complete_mall_info"=>1]);

        }

        return $this->jsonSuccess();
    }
}
