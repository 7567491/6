{extend name="public/container"}
{block name="head"}
<style>
    .layui-table th, .layui-table td {
        text-align: center;
    }
</style>
{/block}
{block name="content"}
<div v-cloak id="app" class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            <h2>更换域名</h2>
            <p style="margin-top: 10px">该功能是为了方便网站后期更换域名，将所有已上传的图片等静态文件的链接地址域名批量替换为其它域名；</p>
            <p>建议先将新域名解析绑定好再执行此操作，否则会导致所有图片打不开；</p>
            <p>假如要将网站域名由5usujian.com改为5usujian.com，则在下方旧域名中输入：5usujian.com，新域名输入：5usujian.com；</p>
            <p style="color: #f00">注意区分http和https！根据自身情况填写！操作前建议先备份数据库，以防万一！</p>
            <div class="layui-form-item" style="margin-top: 20px;">
                <div class="layui-inline">
                    <label class="layui-form-label">旧域名：</label>
                    <div class="layui-input-inline">
                        <input class="layui-input" v-model="old_domain" />
                    </div>
                </div>
                <div style="padding-left: 110px;">输入旧域名，以http://或https://开头，结尾不要有/，一般与系统设置-基础配置-网站地址保持一致，如：http://5usujian.com；</div>
            </div>
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">新域名：</label>
                    <div class="layui-input-inline">
                        <input class="layui-input" v-model="new_domain" />
                    </div>
                </div>
                <div style="padding-left: 110px;">输入新域名，以http://或https://开头，结尾不要有/，如：http://5usujian.com；</div>
            </div>
            <div class="layui-form-item" style="padding-left: 110px;">
                <button class="layui-btn layui-btn-danger" type="button" @click="change">立即更换</button>
            </div>
        </div>
    </div>
</div>
{/block}
{block name="script"}
<script>
    var layer = layui.layer;
    require(['vue', 'axios'], function (Vue, axios) {
        function changeDomain(data) {
            return axios.post("{:Url('changeDomain')}", data);
        }
        new Vue({
            el: "#app",
            data: {
                old_domain: '',
                new_domain: '',
                loading: null
            },
            created: function () {

            },
            methods: {
                change() {
                    if (confirm('确定执行更换域名操作吗？')) {
                        this.loading = layer.load('Loading...', {
                            shade: [0.5,'#fff']
                        });
                        changeDomain({
                            old_domain: this.old_domain,
                            new_domain: this.new_domain,
                        })
                        .then(res => {
                            layer.close(this.loading)
                            if (res.data.code > 200) {
                                layer.open({
                                    title: '更换失败',
                                    content: res.data.msg
                                });
                                return
                            }
                            layer.open({
                                title: '激活成功',
                                content: res.data.msg
                            });
                        })
                        .catch(err => {
                            layer.close(this.loading)
                            console.error(err.msg)
                        })
                    }
                }
            }
        });
    });
</script>
{/block}
