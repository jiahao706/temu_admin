<?php

namespace App\Service;


use App\Model\TemuGoods;
use App\Model\TemuGoodsSales;
use App\Model\TemuGoodsSku;
use App\Model\TemuMallsDeliveryRestrict;
use App\Model\TemuMallsGoodsRefundCost;

/**
 * User: jiahao.dong
 * Date: 2023/4/25
 * Time: 下午4:47
 */
class TemuDataStatisticsService
{

    /** 获取今日总销量
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午4:58
     * @return mixed
     */
    public static function getTodaySalesNum($mallIds)
    {
        $todayStartTime = date("Y-m-d H:i:s",time()-30*3600);
        $todaySaleVolumeInfo = TemuGoodsSku::selectRaw("sum(`today_sale_volume`+0) as today_sale_volume,mall_id")
            ->whereIn("mall_id", $mallIds)
            ->whereRaw("`updated_at`>='$todayStartTime'")
            ->groupBy("mall_id")
            ->get();
        return array_column($todaySaleVolumeInfo->toArray(), "today_sale_volume", "mall_id");
    }


    /** 获取今日销售额
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午6:31
     * @return mixed
     */
    public static function getTodaySalesVolume($mallIds)
    {
        $todayStartTime = date("Y-m-d H:i:s",time()-30*3600);
        $info = TemuGoodsSku::selectRaw("sum((today_sale_volume+0)*((supplier_price+0)/100)) as today_sales_volume,mall_id")
            ->whereIn("mall_id", $mallIds)
            ->whereRaw("`updated_at`>='$todayStartTime'")
            ->groupBy("mall_id")
            ->get();
        return array_column($info->toArray(), "today_sales_volume", "mall_id");
    }

    /** 获取今日利润
     * User: jiahao.dong
     * Date: 2023/4/27
     * Time: 下午2:37
     * @return mixed
     */
    public static function getTodayProfit($mallIds)
    {
        $todayStartTime = date("Y-m-d H:i:s",time()-30*3600);
        $info = TemuGoodsSku::selectRaw("sum((today_sale_volume+0)*((supplier_price+0)/100-cost_price)) as today_profit,mall_id")
            ->where("cost_price", "!=", 0.00)
            ->whereIn("mall_id", $mallIds)
            ->whereRaw("`updated_at`>='$todayStartTime'")
            ->groupBy("mall_id")->get();
        return array_column($info->toArray(), "today_profit", "mall_id");

    }

    /**
     * 获取当月利润
     * @param $mallIds
     * @return array
     */
    public static function getThisMonthProfit($mallIds)
    {
        $info = TemuGoodsSales::selectRaw("sum((today_sale_volume+0)*((price+0)/100-cost_price)) as this_month_profit,mall_id")
            ->where("cost_price", "!=", 0.00)
            ->whereIn("mall_id", $mallIds)
            ->whereRaw("DATE_FORMAT(`created_at`,'%Y-%m')='".date("Y-m",time())."'")
            ->groupBy("mall_id")->get();
        return array_column($info->toArray(), "this_month_profit", "mall_id");

    }

    /** 获取今日畅销商品
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午5:20
     * @param $mallId
     * @return array
     */
    public static function getTodayBestsellingGoods($mallId)
    {
        $hotSaleGoodsInfo = [];
        $todayBestsellingGoods = TemuGoodsSku::with("mall")->selectRaw("sum(`today_sale_volume`+0) as sum_today_sale_volume,sum(`ware_house_inventory_num`+0) as sum_ware_house_inventory_num,
        sum(`unavailable_warehouse_inventory_num`+0) as sum_unavailable_warehouse_inventory_num,sum(`wait_receive_num`+0) as sum_wait_receive_num,goods_id")
            ->where("mall_id", $mallId)
            ->groupBy("goods_id")->orderBy("sum_today_sale_volume", "desc")->limit(3)->get();
        if (!empty($todayBestsellingGoods)) {
            $goodsIdsInfo = array_column($todayBestsellingGoods->toArray(), null, "goods_id");
            $goods = TemuGoods::whereIn("goods_id", array_keys($goodsIdsInfo))->get();
            $hotSaleGoodsInfo = [
                "goods" => array_column($goods->toArray(), null, "goods_id"),
                "salesInfo" => $goodsIdsInfo
            ];
        }
        return $hotSaleGoodsInfo;
    }

    /** 获取近7日畅销商品
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午5:28
     * @return array
     */
    public static function get7dayBestsellingGoods()
    {
        $hotSaleGoodsInfo = [];
        $todayBestsellingGoods = TemuGoodsSku::selectRaw("sum(`last_seven_days_sale_volume`+0) as sum_last_seven_days_sale_volume,sum(`ware_house_inventory_num`+0) as sum_ware_house_inventory_num,
        sum(`unavailable_warehouse_inventory_num`+0) as sum_unavailable_warehouse_inventory_num,sum(`wait_receive_num`+0) as sum_wait_receive_num,goods_id")
            ->where("user_id", 1)
            ->groupBy("goods_id")->orderBy("sum_last_seven_days_sale_volume", "desc")->limit(3)->get();
        if (!empty($todayBestsellingGoods)) {
            $goodsIdsInfo = array_column($todayBestsellingGoods->toArray(), null, "goods_id");
            $goods = TemuGoods::whereIn("goods_id", array_keys($goodsIdsInfo))->get();
            $hotSaleGoodsInfo = [
                "goods" => array_column($goods->toArray(), null, "goods_id"),
                "salesInfo" => $goodsIdsInfo
            ];
        }
        return $hotSaleGoodsInfo;
    }


    /** 获取近7日滞销商品
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午5:20
     * @return array
     */
    public static function get7dayUnsableGoods()
    {
        $unsableSaleGoodsInfo = [];
        $todayUnsableGoods = TemuGoodsSku::selectRaw("sum(`last_seven_days_sale_volume`) as sum_last_seven_days_sale_volume,
        sum(`ware_house_inventory_num`) as sum_ware_house_inventory_num,goods_id")
//            ->where("ware_house_inventory_num",">=",1)
            ->where("user_id", 1)
            ->groupByRaw("goods_id")->havingRaw("sum_ware_house_inventory_num>=1")->orderBy("sum_last_seven_days_sale_volume", "asc")->limit(3)->get();
        if (!empty($todayUnsableGoods)) {
            $goodsIdsInfo = array_column($todayUnsableGoods->toArray(), null, "goods_id");
            $goods = TemuGoods::whereIn("goods_id", array_keys($goodsIdsInfo))->get();
            $unsableSaleGoodsInfo = [
                "goods" => array_column($goods->toArray(), null, "goods_id"),
                "salesInfo" => $goodsIdsInfo
            ];
        }
        return $unsableSaleGoodsInfo;
    }

    /** 获取今日滞销商品
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午5:30
     * @return array
     */
    public static function getTodayUnsableGoods()
    {
        $unsableSaleGoodsInfo = [];
        $todayUnsableGoods = TemuGoodsSku::selectRaw("sum(`today_sale_volume`) as sum_today_sale_volume,
        sum(`ware_house_inventory_num`) as sum_ware_house_inventory_num,sum(`unavailable_warehouse_inventory_num`) as sum_unavailable_warehouse_inventory_num,sum(`wait_receive_num`) as sum_wait_receive_num,goods_id")
//            ->where("ware_house_inventory_num",">=",1)
            ->where("user_id", 1)
            ->groupBy("goods_id")->havingRaw("`sum_ware_house_inventory_num`>=1")->orderBy("sum_today_sale_volume", "asc")->limit(3)->get();
        if (!empty($todayUnsableGoods)) {
            $goodsIdsInfo = array_column($todayUnsableGoods->toArray(), null, "goods_id");
            $goods = TemuGoods::whereIn("goods_id", array_keys($goodsIdsInfo))->get();
            $unsableSaleGoodsInfo = [
                "goods" => array_column($goods->toArray(), null, "goods_id"),
                "salesInfo" => $goodsIdsInfo
            ];
        }
        return $unsableSaleGoodsInfo;
    }

    /** 获取今日畅销sku
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午5:25
     * @param $mallId
     * @return array
     */
    public static function getTodayBestsellingSku($mallId)
    {
        $hotSaleSkusInfo = [];
        $todayBestsellingSkus = TemuGoodsSku::with(["mall"])->selectRaw("today_sale_volume,sku_name,ware_house_inventory_num,
        unavailable_warehouse_inventory_num,wait_receive_num,goods_id")
            ->where("mall_id", $mallId)
            ->orderByRaw("`today_sale_volume`+0 desc")->limit(3)->get();
        if (!empty($todayBestsellingSkus)) {
            $hotSkus = $todayBestsellingSkus->toArray();
            $goodsIdsInfo = array_column($hotSkus, null, "goods_id");
            $goods = TemuGoods::whereIn("goods_id", array_keys($goodsIdsInfo))->get();
            $hotSaleSkusInfo = [
                "goods" => array_column($goods->toArray(), null, "goods_id"),
                "salesInfo" => $hotSkus
            ];
        }
        return $hotSaleSkusInfo;
    }


    /** 获取七日畅销sku
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午5:25
     * @return array
     */
    public static function get7dayBestsellingSku()
    {
        $hotSaleSkusInfo = [];
        $sevendayBestsellingSkus = TemuGoodsSku::selectRaw("last_seven_days_sale_volume,sku_name,ware_house_inventory_num,
        unavailable_warehouse_inventory_num,wait_receive_num,goods_id")
            ->where("user_id", 1)
            ->orderByRaw("`last_seven_days_sale_volume`+0 desc")->limit(3)->get();
        if (!empty($sevendayBestsellingSkus)) {
            $goodsIdsInfo = array_column($sevendayBestsellingSkus->toArray(), null, "goods_id");
            $goods = TemuGoods::whereIn("goods_id", array_keys($goodsIdsInfo))->get();
            $hotSaleSkusInfo = [
                "goods" => array_column($goods->toArray(), null, "goods_id"),
                "salesInfo" => $goodsIdsInfo
            ];
        }
        return $hotSaleSkusInfo;
    }

    /** 获取今日滞销sku
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午5:26
     * @return array
     */
    public static function getTodayUnsableSku()
    {
        $unsableSaleSkusInfo = [];
        $todayUnsableSkus = TemuGoodsSku::select(["today_sale_volume", "sku_name", "goods_id"])->distinct("goods_id")
            ->where("ware_house_inventory_num", ">=", 1)
            ->where("user_id", 1)
            ->orderByRaw("`today_sale_volume`+0 asc")->limit(3)->get();
        if (!empty($todayUnsableSkus)) {
            $goodsIdsInfo = array_column($todayUnsableSkus->toArray(), null, "goods_id");
            $goods = TemuGoods::whereIn("goods_id", array_keys($goodsIdsInfo))->get();
            $unsableSaleSkusInfo = [
                "goods" => array_column($goods->toArray(), null, "goods_id"),
                "salesInfo" => $goodsIdsInfo
            ];
        }
        return $unsableSaleSkusInfo;
    }

    /** 获取今日快递费限制金额
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午4:58
     * @return mixed
     */
    public static function getTodayDeliveryRestrict($mallIds)
    {
        $todayDeliveryRestrictInfo = TemuMallsDeliveryRestrict::selectRaw("sum(`amount`) as today_amounts,mall_id")
            ->whereRaw("DATE_FORMAT(`freeze_start_time`,'%Y-%m-%d')=?", [date("Y-m-d", time())])
            ->whereIn("mall_id", $mallIds)
            ->groupBy("mall_id")
            ->get();
        return array_column($todayDeliveryRestrictInfo->toArray(), "today_amounts", "mall_id");
    }

    /**
     * 获取今日退货运费金额
     * @param $mallIds
     * @return array
     */
    public static function getTodayRefundCost($mallIds)
    {
        $todayRefundCostInfo = TemuMallsGoodsRefundCost::selectRaw("sum(`amount`) as today_amounts,mall_id")
            ->whereRaw("DATE_FORMAT(`freeze_start_time`,'%Y-%m-%d')=?", [date("Y-m-d", time())])
            ->whereIn("mall_id", $mallIds)
            ->groupBy("mall_id")
            ->get();
        return array_column($todayRefundCostInfo->toArray(), "today_amounts", "mall_id");
    }

    /** 获取当月发货运费
     * User: jiahao.dong
     * Date: 2023/4/25
     * Time: 下午4:58
     * @return mixed
     */
    public static function getThisMonthDeliveryRestrictNum($mallIds)
    {
        $now = time();
        $thisMonthStart = date("Y-m-01 00:00:00", $now);
        $thisMonthEnd = date("Y-m-d 23:59:59", $now);
        $thisMonthDeliveryRestrictInfo = TemuMallsDeliveryRestrict::selectRaw("sum(`amount`) as this_month_amounts,mall_id")
            ->whereRaw("`freeze_start_time`>='$thisMonthStart' and `freeze_start_time`<='$thisMonthEnd'")
            ->whereIn("mall_id", $mallIds)
            ->groupBy("mall_id")
            ->get();
        return array_column($thisMonthDeliveryRestrictInfo->toArray(), "this_month_amounts", "mall_id");
    }

    /**
     * 获取当月退货运费
     * @param $mallIds
     * @return array
     */
    public static function getThisMonthRefundCostNum($mallIds)
    {
        $now = time();
        $thisMonthStart = date("Y-m-01 00:00:00", $now);
        $thisMonthEnd = date("Y-m-d 23:59:59", $now);
        $thisMonthDeliveryRestrictInfo = TemuMallsGoodsRefundCost::selectRaw("sum(`amount`) as this_month_amounts,mall_id")
            ->whereRaw("`freeze_start_time`>='$thisMonthStart' and `freeze_start_time`<='$thisMonthEnd'")
            ->whereIn("mall_id", $mallIds)
            ->groupBy("mall_id")
            ->get();
        return array_column($thisMonthDeliveryRestrictInfo->toArray(), "this_month_amounts", "mall_id");
    }

    /**
     * 获取sku 总库存
     * @param TemuGoodsSku $skuInfo
     * @return mixed
     */
    public static function getSkuTotalInventory(TemuGoodsSku $skuInfo)
    {
        return $skuInfo->ware_house_inventory_num + $skuInfo->unavailable_warehouse_inventory_num + $skuInfo->wait_receive_num;
    }

    /**
     * 获取sku 总库存，不包括等待收货的
     * @param TemuGoodsSku $skuInfo
     * @return mixed
     */
    public static function getSkuTotalInventoryExcludeWaitReceiveNum(TemuGoodsSku $skuInfo)
    {
        return $skuInfo->ware_house_inventory_num + $skuInfo->unavailable_warehouse_inventory_num;
    }

    /**
     * 获取sku 不合理备货库存
     * @param $skuTotalInventoryNum
     * @param $lastThirtyDaysSaleVolume
     * @return float|int|mixed
     */
    public static function getSkuUnreasonableInventory($skuTotalInventoryNum,$lastThirtyDaysSaleVolume)
    {
//        $skuTotalInventoryNum = self::getSkuTotalInventoryExcludeWaitReceiveNum($skuInfo);
        $skuReasonableInventoryNum = self::getSkuReasonableInventory($lastThirtyDaysSaleVolume);
        return sprintf("%.2f",$skuTotalInventoryNum - $skuReasonableInventoryNum);
    }

    /**
     * 获取sku 春节不合理备货库存
     * @param $skuTotalInventoryNum 平台总库存
     * @param $lastThirtyDaysSaleVolume 30天销量
     * @param $purchaseConfigDay 平台建议备货天数
     * @return string
     */
    public static function getSpringFestivalSkuUnreasonableInventory($skuTotalInventoryNum,$lastThirtyDaysSaleVolume,$purchaseConfigDay)
    {
        $skuReasonableInventoryNum =$lastThirtyDaysSaleVolume/30*$purchaseConfigDay;
        return sprintf("%.2f",$skuTotalInventoryNum - $skuReasonableInventoryNum);
    }


    /**
     * 获取sku 不合理库存总成本
     * @param TemuGoodsSku $skuInfo
     * @return float|int
     */
    public static function getSkuUnreasonableInventoryTotalCostPrice($unreasonable_inventory,$cost_price)
    {
//        $skuUnreasonableInventory = self::getSkuUnreasonableInventory($skuInfo);
//        $skuUnreasonableInventory = $skuInfo->unreasonable_inventory;
        return $unreasonable_inventory <= 0 ? 0 : sprintf("%.2f",$cost_price * $unreasonable_inventory);
    }

    /**
     * 根据sku 近30日销量获取sku 合理备货库存
     * 日销 <10  12天备货量（近30天销量/30天 *12）
     *
     * 日销 >=10 and 日销<50  13天备货量 (近30天销量/30天 *13)
     *
     * 日销 >=50 and 日销<100  14天备货量 (近30天销量/30天 *14)
     *
     * 日销 >=100 and 日销<500  16天备货量 (近30天销量/30天 *16)
     *
     * 日销>=500  18天备货量 (近30天销量/30天 *18)
     * @param $lastThirtyDaysSaleVolume
     * @return float|int
     */
    public static function getSkuReasonableInventory($lastThirtyDaysSaleVolume)
    {
        $reasonableInventoryNum = 0;
        if(empty($lastThirtyDaysSaleVolume)){
            return $reasonableInventoryNum;
        }
        $avgPerDaySalesNum = $lastThirtyDaysSaleVolume / 30;
        switch ($avgPerDaySalesNum) {
            case $avgPerDaySalesNum < 10:
                $reasonableInventoryNum = $avgPerDaySalesNum * 12;
                break;
            case ($avgPerDaySalesNum >= 10 && $avgPerDaySalesNum < 50):
                $reasonableInventoryNum = $avgPerDaySalesNum * 13;
                break;
            case ($avgPerDaySalesNum >= 50 && $avgPerDaySalesNum < 100):
                $reasonableInventoryNum = $avgPerDaySalesNum * 14;
                break;
            case ($avgPerDaySalesNum >= 100 && $avgPerDaySalesNum < 500):
                $reasonableInventoryNum = $avgPerDaySalesNum * 16;
                break;
            case $avgPerDaySalesNum >= 500:
                $reasonableInventoryNum = $avgPerDaySalesNum * 18;
                break;
        }
        return $reasonableInventoryNum;
    }

    public static function getSkuUnreasonableInventoryHelpDesc()
    {
        $str = <<<HELP
        根据sku 近30日销量获取sku 合理备货库存,
        日销 <10  合理库存为12天备货量（近30天销量/30天 *12）,
        日销 >=10 and 日销<50  合理库存为13天备货量 (近30天销量/30天 *13),
        日销 >=50 and 日销<100  合理库存为14天备货量 (近30天销量/30天 *14),
        日销 >=100 and 日销<500  合理库存为16天备货量 (近30天销量/30天 *16),
        日销>=500  合理库存为18天备货量 (近30天销量/30天 *18)
HELP;
        return $str;
    }

    /**
     * 获取店铺不合理库存成本
     * @param $mallIds
     * @return array
     */
    public static function getTotalUnreasonableInventory($mallIds)
    {
        $totalUnreasonableInventoryInfo = TemuGoodsSku::selectRaw("sum(unreasonable_inventory_total_cost_price) as total_unreasonable_inventory_total_cost_price,mall_id")
            ->whereIn("mall_id", $mallIds)
            ->groupBy("mall_id")
            ->get();
        return array_column($totalUnreasonableInventoryInfo->toArray(), "total_unreasonable_inventory_total_cost_price", "mall_id");
    }

}
