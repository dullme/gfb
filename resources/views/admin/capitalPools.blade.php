<div class="row">
    <!-- ./col -->
    <div class="col-md-4 col-lg-3">
        <!-- small box -->
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>{{ $amount['ad_fee'] }}<sup style="font-size: 20px">元</sup></h3>

                <p>广告费总额</p>
            </div>
            <div class="icon">
                <i class="fa fa-money"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-3">
        <!-- small box -->
        <div class="small-box bg-green">
            <div class="inner">
                <h3>{{ $amount['ad_fee'] - $amount['amount'] }}<sup style="font-size: 20px">元</sup></h3>

                <p>资金池金额</p>
            </div>
            <div class="icon">
                <i class="fa fa-jpy"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-md-4 col-lg-3">
        <!-- small box -->
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>{{ $amount['amount'] }}<sup style="font-size: 20px">元</sup></h3>

                <p>分润总额</p>
            </div>
            <div class="icon">
                <i class="fa fa-money"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-3">
        <!-- small box -->
        <div class="small-box bg-red">
            <div class="inner">
                <h3>{{ $amount['withdraw'] }}<sup style="font-size: 20px">元</sup></h3>

                <p>提现总额</p>
            </div>
            <div class="icon">
                <i class="fa fa-jpy"></i>
            </div>
        </div>
    </div>
</div>