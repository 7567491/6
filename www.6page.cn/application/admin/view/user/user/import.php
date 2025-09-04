{extend name="public/container"}
{block name="content"}
<div class="layui-fluid">
    <div v-cloak class="layui-row layui-col-space15" id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <div class="layui-form-item">
                        <div class="layui-form-item">
                            <label class="layui-form-label">上传文件：</label>
                            <div class="layui-input-block">
                                <input type="text" name="title" v-model="link" style="width:50%;display:inline-block;margin-right: 10px;" autocomplete="off" disabled placeholder="文件位置" class="layui-input">
                                <label style="display: inline;" class="file">
                                    <button type="button" id="ossuploads" class="ossupload layui-btn layui-btn-sm layui-btn-normal">上传文件</button>
                                </label>
                                <button class="layui-btn layui-btn-sm" type="button" @click="save">导入</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name='script'}
<script>
    require(['vue','request'],function(Vue) {
        new Vue({
            el: "#app",
            data: {
                link:''
            },
            methods:{
                save:function () {
                    var that=this;
                    if(!that.link) return layList.msg('请上传需要导入的文件');
                    layList.loadFFF();
                    layList.basePost(layList.U({a:'importUsers'}),{link:that.link},function (res) {
                        layList.loadClear();
                        layer.msg('导入成功',{icon:1},function () {
                            parent.layer.closeAll();
                            window.location.reload();
                        });
                    },function (res) {
                        layList.msg(res.msg);
                        layList.loadClear();
                    });
                }
            },
            mounted:function () {
                var that=this;
                layui.use('upload', function(){
                    var upload = layui.upload;
                    //执行实例
                    upload.render({
                        elem: '#ossuploads' //绑定元素
                        ,accept: 'file'
                        ,url: "{:Url('file_import_upload')}" //上传接口
                        ,done: function(res){
                            that.link =res.data.filePath;
                            layList.msg('上传成功');
                        }
                        ,error: function(err){
                            //请求异常回调
                            layList.msg(err);
                        }
                    });
                });
            }
        })
    })
</script>
{/block}
