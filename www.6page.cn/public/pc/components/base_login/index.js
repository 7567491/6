define([
    'api/auth',
    'api/login',
    'plugins/blueimp-md5/js/md5',
    'qrcode',
    'text!components/base_login/index.html',
    'css!components/base_login/index.css'
], function (authApi, loginApi, md5, QRCode, html) {
    return {
        props: {
            loginVisible: {
                type: Boolean,
                default: false
            },
            publicData: {
                type: Object,
                default: function () {
                    return {};
                }
            },
            agreeContent: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        data: function () {
            return {
                state: 3, // 1 注册，2 找回密码，3 登录
                type: window.login_types == 2 ? 3 : 1,  // 1 账号登录，2 快速登录， 3 扫码关注登录
                phone: '',
                code: '',
                pwd: '',
                agree: false,
                count: -1,
                TIME_COUNT: 60,
                qrcode: null,
                timeId: null,
                spread_uid: 0,
                // login_types存在container.html中
                login_types: window.login_types,
            };
        },
        watch: {
            state: function () {
                this.phone = '';
                this.code = '';
                this.pwd = '';
                this.agree = false;
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                    this.count = -1;
                }
            },
            // loginVisible: function (newData) {
            //     if (newData) {
            //         this.getLoginQrcode()
            //     } else {
            //         document.getElementById("login-qrcode").innerHTML = ""
            //         clearInterval(this.timeId)
            //     }
            // },
            type: function (val) {
                if (val == 3) {
                    document.getElementById("login-qrcode").innerHTML = ""
                    clearInterval(this.timeId)
                    this.getLoginQrcode()
                }
            },
            loginVisible: function (val) {
                var vm = this
                if (typeof slide_captcha === 'undefined' || !slide_captcha) {
                    return
                }
                if (val) {
                    setTimeout(function () {
                        // // 初始化验证码  弹出式
                        $('#slide-captcha').slideVerify({
                            baseUrl: slide_captcha_api,  //服务器请求地址, 默认地址为安吉服务器;
                            mode: 'pop',     //展示模式
                            containerId: 'captcha-btn',//pop模式 必填 被点击之后出现行为验证码的元素id
                            imgSize : {       //图片的大小对象,有默认值{ width: '310px',height: '155px'},可省略
                                width: '400px',
                                height: '200px',
                            },
                            barSize:{          //下方滑块的大小对象,有默认值{ width: '310px',height: '50px'},可省略
                                width: '400px',
                                height: '40px',
                            },
                            beforeCheck:function(){
                                if (!vm.phone) {
                                    vm.$message.warning('请输入手机号');
                                    return false
                                }
                                if (!/^1[3456789]\d{9}$/.test(vm.phone)) {
                                    vm.$message.warning('手机号错误');
                                    return false
                                }
                                return true
                            },
                            ready : function() {},  //加载完毕的回调
                            success : function(params) { //成功的回调
                                vm.count = vm.TIME_COUNT;
                                vm.timer = setInterval(function () {
                                    vm.count--;
                                    if (vm.count < 0) {
                                        clearInterval(vm.timer);
                                        vm.timer = null;
                                    }
                                }, 1000);
                                authApi.code({
                                    phone: vm.phone,
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
                            error : function() {}        //失败的回调
                        });
                    }, 0)
                }
            }
        },
        created: function () {
            var vm = this;
            vm.spread_uid = this.getParmas('spread_uid')
            window.addEventListener('keydown', function (event) {
                if (event.key == 'Enter' && vm.loginVisible) {
                    vm.login();
                }
            });
        },
        mounted: function () {
            var vm = this
        },
        methods: {
            // 如果有spread_uid，那么登录成功后额外请求一次用户信息，绑定雇佣关系
            bindEmpoy: function () {
              if (this.spread_uid) {
                  authApi.user_info({
                      spread_uid: this.spread_uid
                  }).then(function () {

                  })
              }
            },
            getParmas: function (params) {
                var reg = new RegExp('(^|&)' + params + '=([^&]*)(&|$)');
                var obj = window.location.search.substr(1).match(reg);
                if (obj) {
                    return decodeURI(obj[2]);
                }
                return null;
            },
            // 获取登录二维码
            getLoginQrcode: function () {
                var _this = this;
                authApi.get_login_qrcode()
                .then(function (res) {
                    var url = res.data['url'];
                    var sence_key = res.data['sence_key']
                    _this.qrcode = new QRCode("login-qrcode", {
                        text: url,
                        width: 200,
                        height: 200,
                        colorDark : "#000000",
                        colorLight : "#ffffff"
                    });
                    _this.timeId = setInterval(function () {
                        authApi.check_wechat_login_status({
                            sence_key: sence_key
                        }).then(function (login_res) {
                            if (login_res.data.status && login_res.data.status === 'waiting') {
                                return
                            }
                            if (login_res.data.account) {
                                _this.$message.success('登录成功');
                                _this.$emit('login-close', 1);
                                clearInterval(this.timeId)
                            }
                        }).catch(function (error) {
                            _this.$message.fail(error);
                            clearInterval(this.timeId)
                        })
                    }, 2000)
                })

            },
            // 获取验证码
            getCode: function () {
                if (typeof slide_captcha !== 'undefined' && slide_captcha) {
                    return
                }
                var vm = this;
                if (!this.phone) {
                    return this.$message.warning('请输入手机号');
                }
                if (!/^1[3456789]\d{9}$/.test(this.phone)) {
                    return this.$message.warning('手机号错误');
                }
                this.count = this.TIME_COUNT;
                this.timer = setInterval(function () {
                    vm.count--;
                    if (vm.count < 0) {
                        clearInterval(vm.timer);
                        vm.timer = null;
                    }
                }, 1000);
                authApi.code({
                    phone: this.phone
                }).then(function (res) {
                    vm.$message.success(res.msg);
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    clearInterval(vm.timer);
                    vm.timer = null;
                    vm.count = -1;
                });
            },
            // 登录
            login: function () {
                this.type === 1 ? this.pwdLogin() : this.smsLogin();
            },
            // 短信登录、注册
            smsLogin: function () {
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
                if (!this.agree) {
                    return this.$message.warning('请勾选用户协议');
                }
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
                loginApi.phoneCheck({
                    phone: this.phone,
                    code: this.code
                }).then(function (res) {
                    vm.$message.success(res.msg);
                    vm.$emit('login-close', 1);
                    vm.phone = '';
                    vm.code = '';
                    vm.count = -1;
                    vm.bindEmpoy();
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    vm.count = -1;
                });
            },
            // 账号密码登录
            pwdLogin: function () {
                var vm = this;
                if (!this.phone) {
                    return this.$message.warning('请输入手机号');
                }
                // if (!/^1[3456789]\d{9}$/.test(this.phone)) {
                //     return this.$message.warning('手机号错误');
                // }
                if (!this.pwd) {
                    return this.$message.warning('请输入密码');
                }
                // if (this.pwd !== '123456' || !/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/.test(this.pwd)) {
                //     return this.$message.warning('密码格式错误');
                // }
                if (!this.agree) {
                    return this.$message.warning('请勾选用户协议');
                }
                loginApi.heck({
                    account: this.phone,
                    pwd: md5(this.pwd)
                }).then(function (res) {
                    vm.$message.success(res.msg);
                    vm.$emit('login-close', 1);
                    vm.bindEmpoy();
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            // 账号注册、找回密码
            register: function () {
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
                if (!this.pwd) {
                    return this.$message.warning('请输入密码');
                }
                if (!/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/.test(this.pwd)) {
                    return this.$message.warning('请输入8-16位字母加数字组合密码');
                }
                if (this.state === 1 && !this.agree) {
                    return this.$message.warning('请勾选用户协议');
                }
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
                loginApi.register({
                    account: this.phone,
                    pwd: md5(this.pwd),
                    code: this.code,
                    type: this.state
                }).then(function (res) {
                    vm.$message.success(res.msg);
                    vm.phone = '';
                    vm.pwd = '';
                    vm.code = '';
                    vm.state = 3;
                    vm.bindEmpoy();
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    vm.count = -1;
                });
            },
        },
        template: html
    };
});