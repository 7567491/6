define([
    'helper',
    'store',
    'helper',
    'text!components/base_login/index.html',
    'css!components/base_login/index.css',
    'wap/js/md5'
], function(helper, store, $h, html) {
    return {
        props: {
            // 是否行内显示
            inline: {
                type: Boolean,
                default: false
            },
            loginShow: {
                type: Boolean,
                default: false
            },
            siteName: {
                type: String,
                default: ''
            },
            cancelBtn: {
                type: Boolean,
                default: true
            }
        },
        data: function () {
            return {
                state: 3,  // 1 注册 2 找回密码 3 登录
                type: window.login_types == 2 ? 3 : 1,  // 1 账号登录 2 手机登录
                phone: '',
                code: '',
                pwd: '',
                agree: false,
                TIME_COUNT: 60,
                count: -1,
                isWeChat: false,
                // login_types存在container.html中
                login_types: window.login_types,
            };
        },
        computed: {
            pwdPlaceholder: function () {
                if (this.state == 1) {
                    return '请输入8-16位字母加数字组合密码';
                } else if (this.state == 2) {
                    return '请输入8-16位字母加数字组合新密码';
                } else if (this.type == 1) {
                    return '请填写密码';
                }
            }
        },
        watch: {
            loginShow: {
                handler: function (val) {
                    if (val) {
                        this.state = 3;
                        this.type = 1;
                        this.phone = '';
                        this.code = '';
                        this.pwd = '';
                        this.agree = false;
                        if (this.timer) {
                            clearInterval(this.timer);
                            this.timer = null;
                            this.count = -1;
                        }

                        var vm = this
                        if (typeof slide_captcha === 'undefined' || !slide_captcha) {
                            return
                        }
                        setTimeout(function () {
                            // // 初始化验证码  弹出式
                            $('#slide-captcha').slideVerify({
                                baseUrl: slide_captcha_api,  //服务器请求地址, 默认地址为安吉服务器;
                                mode: 'pop',     //展示模式
                                containerId: 'captcha-btn',//pop模式 必填 被点击之后出现行为验证码的元素id
                                imgSize : {       //图片的大小对象,有默认值{ width: '310px',height: '155px'},可省略
                                    width: '300px',
                                    height: '150px',
                                },
                                barSize:{          //下方滑块的大小对象,有默认值{ width: '310px',height: '50px'},可省略
                                    width: '200px',
                                    height: '30px',
                                },
                                beforeCheck:function(){
                                    if (!vm.phone) {
                                        helper.pushMsg('请输入手机号');
                                        return;
                                    }
                                    if (!/^1[3456789]\d{9}$/.test(vm.phone)) {
                                        helper.pushMsg('手机号错误');
                                        return;
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
                                    helper.loadFFF();
                                    store.basePost(helper.U({
                                        c: 'auth_api',
                                        a: 'code'
                                    }),{
                                        phone: vm.phone,
                                        token: params.data.token,
                                        pointJson: params.data.pointJson,
                                        captchaType: 'blockPuzzle'
                                    }, function (res) {
                                        helper.loadClear();
                                        helper.pushMsg(res.data.msg);
                                    }, function () {
                                        helper.loadClear();
                                        if (vm.timer) {
                                            clearInterval(vm.timer);
                                            vm.timer = null;
                                            vm.count = -1;
                                        }
                                    });
                                },
                                error : function() {}        //失败的回调
                            });
                        }, 0)

                    }
                },
                immediate: true
            },
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
            }
        },
        created: function () {
            var ua = navigator.userAgent.toLowerCase();
            if (ua.match(/MicroMessenger/i) == 'micromessenger') {
                this.isWeChat = true;
            }
        },
        methods: {
            // 获取验证码
            getCode: function () {
                if (typeof slide_captcha !== 'undefined' && slide_captcha) {
                    return
                }
                var vm = this;
                if (!this.phone) {
                    helper.pushMsg('请输入手机号');
                    return;
                }
                if (!/^1[3456789]\d{9}$/.test(this.phone)) {
                    helper.pushMsg('手机号错误');
                    return;
                }
                this.count = this.TIME_COUNT;
                this.timer = setInterval(function () {
                    vm.count--;
                    if (vm.count < 0) {
                        clearInterval(vm.timer);
                        vm.timer = null;
                    }
                }, 1000);
                helper.loadFFF();
                store.basePost(helper.U({
                    c: 'auth_api',
                    a: 'code'
                }),{
                    phone: vm.phone
                }, function (res) {
                    helper.loadClear();
                    helper.pushMsg(res.data.msg);
                }, function () {
                    helper.loadClear();
                    if (vm.timer) {
                        clearInterval(vm.timer);
                        vm.timer = null;
                        vm.count = -1;
                    }
                });
            },
            // 注册账号、忘记密码
            register: function () {
                var vm = this;
                if (!this.phone) {
                    helper.pushMsg('请填写手机号');
                    return;
                }
                if (!/^1[3456789]\d{9}$/.test(this.phone)) {
                    helper.pushMsg('手机号错误');
                    return;
                }
                if (!this.code) {
                    helper.pushMsg('请填写验证码');
                    return;
                }
                if (!/^\d{6}$/.test(this.code)) {
                    helper.pushMsg('验证码错误');
                    return;
                }
                if (!this.pwd) {
                    helper.pushMsg('请填写密码');
                    return;
                }
                if (!/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/.test(this.pwd)) {
                    helper.pushMsg('请填写8-16位字母加数字组合密码');
                    return;
                }
                if (this.state == 1) {
                    if (!this.agree) {
                        helper.pushMsg('请勾选用户协议');
                        return;
                    }
                }
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                    vm.count = -1;
                }
                helper.loadFFF();
                store.basePost(helper.U({
                    c: 'login',
                    a: 'register'
                }), {
                    account: this.phone,
                    pwd: hex_md5(this.pwd),
                    code: this.code,
                    type: this.state
                }, function (res) {
                    helper.loadClear();
                    helper.pushMsg(res.data.msg, function () {
                        vm.phone = '';
                        vm.pwd = '';
                        vm.code = '';
                        vm.state = 3;
                        vm.type = 1;
                    });
                }, function () {
                    helper.loadClear();
                });
            },
            // 立即登录
            login: function () {
                if (!this.phone) {
                    helper.pushMsg('请填写手机号');
                    return;
                }
                if (!/^1[3456789]\d{9}$/.test(this.phone)) {
                    helper.pushMsg('手机号错误');
                    return;
                }
                if (this.type == 1) {
                    if (!this.pwd) {
                        helper.pushMsg('请填写密码');
                        return;
                    }
                } else {
                    if (!this.code) {
                        helper.pushMsg('请填写验证码');
                        return;
                    }
                    if (!/^\d{6}$/.test(this.code)) {
                        helper.pushMsg('验证码错误');
                        return;
                    }
                }
                if (!this.agree) {
                    helper.pushMsg('请勾选用户协议');
                    return;
                }
                this.type == 1 ? this.pwdLogin() : this.smsLogin();
            },
            // 账号登录
            pwdLogin: function () {
                var vm = this;
                helper.loadFFF();
                store.basePost(helper.U({
                    c: 'login',
                    a: 'check'
                }), {
                    account: this.phone,
                    pwd: hex_md5(this.pwd)
                }, function (res) {
                    helper.loadClear();
                    helper.pushMsg(res.data.msg, function () {
                        vm.phone = '';
                        vm.pwd = '';
                        vm.$emit('login-close', res.data.data);
                    });
                }, function () {
                    helper.loadClear();
                });
            },
            // 手机登录
            smsLogin: function () {
                var vm = this;
                var url = helper.U({ c: 'login', a: 'phone_check' });
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                    vm.count = -1;
                }
                helper.loadFFF();
                store.basePost(url, {
                    phone: this.phone,
                    code: this.code
                }, function (res) {
                    helper.loadClear();
                    helper.pushMsg(res.data.msg, function () {
                        vm.phone = '';
                        vm.code = '';
                        vm.$emit('login-close', res.data.data);
                    });
                }, function () {
                    helper.loadClear();
                });
            },
            // 点击协议
            goAgree: function () {
                window.location.href = helper.U({
                    c: 'index',
                    a: 'agree'
                });
            },
            wechatLogin(){
                if (!this.agree) {
                    helper.pushMsg('请勾选用户协议');
                    return;
                }
                var ref = $h.getParmas('ref');
                var spread_uid = $h.getParmas('spread_uid');
                var q = {}
                if (ref) {
                    q.ref = ref
                }
                if (spread_uid) {
                    q.spread_uid = spread_uid
                }
                if (this.isEmptyObject(q)) {
                    q = null
                }
                window.location.href = helper.U({
                    c: 'login',
                    a: 'wechatLogin',
                    q: q
                });
            },
            isEmptyObject(obj) {
                for (var key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        return false;
                    }
                }
                return true;
            }
        },
        template: html
    };
});