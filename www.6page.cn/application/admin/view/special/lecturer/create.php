{extend name="public/container"}
{block name='head_top'}
<style>
    .layui-form-item .special-label i{display: inline-block;width: 18px;height: 18px;font-size: 18px;color: #fff;}
    .layui-form-item .label-box p{line-height: inherit;}
    .m-t-5{margin-top:5px;}
    #app .layui-barrage-box{margin-bottom: 10px;margin-top: 10px;margin-left: 10px;border: 1px solid #10952a;border-radius: 5px;cursor: pointer;position: relative;}
    #app .layui-barrage-box.border-color{border-color: #0bb20c;}
    #app .layui-barrage-box .del-text{position: absolute;top: 0;left: 0;background-color: rgba(0,0,0,0.5);color: #ffffff;width: 92%;text-align: center;}
    #app .layui-barrage-box p{padding:5px 5px; }
    #app .layui-empty-text{text-align: center;font-size: 18px;}
    #app .layui-empty-text p{padding: 10px 10px;}
    .edui-default .edui-for-image .edui-icon {background-position: -380px 0px;}
    .layui-form-item .special-label {width: 50px;float: left;height: 30px;line-height: 38px;margin-left: 10px;margin-top: 5px;border-radius: 5px;background-color: #10952a;text-align: center;}
    .layui-form-item .special-label i {display: inline-block;width: 18px;height: 18px;font-size: 18px;color: #fff;}
    .layui-form-item .label-box {border: 1px solid;border-radius: 10px;position: relative;padding: 10px;height: 30px;color: #fff;background-color: #393D49;text-align: center;cursor: pointer;display: inline-block;line-height: 10px;}
    .layui-form-item .label-box p {line-height: inherit;}
    .upload-image-box .mask p{width: 50px;}
</style>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/third-party/zeroclipboard/ZeroClipboard.js"></script>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/ueditor.config.js"></script>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/ueditor.all.min.js"></script>
{/block}
{block name="content"}
<div class="layui-fluid">
    <div class="layui-row layui-col-space15" id="app" v-cloak>
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="font-weight: bold;">{{id ? '编辑讲师':'添加讲师'}}</div>
                </div>
                <div class="layui-card-body">
                    <form action="" class="layui-form">
                        <div class="layui-form-item">
                            <div class="layui-form-item required">
                                <label class="layui-form-label">讲师名称：</label>
                                <div class="layui-input-inline">
                                    <input type="text" name="lecturer_name" v-model.trim="formData.lecturer_name" autocomplete="off" placeholder="请输入讲师名称" maxlength="8" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">标签：</label>
                                <div class="layui-input-inline">
                                    <input type="text" v-model.trim="label" name="price_min" autocomplete="off" placeholder="最多6个字" maxlength="6" class="layui-input">
                                </div>
                                <div class="layui-input-inline" style="width: auto;">
                                    <button type="button" class="layui-btn layui-btn-normal" @click="addLabrl" >
                                        <i class="layui-icon">&#xe654;</i>
                                    </button>
                                </div>
                                <div class="layui-form-mid layui-word-aux">输入标签名称后点击”+“号按钮添加；最多写入6个字；点击标签即可删除</div>
                            </div>
                            <div v-if="formData.label.length" class="layui-form-item">
                                <div class="layui-input-block">
                                    <button v-for="(item,index) in formData.label" :key="index" type="button" class="layui-btn layui-btn-normal layui-btn-sm" @click="delLabel(index)">{{item}}</button>
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">头像：（200*200）</label>
                                <div class="layui-input-block">
                                    <div class="upload-image-box" v-if="formData.lecturer_head" >
                                        <img :src="formData.lecturer_head" alt="">
                                        <div class="mask"  style="display: block">
                                            <p>
                                                <i class="fa fa-eye" @click="look(formData.lecturer_head)"></i>
                                                <i class="fa fa-trash-o" @click="delect('lecturer_head')"></i>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="upload-image" v-show="!formData.lecturer_head" @click="upload('lecturer_head')">
                                        <div class="fiexd"><i class="fa fa-plus"></i></div>
                                        <p>选择图片</p>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item required" style="margin-top: 10px;">
                                <label class="layui-form-label">说明：</label>
                                <div class="layui-input-block">
                                    <input type="text" name="explain" style="width: 100%" v-model="formData.explain" autocomplete="off" maxlength="100" placeholder="请输入讲师说明(100字以内)" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item required" style="margin-top: 10px;">
                                <label class="layui-form-label">简介：</label>
                                <div class="layui-input-block">
                                    <textarea id="editor">{{formData.introduction}}</textarea>
                                </div>
                            </div>
                            <div class="layui-form-item submit">
                                <label class="layui-form-label">排序：</label>
                                <div class="layui-input-inline">
                                    <input type="number" name="sort" v-model="formData.sort" min="0" max="9999" class="layui-input" v-sort>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">状态：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="is_show" value="1" title="显示" v-model="formData.is_show" lay-filter="is_show" >
                                    <input type="radio" name="is_show" value="0" title="隐藏" v-model="formData.is_show" lay-filter="is_show">
                                </div>
                            </div>
                            <div class="layui-form-item submit">
                                <div class="layui-input-block">
                                    <button class="layui-btn layui-btn-normal" type="button" @click="save">{{id ? '立即修改':'立即提交'}}</button>
                                    <button class="layui-btn layui-btn-primary clone" type="button" @click="clone_form">取消</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
<script type="text/javascript" src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name='script'}
<script>
    var id={$id},lecturer=<?=isset($lecturer) ? $lecturer : []?>;
    require(['vue','helper','zh-cn','request','plupload','aliyun-oss','OssUpload'],function(Vue,$h) {
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
                formData:{
                    lecturer_name:lecturer.lecturer_name || '',
                    lecturer_head: lecturer.lecturer_head || '',
                    label: lecturer.label || [],
                    explain: lecturer.explain || '',
                    introduction: lecturer.introduction || '',
                    sort:Number(lecturer.sort) || 0,
                    is_show:lecturer.is_show || 1
                },
                label: '',
            },
            methods:{
                //查看图片
                look: function (pic) {
                   parent.$eb.openImage(pic);
                },
                //上传图片
                upload: function (key, count) {
                    ossUpload.createFrame('请选择图片', {fodder: key, max_count: count === undefined ? 0 : count},{w:800,h:550});
                },
                delLabel: function (index) {
                    this.formData.label.splice(index, 1);
                    this.$set(this.formData, 'label', this.formData.label);
                },
                addLabrl: function () {
                    if (this.label) {
                        if (this.label.length > 6) return layList.msg('您输入的标签字数太长');
                        var length = this.formData.label.length;
                        if (length >= 3) return layList.msg('标签最多添加3个');
                        for (var i = 0; i < length; i++) {
                            if (this.formData.label[i] == this.label) return layList.msg('请勿重复添加');
                        }
                        this.formData.label.push(this.label);
                        this.$set(this.formData, 'label', this.formData.label);
                        this.label = '';
                    }
                },
                clone_form: function () {
                    var that = this;
                    if (parseInt(id) == 0) {
                        parent.layer.closeAll();
                    }
                    var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                    parent.layer.close(index); //再执行关闭
                },
                save:function () {
                    var that=this;
                    that.formData.introduction = that.ue.getContent();
                    that.$nextTick(function () {
                        if (!that.formData.lecturer_name) return layList.msg('请输入讲师名称');
                        if (!that.formData.label.length) return layList.msg('请输入标签');
                        if (!that.formData.lecturer_head) return layList.msg('请输入讲师头像');
                        if (!that.formData.explain) return layList.msg('请编辑讲师说明');
                        if (!that.formData.introduction) return layList.msg('请编辑讲师介绍');
                        layList.loadFFF();
                        layList.basePost(layList.U({a: 'save_lecturer', q: {id: id}}), that.formData, function (res) {
                            layList.loadClear();
                            if (parseInt(id) == 0) {
                                layList.layer.confirm('添加成功,您要继续添加讲师吗?', {
                                    btn: ['继续添加', '立即提交'] //按钮
                                }, function (index) {
                                    layList.layer.close(index);
                                }, function () {
                                    parent.layer.closeAll();
                                });
                            } else {
                                layList.msg('修改成功', function () {
                                    parent.layer.closeAll();
                                })
                            }
                        }, function (res) {
                            layList.msg(res.msg);
                            layList.loadClear();
                        });
                    })
                },
                delect:function(key){
                    var that=this;
                    that.formData[key]='';
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
            },
            mounted:function () {
                var that=this;
                window.changeIMG = that.changeIMG;
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
                layList.form.on('radio(is_show)',function (data) {
                    that.formData.is_show=data.value;
                });
            }
        })
    })
</script>
{/block}
