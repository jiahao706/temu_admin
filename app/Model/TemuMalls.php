<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemuMalls extends Model
{
    use SoftDeletes;
    public static $allMall;

    const SPIDER_DEFAULT = 0;

    const SPIDER_SUCCESS = 1;

    const SPIDER_ERROR = 2;

    const ADD_FROM_FE = 1;
    const ADD_FROM_ADMIN = 0;

    const START_SPIDER =1;
    const STOP_SPIDER =0;

    const SHOW_IN_HOME = 1;
    const NOT_SHOW =0;


    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_malls';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'mall_id',
        'mall_name',
        'mall_logo',
        'created_at',
        'updated_at',
        'last_spider_time',
        'username',
        'password',
        'spider_status_msg',
        'spider_status',
        'delivery_restrict_amount',
        'other_cost',
        'other_cost_msg',
        'share_ratio',
        'user_id',
        'type',
        'goods_refund_cost',
        'belongs_to_users',
        'is_start_spider',
        'is_show_in_home',
    ];

    public static function getAll()
    {
        if(empty(self::$allMall)){
            self::$allMall = array_column(self::all()->toArray(),null,"mall_id");
        }
        return self::$allMall;
    }

    public static function getSpiderStatus()
    {
        return [
            self::START_SPIDER=>"采集",
            self::STOP_SPIDER=>"暂不采集"
        ];
    }

    public static function getShowInHomeStatus()
    {
        return [
            self::SHOW_IN_HOME=>"展示",
            self::NOT_SHOW=>"不展示"
        ];
    }
}
