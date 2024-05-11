
<style>
    #grid-table{
        border-collapse: collapse;
    }
    #grid-table thead th{
        border: 2px solid #ebebeb;
        background: #f5f5f5;
        text-align: center;
    }
    #grid-table td{
        border: 2px solid #ebebeb;
        vertical-align: middle;
    }
    #grid-table tr:first-child td{
        border-top: 1px solid #ccc;
    }
    #grid-table tr td:first-child{
        border-left: 1px solid #ccc;
    }
    </style>
<div class="row">

    <div class="col-md-12">
        <div class="box grid-box">
            <!-- /.box-header -->
            <div class="box-body table-responsive no-padding" style=" width:100%;height:100%; overflow:scroll;">
                <table class="table table-hover grid-table" id="grid-table" border="1" cellspacing="0" border-color="#ccc"
                       style="width:3000px; height:100%; text-align:center;max-width: none;border: 1px solid #ccc;">
                    <thead>
                    <tr>
                        <th rowspan="2" >商品信息</th>
                        <th rowspan="2">SKU属性</th>
                        <th rowspan="2">申报价格</th>
                        <th rowspan="2">币种</th>
                        <th rowspan="2">SKU货号</th>
                        <th rowspan="2">开款核价状态</th>
                        <th rowspan="2">商品调价核价状态</th>
                        <th rowspan="2">缺货数量</th>
                        <th rowspan="2">建议备货数量</th>
                        <th rowspan="2">可售天数</th>
                        <th rowspan="2">库存可售天数</th>
                        <th rowspan="2">仓内库存可售天数</th>
                        <th rowspan="2">近7日用户加购数量</th>
                        <th rowspan="2">用户累计加购数量</th>
                        <th rowspan="2">已订阅待提醒到货</th>
                        <th colspan="3">销售数据</th>
                        <th colspan="5">库存数据</th>
                        <th colspan="4">VMI备货单数</th>
                        <th colspan="4">非VMI备货单数</th>
                        <th rowspan="2">备货逻辑</th>
                    </tr>
                    <tr>
                        <th>今日</th>
                        <th>近7天</th>
                        <th>近30天</th>
                        <th>仓内可用库存</th>
                        <th>仓内暂不可用库存</th>
                        <th>已发货库存</th>
                        <th>已下单待发货库存</th>
                        <th>待审核备货库存</th>
                        <th>待发货</th>
                        <th>在途单数</th>
                        <th>发货延迟</th>
                        <th>到货延迟</th>
                        <th>待发货</th>
                        <th>在途单数</th>
                        <th>发货延迟</th>
                        <th>到货延迟</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(!empty($goods))
                        @foreach($goods as $index =>$item)
                            @if(!empty($item->skus))
                                @foreach($item->skus as $_sku_index => $_sku)
                    <tr data-key="1">
                            @if($_sku_index == 0)
                                <td rowspan="{{count($item->skus)}}" style="width: 400px;">
                                    <div style="padding:13px 0 13px 70px;position:relative;">
                                        <img src="{{$item->img}}"
                                            style="width:60px;height:60px;left:0;position:absolute;top:13px;cursor:pointer;">
                                        <p>{{$item->title}}</p>
                                        <p style="color: rgba(0,0,0,.4);margin: 0;">{{$item->category}}</p>
                                        <p style="color: rgba(0,0,0,.4);margin: 0;">SKC:{{$item->skc}}</p>
                                        <p style="color: rgba(0,0,0,.4);margin: 0;">SPU:{{$item->spu}}</p>
                                        <p style="color: rgba(0,0,0,.4);margin: 0;">SKC货号:{{$item->sku_article_number}}</p>
                                        <p style="color: rgba(0,0,0,.4);margin: 0;">加入站点时长:{{!empty($item->join_site_duration)?$item->join_site_duration:"-"}}天</p>
                                    </div>
                                </td>
                            @endif
                            <td>{{$_sku->sku_name}}</td>
                            <td>{{!empty($_sku->supplier_price)?"¥".sprintf("%.2f",$_sku->supplier_price/100):$_sku->supplier_price}}</td>
                            <td>{{$_sku->sku_currency_type}}</td>
                            <td>{{$_sku->sku_ext_code}}</td>
                            <td>{{$_sku->is_verify_price}}</td>
                            <td>{{$_sku->is_adjusted}}</td>
                            <td>{{$_sku->lack_quantity}}</td>
                            <td>{{$_sku->advice_quantity}}</td>
                            <td>{{$_sku->available_sale_days}}</td>
                            <td>{{$_sku->available_sale_days_from_inventory}}</td>
                            <td>{{$_sku->warehouse_available_sale_days}}</td>
                            <td>{{$_sku->in_cart_number_7d}}</td>
                            <td>{{$_sku->in_card_number}}</td>
                            <td>{{$_sku->nomsg_subs_cnt_cnt_sth}}</td>
                            <td>{{$_sku->today_sale_volume}}</td>
                            <td>{{$_sku->last_seven_days_sale_volume}}</td>
                            <td>{{$_sku->last_thirty_days_sale_volume}}</td>
                            <td>{{$_sku->sales_inventory_num}}</td>
                            <td>{{$_sku->unavailable_warehouse_inventory_num}}</td>
                            <td>{{$_sku->wait_receive_num}}</td>
                            <td>{{$_sku->wait_delivery_inventory_num}}</td>
                            <td>{{$_sku->wait_approve_inventory_num}}</td>
                            <td>{{$_sku->wait_delivery_num}}</td>
                            <td>{{$_sku->transportation_num}}</td>
                            <td>{{$_sku->delivery_delay_num}}</td>
                            <td>{{$_sku->arrival_delay_num}}</td>
                            <td>{{$_sku->not_vmi_wait_delivery_num}}</td>
                            <td>{{$_sku->not_vmi_transportation_num}}</td>
                            <td>{{$_sku->not_vmi_delivery_delay_num}}</td>
                            <td>{{$_sku->not_vmi_arrival_delay_num}}</td>
                                @if($_sku_index == 0)
                                    <td rowspan="{{count($item->skus)}}">
                                        {{$item->purchase_config}}
                                    </td>
                                @endif
                    </tr>
                                 @endforeach
                            @endif
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>



            <!-- /.box-body -->
            {{$goods->links()}}
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.3.0/chart.min.js" integrity="sha512-mlz/Fs1VtBou2TrUkGzX4VoGvybkD9nkeXWJm3rle0DPHssYYx4j+8kIS15T78ttGfmOjH0lLaBXGcShaVkdkg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
