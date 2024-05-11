<html lang="zh-CN"><head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>跨境电商管理系统</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/vendor/laravel-admin/sweetalert2/dist/sweetalert2.css">
    <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>
<body>
<div class="relative min-h-screen flex">
    <div class="
                    flex flex-col
                    sm:flex-row
                    items-center
                    md:items-start
                    sm:justify-center
                    md:justify-start
                    flex-auto
                    min-w-0
                    bg-white
                ">
        <div class="
                        sm:w-1/2
                        xl:w-3/5
                        h-full
                        hidden
                        md:flex
                        flex-auto
                        items-center
                        justify-center
                        p-10
                        overflow-hidden
                        bg-purple-900
                        text-white
                        bg-no-repeat bg-cover
                        relative
                    " style="background-image: url(/images/tuiguang_bg.jpg)">
            <div class="
                            absolute
                            bg-gradient-to-b
                            from-indigo-600
                            to-blue-500
                            opacity-75
                            inset-0
                            z-0
                        "></div>
            <div class="w-full max-w-md z-10">
                <div class="sm:text-4xl xl:text-5xl font-bold leading-tight mb-6">跨境电商管理系统</div>
                <div class="sm:text-sm xl:text-md text-gray-200 font-normal">欢迎来到跨境电商卖家中心第三方管理系统,祝您使用愉快...</div>
            </div>
            <ul class="circles">
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
            </ul>
        </div>
        <div class="
                        md:flex md:items-center md:justify-center
                        w-full
                        sm:w-auto
                        md:h-full
                        w-2/5
                        xl:w-2/5
                        p-8
                        md:p-10
                        lg:p-14
                        sm:rounded-lg
                        md:rounded-none
                        bg-white
                    ">
            <div class="max-w-md w-full mx-auto space-y-8" id="login_box">
                <div class="text-center">
                    <h2 class="mt-6 text-3xl font-bold text-gray-900">欢迎登录系统</h2>
                </div>
                <div class="flex items-center justify-center space-x-2">
                    <span class="h-px w-16 bg-gray-200"></span>
                    <span class="text-gray-300 font-normal">填写账号登录信息</span>
                    <span class="h-px w-16 bg-gray-200"></span>
                </div>
{{--                <form class="mt-8 space-y-6" action="#" method="POST">--}}
                    <input type="hidden" name="remember" value="true">
                    <div class="relative">
{{--                        <label class="ml-3 text-sm font-bold text-gray-700 tracking-wide">用户名</label>--}}
                        <input class="
                                        w-full
                                        text-base
                                        px-4
                                        py-2
                                        border-b border-gray-300
                                        focus:outline-none
                                        rounded-2xl
                                        focus:border-indigo-500
                                        username
                                    " type="" placeholder="请输入用户名">
                    </div>
                    <div class="mt-8 content-center">
{{--                        <label class="ml-3 text-sm font-bold text-gray-700 tracking-wide">密码</label>--}}
                        <input class="
                                        w-full
                                        content-center
                                        text-base
                                        px-4
                                        py-2
                                        border-b
                                        rounded-2xl
                                        border-gray-300
                                        focus:outline-none focus:border-indigo-500
                                        password
                                    " type="password" placeholder="请输入密码">
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" name="remember_me" type="checkbox" class="
                                            h-4
                                            w-4
                                            bg-blue-500
                                            focus:ring-blue-400
                                            border-gray-300
                                            rounded
                                            checked
                                        " checked="checked">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-900">记住我</label>
                        </div>
{{--
                        <div class="text-sm">
                            <a href="#" class="text-indigo-400 hover:text-blue-500">忘记密码？</a>
                        </div>
--}}
                    </div>
                    <div>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button  class="
                                        w-full
                                        flex
                                        justify-center
                                        bg-gradient-to-r
                                        from-indigo-500
                                        to-blue-600
                                        hover:bg-gradient-to-l
                                        hover:from-blue-500
                                        hover:to-indigo-600
                                        text-gray-100
                                        p-4
                                        rounded-full
                                        tracking-wide
                                        font-semibold
                                        shadow-lg
                                        cursor-pointer
                                        transition
                                        ease-in
                                        duration-500
                                    " id="login_btn">登 录</button>
                    </div>
                    <p class="
                                    items-center
                                    justify-center
                                    mt-10
                                    text-center text-md text-gray-500
                                ">
                        <span>还没有账号？</span>
                        <a href="javascript:void(0);" class="
                                        text-indigo-400
                                        hover:text-blue-500
                                        no-underline
                                        hover:underline
                                        cursor-pointer
                                        transition
                                        ease-in
                                        duration-300
                                    " id="go_register">立即注册</a>
                    </p>
{{--
                </form>
--}}
            </div>
            <div class="max-w-md w-full mx-auto space-y-8" id="register_box" style="display: none;">
                <div class="text-center">
                    <h2 class="mt-6 text-3xl font-bold text-gray-900">欢迎注册系统</h2>
                </div>
                <div class="flex items-center justify-center space-x-2">
                    <span class="h-px w-16 bg-gray-200"></span>
                    <span class="text-gray-300 font-normal">填写账号注册信息</span>
                    <span class="h-px w-16 bg-gray-200"></span>
                </div>
{{--                <form class="mt-8 space-y-6" action="#" method="POST">--}}
                    <div class="relative">
                        <label class="ml-3 text-sm font-bold text-gray-700 tracking-wide">用户名</label>
                        <input class="
                                        w-full
                                        text-base
                                        px-4
                                        py-2
                                        border-b border-gray-300
                                        focus:outline-none
                                        rounded-2xl
                                        focus:border-indigo-500
                                        username
                                    " type="" placeholder="请输入用户名">
                    </div>
                    <div class="mt-8 content-center">
                        <label class="ml-3 text-sm font-bold text-gray-700 tracking-wide">密码</label>
                        <input class="
                                        w-full
                                        content-center
                                        text-base
                                        px-4
                                        py-2
                                        border-b
                                        rounded-2xl
                                        border-gray-300
                                        focus:outline-none focus:border-indigo-500
                                        password
                                    " type="" placeholder="请输入密码">
                    </div>
                    <div class="mt-8 content-center">
                        <label class="ml-3 text-sm font-bold text-gray-700 tracking-wide">手机号码</label>
                        <input class="
                                        w-full
                                        content-center
                                        text-base
                                        px-4
                                        py-2
                                        border-b
                                        rounded-2xl
                                        border-gray-300
                                        focus:outline-none focus:border-indigo-500
                                        phone
                                    " type="" placeholder="请输入手机号码">
                    </div>
                    <div class="mt-8 content-center">
                        <label class="ml-3 text-sm font-bold text-gray-700 tracking-wide">邀请码</label>
                        <input class="
                                        w-full
                                        content-center
                                        text-base
                                        px-4
                                        py-2
                                        border-b
                                        rounded-2xl
                                        border-gray-300
                                        focus:outline-none focus:border-indigo-500
                                        invite_code
                                    " type="" placeholder="请输入邀请码">
                    </div>
                    <div>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button type="submit" class="
                                        w-full
                                        flex
                                        justify-center
                                        bg-gradient-to-r
                                        from-indigo-500
                                        to-blue-600
                                        hover:bg-gradient-to-l
                                        hover:from-blue-500
                                        hover:to-indigo-600
                                        text-gray-100
                                        p-4
                                        rounded-full
                                        tracking-wide
                                        font-semibold
                                        shadow-lg
                                        cursor-pointer
                                        transition
                                        ease-in
                                        duration-500
                                    " id="register_btn">注册</button>
                    </div>
                    <p class="
                                    items-center
                                    justify-center
                                    mt-10
                                    text-center text-md text-gray-500
                                ">
                        <span>有账号</span>
                        <a href="javascript:void(0);" class="
                                        text-indigo-400
                                        hover:text-blue-500
                                        no-underline
                                        hover:underline
                                        cursor-pointer
                                        transition
                                        ease-in
                                        duration-300
                                    " id="go_login">去登录</a>
                    </p>
{{--                </form>--}}
            </div>

        </div>
    </div>
</div>
<!-- jQuery 2.1.4 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js")}}"></script>
<!-- Bootstrap 3.3.5 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js")}}"></script>
<script src="{{ admin_asset("vendor/laravel-admin/sweetalert2/dist/sweetalert2.min.js")}}"></script>
<script>
    $(function () {

        function showError(msg){
            swal({
                    title: msg,
                    type: 'error',
                    showCancelButton: false,
                    showConfirmButton: false,
                    toast:true,
                    position:"top",
                    width: "300px",
                    padding: "10px"
                }
            );
        }

        function showSuccess(msg){
            swal({
                    title: msg,
                    type: 'success',
                    showCancelButton: false,
                    showConfirmButton: false,
                    toast:true,
                    position:"top",
                    width: "300px",
                    padding: "10px"
                }
            );
        }
        $(document).keydown(function(event){
            if(event.keyCode==13){
                //回车事件的处理代码
                $("#login_btn").click();
            }
        });


        $("#go_register").click(function () {
            $("#login_box").hide();
            $("#register_box").show();
        })

        $("#go_login").click(function () {
            $("#register_box").hide()
            $("#login_box").show()
        })

        $("#login_btn").click(function () {
            var uname = $("#login_box .username").val();
            if(uname == ""){
                showError("用户名不能为空");
                return false
            }

            var password = $("#login_box .password").val();
            if(password == ""){
                showError("密码不能为空");
                return false;
            }
            var isRemember = $("#remember_me").is(':checked');
            $.ajax({
                url:"/admin/auth/ajxlogin",
                type:"post",
                data:{
                    username:uname,
                    password:password,
                    remember:isRemember?1:0,
                    _token:$("#login_box input[name='_token']").val()
                },
                success:function (res) {
                    if(res.status !=200){
                        showError(res.data);
                    }else{
                        window.location.href="/admin";
                    }
                }
            })
        })

        function isPhone(str){
            var reg=/^1[3456789]{1}\d{9}$/;  /*定义验证表达式*/
            return reg.test(str);   /*进行验证*/
        }

        $("#register_btn").click(function () {
            var uname = $("#register_box .username").val();
            if(uname == ""){
                showError("用户名不能为空");
                return false
            }

            var password = $("#register_box .password").val();
            if(password == ""){
                showError("密码不能为空");
                return false;
            }

            var phone = $("#register_box .phone").val();
            if(phone == ""){
                showError("手机号码不能为空");
                return false;
            }else{
                if(!isPhone(phone)){
                    showError("手机号码格式不正确!");
                    return false;
                }
            }
            var invite_code = $("#register_box .invite_code").val();
            if(invite_code == ""){
                showError("邀请码不能为空");
                return false;
            }

            $.ajax({
                url:"/admin/auth/ajxregister",
                type:"post",
                data:{
                    username:uname,
                    password:password,
                    phone:phone,
                    invite_code:invite_code,
                    _token:$("#register_box input[name='_token']").val()
                },
                success:function (res) {
                    if(res.status !=200){
                        showError(res.data);
                    }else{
                        showSuccess("恭喜您,注册成功!");
                    }
                }
            })
        })
    })
</script>


</body></html>
