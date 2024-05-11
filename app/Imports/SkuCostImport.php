<?php

namespace App\Imports;

use App\Model\TemuCategory;
use App\Model\TemuGoodsSales;
use App\Model\TemuGoodsSku;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class SkuCostImport implements ToCollection,ToArray,WithCalculatedFormulas
{
    protected $mallId;

    public function __construct($mallId)
    {
        $this->mallId = $mallId;
    }


    public function array(array $array){
        return $array;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        if (!empty($collection)) {
            foreach ($collection as $index => $value) {
                if ($index == 0) {
                    continue;
                }
                if(empty(trim($value[0]))){
                    break;
                }

                //修改 sku 成本价格
                $sku_ext_code = trim($value[0]);
                $cost_price = trim($value[1]);
                //分类
                $category = !empty($value[2])?trim($value[2]):null;
                //子分类
                $child_category = !empty($value[3])?trim($value[3]):null;
                //差价计算个数
                $number_of_price_diffrence = !empty($value[4])?intval($value[4]):1;


                $skus = TemuGoodsSku::where([
                    "sku_ext_code" => $sku_ext_code,
                    "mall_id" => $this->mallId
                ])->get();

               /* if($sku_ext_code == 'AM2023062501-180-3pcs'){
                    dump($cost_price);
                    dump($skus);die;
                }*/
                if (!empty($skus)) {
                    foreach ($skus as $sku) {

                        $categoryId = null;
                        $childCategoryId = null;
                        //分类创建
                        if(!empty($category)){
                            $categoryInfo = TemuCategory::where(["category_name"=>$category])->first();

                            if(!empty($categoryInfo)){
                                $categoryId = $categoryInfo->id;
                            }else{
                                $res = TemuCategory::create([
                                    "category_name"=>$category
                                ]);
                                $res && $categoryId = $res->id;
                            }
                        }
                        //子分类创建
                        if(!empty($child_category) && !empty($categoryId)){
                            $childCategoryInfo = TemuCategory::where([
                                "pid"=>$categoryId,
                                "category_name"=>$child_category
                            ])->first();
                            if(!empty($childCategoryInfo)){
                                $childCategoryId = $childCategoryInfo->id;
                            }else{
                                $childCategoryId = TemuCategory::create([
                                    "category_name"=>$child_category,
                                    "pid"=>$categoryId
                                ]);
                            }
                        }

                        $skuUpdate = [
                            "cost_price" => $cost_price,
                            "number_of_price_diffrence"=>$number_of_price_diffrence
                        ];
                        if(!empty($childCategoryId)){
                            $skuUpdate+=["category_id"=>$childCategoryId];
                        }elseif (!empty($categoryId)){
                            $skuUpdate+=["category_id"=>$categoryId];
                        }

                        $sku->update($skuUpdate);

                        //修改历史销售记录的分类ID
                        if(!empty($skuUpdate["category_id"])){
                            TemuGoodsSales::where([
                                "goods_id" => $sku->goods_id,
                                "goods_sku_id" => $sku->goods_sku_id,
                                "product_sku_id" => $sku->product_sku_id,
                                "mall_id" => $this->mallId
                            ])
                                ->update([
                                    "category_id" => $skuUpdate["category_id"],
                                    "number_of_price_diffrence"=>$number_of_price_diffrence
                                ]);
                        }

                        //修改销售记录
                        //修改今天的历史记录 成本价
                        TemuGoodsSales::where([
                            "goods_id" => $sku->goods_id,
                            "goods_sku_id" => $sku->goods_sku_id,
                            "product_sku_id" => $sku->product_sku_id,
                            "mall_id" => $this->mallId
                        ])
                            ->whereRaw("DATE_FORMAT(`created_at`,'%Y-%m-%d')=?", [date("Y-m-d", time())])
                            //->whereRaw("`created_at`>='2023-07-01 00:00:00'")
                            ->update([
                                "cost_price" => $cost_price
                            ]);

                        //如果之前没有录入成本则今天补录
                        TemuGoodsSales::where([
                            "goods_id" => $sku->goods_id,
                            "goods_sku_id" => $sku->goods_sku_id,
                            "product_sku_id" => $sku->product_sku_id,
                            "mall_id" => $this->mallId
                        ])
                            ->whereRaw("`cost_price`=0")
                            ->update([
                                "cost_price" => $cost_price
                            ]);
                    }
                }
            }
        }
    }
}
