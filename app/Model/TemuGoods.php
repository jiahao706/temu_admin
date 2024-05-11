<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TemuGoods extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_goods';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'goods_id',
        'title',
        'img',
        'skc',
        'spu',
        'sku_article_number',
        'join_site_duration',
        'category',
        'purchase_config',
        'created_at',
        'updated_at',
        'mall_id',
    ];

    public function skus()
    {
        return $this->hasMany(TemuGoodsSku::class,"goods_id","goods_id");
    }
}
