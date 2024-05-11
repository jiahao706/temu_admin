<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TemuOrderPackageDetail extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_order_package_detail';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'mall_id',
        'sub_purchase_order_sn',
        'product_skc_id',
        'skc_ext_code',
        'product_sku_id',
        'spec_name',
        'deliver_sku_num',
        'created_at',
        'updated_at',
    ];
}
