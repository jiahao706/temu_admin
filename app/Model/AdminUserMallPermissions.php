<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/29
 * Time: 上午7:27
 */
namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AdminUserMallPermissions extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = "admin_user_mall_permissions";


    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'mall_id',
        'user_id',
        'created_at',
        'updated_at',
    ];
}
