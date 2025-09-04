{extend name="public/container"}
{block name='head'}
<style>
    [v-cloak] {
        display: none;
    }
    .active-wrapper{
        padding: 100px 0;
        text-align: center;
    }
    .active-btn{
        margin-top: 20px;
    }
    .active-tips{
        font-size: 14px;
        margin-top: 10px;
    }
</style>
{/block}
{block name="content"}
<div v-cloak id="app" class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="active-wrapper">
                    <h3>系统尚未激活，部分功能受限，完成激活后可使用全部功能</h3>
                    <div class="active-tips">请进入“系统设置-授权激活”完成激活操作，感谢您的理解；</div>
                    <div class="active-btn">
                        <a class="layui-btn layui-btn-normal" href="{:url('admin/index/auth')}">去激活</a>
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
    require(['vue', 'axios'], function (Vue, axios) {
        new Vue({
            el: "#app",
            data: {
            },
            created: function () {
            },
            methods: {
            }
        });
    });
</script>
{/block}
