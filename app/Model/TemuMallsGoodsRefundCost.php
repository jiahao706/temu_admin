<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TemuMallsGoodsRefundCost extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_malls_goods_refund_cost';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'mall_id',
        'shipping_no',
        'freeze_start_time',
        'amount',
        'currency',
        'last_spider_time',
        'created_at',
        'updated_at',
    ];
}
