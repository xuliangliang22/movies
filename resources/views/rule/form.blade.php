<form {!! $attributes !!}>
    <div class="box-body fields-group">

        @foreach($fields as $field)
            {!! $field->render() !!}
        @endforeach

    </div>

    <!-- /.box-body -->
    <div class="box-footer">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <div class="col-sm-2 pull-right">
            <a class="btn btn-info" id="running">运行</a>
        </div>

    </div>
</form>
<div id="shade" style="position:absolute;top:0px;left: 0px;width:100%;height:100%;z-index: 100;background: rgba(0,0,0,.3);;display: none;text-align: center;color: #f00;font-size: 25px;font-style: italic;box-sizing: border-box;padding-top: 300px;overflow: auto;">请等待,不要点击或刷新..</div>

<script>
    $(function(){
        $('select[name=arctype]').change(function(){
            var typeid = $(this).val();
            $('form .gurl').parent().parent().remove();
            $('form .rule').remove();
            $.get('/admin/rule_run?type=gurl&id='+typeid,function(data){
                if(data != ''){
                    $('form .box-body').append(data);
                }
            },'html');
        });

        $('form').on('change','select[name=gurl]',function(data){
            var gurlid = $(this).val();
            $('form .rule').remove();
            $.get('/admin/rule_run?type=rule&id='+gurlid,function(data){
                if(data != ''){
                    $('form .box-body').append(data);
                }
            },'html');
        });
    });


    //保存列表页的内容
    $('#running').click(function () {
        //做一个遮罩效果
        $('#shade').show();
        _run_interval(false);

        var args = _get_ruleid_args();

        if(isNaN(args[0]) || args[0] < 1){
            alert('保存列表,规则id'+args[0]+'不正确,请重新提交!');
            return false;
        }
        _run('command',args[0],args[1]);
    });

    function _run_interval(clear_run) {
        $play = setInterval(function () {
            var stext = $('#shade').text();
            var ptext = stext+'.';
            $('#shade').text(ptext);
        },1000);

        if(clear_run){
            clearInterval($play);
        }
    }

    //获得规则id与参数列表
    function _get_ruleid_args() {
        var rule_id = $('input[name=rule]').data('ruleid');
        //获得参数列表
        var args = '';
        $('.args input').each(function (i) {
            arg_name = $(this).attr('name');
            arg_value = $(this).val();
            args += '&'+arg_name+'='+arg_value;
        });
        return [rule_id,args];
    }


    function _run(type,id,other){
        $.get('/admin/rule_run?type='+type+'&id='+id+other,function(data){
            $('#shade').hide();
            _run_interval(true);
            if(data == ''){
               astr = '请求失败,请检查!';
            }else {
               astr = data;
            }
            alert(astr);
        },'html');
    }
</script>
