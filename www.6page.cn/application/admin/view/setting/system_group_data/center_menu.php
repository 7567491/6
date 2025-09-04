{extend name="public/container"}
{block name="content"}
<div class="layui-fluid">
    <div class="layui-row layui-col-space15"  id="app">
        <!--产品列表-->
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="font-weight: bold;">个人中心菜单</div>
                </div>
                <div class="layui-card-body">
                    <div class="layui-btn-group">
                        <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" >个人中心题库模块</button>
                        <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" onclick="window.location.reload()">
                            <i class="layui-icon">&#xe669;</i>刷新
                        </button>
                    </div>
                    <table id="Lists" lay-filter="Lists"></table>
                    <script type="text/html" id="icons">
                        {{# if(d.icon) { }}
                        <img lay-event='open_images' src="{{d.icon}}" height="50">
                        {{# } }}
                    </script>
                    <script type="text/html" id="status">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='status'
                               lay-text='显示|隐藏' {{ d.status== 1 ? 'checked' : '' }}>
                    </script>
                    <script type="text/html" id="acts">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="$eb.createModalFrame('编辑','{:Url('edit')}?gid={$gid}&id={{d.id}}')"><i class="fa fa-paste"></i>编辑</button>
                    </script>
                </div>
                <div class="layui-card-body">
                    <div class="layui-btn-group">
                        <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" onclick="$eb.createModalFrame('添加我的服务菜单','{:Url('create_recemmend_custom')}?is_fixed=2')"><i class="layui-icon">&#xe608;</i>添加我的服务菜单</button>
                        <a target="_blank" href="{:Url('setting.system_config/mp_page_list')}" class="layui-btn layui-btn-normal layui-btn-sm">系统页面链接查询</a>
                    </div>
                    <table id="List" lay-filter="List"></table>
                    <script type="text/html" id="icon">
                        {{# if(d.icon) { }}
                        <img src="{{d.icon}}" height="50" onclick="open_icon('{{d.icon}}')">
                        {{# } }}
                    </script>
                    <script type="text/html" id="is_show">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='is_show' lay-text='显示|隐藏'  {{ d.is_show == 1 ? 'checked' : '' }}>
                    </script>
                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="dropdown(this)"><i class="layui-icon">&#xe625;</i>操作</button>
                        <ul class="layui-nav-child layui-anim layui-anim-upbit">
                            <li>
                                <div onclick="$eb.createModalFrame('{{d.title}}-'+this.innerText,'{:Url('create_recemmend_custom')}?id={{d.id}}&is_fixed=2')">
                                    <i class="fa fa-paste"></i> 编辑
                                </div>
                            </li>
                            <li>
                                <div onclick="delete_menu({{d.id}})">
                                    <i class="fa fa-trash"></i> 删除
                                </div>
                            </li>
                        </ul>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}
{block name="script"}
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<script>
    //实例化form
    layList.form.render();
    var gid="{$gid}";
    //加载列表
    layList.tableList('List',"{:Url('recommend_list',['is_fixed' => 2])}",function (){
        return [
            {field: 'title', title: '导航名称',align:'center'},
            {field: 'icon', title: '图标',templet:'#icon',align:'center'},
            {field: 'sort', title: '排序',sort: true,event:'sort',align:'center'},
            {field: 'is_show', title: '状态',templet:'#is_show',align:'center'},
            {field: 'right', title: '操作',align:'center',toolbar:'#act'},
        ];
    });
    function delete_menu(id) {
        var url=layList.U({a:'delete_recomm',q:{id:id}});
        $eb.$swal('delete',function(){
            $eb.axios.get(url).then(function(res){
                if(res.status == 200 && res.data.code == 200) {
                    $eb.$swal('success',res.data.msg);
                    location.reload()
                }else
                    return Promise.reject(res.data.msg || '删除失败')
            }).catch(function(err){
                $eb.$swal('error',err);
            });
        });
    }
    function open_icon(icon) {
        $eb.openImage(icon);
    }
    //加载列表
    layList.tableList('Lists', "{:Url('get_group_data_list')}?gid="+gid, function () {
        return [
            {field: 'title', title: '标题',align:'center'},
            {field: 'explain', title: '说明',align:'center'},
            {field: 'icon', title: '图标', templet: '#icons',align:'center'},
            {field: 'sort', title: '排序',align:'center'},
            {field: 'status', title: '状态', templet: '#status',align:'center'},
            {field: 'right', title: '操作', align: 'center', toolbar: '#acts'},
        ];
    });
    //自定义方法
    var action= {
        set_value: function (field, id, value) {
            layList.baseGet(layList.Url({
                a: 'set_value',
                q: {field: field, id: id, value: value}
            }), function (res) {
                layList.msg(res.msg);
            });
        },
        set_group_data: function (field, id, value) {
            layList.baseGet(layList.Url({
                a: 'set_group_data',
                q: {field: field, id: id, value: value}
            }), function (res) {
                layList.msg(res.msg);
            });
        }
    };
    layList.switch('status', function (odj, value) {
        action.set_group_data('status', value, odj.elem.checked == true ? 1 : 0);
    });
    layList.switch('is_show',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({a:'set_show',p:{is_show:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({a:'set_show',p:{is_show:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    //快速编辑
    layList.edit(function (obj) {
        var id=obj.data.id,value=obj.value;
        switch (obj.field) {
            case 'title':
                action.set_value('title',id,value);
                break;
            case 'sort':
                if (value.trim()) {
                    if (isNaN(value.trim())) {
                        layList.msg('请输入正确的数字');
                    } else {
                        if (value.trim() < 0) {
                            layList.msg('排序不能小于0');
                        } else if (value.trim() > 9999) {
                            layList.msg('排序不能大于9999');
                        } else if (parseInt(value.trim()) != value.trim()) {
                            layList.msg('排序不能为小数');
                        } else {
                            action.set_value('sort', id, value.trim());
                        }
                    }
                } else {
                    layList.msg('排序不能为空');
                }
                break;
        }
    }, false, 'List');
    //下拉框
    $(document).click(function (e) {
        $('.layui-nav-child').hide();
    })
    function dropdown(that){
        var oEvent = arguments.callee.caller.arguments[0] || event;
        oEvent.stopPropagation();
        var offset = $(that).offset();
        var top=offset.top-$(window).scrollTop();
        var index = $(that).parents('tr').data('index');
        $('.layui-nav-child').each(function (key) {
            if (key != index) {
                $(this).hide();
            }
        })
        if($(document).height() < top+$(that).next('ul').height()){
            $(that).next('ul').css({
                'padding': 10,
                'top': - ($(that).parent('td').height() / 2 + $(that).height() + $(that).next('ul').height()/2),
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }else{
            $(that).next('ul').css({
                'padding': 10,
                'top':$(that).parent('td').height() / 2 + $(that).height(),
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }
    }
    //监听并执行排序
    layList.sort(['sort'],true);

</script>
{/block}
