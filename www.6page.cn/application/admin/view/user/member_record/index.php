{extend name="public/container"}
{block name="head_top"}

{/block}
{block name="content"}
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="font-weight: bold;">会员记录</div>
                </div>
                <div class="layui-card-body">
                    <div class="layui-row layui-col-space15">
                        <div class="layui-col-md12">
                            <form class="layui-form layui-form-pane" action="">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">用户昵称</label>
                                        <div class="layui-input-inline">
                                            <input type="text" name="title" lay-verify="title" class="layui-input" placeholder="UID、昵称">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">类别</label>
                                        <div class="layui-input-inline">
                                            <select name="type" lay-verify="type">
                                                <option value="">全部</option>
                                                <option value="6">免费</option>
                                                <option value="1">月卡</option>
                                                <option value="2">季卡</option>
                                                <option value="3">年卡</option>
                                                <option value="4">终身卡</option>
                                                <option value="5">卡密</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <div class="layui-input-inline">
                                            <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                                <i class="layui-icon">&#xe615;</i>搜索
                                            </button>
                                            <button class="layui-btn layui-btn-normal layui-btn-sm" onclick="window.location.reload()">
                                                <i class="layui-icon">&#xe669;</i>刷新
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="layui-col-md12">
                            <table class="layui-hide" id="List" lay-filter="List"></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name="script"}
<script>
    layList.form.render();
    layList.tableList({o:'List'},layList.U({a:'member_record_list'}),function (){
        return [
            {field: 'id', title: '编号', align: 'center',width:'5%'},
            {field: 'uid', title: '昵称/UID',align: 'center'},
            {field: 'title', title: '类别',align: 'center'},
            {field: 'source', title: '来源',align: 'center'},
            {field: 'validity', title: '有效期',align: 'center'},
            {field: 'price', title: '优惠价',align: 'center'},
            {field: 'code', title: '卡号',align: 'center'},
        ];
    });
    //查询
    layList.search('search',function(where){
        layList.reload(where,true);
    });
    layList.tool(function (layEvent,data,obj) {
        switch (layEvent){
            case 'delete':
                var url=layList.U({a:'delete',q:{id:data.id}});
                $eb.$swal('delete',function(){
                    $eb.axios.get(url).then(function(res){
                        if(res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success',res.data.msg);
                            obj.del();
                        }else
                            return Promise.reject(res.data.msg || '删除失败')
                    }).catch(function(err){
                        $eb.$swal('error',err);
                    });
                });
                break;
        }
    });
</script>
{/block}
