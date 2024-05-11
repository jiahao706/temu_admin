<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">店铺七日热销商品前三名</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool"  onclick="window.location.href='{{url("/admin/statistics/temugoods/senvendayhot")}}'">
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
                    <th style="text-align: center;">商品销售数量</th>
                    <th style="text-align: center;">商品仓内可用库存</th>
                    <th style="text-align: center;">商品仓内暂不可用库存</th>
                    <th style="text-align: center;">商品已发货库存</th>
                    <th style="text-align: center;">合计库存</th>
                </tr>
                </thead>
                <tbody>
                @if(!empty($salesInfo))
                    @foreach($salesInfo["salesInfo"] as $_goodsId => $_sale)
                        <tr style="width: 300px;">
                            <td>
                                <div style="padding:13px 0 13px 70px;position:relative;">
                                    <img src="{{$salesInfo["goods"][$_goodsId]["img"]}}"
                                         style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;"/>
                                    <p>{{$salesInfo["goods"][$_goodsId]["title"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">{{$salesInfo["goods"][$_goodsId]["category"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{{$salesInfo["goods"][$_goodsId]["skc"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{{$salesInfo["goods"][$_goodsId]["spu"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{{$salesInfo["goods"][$_goodsId]["sku_article_number"]}}</p>
                                    <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:{{!empty($salesInfo["goods"][$_goodsId]["join_site_duration"])?$salesInfo["goods"][$_goodsId]["join_site_duration"]:"-"}}天</p>
                                </div>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["sum_last_seven_days_sale_volume"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["sum_ware_house_inventory_num"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["sum_unavailable_warehouse_inventory_num"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["sum_wait_receive_num"]}}</b>
                            </td>
                            <td style="text-align: center;vertical-align: middle;">
                                <b>{{$_sale["sum_sales_inventory_num"]+$_sale["sum_unavailable_warehouse_inventory_num"]+$_sale["sum_wait_receive_num"]}}</b>
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



