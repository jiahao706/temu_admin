<!-- Main Header -->
<header class="main-header">

    <!-- Logo -->
    <a href="{{ admin_url('/') }}" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini">{!! config('admin.logo-mini', config('admin.name')) !!}</span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg">{!! config('admin.logo', config('admin.name')) !!}</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <ul class="nav navbar-nav hidden-sm visible-lg-block">
            {!! Admin::getNavbar()->render('left') !!}
        </ul>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

            {!! Admin::getNavbar()->render() !!}

            <!-- User Account Menu -->
                <li class="dropdown user user-menu">
                    <!-- Menu Toggle Button -->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <!-- The user image in the navbar-->
                        <img src="{{ Admin::user()->avatar }}" class="user-image" alt="User Image">
                        <!-- hidden-xs hides the username on small devices so only the image appears. -->
                        <span class="hidden-xs">{{ Admin::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- The user image in the menu -->
                        <li class="user-header">
                            <img src="{{ Admin::user()->avatar }}" class="img-circle" alt="User Image">

                            <p>
                                {{ Admin::user()->name }}
                                <small>注册时间 {{ Admin::user()->created_at }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                            <div class="pull-left">
                                <a href="{{ admin_url('auth/setting') }}" class="btn btn-default btn-flat">{{ trans('admin.setting') }}</a>
                            </div>
                            <div class="pull-right">
                                <a href="{{ admin_url('auth/logout') }}" class="btn btn-default btn-flat">{{ trans('admin.logout') }}</a>
                            </div>
                            <div class="pull-right">
                                <a href="javascript:void(0);" class="btn btn-default btn-flat" id="zhuxiao">注销账号</a>
                            </div>
                        </li>
                    </ul>
                </li>
                <!-- Control Sidebar Toggle Button -->
                {{--<li>--}}
                {{--<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>--}}
                {{--</li>--}}
            </ul>
        </div>
    </nav>
</header>
<script type="text/javascript">
    $(function (){
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

        $("#zhuxiao").on("click",function (){
            swal({
                title: "操作提示",
                text: `是否确定注销当前账号？`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#4D9BFF",
                cancelButtonText: "取消",
                confirmButtonText: "确定",
                // closeOnConfirm: true,
               // showLoaderOnConfirm: true,
                timer: 0
            }).then( function (isConfirm) {
                var y = isConfirm.value
                if (y == true){
                    $.ajax({
                        url:"/admin/auth/user/logoff",
                        type:"post",
                        data:{
                            _token:LA.token
                        },
                        success:function (res) {
                            if(res.status !=200){
                                showError(res.data);
                            }else{
                                window.location.reload();
                            }
                        }
                    })
                }
            });
        })

    })
</script>
