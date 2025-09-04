{extend name="public/container"}
{block name="head_top"}
<link rel="stylesheet" type="text/css" href="{__ADMIN_PATH}css/main.css" />
<link href="{__FRAME_PATH}css/plugins/iCheck/custom.css" rel="stylesheet">
{/block}
{block name="content"}
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <div id="app" class="row">
                        <div class="col-sm-12">
                            <div class="wechat-reply-wrapper wechat-menu">
                                <div class="ibox-content clearfix">
                                    <div class="view-wrapper col-sm-4">
                                        <div class="mobile-header">公众号</div>
                                        <section class="view-body">
                                            <div class="time-wrapper"><span class="time">9:36</span></div>
                                        </section>
                                        <div class="menu-footer">
                                            <ul class="flex">
                                                <li v-for="(menu, index) in menus" :class="{active:menu === checkedMenu}">
                                                      <span @click="activeMenu(menu,index,null)"><i class="icon-sub"></i>{{ menu.name || '一级菜单' }}</span>
                                                      <div class="sub-menu">
                                                          <ul>
                                                              <li v-for="(child, cindex) in menu.sub_button" :class="{active:child === checkedMenu}">
                                                                  <span @click="activeMenu(child,cindex,index)">{{ child.name || '二级菜单' }}</span>
                                                              </li>
                                                              <li v-if="menu.sub_button.length < 5" @click="addChild(menu,index)"><i class="icon-add"></i></li>
                                                          </ul>
                                                      </div>
                                                  </li>
                                                <li v-if="menus.length < 3" @click="addMenu()"><i class="icon-add"></i></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="control-wrapper menu-control col-sm-8" v-show="checkedMenuId !== null">
                                        <section>
                                            <div class="control-main">
                                                <h3 class="popover-title">菜单名称 <a class="fr" href="javascript:void(0);" @click="delMenu">删除</a></h3>
                                                <p class="tips-txt">已添加子菜单，仅可设置菜单名称。</p>
                                                <div class="menu-content control-body">
                                                    <form action="">
                                                        <div class="form-group clearfix">
                                                            <label for="" class="col-sm-2">菜单名称</label>
                                                            <div class="col-sm-9 group-item">
                                                                <input type="text" placeholder="菜单名称" class="form-control" v-model="checkedMenu.name">
                                                                <span>字数不超过13个汉字或40个字母</span>
                                                            </div>
                                                        </div>
                                                        <div class="form-group clearfix">
                                                            <label class="col-sm-2 control-label tips" for="">规则状态</label>
                                                            <div class="group-item col-sm-9">
                                                                <select class="form-control m-b" name="" id="" v-model="checkedMenu.type">
                                                                    <option value="click">关键字</option>
                                                                    <option value="view">跳转网页</option>
                                                                    <option value="miniprogram">小程序</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="menu-control-box">
                                                            <!-- 关键字 -->
                                                            <div class="keywords item" :class="{show:checkedMenu.type=='click'}">
                                                                <p>关键字</p>
                                                                <input type="text" placeholder="请输入关键字" class="form-control" v-model="checkedMenu.key">
                                                            </div>
                                                            <!-- 跳转地址 -->
                                                            <div class="url item" :class="{show:checkedMenu.type=='view'}">
                                                                <p>跳转地址</p>
                                                                <input type="text" v-model="checkedMenu.url" placeholder="请输入跳转地址" class="form-control">
                                                                <p class="text-left"></p>
                                                                <div class="well well-lg">
                                                                        <span class="help-block m-b-none">首页：{$Request.domain}{$m_home_url}</span>
                                                                        <span class="help-block m-b-none">个人中心：{$Request.domain}{:url('/m/my')}</span>
                                                                </div>
                                                            </div>
                                                            <!-- 小程序 -->
                                                            <div class="url item" :class="{show:checkedMenu.type=='miniprogram'}">
                                                                <p>小程序appid</p>
                                                                <input type="text" v-model="checkedMenu.appid" placeholder="请输入appid" class="form-control">
                                                                <p style="margin-top: 10px;">需要打开的小程序页面</p>
                                                                <input type="text" v-model="checkedMenu.pagepath" placeholder="需要打开的小程序页面，首页为：pages/index/index" class="form-control">
                                                                <p class="text-left"></p>
                                                                <div style="margin-top: 10px;" class="well well-lg">
                                                                    <span class="help-block m-b-none">小程序页面索引请<a style="color: #0d8ddb; text-decoration: underline" href="{:Url('setting.system_config/mp_page_list')}" target="_blank">点击此处获取</a></span>
                                                                    <span class="help-block m-b-none">appid请到<a style="color: #0d8ddb; text-decoration: underline" target="_blank" href="https://mp.weixin.qq.com/">微信小程序管理后台</a>的开发管理中获取</span>
                                                                </div>
                                                            </div>
                                                            <!-- 多客服 -->
                                                            <div class="service item">
                                                                <p>回复内容</p>
                                                                <textarea  cols="60" rows="10"></textarea>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                <div class="ibox-content submit">
                                    <button class="btn btn-w-m btn-primary" @click="submit">保存发布</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}
{block name="script"}
<script src="{__FRAME_PATH}js/plugins/iCheck/icheck.min.js"></script>
<script src="{__FRAME_PATH}js/bootstrap.min.js"></script>
<script src="{__FRAME_PATH}js/content.min.js"></script>
<script src="{__PLUG_PATH}reg-verify.js"></script>
<script type="text/javascript">
    $eb = parent._mpApi;
    $eb.mpFrame.start(function(Vue){
        var $http = $eb.axios;
        const vm = new Vue({
            data:{
                menus:<?=$menus?>,
                checkedMenu:{
                    type:'click',
                    name:''
                },
                checkedMenuId:null,
                parentMenuId:null
            },
            methods:{
                defaultMenusData:function(){
                    return {
                        type:'click',
                        name:'',
                        sub_button:[]
                    };
                },
                defaultChildData:function(){
                    return {
                        type:'click',
                        name:''
                    };
                },
                addMenu:function(){
                    if(!this.check()) return false;
                    var data = this.defaultMenusData(),id = this.menus.length;
                    this.menus.push(data);
                    this.checkedMenu = data;
                    this.checkedMenuId = id;
                    this.parentMenuId = null;
                },
                addChild:function(menu,index){
                    if(!this.check()) return false;
                    var data = this.defaultChildData(),id = menu.sub_button.length;
                    menu.sub_button.push(data);
                    this.checkedMenu = data;
                    this.checkedMenuId = id;
                    this.parentMenuId = index;
                },
                delMenu:function(){
                    console.log(this.parentMenuId);
                    this.parentMenuId === null ?
                        this.menus.splice(this.checkedMenuId,1) : this.menus[this.parentMenuId].sub_button.splice(this.checkedMenuId,1);
                    this.parentMenuId = null;
                    this.checkedMenu = {};
                    this.checkedMenuId = null;
                },
                activeMenu:function(menu,index,pid){
                    if(!this.check()) return false;
                    pid === null ?
                        (this.checkedMenu = menu) : (this.checkedMenu = this.menus[pid].sub_button[index],this.parentMenuId = pid);
                    this.checkedMenuId=index
                },
                check:function(){
                    if(this.checkedMenuId === null) return true;
                    if(!this.checkedMenu.name){
                        $eb.message('请输入按钮名称!');
                        return false;
                    }
                    if(this.checkedMenu.type == 'click' && !this.checkedMenu.key){
                        $eb.message('请输入关键字!');
                        return false;
                    }
                    if(this.checkedMenu.type == 'view' && !$reg.isHref(this.checkedMenu.url)){
                        $eb.message('请输入正确的跳转地址!');
                        return false;
                    }
                    if(this.checkedMenu.type == 'miniprogram'
                        && (!this.checkedMenu.appid
                        || !this.checkedMenu.pagepath
                        || !this.checkedMenu.url)){
                        $eb.message('请填写完整小程序配置!');
                        return false;
                    }
                    return true;
                },
                submit:function(){
                    if(!this.menus.length){
                        $eb.message('error','请添加菜单!');
                        return false;
                    }
                    // 遍历菜单并给所有type是miniprogram的添加url
                    for(var i=0;i<this.menus.length;i++){
                        var menu = this.menus[i];
                        if(menu.type == 'miniprogram'){
                            menu.url = 'https://mp.weixin.qq.com';
                        }
                        if(menu.sub_button && menu.sub_button.length){
                            for(var j=0;j<menu.sub_button.length;j++){
                                var child = menu.sub_button[j];
                                if(child.type == 'miniprogram'){
                                    child.url = 'https://mp.weixin.qq.com';
                                }
                            }
                        }
                    }
                    $http.post("{:url('wechat.menus/save',array('dis'=>1))}",{button:this.menus}).then(function (res) {
                        if(res.status == 200 && res.data.code == 200)
                            $eb.message('success','发布菜单成功!');
                        else
                            return Promise.reject(res.data.msg || '发布菜单失败!');
                    }).catch(function(err){
                        $eb.message('error',err);
                    })
                }
            },
            mounted:function(){
                window.vm = this;
            }
        });
        vm.$mount(document.getElementById('app'));
    });
    $('.i-checks').iCheck({
        checkboxClass: 'icheckbox_square-green',
        radioClass: 'iradio_square-green',
    });
</script>
{/block}

