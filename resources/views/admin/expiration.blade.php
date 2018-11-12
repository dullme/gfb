<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">修改</h3>

        <div class="box-tools">
            <div class="btn-group pull-right" style="margin-right: 5px">
                <a href="http://gfb.test/admin/users" class="btn btn-sm btn-default" title="列表"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>
            </div>
        </div>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form action="http://gfb.test/admin/users" method="post" accept-charset="UTF-8" class="form-horizontal" pjax-container="">

        <div class="box-body">

            <div class="fields-group">

                <div class="form-group">
                    <label for="number" class="col-sm-2  control-label">新增数量</label>

                    <label class="control-label" for="inputError"><span id="check-expiration-message" style="color: #dd4b39"></span></label>
                    <div class="col-sm-3" style="display: inline-flex">
                        <div class="input-group">
                            <input id="start_id" type="text" class="form-control" placeholder="用户名" name="id[start]" value="">
                            <span class="input-group-addon" style="border-left: 0; border-right: 0;">-</span>
                            <input id="end_id" type="text" class="form-control" placeholder="用户名" name="id[end]" value="">
                        </div>
                        <a id="check-expiration" style="margin-left: 10px" class="btn btn-default">修改</a>
                    </div>
                </div>

                <div class="form-group  ">
                    <label for="validity_period" class="col-sm-2  control-label">有效期限/月</label>
                    <div class="col-sm-8">
                        <div class="input-group">
                            <input style="width: 100px; text-align: center;" type="text" id="validity_period" name="validity_period" value="3" class="form-control validity_period initialized" placeholder="输入 有效期限/月">
                        </div>
                    </div>
                </div>


            </div>

        </div>
    </form>
</div>

<script>
    $(function () {
        $('#check-expiration').click(function () {
            $('#check-expiration-message').html("");
            var start_id = $('#start_id').val();
            var end_id = $('#end_id').val();
            var validity_period = $('#validity_period').val();
            if(start_id == '' || end_id == ''){
                $('#check-expiration-message').html("开始或结束用户名不能为空");
                return false;
            }
            if(start_id > end_id){
                $('#check-expiration-message').html("开始用户名不能大于结束用户名");
                return false;
            }
            if(validity_period == '' || validity_period == 0){
                $('#check-expiration-message').html("有效期不能为零或空");
                return false;
            }

            axios.post('/admin/edit-expiration', {
                start_id: start_id,
                end_id: end_id
            })
            .then(function (response) {
                alert(response.data.message);
            })
            .catch(function (error) {
                alert(error.data.message);
            });

        });

    })
</script>