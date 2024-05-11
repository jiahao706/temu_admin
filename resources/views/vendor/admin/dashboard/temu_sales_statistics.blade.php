<div class="box box-default">
    <div class="box-header with-border" style="padding: 5px !important;">
        <h3 class="box-title h3"style="color: red;" >店铺销售数据统计,每小时55分左右更新</h3>
    </div>
    <style>
        .top_box {
            height: 30px;
            width: 30px;
            /*position: relative;*/
            /*margin: 100px auto;*/
        }
        .top_box img {
            width: 100%;
            height: 100%;
        }
        .top_box .number {
            position: absolute;
            margin-left: -26px;
            /* margin-top: -1px; */
            margin-top: 3px;
            color: #fff;
            line-height: 23px;
            width: 23px;
            text-align: center;
            font-size: 23px;
            font-weight: 600;
        }
    </style>
    <!-- /.box-header -->
    <div class="box-body" style="padding: 10px;">
        @if (!empty($res))
            <?php $topIndex =0;?>
            @foreach($res as $index=>$mallRes)
                @if(empty($mallRes["mallName"]))
                    @continue
                @endif
                <?php $topIndex++;?>
                <div class="table-responsive col-md-12}}">
                    <table class="table table-striped" style="border: 2px solid #d2d6de">
                        <thead>
                        <tr>
                            <td colspan="12" class="box-title" style="font-size: 17px" >
                                <div class="top_box pull-left">
                                    @if($index<=2)
                                        <img src="/images/top{{$index+1}}.png" style="width: 30px;margin-top:-9px;">
                                    @else
                                        <img src="/images/topn.png" style="width: 30px;margin-top:-1px;">
                                        <span class = "number">{{$topIndex}}</span>
                                    @endif
                                </div>
                                <b>店铺名称: {{$mallRes["mallName"]}}</b>
                                <b class="pull-right">{{$mallRes["lastSpiderTime"]}}</b>
                                @if(!empty($mallRes["mallDetail"]->belongs_to_users))
                                    <b >,店铺负责人:{{$mallRes["mallDetail"]->belongs_to_users}}</b>
                                @else
                                    <b >,店铺负责人:未分配</b>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="box-title"><b>销售量</b></td>
                            <td class="box-title"><b>营业额</b></td>
                            <td class="box-title"><b>毛利润</b></td>
                            <td class="box-title"><b>本月预估毛利润</b></td>
                            <td class="box-title"><b>本月发货运费</b></td>
                            <td class="box-title"><b>本月退货运费</b></td>
                            {{--<td class="box-title"><b>发货运费</b></td>
                            <td class="box-title"><b>退货运费</b></td>--}}
                            <td class="box-title"><b>不合理库存成本</b></td>
                            <td class="box-title"><b>人工成本/月</b></td>
                            <td class="box-title"><b>本月预估净利润</b></td>

                            <td class="box-title"><b>本月预估奖金</b></td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="mall-sales-info" mallid="{{$mallRes["mallId"]}}">
                            <td style="color: red;text-align: left;vertical-align: middle;">
                                <b class="todaySalesNum">{{$mallRes["todaySalesNum"]}}</b>
                            </td>
                            <td style="color: red;text-align: left;vertical-align: middle;">
                                <b class="todaySalesVolume">{{$mallRes["todaySalesVolume"]}}</b>
                            </td>
                            <td style="color: red;text-align: left;vertical-align: middle;">
                                <b class="todayProfit">{{$mallRes["todayProfit"]}}</b>
                            </td>
                            <td style="color: red;text-align: left;vertical-align: middle;">
                                {{--                                <b class="todayProfit">{{$mallRes["thisMonthProfit"]}}</b>--}}
                                <b class="thisMonthProfit">加载中...</b>
                            </td>
                            <td style="color: red;text-align: left;vertical-align: middle;">
                                {{--                                <b class="thisMonthDeliveryRestrict">{{$mallRes["thisMonthDeliveryRestrict"]}}</b>--}}
                                <b class="thisMonthDeliveryRestrict">加载中...</b>
                            </td>
                            <td style="color: red;text-align: left;vertical-align: middle;">
                                {{--                                <b class="thisMonthDeliveryRestrict">{{$mallRes["thisMonthRefundCost"]}}</b>--}}
                                <b class="thisMonthRefundCost">加载中...</b>
                            </td>
                            <td style="color: red;text-align: left;vertical-align: middle;">
                                <b class="unreasonableInventoryCost">加载中...</b>
                            </td>
                            {{--
                                                        <td style="text-align: left;vertical-align: middle;">
                                                            <a class="btn" href="{{"/admin/fundmanage/restrict/delivery?temu_malls_delivery_restrict[mall_id]=".$mallRes["mallId"]}}" role="button" style="color: red">
                                                                <b class="todayDeliveryRestrict">{{$mallRes["todayDeliveryRestrict"]}}</b>
                                                            </a>
                                                        </td>
                                                        <td style="color: red;text-align: left;vertical-align: middle;">
                                                            <b class="todayRefundCost">{{$mallRes["todayRefundCost"]}}</b>
                                                        </td>
                            --}}

                            <td style="color: red;text-align: left;vertical-align: middle;">
                                <b class="otherCost">{{$mallRes["otherCost"]}}</b>
                            </td>

                            <td style="color: red;text-align: left;vertical-align: middle;" ><b class="netProfit">加载中...</b></td>
                            <td style="color: red;text-align: left;vertical-align: middle;"><b class="estimateCommission">加载中...</b></td>
                        </tr>
                        <tr style="height: 150px;">
                            <td colspan="12">
                                <span class="pull-left" style="padding: 4px;"><b>曲线图数据统计时间范围:</b><b class="day_info" style="margin-left: 10px;"></b></span>
                                <div class="col-sm-4">
                                    <div class="input-group input-group-sm pull-left">
                                        <div class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <input type="text" class="form-control  temu_malls_start_time" placeholder="店铺数据统计时间范围"  value="2023-05-11 00:00:00" autocomplete="off">
                                        <span class="input-group-addon" style="border-left: 0; border-right: 0;">-</span>

                                        <input type="text" class="form-control temu_malls_end_time" placeholder="店铺数据统计时间范围"  value="2023-05-11 23:59:59" autocomplete="off">
                                    </div>

                                </div>
                                <div class="col-sm-1 pull-left" >
                                    <button class="btn btn-default btn-sm statistics_btn" style="margin-left: -16px;" mallid="{{$mallRes["mallId"]}}"><i class="fa fa-search"></i>&nbsp;&nbsp;统计</button>
                                </div>
                                <br><br><br>
                                <div class="col-sm-12">
                                    <canvas class="myChart" mallid="{{$mallRes["mallId"]}}" ></canvas>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /.table-responsive -->
            @endforeach
        @endif
    </div>
    <!-- /.box-body -->
</div>
<script src="https://cdn.staticfile.org/Chart.js/3.9.1/chart.js"></script>
<script>

    function getDay(day){

        var today = new Date();

        var targetday_milliseconds=today.getTime() + 1000*60*60*24*day;

        today.setTime(targetday_milliseconds); //注意，这行是关键代码

        var tYear = today.getFullYear();

        var tMonth = today.getMonth();

        var tDate = today.getDate();

        tMonth = doHandleMonth(tMonth + 1);

        tDate = doHandleMonth(tDate);

        return tYear+"-"+tMonth+"-"+tDate;

    }

    function doHandleMonth(month){

        var m = month;

        if(month.toString().length == 1){

            m = "0" + month;

        }
        return m;
    }

    var mallChartConfig = {}
    function getMallStatistics(mallids,temu_malls_start_time,temu_malls_end_time)
    {
        $.ajax({
            url:"/admin/home/latest_data_statistics",
            type:"post",
            data:{
                temu_malls_start_time:temu_malls_start_time,
                temu_malls_end_time:temu_malls_end_time,
                mallids:mallids,
                _token:"{{csrf_token()}}"
            },
            success:function (res) {
                if(res.status == 200){
                    var allDay = res.data.all_day;
                    $(".myChart").each(function () {
                        var curMallId = $(this).attr("mallid")
                        // var thisChart = Chart.getChart($(this))
                        if(curMallId == mallids && mallChartConfig.hasOwnProperty(curMallId)){
                            thisChart = mallChartConfig[curMallId];
                            thisChart.data.labels = res.data.all_day;
                            thisChart.data.datasets[0].data = res.data.mall_info[curMallId].latest_sale_volume;
                            thisChart.data.datasets[1].data = res.data.mall_info[curMallId].latest_sales_money;
                            thisChart.data.datasets[2].data = res.data.mall_info[curMallId].latest_profit;
                            //thisChart.data.datasets[3].data = res.data.mall_info[curMallId].latest_amounts;
                            //thisChart.data.datasets[4].data = res.data.mall_info[curMallId].refund_cost;
                            thisChart.update()
                            var tables =$(this).parents(".table-striped");
                            $(tables[0]).find(".todaySalesNum").text(res.data.total_statistics[curMallId].latest_sale_volume);
                            $(tables[0]).find(".todaySalesVolume").text(res.data.total_statistics[curMallId].latest_sales_money);
                            $(tables[0]).find(".todayProfit").text(res.data.total_statistics[curMallId].latest_profit);
                            //$(tables[0]).find(".todayDeliveryRestrict").text(res.data.total_statistics[curMallId].latest_amounts);
                            //$(tables[0]).find(".todayRefundCost").text(res.data.total_statistics[curMallId].refund_cost);
                        }else{
                            if(!mallChartConfig.hasOwnProperty(curMallId)){
                                var myChart = new Chart($(this), {
                                    type: 'line', //bar:柱状图  line:折线图  pie:环形图  horizontalBar:横向柱状图(最新的版本好像不支持)
                                    data: {
                                        labels: allDay,
                                        datasets: [{
                                            label: '销量', //鼠标悬停提示的汉字 key
                                            data: res.data.mall_info[curMallId].latest_sale_volume,  //鼠标悬停提示的数值
                                            backgroundColor: '#14d1ff', //柱体颜色
                                            borderColor: '#14d1ff',//柱体边框颜色,数组 可以是一个 也可以是多个
                                            //borderWidth: 3,  //柱体边框宽度
                                            lineTension:0.5,  //贝塞尔曲线张力  0为直线
                                            // pointRadius:12, //点的半径
                                            pointHoverRadius:15, //鼠标悬浮时,点的半径
                                            //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                                        }, {
                                            label: '营业额', //鼠标悬停提示的汉字 key
                                            data: res.data.mall_info[curMallId].latest_sales_money,  //鼠标悬停提示的数值
                                            backgroundColor: '#0044cc', //柱体颜色
                                            borderColor: '#0044cc',//柱体边框颜色,数组 可以是一个 也可以是多个
                                            //borderWidth: 3,  //柱体边框宽度
                                            lineTension:0.5,  //贝塞尔曲线张力  0为直线
                                            // pointRadius:12, //点的半径
                                            pointHoverRadius:15, //鼠标悬浮时,点的半径
                                            //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色#9f191f
                                        },{
                                            label: '毛利润', //鼠标悬停提示的汉字 key
                                            data: res.data.mall_info[curMallId].latest_profit,  //鼠标悬停提示的数值
                                            backgroundColor: '#9f191f', //柱体颜色
                                            borderColor: '#9f191f',//柱体边框颜色,数组 可以是一个 也可以是多个
                                            //borderWidth: 3,  //柱体边框宽度
                                            lineTension:0.5,  //贝塞尔曲线张力  0为直线
                                            // pointRadius:12, //点的半径
                                            pointHoverRadius:15, //鼠标悬浮时,点的半径
                                            //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                                        },{
                                            label: '发货运费', //鼠标悬停提示的汉字 key
                                            data: res.data.mall_info[curMallId].latest_amounts,  //鼠标悬停提示的数值
                                            backgroundColor: '#a5dc86', //柱体颜色
                                            borderColor: '#a5dc86',//柱体边框颜色,数组 可以是一个 也可以是多个
                                            //borderWidth: 3,  //柱体边框宽度
                                            lineTension:0.5,  //贝塞尔曲线张力  0为直线
                                            // pointRadius:12, //点的半径
                                            pointHoverRadius:15, //鼠标悬浮时,点的半径
                                            //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                                        },{
                                            label: '退货运费', //鼠标悬停提示的汉字 key
                                            data: res.data.mall_info[curMallId].refund_cost,  //鼠标悬停提示的数值
                                            backgroundColor: '#FF9233', //柱体颜色
                                            borderColor: '#FF9233',//柱体边框颜色,数组 可以是一个 也可以是多个
                                            //borderWidth: 3,  //柱体边框宽度
                                            lineTension:0.5,  //贝塞尔曲线张力  0为直线
                                            // pointRadius:12, //点的半径
                                            pointHoverRadius:15, //鼠标悬浮时,点的半径
                                            //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                                        }
                                        ]
                                    },
                                    options: {
                                        scales: {
                                            /* yAxes: [{
                                                 ticks: {
                                                     beginAtZero:true
                                                 }
                                             }]*/
                                        },
                                        responsive: true,
                                        maintainAspectRatio: false
                                    }
                                });
                                mallChartConfig[curMallId] = myChart
                            }

                        }

                    })
                }
            }
        })

    }

    function getMallThisMonthStatistics(mallids)
    {
        $.ajax({
            url:"/admin/home/this_month_data_statistics",
            type:"post",
            data:{
                mallids:mallids,
                _token:"{{csrf_token()}}"
            },
            success:function (res) {
                if(res.status == 200){
                    $(".mall-sales-info").each(function () {
                        var curMallId = $(this).attr("mallid")
                        var monthData = res.data;
                        if(monthData.hasOwnProperty(curMallId)){
                            $(this).find(".thisMonthProfit").text(monthData[curMallId].thisMonthProfit);
                            $(this).find(".thisMonthDeliveryRestrict").text(monthData[curMallId].thisMonthDeliveryRestrict);
                            $(this).find(".thisMonthRefundCost").text(monthData[curMallId].thisMonthRefundCost);
                            $(this).find(".unreasonableInventoryCost").text(monthData[curMallId].unreasonableInventoryCost);
                            $(this).find(".estimateCommission").text(monthData[curMallId].estimateCommission);
                            $(this).find(".netProfit").text(monthData[curMallId].netProfit);

                        }
                    })
                }
            }
        })
    }

    function getMallsHotSalesStatistics(mallids)
    {
        $.ajax({
            url:"/admin/home/get_malls_hot_sales",
            type:"post",
            data:{
                mallids:mallids,
                _token:"{{csrf_token()}}"
            },
            success:function (res) {
                $(".index-home .content").append(res);
            }
        })

    }

    function loadInitStatistics(){
        $(".temu_malls_start_time").val(getDay(-6));
        $(".temu_malls_end_time").val(getDay(0));
        $(".day_info").text( getDay(-6)+' - '+getDay(0));
        $("#app").addClass("index-home");
        var mallIds = new Array()
        $(".myChart").each(function () {
            mallIds.push($(this).attr("mallid"))
        })
        getMallThisMonthStatistics(mallIds.join(","));
        getMallStatistics(mallIds.join(","),$(".temu_malls_start_time").val(),$(".temu_malls_end_time").val());
        for (var i in mallIds){
            getMallsHotSalesStatistics(mallIds[i]);
        }

    }

    $(function () {
        $(".temu_malls_start_time").each(function () {
            $(this).datetimepicker({
                "format":"YYYY-MM-DD",
                "locale":"zh-CN",
                "widgetPositioning": {
                    horizontal: 'auto',
                    vertical: 'bottom'
                }
            })
        })
        $(".temu_malls_end_time").each(function () {
            $(this).datetimepicker({
                "format":"YYYY-MM-DD",
                "locale":"zh-CN",
                "widgetPositioning":{
                    horizontal: 'auto',
                    vertical: 'bottom'
                },
                "useCurrent":false,
            })
        })
        $(".temu_malls_start_time").on("dp.change", function (e) {
            $(this).siblings(".temu_malls_end_time").data("DateTimePicker").minDate(e.date);
        });
        $(".temu_malls_end_time").on("dp.change", function (e) {
            $(this).siblings(".temu_malls_start_time").data("DateTimePicker").maxDate(e.date);
        });


        $(".statistics_btn").on("click",function () {
            var tables =$(this).parents(".table-striped");
            var starttime = $(tables[0]).find(".temu_malls_start_time").val();
            var endtime = $(tables[0]).find(".temu_malls_end_time").val();
            if(starttime!="" && endtime!=""){
                $(tables[0]).find(".day_info").text( starttime+' - '+endtime);
            }else if(endtime ==""){
                $(tables[0]).find(".day_info").text( starttime);
            }else{
                $(tables[0]).find(".day_info").text( endtime);
            }
            getMallStatistics($(this).attr('mallid'),starttime,endtime)
        })

        loadInitStatistics()

        /*$(".myChart").each(function (index,obj) {
            var today = $(".temu_malls_start_time").val();
            var tables = $(obj).parents(".table-striped")
            var thisTable = tables[0];

            var todaySalesNum = $(thisTable).find(".todaySalesNum").text()
            var todaySalesVolume = $(thisTable).find(".todaySalesVolume").text()
            var todayProfit = $(thisTable).find(".todayProfit").text()
            var todayDeliveryRestrict = $(thisTable).find(".todayDeliveryRestrict").text()
            var netProfit = $(thisTable).find(".netProfit").text()
            var myChart = new Chart($(this), {
                type: 'line', //bar:柱状图  line:折线图  pie:环形图  horizontalBar:横向柱状图(最新的版本好像不支持)
                data: {
                    labels: [today],
                    datasets: [{
                        label: '销量', //鼠标悬停提示的汉字 key
                        data: [todaySalesNum],  //鼠标悬停提示的数值
                        backgroundColor: '#14d1ff', //柱体颜色
                        borderColor: '#14d1ff',//柱体边框颜色,数组 可以是一个 也可以是多个
                        //borderWidth: 3,  //柱体边框宽度
                        lineTension:0.5,  //贝塞尔曲线张力  0为直线
                        // pointRadius:12, //点的半径
                        pointHoverRadius:15, //鼠标悬浮时,点的半径
                        //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                    }, {
                        label: '营业额', //鼠标悬停提示的汉字 key
                        data: [todaySalesVolume],  //鼠标悬停提示的数值
                        backgroundColor: '#0044cc', //柱体颜色
                        borderColor: '#0044cc',//柱体边框颜色,数组 可以是一个 也可以是多个
                        //borderWidth: 3,  //柱体边框宽度
                        lineTension:0.5,  //贝塞尔曲线张力  0为直线
                        // pointRadius:12, //点的半径
                        pointHoverRadius:15, //鼠标悬浮时,点的半径
                        //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色#9f191f
                    },{
                        label: '毛利润', //鼠标悬停提示的汉字 key
                        data: [todayProfit],  //鼠标悬停提示的数值
                        backgroundColor: '#9f191f', //柱体颜色
                        borderColor: '#9f191f',//柱体边框颜色,数组 可以是一个 也可以是多个
                        //borderWidth: 3,  //柱体边框宽度
                        lineTension:0.5,  //贝塞尔曲线张力  0为直线
                        // pointRadius:12, //点的半径
                        pointHoverRadius:15, //鼠标悬浮时,点的半径
                        //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                    },{
                        label: '运费', //鼠标悬停提示的汉字 key
                        data: [todayDeliveryRestrict],  //鼠标悬停提示的数值
                        backgroundColor: '#a5dc86', //柱体颜色
                        borderColor: '#a5dc86',//柱体边框颜色,数组 可以是一个 也可以是多个
                        //borderWidth: 3,  //柱体边框宽度
                        lineTension:0.5,  //贝塞尔曲线张力  0为直线
                        // pointRadius:12, //点的半径
                        pointHoverRadius:15, //鼠标悬浮时,点的半径
                        //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                    },{
                        label: '纯利润', //鼠标悬停提示的汉字 key
                        data: [netProfit],  //鼠标悬停提示的数值
                        backgroundColor: '#FF9233', //柱体颜色
                        borderColor: '#FF9233',//柱体边框颜色,数组 可以是一个 也可以是多个
                        //borderWidth: 3,  //柱体边框宽度
                        lineTension:0.5,  //贝塞尔曲线张力  0为直线
                        // pointRadius:12, //点的半径
                        pointHoverRadius:15, //鼠标悬浮时,点的半径
                        //hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                    }
                    ]
                },
                options: {
                    scales: {
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

        });*/
        /*
                var ctxs = document.getElementsByClassName("myChart");

                for(i=0;i<ctxs.length;i++){
                    var myChart = new Chart(ctxs[i], {
                        type: 'line', //bar:柱状图  line:折线图  pie:环形图  horizontalBar:横向柱状图(最新的版本好像不支持)
                        data: {
                            labels: ['00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'],
                            datasets: [{
                                label: '请求数', //鼠标悬停提示的汉字 key
                                data: [20,30,50,70,56,45,63,25,41,52,36,25,41,25,35],  //鼠标悬停提示的数值
                                backgroundColor: ['rgba(93,162,255,0.2)','red','#0000ff'], //柱体颜色
                                borderColor: ['#4088FF','red'],//柱体边框颜色,数组 可以是一个 也可以是多个
                                borderWidth: 3,  //柱体边框宽度
                                lineTension:0.5,  //贝塞尔曲线张力  0为直线
                                pointRadius:12, //点的半径
                                pointHoverRadius:20, //鼠标悬浮时,点的半径
                                hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                            },
                                {
                                    label: '点击数', //鼠标悬停提示的汉字 key
                                    data: [10,20,40,60,46,25,43,15,21,32,16,15,71,95,35],  //鼠标悬停提示的数值
                                    backgroundColor: ['rgba(90,162,255,0.2)','black','#0000ff'], //柱体颜色
                                    borderColor: ['#4088FF','black'],//柱体边框颜色,数组 可以是一个 也可以是多个
                                    borderWidth: 1,  //柱体边框宽度
                                    lineTension:0.5,  //贝塞尔曲线张力  0为直线
                                    pointRadius:4, //点的半径
                                    pointHoverRadius:20, //鼠标悬浮时,点的半径
                                    hoverBackgroundColor:'red',  //鼠标悬浮时,原点的颜色
                                }
                            ]
                        },
                        options: {
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            },
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
        */
    })



</script>
