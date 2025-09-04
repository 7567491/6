{extend name="public/container"}
{block name='head_top'}
<style>
    .layui-table {
        width: 100%!important;
    }

    .layui-form-item .special-label {
        width: 50px;
        float: left;
        height: 30px;
        line-height: 38px;
        margin-left: 10px;
        margin-top: 5px;
        border-radius: 5px;
        background-color: #10952a;
        text-align: center;
    }

    .layui-form-item .special-label i {
        display: inline-block;
        width: 18px;
        height: 18px;
        font-size: 18px;
        color: #fff;
    }

    .layui-form-item .label-box {
        border: 1px solid;
        border-radius: 10px;
        position: relative;
        padding: 10px;
        height: 30px;
        color: #fff;
        background-color: #393D49;
        text-align: center;
        cursor: pointer;
        display: inline-block;
        line-height: 10px;
    }

    .layui-form-item .label-box p {
        line-height: inherit;
    }

    .edui-default .edui-for-image .edui-icon {
        background-position: -380px 0px;
    }

    .layui-tab-title .layui-this:after {
        border-bottom-color: #fff!important;
    }
    .upload-image-box .mask p{width: 50px;}
    .file {
        position: relative;
        background: #10952a;
        border: 1px solid #99D3F5;
        border-radius: 4px;
        padding: 7px 12px;
        overflow: hidden;
        color: #fff;
        text-decoration: none;
        text-indent: 0;
        line-height: 20px;
        width: 120px;
    }
    .file input {
        width: 100%;
        position: absolute;
        font-size: 5px;
        right: 0;
        top: 0;
        opacity: 0;
    }
    .file:hover {
        background: #AADFFD;
        border-color: #78C3F3;
        color: #004974;
        text-decoration: none;
    }
    .layui-progress {
        margin-top: 12px;
    }
    .layui-form-label{
        width: 130px;
    }
    .layui-input-block{
        margin-left: 130px;
    }
</style>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/third-party/zeroclipboard/ZeroClipboard.js"></script>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/ueditor.config.js"></script>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/ueditor.all.min.js"></script>
{/block}
{block name="content"}
<div v-cloak id="app" class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            <form class="layui-form" action="">
                <div class="layui-tab" lay-filter="tab">
                    <ul class="layui-tab-title">
                        <li class="layui-this">基本设置</li>
                        <li>上传内容</li>
                        <li>价格设置</li>
                    </ul>
                    <div class="layui-tab-content">
                        <div class="layui-tab-item layui-show">
                            <div class="layui-form-item">
                                <label class="layui-form-label required">课件资料名称：</label>
                                <div class="layui-input-block">
                                    <input type="text" name="title" required v-model.trim="formData.title" autocomplete="off" placeholder="请输入课件资料名称" maxlength="60" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label required">课件资料分类：</label>
                                <div class="layui-input-block">
                                    <select name="subject_id" v-model="formData.cate_id" lay-search="" lay-filter="cate_id" lay-verify="required">
                                        <option value="0">请选分类</option>
                                        <option  v-for="item in cate_list" :value="item.id" :disabled="item.pid==0 ? true : false">{{item.html}}{{item.title}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">课件资料排序：</label>
                                <div class="layui-input-inline">
                                    <input type="number" name="sort" v-model="formData.sort" min="0" max="9999" class="layui-input" v-sort>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label required">课件资料封面：（710*400）</label>
                                <div class="layui-input-block">
                                    <div class="upload-image-box" v-if="formData.image" @mouseenter="mask.image = true" @mouseleave="mask.image = false">
                                        <img :src="formData.image" alt="">
                                        <div class="mask" v-show="mask.image" style="display: block">
                                            <p>
                                                <i class="fa fa-eye" @click="look(formData.image)"></i>
                                                <i class="fa fa-trash-o" @click="delect('image')"></i>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="upload-image" v-show="!formData.image" @click="upload('image')">
                                        <div class="fiexd"><i class="fa fa-plus"></i></div>
                                        <p>选择图片</p>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label required">推广海报：（600*740）</label>
                                <div class="layui-input-block">
                                    <div class="upload-image-box" v-if="formData.poster_image" @mouseenter="mask.poster_image = true" @mouseleave="mask.poster_image = false">
                                        <img :src="formData.poster_image" alt="">
                                        <div class="mask" v-show="mask.poster_image" style="display: block">
                                            <p><i class="fa fa-eye" @click="look(formData.poster_image)"></i>
                                                <i class="fa fa-trash-o" @click="delect('poster_image')"></i></p>
                                        </div>
                                    </div>
                                    <div class="upload-image" v-show="!formData.poster_image" @click="upload('poster_image')">
                                        <div class="fiexd"><i class="fa fa-plus"></i></div>
                                        <p>选择图片</p>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">课件资料简介：</label>
                                <div class="layui-input-block">
                                    <textarea v-model="formData.description" class="layui-textarea"></textarea>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label required">课件资料详情：</label>
                                <div class="layui-input-block">
                                    <textarea id="editor">{{formData.abstract}}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="layui-tab-item">
                            <div class="layui-form-item">
                                <label class="layui-form-label">插入课件资料包:</label>
                                <div class="layui-input-block" style="overflow:hidden;">
                                    <div class="layui-row layui-col-space15">
                                        <div class="layui-col-md8">
                                            <input v-model="link" type="text" name="title" placeholder="请输入课件资料包链接" autocomplete="off" class="layui-input">
                                            <div>{{ fileName }}</div>
                                        </div>
                                        <div class="layui-col-md4">
                                            <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" @click="uploadVideo">确认课件资料</button>
                                            <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" id="upload">上传课件资料</button>
                                        </div>
                                    </div>
                                    <div class="layui-row layui-col-space15">
                                        <div class="layui-col-md12">
                                            <div class="layui-form-mid layui-word-aux">课件资料格式仅支持zip|rar格式，课件资料大于1000M，可以在OSS上传，然后在此添加OSS链接。</div>
                                        </div>
                                    </div>
                                    <div v-show="is_video" class="layui-row layui-col-space15">
                                        <div class="layui-col-md8">
                                            <div class="layui-progress" lay-showPercent="true" lay-filter="progress">
                                                <div class="layui-progress-bar layui-bg-blue" lay-percent="0%"></div>
                                            </div>
                                        </div>
                                        <div class="layui-col-md4">
                                            <button type="button" class="layui-btn layui-btn-danger layui-btn-sm" @click="cancelUpload">取消上传</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label required">网盘链接：</label>
                                <div class="layui-input-block">
                                    <input type="text" name="network_disk_link" required v-model="formData.network_disk_link" autocomplete="off" placeholder="由于IOS微信端不支持课件资料下载，请通过百度网盘上传、下载课件资料"  class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label required">提取码：</label>
                                <div class="layui-input-block">
                                    <input type="text" name="network_disk_pwd" required v-model="formData.network_disk_pwd" autocomplete="off" placeholder="请输入百度网盘文件获取密码"  class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">只显示网盘下载：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="is_network_disk" lay-filter="is_network_disk" v-model="formData.is_network_disk" value="1" title="是">
                                    <input type="radio" name="is_network_disk" lay-filter="is_network_disk" v-model="formData.is_network_disk" value="0" title="否">
                                </div>
                            </div>
                        </div>
                        <div class="layui-tab-item">
                            <div class="layui-form-item">
                                <label class="layui-form-label">付费方式：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="pay_type" lay-filter="pay_type" v-model="formData.pay_type" value="1" title="付费">
                                    <input type="radio" name="pay_type" lay-filter="pay_type" v-model="formData.pay_type" value="0" title="免费">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.pay_type == 1">
                                <label class="layui-form-label">购买金额：</label>
                                <div class="layui-input-block">
                                    <input style="width: 300px" type="number" name="money" lay-verify="number" v-model="formData.money" autocomplete="off" class="layui-input">
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label" style="padding: 9px 0;">会员付费方式：</label>
                                    <div class="layui-input-block">
                                        <input type="radio" name="member_pay_type" lay-filter="member_pay_type" v-model="formData.member_pay_type" value="1" title="付费">
                                        <input type="radio" name="member_pay_type" lay-filter="member_pay_type" v-model="formData.member_pay_type" value="0" title="免费">
                                    </div>
                                </div>
                                <div class="layui-form-item" v-show="formData.member_pay_type == 1">
                                    <label class="layui-form-label" style="padding: 9px 0;">会员购买金额：</label>
                                    <div class="layui-input-block">
                                        <input style="width: 300px" type="number" name="member_money" lay-verify="number" v-model="formData.member_money" autocomplete="off" class="layui-input" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn layui-btn-normal" type="button" lay-filter="formDemo" @click="save">{$id ?
                            '确认修改':'立即提交'}
                        </button>
                        <button class="layui-btn layui-btn-primary clone" type="button" @click="clone_form">取消
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name='script'}
<script>
    var id = {$id}, download =<?=isset($download) ? $download : "{}"?>;
    require(['vue','helper','zh-cn','request','plupload','aliyun-oss','OssUpload'], function (Vue,$h) {
        new Vue({
            el: "#app",
            directives: {
                sort: {
                    bind: function (el, binding, vnode) {
                        var vm = vnode.context;
                        el.addEventListener('change', function () {
                            if (!this.value || this.value < 0) {
                                vm.formData.sort = 0;
                            } else if (this.value > 9999) {
                                vm.formData.sort = 9999;
                            } else {
                                vm.formData.sort = parseInt(this.value);
                            }
                        });
                    }
                }
            },
            data: {
                cate_list: [],
                formData: {
                    title: download.title || '',
                    cate_id: download.cate_id || 0,
                    image: download.image || '',
                    poster_image: download.poster_image || '',
                    abstract:download.abstract || '',
                    description: download.description || '',
                    sort: download.sort || 0,
                    sales: download.sales || 0,
                    pay_type: download.pay_type == 1 ? 1 : 0,
                    money: download.money || 0.00,
                    member_pay_type: download.member_pay_type == 1 ? 1 : 0,
                    member_money: download.member_money || 0.00,
                    link:download.link || '',
                    network_disk_link:download.network_disk_link || '',
                    network_disk_pwd:download.network_disk_pwd || '',
                    is_network_disk: download.is_network_disk || 0
                },
                link: download.link || '',
                fileName: '',
                host: ossUpload.host + '/',
                mask: {
                    poster_image: false,
                    image: false,
                    service_code: false,
                },
                ue: null,
                is_video: false,
                is_upload:false,
                is_suspend:false,
                //上传类型
                mime_types: {
                    Image: "jpg,gif,png,JPG,GIF,PNG",
                    Video: "mp4,MP4",
                    Audio: "mp3,MP3",
                },
                videoWidth: 0,
                uploader: null,
                uploadInst: null
            },
            methods: {
                //取消
                cancelUpload: function () {
                    this.uploader.stop();
                    this.is_video = false;
                    this.videoWidth = 0;
                    this.is_upload = false;
                },
                //删除图片
                delect: function (key, index) {
                    var that = this;
                    if (index != undefined) {
                        that.formData[key].splice(index, 1);
                        that.$set(that.formData, key, that.formData[key]);
                    } else {
                        that.$set(that.formData, key, '');
                    }
                },
                //查看图片
                look: function (pic) {
                    parent.$eb.openImage(pic);
                },
                //鼠标移入事件
                enter: function (item) {
                    if (item) {
                        item.is_show = true;
                    } else {
                        this.mask = true;
                    }
                },
                //鼠标移出事件
                leave: function (item) {
                    if (item) {
                        item.is_show = false;
                    } else {
                        this.mask = false;
                    }
                },
                changeIMG: function (key, value, multiple) {
                    if (multiple) {
                        var that = this;
                        value.map(function (v) {
                            that.formData[key].push({pic: v, is_show: false});
                        });
                        this.$set(this.formData, key, this.formData[key]);
                    } else {
                        this.$set(this.formData, key, value);
                    }
                },
                confirmAdd:function(){
                    var that = this;
                    if(that.link.substr(0,7).toLowerCase() == "http://" || that.link.substr(0,8).toLowerCase() == "https://"){
                        that.is_upload=true;
                        that.uploadVideo();
                    }else{
                        layList.msg('请输入正确的课件资料链接');
                    }
                },
                uploadVideo: function () {
                    if (this.link.substr(0, 7).toLowerCase() == "http://" || this.link.substr(0, 8).toLowerCase() == "https://") {
                        this.setContent(this.link);
                        if (this.link.indexOf('?') === -1) {
                            if (this.link.indexOf('#') === -1) {
                                this.fileName = this.link.slice(this.link.lastIndexOf('/') + 1);
                            } else {
                                this.fileName = this.link.slice(this.link.lastIndexOf('/') + 1, this.link.indexOf('#'));
                            }
                        } else {
                            this.fileName = this.link.slice(this.link.lastIndexOf('/') + 1, this.link.indexOf('?'));
                        }
                    } else {
                        layList.msg('请输入正确的课件资料链接');
                    }
                },
                setContent:function(link){
                    this.formData.link = link;
                },
                //上传图片
                upload: function (key, count) {
                    ossUpload.createFrame('请选择图片', {fodder: key, max_count: count === undefined ? 0 : count},{w:800,h:550});
                },
                //获取分类
                get_subject_list: function () {
                    var that = this;
                    layList.baseGet(layList.U({a: 'get_cate_list'}), function (res) {
                        that.$set(that, 'cate_list', res.data);
                        that.$nextTick(function () {
                            layList.form.render('select');
                        })
                    });
                },
                save: function () {
                    var that = this;
                    that.formData.abstract = that.ue.getContent();
                    that.$nextTick(function () {
                    if (!that.formData.title) return layList.msg('请输入课件资料名称');
                    if (!that.formData.cate_id) return layList.msg('请选择分类');
                    if (!that.formData.image) return layList.msg('请上传课件资料封面');
                    if (!that.formData.poster_image) return layList.msg('请上传推广海报');
                    if (!that.formData.abstract) return layList.msg('请输入课件资料详情');
                    
                    
                    // if (!that.formData.link && !that.formData.is_network_disk) return layList.msg('请上传文件');
                    if (!that.formData.network_disk_link) return layList.msg('请输入百度网盘文件链接');
                    if (!that.formData.network_disk_pwd) return layList.msg('请输入百度网盘文件获取密码');
                    if (that.formData.pay_type == 1) {
                        if (!that.formData.money || that.formData.money == 0.00) return layList.msg('请填写购买金额');
                    }
                    if (that.formData.member_pay_type == 1) {
                        if (!that.formData.member_money || that.formData.member_money == 0.00) return layList.msg('请填写会员购买金额');
                    }
                    layList.loadFFF();
                    layList.basePost(layList.U({
                        a: 'save_data',
                        q: {id: id}
                    }), that.formData, function (res) {
                        layList.loadClear();
                        if (parseInt(id) == 0) {
                            layList.layer.confirm('添加成功,您要继续添加课件资料吗?', {
                                btn: ['继续添加', '立即提交'] //按钮
                            }, function (index) {
                                layList.layer.close(index);
                            }, function () {
                                parent.layer.closeAll();
                            });
                        } else {
                            layList.msg('修改成功', function () {
                                parent.layer.closeAll();
                                window.location.href = layList.U({a: 'index'});
                            })
                        }
                    }, function (res) {
                        layList.msg(res.msg);
                        layList.loadClear();
                    });
                    })
                },
                clone_form: function () {
                    if (parseInt(id) == 0) {
                        var that = this;
                        if (that.formData.image) return layList.msg('请先删除上传的图片在尝试取消');
                        if (that.formData.poster_image) return layList.msg('请先删除上传的图片在尝试取消');
                        parent.layer.closeAll();
                    }
                    parent.layer.closeAll();
                }
            },
            mounted: function () {
                var that = this;
                window.changeIMG = that.changeIMG;
                layList.date({
                    elem: '#start_time',
                    theme: '#393D49',
                    type: 'datetime',
                    done: function (value) {
                        that.formData.pink_strar_time = value;
                    }
                });
                layList.date({
                    elem: '#end_time',
                    theme: '#393D49',
                    type: 'datetime',
                    done: function (value) {
                        that.formData.pink_end_time = value;
                    }
                });
                //选择图片
                function changeIMG(index, pic) {
                    $(".image_img").css('background-image', "url(" + pic + ")");
                    $(".active").css('background-image', "url(" + pic + ")");
                    $('#image_input').val(pic);
                }

                //选择图片插入到编辑器中
                window.insertEditor = function(list,fodder){
                    list = handle_editor_img(list);
                    that.ue.execCommand('insertimage', list);
                };
                this.$nextTick(function () {
                    layList.form.render();
                    //实例化编辑器
                    UE.registerUI('选择图片', function (editor, uiName) {
                        var btn = new UE.ui.Button({
                            name: uiName,
                            title: uiName,
                            cssRules: 'background-position: -380px 0;',
                            onclick: function() {
                                ossUpload.createFrame(uiName, { fodder: editor.key }, { w: 800, h: 550 });
                            }
                        });
                        return btn;
                    });
                    that.ue = UE.getEditor('editor');
                });
                //获取科目
                that.get_subject_list();

                layList.form.on('radio(pay_type)', function (data) {
                    that.formData.pay_type = parseInt(data.value);
                    if (that.formData.pay_type != 1) {
                        that.formData.is_pink = 0;
                        that.formData.member_pay_type = 0;
                        that.formData.member_money = 0;
                    };
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                layList.form.on('radio(is_network_disk)', function (data) {
                    that.formData.is_network_disk = parseInt(data.value);
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                layList.form.on('radio(member_pay_type)', function (data) {
                    that.formData.member_pay_type = parseInt(data.value);
                    if (that.formData.member_pay_type != 1) {
                        that.formData.member_money = 0;
                    };
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                layList.select('cate_id', function (obj) {
                    that.formData.cate_id = obj.value;
                });

                var element = layui.element;
                element.render('progress');
                that.uploader = ossUpload.upload({
                    id: 'upload',
                    mime_types: [
                        {title: "Zip files", extensions: "zip,rar"}
                    ],
                    init: function (params) {
                    },
                    uploadIng: function (file) {
                        element.progress('progress', file.percent + '%');
                    },
                    FilesAddedSuccess: function (files) {
                        that.is_video = true;
                    },
                    success: function (res) {
                        layList.msg('上传成功');
                        that.is_video = false;
                        that.link = res.url;
                        that.uploadVideo();
                    },
                    fail: function (err) {
                        that.is_video = false;
                        that.is_upload = false;
                        layList.msg(err);
                    }
                });
            }
        })
    })
</script>
{/block}
