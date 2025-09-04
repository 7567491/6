{extend name="public/container"}
{block name="content"}
<div class="layui-fluid">
    <div class="layui-row layui-col-space15"  id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">搜索条件</div>
                <div class="layui-card-body">
                    <div class="layui-form layui-form-pane">
                        <div class="layui-form-item">
                            练习名称：
                            <div class="layui-inline">
                                <input class="layui-input" name="title" id="demoReload" placeholder="请输入练习名称">
                            </div>
                            <div class="layui-inline">
                                <label class="layui-form-label">练习类型</label>
                                <div class="layui-input-block">
                                    <select name="type" id="test">
                                        <option value="">全部</option>
                                        <option value="1">练习</option>
                                        <option value="2">考试</option>
                                    </select>
                                </div>
                            </div>
                            <button class="layui-btn layui-btn-normal layui-btn-sm" lay-submit="search" lay-filter="search">搜索</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">练习列表</div>
                <div class="layui-card-body">
                    <div class="layui-btn-container conrelTable">
                        <button class="layui-btn layui-btn-normal layui-btn-sm" type="button" data-type="add_test_paper">
                            确定
                        </button>
                        <button class="layui-btn layui-btn-normal layui-btn-sm" onclick="window.location.reload()"><i class="layui-icon layui-icon-refresh"></i> 刷新</button>
                    </div>
                    <table class="layui-hide" id="List" lay-filter="List"></table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name="script"}
<script>
    var uid="{$uid}";
    //实例化form
    layList.form.render();
    //加载列表
    layList.tableList({o:'List', done:function () {}},"{:Url('admin/educational.student/getTestPaperListTest')}",function (){
        return [
            {type:'checkbox'},
            {field: 'id', title: '编号', width:'8%',align: 'center'},
            {field: 'cate', title: '练习分类', width:'12%', align: 'center'},
            {field: 'title', title: '练习标题'},
        ];
    },20);
    //查询
    layList.search('search',function(where){
        layList.reload({
            type:where.type,
            title: where.title
        },true);
    });
    $('.conrelTable').find('button').each(function () {
        var type=$(this).data('type');
        $(this).on('click',function () {
            action[type] && action[type]();
        })
    });
    var action={
        add_test_paper:function () {
            var ids=layList.getCheckData().getIds('id');
            if(ids.length){
                var str = ids.join(',');
                layList.baseGet(layList.Url({
                    a: 'sendTestPaper',
                    q: {uid: uid, tid: str}
                }), function (res) {
                    layList.msg(res.msg,function () {
                        parent.layer.closeAll();
                    });
                });
            }else{
                layList.msg('请选择需要发送的练习');
            }
        },
        refresh:function () {
            layList.reload();
        }
    };
</script>
{/block}
