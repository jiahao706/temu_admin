<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TemuGoodsSku extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_goods_sku';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'goods_id',
        'goods_sku_id',
        'product_sku_id',
        'sku_name',
        'sku_currency_type',
        'supplier_price',
        'sku_ext_code',
        'is_verify_price',
        'is_adjusted',
        'lack_quantity',
        'advice_quantity',
        'available_sale_days',
        'available_sale_days_from_inventory',
        'warehouse_available_sale_days',
        'in_cart_number_7d',
        'in_card_number',
        'nomsg_subs_cnt_cnt_sth',
        'today_sale_volume',
        'last_seven_days_sale_volume',
        'last_thirty_days_sale_volume',
        'ware_house_inventory_num',
        'unavailable_warehouse_inventory_num',
        'wait_receive_num',
        'wait_delivery_inventory_num',
        'wait_approve_inventory_num',
        'wait_delivery_num',
        'transportation_num',
        'delivery_delay_num',
        'arrival_delay_num',
        'not_vmi_wait_delivery_num',
        'not_vmi_transportation_num',
        'not_vmi_delivery_delay_num',
        'not_vmi_arrival_delay_num',
        'purchase_config',
        'cost_price',
        'created_at',
        'updated_at',
        'mall_id',
        'unreasonable_inventory',
        'category_id',
        'number_of_price_diffrence',
        'unreasonable_inventory_total_cost_price',
    ];

    public function goods()
    {
        return $this->belongsTo(TemuGoods::class,"goods_id","goods_id");
    }

    public function skuSaleHistory()
    {
        return $this->hasMany(TemuGoodsSales::class,"goods_sku_id","goods_sku_id");
    }

    public function mall()
    {
        return $this->hasOne(TemuMalls::class,"mall_id","mall_id");
    }
}
