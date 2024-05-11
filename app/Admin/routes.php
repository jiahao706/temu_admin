<?php

use Encore\Admin\Controllers\AuthController;
use Illuminate\Routing\Router;

function registerAdminRoutes()
{
    $attributes = [
        'prefix'     => config('admin.route.prefix'),
        'middleware' => config('admin.route.middleware'),
    ];

    app('router')->group($attributes, function ($router) {

        /* @var \Illuminate\Support\Facades\Route $router */
        $router->namespace('\Encore\Admin\Controllers')->group(function ($router) {

            /* @var \Illuminate\Routing\Router $router */
            $router->resource('auth/roles', 'RoleController')->names('admin.auth.roles');
            $router->resource('auth/permissions', 'PermissionController')->names('admin.auth.permissions');
            $router->resource('auth/menu', 'MenuController', ['except' => ['create']])->names('admin.auth.menu');
            $router->resource('auth/logs', 'LogController', ['only' => ['index', 'destroy']])->names('admin.auth.logs');

            $router->post('_handle_form_', 'HandleController@handleForm')->name('admin.handle-form');
            $router->post('_handle_action_', 'HandleController@handleAction')->name('admin.handle-action');
            $router->get('_handle_selectable_', 'HandleController@handleSelectable')->name('admin.handle-selectable');
            $router->get('_handle_renderable_', 'HandleController@handleRenderable')->name('admin.handle-renderable');
        });

        $router->resource('auth/users', '\App\Admin\Controllers\UserController')->names('admin.auth.users');

        $authController = config('admin.auth.controller', AuthController::class);

        /* @var \Illuminate\Routing\Router $router */
        $router->get('auth/login', $authController.'@getLogin')->name('admin.login');
        $router->post('auth/login', $authController.'@postLogin');
        $router->post('auth/ajxlogin', $authController.'@postAjaxLogin');
        $router->post('auth/ajxregister', $authController.'@postAjaxRegister');
        $router->post('auth/ajxloginpdd', $authController.'@ajxLoginPdd');
        $router->get('auth/logout', $authController.'@getLogout')->name('admin.logout');
        $router->get('auth/setting', $authController.'@getSetting')->name('admin.setting');
        $router->put('auth/setting', $authController.'@putSetting');
        $router->post('auth/user/logoff','\App\Admin\Controllers\UserController@logOff')->name('admin.user.logoff');
    });

}
registerAdminRoutes();

//Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => ['web','AdminRegisgerUserCheck', 'admin'],
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {
    $router->get('/', 'HomeController@index')->name('home');
    $router->resource("sales/temusales","TemusalesController");
    $router->get('statistics/temugoods/todayhot', 'TemuGoodsStatisticsController@todayHotGoods');
    $router->get('statistics/temugoods/hotgoods', 'TemuGoodsStatisticsController@hotGoods');
    $router->get('statistics/temugoods/hotsku', 'TemuGoodsStatisticsController@hotSku');
    $router->get('statistics/temugoods/todayskuhot', 'TemuGoodsStatisticsController@todayHotSku');
    $router->get('statistics/temugoods/senvendayhot', 'TemuGoodsStatisticsController@sevenDayHotGoods');
    $router->get('statistics/temugoods/senvendayskuhot', 'TemuGoodsStatisticsController@sevenDayHotSku');
    $router->get('statistics/temugoods/changeprice', 'TemuGoodsStatisticsController@changePrice');
    $router->get('statistics/temugoods/{id}/edit', 'TemuGoodsStatisticsController@edit');
    //$router->post('statistics/temugoods/update/{id}', 'TemuGoodsStatisticsController@update');
    $router->post('statistics/temugoods/updatesku', 'TemuGoodsStatisticsController@updatesku');
    $router->any('costmanage/collectview', 'CostmanageController@collectView');
    $router->post('costmanage/upload', 'CostmanageController@upload');
    $router->get('costmanage/export', 'CostmanageController@export');
    $router->get('mallmanage/temumalls/malllist','TemuMallsController@mallsList')->name("amin.malllist.index");
    $router->get('mallmanage/temumalls/malllist/create','TemuMallsController@create')->name("amin.malllist.create");
    $router->post('mallmanage/temumalls/malllist/store','TemuMallsController@store')->name("amin.malllist.store");
    $router->get('mallmanage/temumalls/malllist/{id}/edit','TemuMallsController@edit')->name("amin.malllist.edit");
    $router->put('mallmanage/temumalls/malllist/update/{id}','TemuMallsController@update')->name("amin.malllist.update");
    $router->get('mallmanage/temumalls/malllist/{id}','TemuMallsController@show')->name("amin.malllist.show");
    $router->delete('mallmanage/temumalls/malllist/{id}','TemuMallsController@destroy')->name("amin.malllist.delete");
    $router->get('fundmanage/restrict/delivery','FundManageController@deliveryFundRestrictList')->name("amin.fundrestrict.delivery");
    $router->get('fundmanage/restrict/goods_refund_cost','FundManageController@goodsRefundCostList')->name("amin.fundrestrict.goods_refund_cost");
    $router->post('home/latest_data_statistics','HomeController@latestDataStatistics')->name("amin.home.latest_data_statistics");
    $router->get('manage/invitationcode','InvitationCodeController@codeList')->name("amin.invitationcode.list");
    $router->delete('manage/invitationcode/{id}','InvitationCodeController@destroy')->name("amin.invitationcode.delete");
    $router->get('manage/invitationcode/join','HomeController@joinView')->name("amin.invitationcode.join_view");
    $router->post('temumalls/bindmalls','TemuMallsController@bindMallsInfo')->name("amin.temumalls.bindMallsInfo");
    $router->get('manage/invitationcode/shouquan','HomeController@shouquan')->name("amin.invitationcode.shouquan");
    $router->get('category/list','CategoryController@list')->name("amin.category.list");
    $router->get('category/salesdata','CategoryController@salesData')->name("amin.category.sales_data");
    $router->post('home/this_month_data_statistics','HomeController@thisMonthDataStatistics')->name("amin.home.thisMonthDataStatistics");
    $router->post('home/get_malls_hot_sales','HomeController@getMallsHotSales')->name("amin.home.getMallsHotSales");

});
