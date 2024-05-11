<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TemuGoodsSales extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_goods_sales';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'goods_id',
        'goods_sku_id',
        'product_sku_id',
        'price',
        'today_sale_volume',
        'last_seven_days_sale_volume',
        'last_thirty_days_sale_volume',
        'created_at',
        'updated_at',
        'cost_price',
        'mall_id',
        'category_id',
        'number_of_price_diffrence',
    ];

    public function skuInfo()
    {
        return $this->belongsTo(TemuGoodsSku::class,"goods_sku_id","goods_sku_id");
    }

    public function goods()
    {
        return $this->hasOne(TemuGoods::class,"goods_id","goods_id")->where("goods_id","!=","");
    }

    public function goodsAllSku()
    {
        return $this->hasMany(TemuGoodsSku::class,"goods_id","goods_id");
    }

    public function mall()
    {
        return $this->hasOne(TemuMalls::class,"mall_id","mall_id");
    }

    public function category()
    {
        return $this->hasOne(TemuCategory::class,"id","category_id");
    }

}
