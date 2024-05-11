<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">店铺今日热销sku前三名({{$mall["mall_name"]}})</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool"  onclick="window.location.href='{{"/admin/statistics/temugoods/hotsku?temu_goods_sales[mall_id]=".$mall["mall_id"]}}'">
                <h3 class="box-title">查看更多</h3>
            </button>
        </div>
    </div>

    <!-- /.box-header -->
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th style="width: 300px;">商品信息</th>
                    <th style="text-align: center;">热销SKU</th>
                    <th style="text-align: center;">sku销售数量</th>
                    <th style="text-align: center;">商品仓内可用库存</th>
                    <th style="text-align: center;">商品仓内暂不可用库存</th>
                    <th style="text-align: center;">商品已发货库存</th>
                    <th style="text-align: center;">合计库存</th>
                        </tr>
                </thead>
                <tbody>
                @if(!empty($salesInfo))
                    @foreach($salesInfo["salesInfo"] as  $_sale)
                        <tr style="width: 300px;">
                            <td>
                                <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{{$salesInfo["goods"][$_sale["goods_id"]]["img"]}}"
                                         style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{{$salesInfo["goods"][$_sale["goods_id"]]["title"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{{$salesInfo["goods"][$_sale["goods_id"]]["category"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{{$salesInfo["goods"][$_sale["goods_id"]]["skc"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{{$salesInfo["goods"][$_sale["goods_id"]]["spu"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{{$salesInfo["goods"][$_sale["goods_id"]]["sku_article_number"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:{{!empty($salesInfo["goods"][$_sale["goods_id"]]["join_site_duration"])?$salesInfo["goods"][$_sale["goods_id"]]["join_site_duration"]:"-"}}天</p>
                                </div>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["sku_name"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["today_sale_volume"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["ware_house_inventory_num"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["unavailable_warehouse_inventory_num"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["wait_receive_num"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["ware_house_inventory_num"]+$_sale["unavailable_warehouse_inventory_num"]+$_sale["wait_receive_num"]}}</b>
                            </td>
                        </tr>
                    @endforeach
                @endif

                </tbody>
            </table>
        </div>
        <!-- /.table-responsive -->
    </div>

    <!-- /.box-body -->
</div>



