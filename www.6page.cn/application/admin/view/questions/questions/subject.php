{extend name="public/container" /}
{block name="head_top"}
<style>
    .layui-form-checkbox i {
        height: 100%;
    }

    .layui-form-checked span, .layui-form-checked:hover span {
        background-color: #10952a;
    }

    .layui-form-checked i, .layui-form-checked:hover i {
        color: #10952a;
    }

    .layui-upload-drag .layui-icon {
        color: #10952a;
    }

    .upload-image-box:hover .mask{
        display: block;
    }

    .upload-image-box .mask {
        display: none;
    }

    .upload-image-box .mask p {
        width: auto;
        height: auto;
        text-align: center;
    }

    .layui-input-block label {
        margin-bottom: 0;
        font-weight: normal;
    }
</style>
<script src="{__ADMIN_PATH}plug/ueditor/ueditor.config.js"></script>
<script src="{__ADMIN_PATH}plug/ueditor/ueditor.all.min.js"></script>
<script src="{__ADMIN_PATH}plug/ueditor/third-party/zeroclipboard/ZeroClipboard.min.js"></script>
{/block}
{block name="content"}
<div v-cloak id="app" class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-header">添加题目</div>
        <div class="layui-card-body">
            <form class="layui-form" action="">
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label">题型：</label>
                            <div class="layui-input-block">
                                <label v-for="(item, index) in types" :key="index">
                                    <input v-model="type" type="radio" :value="item.value">
                                    <div :class="{ 'layui-form-radioed': item.value === type }" class="layui-unselect layui-form-radio">
                                        <i :class="{ 'layui-anim-scaleSpring': item.value === type }" class="layui-anim layui-icon">{{ item.value === type ? '&#xe643;' : '&#xe63f;' }}</i>
                                        <div>{{ item.title }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-show="type !== 3" class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label">选项类型：</label>
                            <div class="layui-input-block">
                                <label v-for="item in optionTypes" :key="item.value">
                                    <input v-model="is_img" type="radio" :value="item.value">
                                    <div :class="{ 'layui-form-radioed': is_img === item.value }" class="layui-unselect layui-form-radio">
                                        <i :class="{ 'layui-anim-scaleSpring': is_img === item.value }" class="layui-anim layui-icon">{{ is_img === item.value ? '&#xe643;' : '&#xe63f;' }}</i>
                                        <div>{{ item.title }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label required">分类：</label>
                            <div class="layui-input-block">
                                <div :class="{ 'layui-form-selected': isSelect }" class="layui-unselect layui-form-select">
                                    <div class="layui-select-title" @click="isSelect = true">
                                        <input v-model="subject_text" type="text" required  lay-verify="required" placeholder="请选择" readonly class="layui-input layui-unselect" @blur="onSelectBlur">
                                        <i class="layui-edge"></i>
                                    </div>
                                    <dl class="layui-anim layui-anim-upbit">
                                        <dd class="layui-select-tips">请选择</dd>
                                        <dd v-for="item in subject_list" :key="item.id" :class="{ 'layui-this': item.id == subject_id }" @click="onSelect(item)">{{ item.html }}{{ item.title }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label required">题目：</label>
                            <div class="layui-input-block">
                                <input v-model.trim="title" type="text" required  lay-verify="required" maxlength="500" placeholder="请输入题目" autocomplete="off" class="layui-input">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label">插图：（630*440）</label>
                            <div class="layui-input-inline" style="width: auto;">
                                <div v-show="formData.image" class="upload-image-box">
                                    <img :src="formData.image">
                                    <div class="mask">
                                        <p>
                                            <i class="fa fa-eye" @click="look(formData.image)"></i>
                                            <i class="fa fa-trash-o" @click="delect('image')"></i>
                                        </p>
                                    </div>
                                </div>
                                <div v-show="!formData.image" class="upload-image" @click="onUpload('image')">
                                    <div class="fiexd">
                                        <i class="fa fa-plus"></i>
                                    </div>
                                    <p>选择图片</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-for="(item, index) in options" :key="index" class="layui-form-item">
                    <div class="layui-row layui-col-space10">
                        <div :class="[is_img ? 'layui-col-md5' : 'layui-col-md10']">
                            <label class="layui-form-label">{{ index | upperCase }}：</label>
                            <div v-show="!is_img" class="layui-input-block">
                                <input v-model.trim="item.value" :readonly="type === 3" type="text" required  lay-verify="required" maxlength="500" placeholder="请输入选项内容" autocomplete="off" class="layui-input">
                            </div>
                            <div v-show="is_img" class="layui-input-inline" style="width: auto;">
                                <div v-show="item.value" class="upload-image-box">
                                    <img :src="item.value">
                                    <div class="mask">
                                        <p>
                                            <i class="fa fa-eye" @click="look(item.value)"></i>
                                            <i class="fa fa-trash-o" @click="delect(index)"></i>
                                        </p>
                                    </div>
                                </div>
                                <div v-show="!item.value" class="upload-image" @click="onUpload(index)">
                                    <div class="fiexd">
                                        <i class="fa fa-plus"></i>
                                    </div>
                                    <p>选择图片</p>
                                </div>
                            </div>
                            <div v-show="is_img" class="layui-form-mid layui-word-aux">270*162px</div>
                        </div>
                        <div v-show="index > 1 && index === options.length - 1" class="layui-col-md2">
                            <button type="button" class="layui-btn layui-btn-danger layui-btn-sm" @click="onDel">删除选项</button>
                        </div>
                    </div>
                </div>
                <div v-show="this.options.length !== 10 && type !== 3" class="layui-form-item">
                    <div class="layui-input-block">
                        <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" @click="onAdd">添加选项</button>
                        <div class="layui-inline layui-word-aux">（最多添加10个选项）</div>
                    </div>
                </div>
                <div v-if="type === 2" class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label">正确答案：</label>
                            <div class="layui-input-block">
                                <label v-for="(item, index) in options" :key="index">
                                    <input v-model="checkboxIndex" type="checkbox" :value="index">
                                    <div :class="{ 'layui-form-checked':  item.checked }" class="layui-unselect layui-form-checkbox">
                                        <span>{{ index | upperCase }}</span><i class="layui-icon layui-icon-ok"></i>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label">正确答案：</label>
                            <div class="layui-input-block">
                                <label v-for="(item, index) in options" :key="index">
                                    <input v-model="radioIndex" type="radio" :value="index">
                                    <div :class="{ 'layui-form-radioed': index === radioIndex }" class="layui-unselect layui-form-radio">
                                        <i :class="{ 'layui-anim-scaleSpring': index === radioIndex }" class="layui-anim layui-icon">{{ index === radioIndex ? '&#xe643;' : '&#xe63f;' }}</i>
                                        <div>{{ index | upperCase }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label">试题难度：</label>
                            <div class="layui-input-block">
                                <div class="layui-inline">
                                    <ul class="layui-rate">
                                        <li v-for="item in 5" :key="item" class="layui-inline" @mousemove="difficultyHover = item" @mouseleave="difficultyHover = difficulty" @click="rateClick(item)">
                                            <i :class="[difficultyHover >= item ? 'layui-icon-rate-solid' : 'layui-icon-rate']" class="layui-icon"></i>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md10">
                            <label class="layui-form-label">答案解析：</label>
                            <div class="layui-input-block">
                                <script id="editor" name="analysis" type="text/plain">{{ analysis }}</script>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-row">
                        <div class="layui-col-md2">
                            <label class="layui-form-label">排序：</label>
                            <div class="layui-input-block">
                                <input v-model.number="sort" type="number" min="0" placeholder="排序" autocomplete="off" class="layui-input">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button type="button" class="layui-btn layui-btn-normal" lay-submit lay-filter="*">立即提交</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="{__ADMIN_PATH}js/layuiList.js"></script>
<script>
    var id={$id},questions={$questions};
    require(['store', 'helper','vue', 'request', 'plupload', 'aliyun-oss', 'OssUpload'], function (app, $h,Vue) {
        var vm = new Vue({
            el: '#app',
            filters: {
                upperCase: function (value) {
                    return String.fromCharCode(value + 65);
                }
            },
            data: {
                host: ossUpload.host + '/',
                mime_types: {
                    Image: "jpg,gif,png,JPG,GIF,PNG",
                    Video: "mp4,MP4",
                },
                subject_id: 0,
                formData: {
                    image: ''
                },
                mask: {
                    image: false
                },
                subject_list: [],
                options: [
                    {
                        value: '',
                        checked: false,
                        mask: false
                    },
                    {
                        value: '',
                        checked: false,
                        mask: false
                    }
                ],
                radioIndex: 0,
                checkboxIndex: [0, 1],
                types: [
                    {
                        title: '单选题',
                        value: 1
                    },
                    {
                        title: '多选题',
                        value: 2
                    },
                    {
                        title: '判断题',
                        value: 3
                    }
                ],
                type: 1,
                title: '',
                analysis: '',
                difficulty: 1,
                difficultyHover: 1,
                isSelect: false,
                subject_text: '',
                sort: 0,
                optionTypes: [
                    {
                        title: '文本',
                        value: 0
                    },
                    {
                        title: '图片',
                        value: 1
                    }
                ],
                is_img: 0
            },
            watch: {
                // 题型改变
                type: function (newValue) {
                    this.radioIndex = 0;
                    this.checkboxIndex = [0, 1];
                    this.is_img = 0;
                    this.title = '';
                    this.formData.image = '';
                    if (newValue == 3) {
                        this.options = [
                            {
                                value: '正确',
                                checked: false,
                                mask: false
                            },
                            {
                                value: '错误',
                                checked: false,
                                mask: false
                            }
                        ];
                    } else {
                        this.options = [
                            {
                                value: '',
                                checked: false,
                                mask: false
                            },
                            {
                                value: '',
                                checked: false,
                                mask: false
                            }
                        ];
                    }
                    this.difficultyHover = 1;
                    this.difficulty = 1;
                    this.sort = 0;
                    if (this.ue) {
                        this.ue.execCommand('cleardoc');
                    }
                },
                // 选项类型改变
                is_img: function (newValue, oldValue) {
                    if (newValue) {
                        this.options.forEach(function (item) {
                            item.value = '';
                        });
                    }
                },
                checkboxIndex: {
                    handler: function () {
                        if(this.options[0].value=='') return;
                        for (var i = 0; i < this.options.length; i++) {
                            this.options[i].checked = false;
                        }
                        for (let j = 0; j < this.checkboxIndex.length; j++) {
                            if(this.options[this.checkboxIndex[j]]==undefined) {
                                this.checkboxIndex.splice(j, 1);
                            }else{
                                this.options[this.checkboxIndex[j]].checked = true;
                            }
                        }
                    },
                    immediate: true
                }
            },
            created: function () {
                this.get_subject_list();

                if (Array.isArray(questions)) {
                    return;
                }

                var options = JSON.parse(questions.option);
                var values = [];
                var rights = [];

                for (var key in options) {
                    if (Object.hasOwnProperty.call(options, key)) {
                        values.push({
                            value: options[key],
                            checked: false
                        });
                    }
                }
                this.type = questions.question_type;

                this.$nextTick(function () {
                    this.is_img = questions.is_img;
                    this.subject_id = questions.pid;
                    this.title = questions.stem;
                    this.formData.image = questions.image;
                    this.difficulty = questions.difficulty;
                    this.difficultyHover = questions.difficulty;
                    this.analysis = questions.analysis;
                    this.sort = questions.sort;
                    this.$nextTick(function () {
                        this.options = values;
                        if (questions.question_type === 2) {
                            rights = questions.answer.split(',');
                            rights.forEach(function (item, index) {
                                rights[index] = item.charCodeAt(0) - 65;
                            });

                            this.checkboxIndex = rights;
                        } else {
                            this.radioIndex = questions.answer.charCodeAt(0) - 65;
                        }
                    });


                });
            },
            mounted: function () {
                this.$nextTick(function () {
                    window.changeIMG = this.changeIMG;

                    this.form = layui.form;

                    // ueditor设置
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
                    this.ue = UE.getEditor('editor');

                    window.insertEditor = function (list, fodder) {
                        list = handle_editor_img(list);
                        vm.ue.execCommand('insertimage', list);
                    };

                    // 提交表单
                    this.form.on('submit(*)', function (data) {
                        var field = data.field;
                        var options = [];
                        var right = '';

                        vm.options.forEach(function (item) {
                            options.push(item.value);
                        });

                        if (vm.type == 2) {
                            if (2 > vm.checkboxIndex.length) {
                                return layList.msg('正确答案数量不能小于2个');
                            }
                            vm.checkboxIndex.sort(function (a, b) {
                                return a - b;
                            });
                            vm.checkboxIndex.forEach(function (item, index) {
                                right += (right ? ',' : '') + String.fromCharCode(vm.checkboxIndex[index] + 65);
                            });
                        } else {
                            right = String.fromCharCode(vm.radioIndex + 65);
                        }

                        layList.loadFFF();
                        layList.basePost(layList.U({
                            a: 'save_add',
                            q: {
                                id: id
                            }
                        }), {
                            question_type: vm.type,
                            is_img: vm.is_img,
                            pid: vm.subject_id,
                            stem: vm.title,
                            image: vm.formData.image,
                            option: JSON.stringify(options),
                            answer: right,
                            difficulty: vm.difficulty,
                            analysis: field.analysis,
                            sort: vm.sort
                        }, function (res) {
                            layList.loadClear();
                            if(parseInt(id) == 0) {
                                layList.layer.confirm('提交成功，是否继续添加题目？', {
                                    btn: ['继续添加', '立即提交']
                                }, function (index) {
                                    layList.layer.close(index);
                                }, function () {
                                    parent.layer.closeAll();
                                });
                            }else{
                                layer.msg('修改成功',{icon:1},function () {
                                    parent.layer.closeAll();
                                });
                            }
                        },function (res) {
                            layList.msg(res.msg);
                            layList.loadClear();
                        });
                    });
                });
            },
            methods: {
                onSelectBlur: function () {
                    setTimeout(function () {
                        vm.isSelect = false;
                    }, 200);
                },
                // 选择分类
                onSelect: function (item) {
                    this.subject_id = item.id;
                    this.subject_text = item.html + item.title;
                },
                //获取分类
                get_subject_list: function () {
                    layList.baseGet(layList.U({
                        a: 'get_subject_list'
                    }), function (res) {
                        vm.subject_list = res.data;
                        for (var i = vm.subject_list.length; i--;) {
                            if (vm.subject_list[i].id === vm.subject_id) {
                                vm.subject_text = vm.subject_list[i].html + vm.subject_list[i].title;
                                break;
                            }
                        }
                    });
                },
                // 添加选项
                onAdd: function () {
                    this.options.push({
                        value: '',
                        checked: false,
                        mask: false
                    });
                },
                // 删除选项
                onDel: function () {
                    this.options.pop();
                    for (let j = 0; j < this.checkboxIndex.length; j++) {
                        if(this.options[this.checkboxIndex[j]]==undefined) this.checkboxIndex.splice(j, 1);
                    }
                },
                onUpload: function (key, count) {
                    ossUpload.createFrame('请选择图片', {
                        fodder: key,
                        max_count: count || 0
                    }, {
                        w: 800,
                        h: 550
                    });
                },
                //删除图片
                delect: function (key, index) {
                    var that = this;
                    if (index != undefined) {
                        that.formData[key].splice(index, 1);
                        that.$set(that.formData, key, that.formData[key]);
                    } else {
                        if (typeof key === 'number') {
                            this.options[key].value = '';
                        } else {
                            that.$set(that.formData, key, '');
                        }
                    }
                },
                //查看图片
                look: function (pic) {
                    parent.$eb.openImage(pic);
                },
                changeIMG: function (key, value, multiple) {
                    if (multiple) {
                        var that = this;
                        value.map(function (v) {
                            that.formData[key].push({pic: v, is_show: false});
                        });
                        this.$set(this.formData, key, this.formData[key]);
                    } else {
                        if (key.indexOf('image') === -1) {
                            this.options[parseInt(key)].value = value;
                        } else {
                            this.$set(this.formData, key, value);
                        }
                    }
                },
                // 选择试题难度
                rateClick: function (value) {
                    this.difficulty = value;
                    this.difficultyHover = value;
                }
            }
        });
    });
</script>
{/block}
