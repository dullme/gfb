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

                <div class="form-group">
                    <label for="number" class="col-sm-2  control-label">用户名</label>

                    <label class="control-label" for="inputError"><span id="check-expiration-message" style="color: #dd4b39"></span></label>
                    <div class="col-sm-3" style="display: inline-flex">
                        <div class="input-group">
                            <input id="start_id" type="text" class="form-control" placeholder="用户名" name="id[start]" value="">
                            <span class="input-group-addon" style="border-left: 0; border-right: 0;">-</span>
                            <input id="end_id" type="text" class="form-control" placeholder="用户名" name="id[end]" value="">
                        </div>
                        <a id="check-expiration" onclick="checkExpiration()" style="margin-left: 10px" class="btn btn-default" >修改</a>
                    </div>
                </div>

                <div class="form-group  ">
                    <label for="validity_period" class="col-sm-2  control-label">是否强制覆盖</label>
                    <div class="col-sm-3">
                        <select id="staff-list-qiangzhi" class="form-control">
                            <option value="0">否</option>
                            <option value="1">是</option>
                        </select>
                    </div>
                </div>

                <div class="form-group  ">
                    <label for="validity_period" class="col-sm-2  control-label">选择员工</label>
                    <div class="col-sm-3">
                        <select id="staff-list" class="form-control">
                            @foreach($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>


            </div>

        </div>
    </form>
</div>

<script>
    $(function () {
        $("#staff-list").select2({placeholder: '请选择'});
        $("#staff-list-qiangzhi").select2({placeholder: '请选择'});
    })


    var lock = true;
    function checkExpiration() {
        $('#check-expiration-message').html("");
        var start_id = $('#start_id').val();
        var end_id = $('#end_id').val();
        var staff_id = $('#staff-list').val();
        var staff_list_qiangzhi = $('#staff-list-qiangzhi').val();

        if(start_id == '' || end_id == ''){
            $('#check-expiration-message').html("开始或结束用户名不能为空");
            return false;
        }
        if(start_id > end_id){
            $('#check-expiration-message').html("开始用户名不能大于结束用户名");
            return false;
        }


        if(lock){
            if (window.confirm("确定修改吗？")) {
                this.lock = false;
                $('#check-expiration-message').html('修改中... ...');
                axios.post('/admin/edit-staff', {
                    start_id: start_id,
                    end_id: end_id,
                    staff_id: staff_id,
                    staff_list_qiangzhi: staff_list_qiangzhi,
                })
                    .then(function (response) {


                        if(response.data.status == 200){

                            swal(
                                "SUCCESS",
                                response.data.message,
                                'success'
                            );
                        } else{
                            swal(
                                "INFO",
                                response.data.message,
                                'info'
                            )
                        }
                        this.lock = true;
                        $('#check-expiration-message').html(response.data.message);
                    })
                    .catch(function (error) {
                        $('#check-expiration-message').html(error.response.data.message);
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
