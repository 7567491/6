<!DOCTYPE html>
<!--suppress JSAnnotator -->
<html lang="zh-CN">
<head>
    <link rel="stylesheet" href="{__PLUG_PATH}layui/css/layui.css">
    <link rel="stylesheet" href="{__ADMIN_PATH}css/images.css">
    <script src="{__PLUG_PATH}jquery-1.10.2.min.js"></script>
    <script src="{__PLUG_PATH}layui/layui.all.js"></script>
    <script src="{__PLUG_PATH}vue/dist/vue.min.js"></script>
</head>
<style>
    /* .layui-hide {
        display: none !important;
    }
    .layui-input-box {
        padding: 10px;
    }
    .layui-input-box .layui-input {
        padding: 0 10px;
    }
    .layui-btn-box {
        padding: 14px 10px;
    }
    .layui-tree {
        max-height: 447px;
        overflow: auto;
    }
    .layui-fluid {
        padding: 0;
    } */
    [v-cloak] {
        display: none;
    }
</style>
<body>

<div v-cloak class="layui-fluid" id="app">
    <!-- <div class="picture-library"> -->
        <div class="library-left">
            <div class="layui-input-box">
            <input type="text" class="layui-input">
            </div>
            <div class="layui-tree" lay-filter="LAY-tree-8">
                <div v-for="(item1, index1) in categoryList" :key="item1.id" :class="{ 'layui-tree-spread': item1.isOpen }" data-id="2" class="layui-tree-set layui-tree-setHide">
                    <div class="layui-tree-entry">
                        <div class="layui-tree-main" @click="OpenTree(item1, index1)">
                            <span class="layui-tree-iconClick">
                                <i :style="{ visibility: item1.child.length ? 'visible' : 'hidden' }" :class="['layui-icon', item1.isOpen ? 'layui-icon-triangle-d' : 'layui-icon-triangle-r']"></i>
                            </span>
                            <span class="layui-tree-txt">{{ item1.name }}</span>
                        </div>
                        <div class="layui-btn-group layui-tree-btnGroup">
                            <i class="layui-icon layui-icon-add-1" data-type="add"></i>
                            <i class="layui-icon layui-icon-edit" data-type="update" @click="updateCategory(item1)"></i>
                            <i class="layui-icon layui-icon-delete" data-type="del"></i>
                        </div>
                    </div>
                    <div :style="{ display: item1.isOpen ? 'block' : 'none' }" class="layui-tree-pack layui-tree-lineExtend layui-tree-showLine">
                        <div v-for="(item2, index2) in item1.child" :key="item2.id" data-id="2000" class="layui-tree-set">
                            <div class="layui-tree-entry">
                                <div class="layui-tree-main" @click="OpenTree(item2, index2)">
                                    <span class="layui-tree-iconClick">
                                        <i class="layui-tree-iconArrow layui-hide"></i>
                                    </span>
                                    <span class="layui-tree-txt">{{ item2.name }}</span>
                                </div>
                                <div class="layui-btn-group layui-tree-btnGroup">
                                    <i class="layui-icon layui-icon-edit" data-type="update"></i>
                                    <i class="layui-icon layui-icon-delete" data-type="del"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="library-right">
            <div class="btn-box">
                <button type="button" class="layui-btn layui-btn-sm">使用选中图片</button>
                <button type="button" class="layui-btn layui-btn-sm">添加分类</button>
                <button type="button" class="layui-btn layui-btn-sm">上传图片</button>
                <button type="button" class="layui-btn layui-btn-sm">删除图片</button>
            </div>
            <div class="content-box">
                <!-- <div class="picture-list">
                    <div v-for="(item,index) in imageList" class="picture-item">
                        <div>
                            <img src="" alt="">
                        </div>
                        <div>
                            <input type="text">
                            <i class="layui-icon layui-icon-edit"></i>
                        </div>
                    </div>
                </div> -->
                <ul>
                    <li v-for="(item, index) in imageList">
                        <div class="img-box on">
                            
                            <img :src="item.att_dir" alt="">
                        </div>
                        <div class="title-box">
                            <input type="text">
                            <i class="layui-icon layui-icon-edit"></i>
                        </div>
                    </li>
                </ul>
            </div>
            <div style="height:55px;"></div>
        </div>
            <!-- <div class="layui-col-xs3 layui-col-sm3 layui-col-md3 layui-col-lg3"> -->
                <!-- <div class="layui-card"> -->
                    <!-- <div class="layui-card-header">
                        <div class="layui-unselect layui-form-select layui-form-selected">
                            <div class="layui-select-title">
                                <input type="text" name="title" v-model="searchTitle" placeholder="搜索分类"
                                       style="height: 24px;line-height:24px;padding-left:7px;font-size: 12px;display: inline;padding-right: 0;width: 100%;"
                                       autocomplete="off" class="layui-input layui-input-search" @keydown="search">
                                                               <i class="layui-icon layui-icon-search" @click="search"  style="cursor: pointer;margin:0 3px;"></i>
                            </div>
                        </div>
                    </div> -->
                    <!-- <div class="layui-card-body" style="padding: 0;height: 100%;overflow:auto;"> -->
                                               <!-- <div class="layadmin-homepage-pad-ver">
                                                   <button type="button" class="layui-btn layui-btn-normal layui-btn-xs" style="width: 80%" @click="addCategory">添加</button>
                                               </div> -->
                        <!-- <div ref="tree" class="demo-tree demo-tree-box"> -->
                            <!-- <div class="layui-tree layui-tree-line">
                                <div class="layui-tree-set layui-tree-setHide">
                                    <div class="layui-tree-entry">
                                        <div class="layui-tree-main" @click="OpenTree({child:[],id:0})">
                                            <span class="layui-tree-iconClick">
                                                <i class="layui-tree-iconArrow"></i>
                                            </span>
                                            <span class="layui-tree-txt" :class="pid == 0 ? 'on' : '' ">全部图片</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-for="(value, k) in categoryList" :class="{ 'layui-tree-spread': value.isOpen }" class="layui-tree-set layui-tree-setHide">
                                    <div class="layui-tree-entry" @mouseover.stop="changeActive(value,k)" @mouseout.stop="removeActive(value,k)">
                                        <div class="layui-tree-main" @click="OpenTree(value,k)">
                                            <span class="layui-tree-iconClick">
                                                <i class="layui-icon layui-icon-triangle-d" v-if="value.child.length && value.isOpen"></i>
                                                <i class="layui-icon layui-icon-triangle-r" v-else-if="value.child.length && !value.isOpen"></i>
                                                <i class="layui-icon " v-else>&nbsp;&nbsp;&nbsp;</i>
                                            </span>
                                            <span class="layui-tree-txt line1" :class="pid == value.id ? 'on': '' " v-text="value.name"></span>
                                        </div>
                                        <div class="layui-btn-group layui-tree-btnGroup layui-layer layui-layer-tips"
                                             v-show="value.isShow">
                                            <div>
                                                <i class="layui-icon layui-icon-add-1" @click.stop="addCategory(value)"
                                                   title="添加"></i>
                                                <i class="layui-icon layui-icon-edit"
                                                   @click.stop="updateCategory(value)" title="编辑"></i>
                                                <i class="layui-icon layui-icon-delete" v-if="!value.child.length"
                                                   title="删除" @click.stop="delCategory(value)"></i>
                                            </div>
                                            <i class="layui-layer-TipsG layui-layer-TipsT"></i>
                                        </div>
                                    </div>
                                    <div class="layui-tree-pack layui-tree-lineExtend layui-tree-showLine"
                                         v-show="value.isOpen" style="display: block">
                                        <div class="layui-tree-set" v-for="(item,key) in value.child">
                                            <div class="layui-tree-entry">
                                                <div class="layui-tree-main" @click="OpenTree(item,key)">
                                                    <span class="layui-tree-iconClick">
                                                        <i class="layui-icon"></i>
                                                    </span>
                                                    <span class="layui-tree-txt line1"
                                                          :class="pid == item.id ? 'on': '' " v-text="item.name"></span>
                                                </div>
                                                <div class="layui-btn-group layui-tree-btnGroup layui-layer layui-layer-tips"
                                                     @mouseover.stop="changeActive(item,k,key)"
                                                     @mouseout.stop="removeActive(item,k,key)">
                                                    <div>
                                                        <i class="layui-icon layui-icon-edit"
                                                           @click.stop="updateCategory(item,value.pid)" title="编辑"></i>
                                                        <i class="layui-icon layui-icon-delete"
                                                           @click.stop="delCategory(item,value.pid)" title="删除"></i>
                                                    </div>
                                                    <i class="layui-layer-TipsG layui-layer-TipsT"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                        <!-- </div> -->
                    <!-- </div> -->
                <!-- </div> -->
            <!-- </div> -->
        <!-- <div class="layui-col-xs9 layui-col-sm9 layui-col-md9 layui-col-lg9">
            <div class="layadmin-homepage-panel layadmin-homepage-shadow">
                <div class="layui-card text-center">
                    <div class="layui-card-header">
                        <div class="layadmin-homepage-pad-ver" style="text-align: left">
                            <div class="layui-btn-group">
                            <button type="button" class="layui-btn layui-btn-normal layui-btn-sm"
                                        :class="selectImages.length ? '':'layui-btn-disabled' " @click="useImages">
                                    使用选中的图片
                                </button>
                                <button type="button" class="layui-btn layui-btn-normal layui-btn-sm"
                                        @click="addCategory">添加分类
                                </button>
                                <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" ref="upload">
                                    上传图片
                                </button>
                                <button type="button" class="layui-btn layui-btn-warm layui-btn-sm"
                                        :class="selectImages.length ? '':'layui-btn-disabled' " @click="moveCate">移动分类
                                </button>
                                <div class="layui-unselect layui-form-select"><div class="layui-select-title"><input type="text" placeholder="请选择" value="" readonly="" class="layui-input layui-unselect"><i class="layui-edge"></i></div><dl class="layui-anim layui-anim-upbit"><dd lay-value="" class="layui-select-tips">请选择</dd><dd lay-value="0" class="">北京</dd><dd lay-value="1" class="">上海</dd><dd lay-value="2" class="">广州</dd><dd lay-value="3" class="">深圳</dd><dd lay-value="4" class="">杭州</dd></dl></div>
                                <button type="button" class="layui-btn layui-btn-danger layui-btn-sm"
                                        :class="selectImages.length ? '':'layui-btn-disabled' " @click="delImage">删除图片
                                </button>
                                <button type="button" class="layui-btn layui-btn-sm layui-btn-normal" @click="onSearch">
                                    <i class="layui-icon layui-icon-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="layui-card-body clearfix image-box" style="padding: 10px;height: 360px;z-index:10;">
                        <div class="layui-col-md2 layui-col-xs2 layui-col-sm2 image" v-for="(item,index) in imageList">
                            <span class="layui-badge layui-bg-cyan" style="position: absolute;" v-if="item.sort">{{ item.sort }}</span>
                            <div class="layui-img-box" :class="item.isSelect ? 'on': '' ">

                                <img :src="item.att_dir" v-if="small == 1" @click="changImage(item)">
                                <img :src="item.att_dir" v-else @click="changImage(item)">
                            </div>
                            <div class="picture-title" @mouseover="onMouseover(item)" @mouseout="onMouseout(item)"><input v-model="item.title" :readonly="item.readonly" @blur="onBlur(item)"><i v-show="item.isEdit" class="layui-icon layui-icon-edit" @click="onEdit($event, item)"></i></div>
                        </div>
                        <div class="empty-image" style="width: 100%;height: 100%;text-align: center;"
                             v-if="!imageList.length && loading == false">
                            <div class="layui-img-box">
                                <img src="{__ADMIN_PATH}images/empty.jpg" style="height: 400px;" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="layui-card-body clearfix page">
                        <div class="layui-col-md8 layui-col-xs8 layui-col-sm8 image_page" ref="image_page"></div>
                    </div>
                </div>
            </div>
        </div> -->
    <!-- </div> -->
</div>
</body>
</html>
<script src="{__ADMIN_PATH}js/layuiList.js?id=dsfwef"></script>
<script>
    var pid = {$pid}, small = {$Request.param.small ? : 0
    } ,
    parentinputname = '{$fodder}', maxLength = {$maxLength};//当前图片分类ID

    new Vue({
        el: "#app",
        data: {
            categoryList: [],
            searchTitle: '',
            pid: pid,
            imageList: [],
            page: 1,
            limit: 20,
            loading: false,
            small: small,
            selectImages: [],
            selectImagesIDS: [],
            uploadInst: null,
            searchImage: ''
        },
        watch: {
            page: function () {
                this.getImageList();
            },
        },
        methods: {
            onSearch: function () {
                var vm = this;
                layer.prompt({
                    title: '输入图片名称，并确认',
                    formType: 0
                }, function (text, index) {
                    if (!text.trim().length) {
                        return;
                    }
                    this.pid = 0;
                    this.searchImage = text.trim();
                    this.page = 1;
                    layer.close(index);
                    var load = layer.load(1);
                    layList.baseGet(this.U({
                        a: 'get_image_list',
                        q: {pid: this.pid, page: this.page, limit: this.limit, title: this.searchImage}
                    }), function (res) {
                        layer.close(load);
                        var list = res.data.list;
                        list.forEach(function (item) {
                            item.readonly = true;
                            item.isEdit = false;
                            if (!item.title) {
                                item.title = item.name.slice(0, item.name.lastIndexOf('.'));
                            }
                        });
                        this.$set(this, 'imageList', list);
                        if (this.page == 1) {
                            layList.laypage.render({
                                elem: this.$refs.image_page
                                , count: res.data.count
                                , limit: this.limit
                                , theme: '#10952a',
                                groups: 3,
                                jump: function (obj) {
                                    vm.page = obj.curr;
                                }
                            });
                        }
                    }.bind(this), function (res) {
                        layer.close(load);
                        layList.msg(res.msg);
                    });
                }.bind(this));
            },
            onEdit: function (event, item) {
                item.readonly = false;
                this.titleFocus = item.title;
                this.$nextTick(function () {
                    event.target.previousElementSibling.focus();
                });
            },
            onBlur: function (item) {
                item.readonly = true;
                if (item.title === this.titleFocus) {
                    return;
                }
                layList.basePost(this.U({
                    a: 'updateImageTitle'
                }), {
                    att_id: item.att_id,
                    title: item.title
                }, function (res) {
                    layList.msg(res.msg);
                }, function (res) {
                    layList.msg(res.msg);
                });
            },
            onMouseover: function (item) {
                item.isEdit = true;
            },
            onMouseout: function (item) {
                item.isEdit = false;
            },
            //删除图片
            delImage: function () {
                var that = this;
                if (!this.selectImages.length) return;
                layList.layer.confirm('是否要删除选中图片？', {
                    btn: ['是的我要删除', '我想想把'] //按钮
                }, function () {
                    layList.basePost(that.U({a: 'delete'}), {imageid: that.selectImagesIDS}, function (res) {
                        layList.msg(res.msg);
                        that.getImageList();
                        window.location.reload()
                    }, function (res) {
                        layList.msg(res.msg);
                    })
                })
            },
            //移动图片分类
            moveCate: function () {
                if (!this.selectImages.length) return;
                return this.getOpenWindow('移动图片', this.U({a: 'moveimg'}) + '?imgaes=' + this.selectImagesIDS);
            },
            //使用选中图片
            useImages: function () {
                if (!this.selectImages.length) return;
                //判断表单限制图片个数
                if (typeof parent.$f != 'undefined') {
                    //已有图片个数
                    var nowpics = parent.$f.getValue(parentinputname).length,
                        props = parent.$f.model()[parentinputname].props || {},
                        maxlength = props.maxLength || 0;
                    //已选图片个数
                    var selectlength = this.selectImages.length;
                    //还可以选择多少张
                    var surplus = maxlength - nowpics;
                    if (nowpics + selectlength > maxlength) {
                        return layList.msg('最多只能选择 ' + surplus + ' 张');
                    }
                }
                //编辑器中
                if (parentinputname.includes('editor')) {
                    var list = this.selectImages.map(function (image) {
                        return {
                            _src: image,
                            src: image
                        };
                    });
                    parent.insertEditor(list, parentinputname);
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index)
                } else {
                    //form表单中
                    if (parent.$f) {
                        var value = parent.$f.getValue(parentinputname);//父级input 值
                        var list = value || [];
                        for (var i = 0; i < this.selectImages.length; i++) {
                            if (value.indexOf(this.selectImages[i]) == -1) list.push(this.selectImages[i]);
                        }
                        parent.$f.changeField(parentinputname, list);
                        parent.$f.closeModal(parentinputname);
                    } else {
                        //独立图片选择页面
                        if(maxLength > 0 ){
                            if(this.selectImages.length > maxLength){
                                return layList.msg('最多能选择' + maxLength + '张');
                            }
                            parent.changeIMG(parentinputname, this.selectImages,1);
                        }else{
                            if(this.selectImages.length > 1){
                                return layList.msg('只能选择一张图片');
                            }
                            parent.changeIMG(parentinputname, this.selectImages[0]);
                        }
                        var index = parent.layer.getFrameIndex(window.name);
                        parent.layer.close(index);
                    }
                }

            },
            //图片选中和取消
            changImage: function (item, index) {
                this.$set(item, 'isSelect', item.isSelect == undefined ? true : !item.isSelect);
                var val = small == 1 ? item['satt_dir'] : item['att_dir'];
                if (item.isSelect === true) {
                    this.selectImages[this.selectImages.length] = val;
                    this.selectImagesIDS[this.selectImages.length] = item['att_id'];
                    item.sort = this.selectImages.length;
                } else {
                    this.selectImages.splice(this.selectImages.indexOf(val), 1);
                    this.selectImagesIDS.splice(this.selectImages.indexOf(item['att_id']), 1);
                    for (var i = 0; i < this.imageList.length; i++) {
                        if (this.imageList[i].sort > item.sort) {
                            this.imageList[i].sort = (this.imageList[i].sort - 1) >= 0 ? this.imageList[i].sort - 1 : 0;
                        }
                    }
                    item.sort = 0;
                }
                this.$set(this, 'selectImages', this.selectImages);
                this.$set(this, 'selectImagesIDS', this.selectImagesIDS);
            },
            //获取图片列表
            getImageList: function () {
                var that = this;
                if (that.loading) return;
                that.loading = true;
                var index = layList.layer.load(1, {shade: [0.1, '#fff']});
                layList.baseGet(this.U({
                    a: 'get_image_list',
                    q: {pid: this.pid, page: this.page, limit: this.limit, title: that.searchImage }
                }), function (res) {
                    that.loading = false;

                    var list = res.data.list;
                    for (var i = list.length; i--;) {
                        list[i].readonly = true;
                        list[i].isEdit = false;
                        if (!list[i].title) {
                            list[i].title = list[i].name.slice(0, list[i].name.lastIndexOf('.'));
                        }
                    }
                    that.$set(that, 'imageList', res.data.list);
                    layList.layer.close(index);
                    if (that.page == 1) {
                        layList.laypage.render({
                            elem: that.$refs.image_page
                            , count: res.data.count
                            , limit: that.limit
                            , theme: '#10952a',
                            jump: function (obj) {
                                that.page = obj.curr;
                            }
                        });
                    }
                }, function () {
                    that.loading = false;
                    layList.layer.close(index);
                });
            },
            //查询分类
            search: function () {
//                if(!this.searchTitle) return layList.msg('请输入搜索内容！');
                this.getCategoryList();
            },
            //打开和关闭树形
            OpenTree: function (item, index) {
                var vm = this;
                if (item.child.length) {
                    vm.$set(vm.categoryList[index], 'isOpen', !item.isOpen);
                }
                if (vm.pid == item.id) {
                    return;
                }
                vm.pid = item.id;
                vm.page = 1;
                vm.getImageList();
                // this.searchImage = '';
                // this.pid = item.id;
                // if (item.child.length) {
                //     // item.isOpen == undefined ? false : item.isOpen;
                //     // this.$set(this.categoryList[index], 'isOpen', !item.isOpen);
                //     item.isOpen == undefined ? false : item.isOpen;
                //     this.$set(this.categoryList[index], 'isOpen', !item.isOpen);
                // } else {
                //     this.page = 1;
                //     this.$set(this, 'selectImages', []);
                //     this.$set(this, 'selectImagesIDS', []);
                //     this.getImageList();
                // }
                // this.uploadInst.reload({
                //     url: this.U({a: 'upload'}) + '?pid=' + this.pid
                // });
            },


            //组装URL
            U: function (opt) {
                opt = typeof opt == 'object' ? opt : {};
                return layList.U({m: 'admin', c: "widget.images", a: opt.a || '', q: opt.q || {}, p: opt.q || {}});
            },
            //获取分类
            getCategoryList: function () {
                // var that = this;
                // console.log(window);
                // console.log(that);
                // layList.baseGet(that.U({a: 'get_image_cate', q: {name: this.searchTitle}}), function (res) {
                //     that.$set(that, 'categoryList', res.data);
                // });
                var vm = this;
                layList.baseGet(vm.U({
                    a: 'get_image_cate',
                    q: {
                        name: vm.searchTitle
                    }
                }), function (res) {
                    vm.categoryList = res.data;
                }, function (err) {
                    layList.msg(err.msg);
                });
            },
            //鼠标移入显示图标
            changeActive: function (item, indexK, index) {
                if (index)
                    this.$set(this.categoryList[indexK]['child'], 'isShow', true);
                else
                    this.$set(this.categoryList[indexK], 'isShow', true);
            },
            //鼠标移出隐藏
            removeActive: function (item, indexK, index) {
                if (index)
                    this.$set(this.categoryList[indexK]['child'], 'isShow', false);
                else
                    this.$set(this.categoryList[indexK], 'isShow', false);
            },
            //添加分类
            addCategory: function (item, pid) {
                item = item == undefined ? {} : item;
                var id = item.id == undefined ? 0 : item.id,
                    pid = pid == undefined ? 0 : pid;
                return this.getOpenWindow(item.name ? item.name + '编辑' : '新增分类', this.U({
                    a: 'addcate',
                    q: {id: pid == 0 ? id : pid}
                }));
            },
            //修改分类
            updateCategory: function (item, pid) {
                item = item == undefined ? {} : item;
                pid = pid == undefined ? 0 : pid;
                return this.getOpenWindow(item.name + '编辑', this.U({a: 'editcate', q: {id: item.id}}));
            },
            //删除分类
            delCategory: function (item, pid) {
                var that = this;
                if (item.child.length) return layList.msg('请先删除子分类再尝试删除此分类！');
                layList.layer.confirm('是否要删除[' + item.name + ']分类？', {
                    btn: ['是的我要删除', '我想想吧'] //按钮
                }, function () {
                    layList.baseGet(that.U({a: 'deletecate', q: {id: item.id}}), function (res) {
                        layList.msg(res.msg, function () {
                            that.getCategoryList();
                        });
                    });
                });
            },
            //打开一个窗口
            getOpenWindow: function (title, url, opt) {
                opt = opt == undefined ? {w: 340, h: 265} : opt;
                return layList.layer.open({
                    type: 2,
                    title: title,
                    shade: [0],
                    area: [opt.w + "px", opt.h + 'px'],
                    anim: 2,
                    content: [url, 'no'],
                });
            },
            //回调
            SuccessCateg: function () {
                this.getCategoryList();
            },
            uploal: function () {
                var that = this;
                this.uploadInst = layList.upload.render({
                    elem: this.$refs.upload
                    , url: this.U({a: 'upload'}) + '?pid=' + this.pid
                    , multiple: true
                    , auto: true
                    , size: 2097152 //限制文件大小，单位 KB
                    , done: function (res) {
                        layList.layer.msg('上传成功', {time: 2000});
                        that.page = 1;
                        that.getImageList();
                    }
                });
            }
        },
        mounted: function () {
            this.getCategoryList();
            this.getImageList();
            window.SuccessCateg = this.SuccessCateg;
            this.uploal();
        }
    })


</script>

