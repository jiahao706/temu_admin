<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TemuMallsDeliveryRestrictOrder extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_malls_delivery_restrict_order';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'mall_id',//temu店铺id
//        'shipping_no',//temu快递运单号
        'sub_purchase_order_sn',//temu快递订单号
        'delivery_order_sn',//temu店铺 发货单号
        'product_name', //temu店铺 产品信息
        'product_skc_picture',//temu店铺 产品图片
        'skc_ext_code',//产品货号
        'product_skc_id',//产品skc
        'express_company',//物流公司
        'express_company_id',//物流公司
        'express_delivery_sn',//物流单号
        'courier_name',//物流信息 联系人姓名
        'courier_phone',//物流信息 联系人电话
        'receive_address_province_name',//收货人省份
        'receive_address_province_code',//收货人省份
        'receive_address_district_name',//收货人地区
        'receive_address_district_code',//收货人地区
        'receive_address_city_name',//收货人城市名称
        'receive_address_city_code',//收货人城市名称
        'receive_address_detail_address',//收货人详细地址
        'receive_address_phone',//收货人电话
        'receive_address_receiver_name',//收货人姓名
        'deliver_skc_num',//发货skc 数量
        'deliver_time',//发货时间
        'receive_time',//收货时间
        'inbound_time',//入库时间
        'deliver_package_num',//已发货包裹数量
        'receive_package_num',//已收货包裹数量
        'status',//发货单状态
        'package_total_deliver_skc_num',//订单所有包裹skc 总件数
        'last_spider_time',//采集时间
        'created_at',
        'updated_at',
    ];
}
