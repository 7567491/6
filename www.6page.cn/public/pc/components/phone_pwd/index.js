define([
    'api/login',
    'api/my',
    'plugins/blueimp-md5/js/md5',
    'text!components/phone_pwd/index.html',
    'css!components/phone_pwd/index.css'
], function(loginApi, myApi, md5, html) {
    return {
        props: {
            phonePwdVisible: {
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
                code: '',
                pwd: '',
                count: -1,
                TIME_COUNT: 60
            };
        },
        watch: {
            phonePwdVisible: function (val) {
                var vm = this
                if (typeof slide_captcha === 'undefined' || !slide_captcha) {
                    return
                }
                if (val) {
                    setTimeout(function () {
                        // // 初始化验证码  弹出式
                        $('#slide-captcha-pwd').slideVerify({
                            baseUrl: slide_captcha_api,  //服务器请求地址, 默认地址为安吉服务器;
                            mode: 'pop',     //展示模式
                            containerId: 'captcha-btn-pwd',//pop模式 必填 被点击之后出现行为验证码的元素id
                            imgSize : {       //图片的大小对象,有默认值{ width: '310px',height: '155px'},可省略
                                width: '400px',
                                height: '200px',
                            },
                            barSize:{          //下方滑块的大小对象,有默认值{ width: '310px',height: '50px'},可省略
                                width: '400px',
                                height: '40px',
                            },
                            beforeCheck:function(){
                                var thePhone = vm.userInfo.phone
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
                var thePhone = vm.userInfo.phone
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
            goCode: function () {
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
                    phone: vm.userInfo.phone,
                }).then(function (res) {
                    vm.$message.success(res.msg);
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    clearInterval(vm.timer);
                    vm.timer = null;
                    vm.count = -1;
                });
            },
            // 获取验证码
            getCode: function () {
                if (typeof slide_captcha !== 'undefined' && slide_captcha) {
                    return
                }
                var vm = this;
                var thePhone = vm.userInfo.phone
                if (!thePhone) {
                    vm.$message.warning('手机号不能为空');
                    return false
                }
                if (!/^1[3456789]\d{9}$/.test(thePhone)) {
                    vm.$message.warning('手机号错误');
                    return false
                }
                vm.goCode()
            },
            submit: function () {
                var vm = this;
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
                if (!this.pwd) {
                    return this.$message.warning('请输入密码');
                }
                if (!/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/.test(this.pwd)) {
                    return this.$message.warning('请输入8-16位字母加数字组合新密码');
                }
                // 修改密码
                loginApi.register({
                    account: this.userInfo.phone,
                    code: this.code,
                    pwd: md5(this.pwd),
                    type: 2
                }).then(function (res) {
                    vm.$message({
                        type: 'success',
                        message: res.msg,
                        onClose: function () {
                            vm.$emit('login-again');
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