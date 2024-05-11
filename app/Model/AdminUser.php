<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/3
 * Time: 上午10:47
 */

namespace App\Model;

use Encore\Admin\Auth\Database\Administrator;

class AdminUser extends Administrator
{
    const REGISTER_FROM_ADMIN = 0;

    const REGISTER_FROM_FE = 1;


    public $fillable = [
        'username',
        'password',
        'name',
        'avatar',
        'phone',
        'source',
        'is_complete_mall_info',
        'mall_name',
        'mall_category',
        'invite_code',
    ];


    /**
     * 一个用户属于多个店铺
     * User: jiahao.dong
     * Date: 2023/5/3
     * Time: 上午1:42
     * @return BelongsToMany
     */
    public function mall_permissions()
    {
        $pivotTable = AdminUserMallPermissions::class;
        $relatedModel = TemuMalls::class;

        return $this->belongsToMany($relatedModel, $pivotTable, "user_id", "mall_id", null, "mall_id")->withTimestamps();
    }

    public function getFeRegisterTag()
    {
        return self::REGISTER_FROM_FE;
    }

    public function getAdminRegisterTag()
    {
        return self::REGISTER_FROM_ADMIN;
    }

}

