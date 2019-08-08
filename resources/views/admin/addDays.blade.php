<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">修改</h3>

        <div class="box-tools">
            <div class="btn-group pull-right" style="margin-right: 5px">
                <a href="{{ url('/admin/users') }}" class="btn btn-sm btn-default" title="列表"><i class="fa fa-list"></i><span class="hidden-xs">&nbsp;列表</span></a>
            </div>
        </div>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form accept-charset="UTF-8" class="form-horizontal">

        <div class="box-body">
            <div class="fields-group">
                <div class="form-group  ">
                    <label for="validity_period" class="col-sm-2  control-label">增加/减少有效期限/天</label>
                    <div class="col-sm-2" style="display: inline-flex">
                        <div class="input-group">
                            <select id="type" style="height: 34px;">
                                <option value="1">增加</option>
                                <option value="2">减少</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <input style="width: 150px; text-align: center;" type="text" id="add_validity_period" name="add_validity_period" value="0" class="form-control validity_period initialized" placeholder="增加/减少有效期限/天">
                        </div>
                        <a id="check-expiration" onclick="checkaddDays()" style="margin-left: 10px" class="btn btn-default" >修改</a>
                    </div>
                    <div style="clear: both"></div>
                    <label style="margin-left: 224px;margin-top: 40px" class="control-label" for="inputError"><span id="check-add-days-message" style="color: #dd4b39"></span></label>

                </div>
            </div>
        </div>
    </form>
</div>

<script>
    var lock = true;
    function checkaddDays() {
        $('#check-add-days-message').html("");
        var add_validity_period = $('#add_validity_period').val();
        var type = $('#type').val();
        if(add_validity_period == '' || add_validity_period == 0){
            $('#check-add-days-message').html("增加/减少天数不能为空或0");
            return false;
        }

        if(lock){
            if (window.confirm("确定修改吗？此操作会清空所有服务器上的Redis数据请谨慎操作！")) {
                this.lock = false;
                $('#check-add-days-message').html('修改中... ...');
                axios.post('/admin/add-days', {
                    add_validity_period: add_validity_period,
                    type: type
                })
                    .then(function (response) {
                        if(response.data.status){
                            $('#check-add-days-message').html('<span style="color:#3c763d">'+response.data.message+'</span>');
                        }else{
                            $('#check-add-days-message').html(response.data.message);
                        }
                        this.lock = true;
                    })
                    .catch(function (error) {
                        $('#check-add-days-message').html(error.response.data.message);
                        this.lock = true;
                    });
            } else {
                return false;
            }
        }else{
            alert('不允许重复修改');
        }


    }
</script>
