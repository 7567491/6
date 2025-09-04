define([
    'api/login',
    'api/my',
    'text!components/bind_phone/index.html',
    'css!components/bind_phone/index.css'
], function(loginApi, myApi, html) {
    return {
        props: {
            bindVisible: {
                type: Boolean,
                default: false
            },
            userInfo: {
                type: Object,
                default: function () {
                    return {}
                }
            }
        },
        data: function () {
            return {
                state: true,
                phone: '',
                code: '',
                count: -1,
                TIME_COUNT: 60
            };
        },
        watch: {
            bindVisible: function (val) {
                var vm = this
                if (typeof slide_captcha === 'undefined' || !slide_captcha) {
                    return
                }
                if (val) {
                    setTimeout(function () {
                        // // 初始化验证码  弹出式
                        $('#slide-captcha-bind').slideVerify({
                            baseUrl: slide_captcha_api,  //服务器请求地址, 默认地址为安吉服务器;
                            mode: 'pop',     //展示模式
                            containerId: 'captcha-btn-bind',//pop模式 必填 被点击之后出现行为验证码的元素id
                            imgSize : {       //图片的大小对象,有默认值{ width: '310px',height: '155px'},可省略
                                width: '400px',
                                height: '200px',
                            },
                            barSize:{          //下方滑块的大小对象,有默认值{ width: '310px',height: '50px'},可省略
                                width: '400px',
                                height: '40px',
                            },
                            beforeCheck:function(){
                                var thePhone = vm.phone
                                if (!thePhone) {
                                    vm.$message.warning('手机号不能为空');
                                    return false
                                }
                                if (!/^1[3456789]\d{9}$/.test(thePhone)) {
                                    vm.$message.warning('手机号错误');
                                    return false
                                }
                                return true
                            },
                            ready : function() {},  //加载完毕的回调
                            success : function(params) { //成功的回调
                                vm.slideCode(params)
                            },
                            error : function() {}        //失败的回调
                        });
                    }, 0)
                }
            },
        },
        methods: {
            slideCode: function (params) {
                var vm = this;
                var thePhone = vm.phone
                if (!thePhone) {
                    vm.$message.warning('手机号不能为空');
                    return false
                }
                if (!/^1[3456789]\d{9}$/.test(thePhone)) {
                    vm.$message.warning('手机号错误');
                    return false
                }
                vm.count = vm.TIME_COUNT;
                vm.timer = setInterval(function () {
                    vm.count--;
                    if (vm.count < 0) {
                        clearInterval(vm.timer);
                        vm.timer = null;
                    }
                }, 1000);
                loginApi.code({
                    // vm.state = false时候说明验证过旧手机号，那么进行绑定新手机号操作
                    phone: thePhone,
                    token: params.data.token,
                    pointJson: params.data.pointJson,
                    captchaType: 'blockPuzzle'
                }).then(function (res) {
                    vm.$message.success(res.msg);
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    clearInterval(vm.timer);
                    vm.timer = null;
                    vm.count = -1;
                });
            },
            // 获取短信验证码
            getCodeBind: function () {
                if (typeof slide_captcha !== 'undefined' && slide_captcha) {
                    return
                }
                var vm = this;
                this.count = this.TIME_COUNT;
                this.timer = setInterval(function () {
                    vm.count--;
                    if (vm.count < 0) {
                        clearInterval(vm.timer);
                        vm.timer = null;
                    }
                }, 1000);
                loginApi.code({
                    phone: vm.phone
                }).then(function (res) {
                    vm.$message.success(res.msg);
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    clearInterval(vm.timer);
                    vm.timer = null;
                    vm.count = -1;
                });
            },
            submitBind: function () {
                var vm = this;
                if (!this.phone) {
                    return this.$message.warning('请输入手机号');
                }
                if (!/^1[3456789]\d{9}$/.test(this.phone)) {
                    return this.$message.warning('手机号错误');
                }
                if (!this.code) {
                    return this.$message.warning('请输入验证码');
                }
                if (!/^\d{6}$/.test(this.code)) {
                    return this.$message.warning('验证码错误');
                }
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
                // 绑定手机号
                myApi.save_phone({
                    phone: vm.phone,
                    code: vm.code
                }).then(function (res) {
                    vm.$message({
                        message: res.msg,
                        type: 'success',
                        onClose:function () {
                            location.reload();
                        }
                    });
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    vm.count = -1;
                });
            }
        },
        template: html
    };
});