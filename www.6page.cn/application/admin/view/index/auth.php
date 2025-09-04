{extend name="public/container"}
{block name='head'}
<style>
    [v-cloak] {
        display: none;
    }
    .active-box{
        max-width: 500px;
        margin: 20px auto;
        text-align: center;
        border: 2px solid #f00;
        padding-bottom: 20px;
    }
    .active-top{
        padding: 20px;
        background-color: #ff0000;
        color: #fff;
    }
    .active-box.active{
        border: 2px solid #0bb20c;
    }
    .active-box.active .active-top{
        background-color: #0bb20c;
    }
    .active-top .active-title{
        font-size: 24px;
    }
    .active-top .active-subtitle{
        font-size: 14px;
        margin-top: 10px;
    }
    .active-content{
        padding: 15px;
    }
    .active-content .layui-form-item{
        margin-bottom: 0;
        text-align: left;
    }
    .lh36{
        line-height: 36px;
    }
    .layui-btn-normal.lk{
        background-color: #ffffff;
        border: 1px solid #ccc;
        color: #333;
    }
    .active-btn{
        margin-top: 20px;
    }
    .red{
        color: #ff0000;
    }
    .green{
        color: #0bb20c;
    }
</style>
{/block}
{block name="content"}
<div v-cloak id="app" class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <div class="active-box unactive" v-if="!status">
                        <div class="active-top">
                            <div class="active-title">
                                系统未激活
                            </div>
                            <div class="active-subtitle">
                                在下方输入激活密钥，点击按钮进行激活
                            </div>
                        </div>
                        <div class="active-content">
                            <textarea style="height: 100px; padding: 10px;" placeholder="请输入激活密钥" name="key" rows="4" required v-model.trim="key" class="layui-input"></textarea>
                            <div class="active-btn">
                                <button class="layui-btn layui-btn-danger" type="button" @click="activate">立即激活</button>
                            </div>
                        </div>
                    </div>
                    <div class="active-box active" v-else>
                        <div class="active-top">
                            <div class="active-title">
                                系统已激活
                            </div>
                            <div class="active-subtitle">
                                正版权益保护中，如有新版会在后台下方显示提醒
                            </div>
                        </div>
                        <div class="active-content">
                            <div class="layui-form-item">
                                <label class="layui-form-label">授权域名：</label>
                                <div class="layui-input-block lh36">
                                    {{access_domain}}
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">购买时间：</label>
                                <div class="layui-input-block lh36">
                                    {{add_date}}
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">到期时间：</label>
                                <div class="layui-input-block lh36">
                                    {{expire_date}}
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">授权时长：</label>
                                <div class="layui-input-block lh36">
                                    {{expire}}天
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">当前版本：</label>
                                <div class="layui-input-block lh36">
                                    {{current_ver}}
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">最新版本：</label>
                                <div class="layui-input-block lh36">
                                    {{ new_ver=='newest' ? '已是最新' : new_ver }}
                                </div>
                            </div>
                        </div>
                        <div class="active-btn">
                            <button class="layui-btn layui-btn-normal lk" type="button" @click="unactivate">取消激活</button>
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
    var activeNotice = window.parent.document.getElementById('active-notice')
    var layer = layui.layer;
    require(['vue', 'axios'], function (Vue, axios) {
        function activateSystem(data) {
            return axios.post("{:Url('activate')}", data);
        }
        function unactivateSystem(data) {
            return axios.post("{:Url('unActive')}", data);
        }
        function update() {
            return axios.get("{:Url('update')}");
        }
        new Vue({
            el: "#app",
            data: {
                key: '',
                status: true,
                access_domain: '',
                add_date: '',
                expire_date: '',
                current_ver: '',
                new_ver: '',
                expire: '',
                spinShow: false,
                loading: null
            },
            created: function () {
                this.getUpdateInfo()
            },
            methods: {
                activate() {
                    this.spinShow = true
                    this.loading = layer.load('Loading...', {
                        shade: [0.5,'#fff']
                    });
                    activateSystem({
                        key: this.key
                    })
                    .then(res => {
                        this.spinShow = false
                        layer.close(this.loading)
                        if (res.data.code > 200) {
                            this.status = false
                            layer.open({
                                title: '激活失败',
                                content: res.data.msg
                            });
                            return
                        }
                        layer.open({
                            title: '激活成功',
                            content: res.data.msg
                        });
                        if (activeNotice) {
                            $(activeNotice).hide()
                        }
                        return this.getUpdateInfo()
                    })
                    .catch(err => {
                        layer.close(this.loading)
                        console.error(err.msg)
                        this.spinShow = false
                    })
                },
                getUpdateInfo() {
                    this.spinShow = true
                    return update()
                    .then(res => {
                        if (res.data.code > 200) {
                            this.status = false
                            return
                        }
                        this.status = true
                        this.access_domain = res.data.data.access_domain
                        this.add_date = res.data.data.add_date
                        this.expire_date = res.data.data.expire_date
                        this.current_ver = res.data.data.current_ver
                        this.new_ver = res.data.data.new_ver
                        this.expire = res.data.data.expire
                        this.spinShow = false
                    })
                    .catch(err => {
                        this.status = false
                        this.spinShow = false
                    })
                },
                unactivate() {
                    this.spinShow = true
                    this.loading = layer.load('Loading...', {
                        shade: [0.5,'#fff']
                    });
                    unactivateSystem()
                    .then(res => {
                        layer.close(this.loading)
                        if (res.data.code > 200) {
                            layer.open({
                                title: '操作失败',
                                content: res.data.msg
                            });
                            return
                        }
                        this.status = false
                        this.spinShow = false
                        layer.open({
                            title: '操作成功',
                            content: res.data.msg
                        });
                    })
                    .catch(res => {
                        layer.close(this.loading)
                        this.spinShow = false
                    })
                }
            }
        });
    });
</script>
{/block}
