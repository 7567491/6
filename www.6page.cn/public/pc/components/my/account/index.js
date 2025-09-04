define([
    'components/phone_pwd/index',
    'components/bind_phone/index',
    'components/change_phone/index',
    'api/my',
    'text!components/my/account/index.html',
    'css!components/my/account/index.css'
], function(PhonePwd, BindPhone, ChangePhone, myApi, html) {
    return {
        components: {
            'phone-pwd': PhonePwd,
            'bind-phone': BindPhone,
            'change-phone': ChangePhone
        },
        filters: {
            phoneEncrypt: function (phone) {
                if (!phone) {
                    return '';
                }
                return phone.replace(/(\d{3})\d*(\d{4})/, '$1****$2');
            }
        },
        props: {
            isLogin: {
                type: Boolean,
                default: false
            },
            userInfo: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        data: function () {
            return {
                nicknameReadonly: true,
                fullnameReadonly: true,
                phonePwdVisible: false,
                bindVisible: false,
                changeVisible: false,
                phonePwd: true,
                avatar: '',
                // login_types存在container.html中
                login_types: window.login_types,
            };
        },
        watch: {
            userInfo: function (val) {
                // 打开手机号绑定弹窗
                if (val.force_binding == 1 && !val.phone) {
                    this.bindVisible = true;
                }
            }
        },
        methods: {
            handleBeforeUpload: function (file) {
                if (file.type == 'image/jpeg') {
                    return true;
                }
                if (file.type == 'image/jpg') {
                    return true;
                }
                if (file.type == 'image/png') {
                    return true;
                }
                this.$message.error('上传头像图片只能是 JPEG、JPG、PNG 格式!');
                return false;
            },
            // 打开手机号绑定弹窗
            openPhoneDg: function () {
                // 如果开启强制绑定手机号，并且手机号为空，则打开绑定手机号弹窗
                if (this.userInfo.force_binding == 1 && !this.userInfo.phone) {
                    this.bindVisible = true;
                    return
                }
                this.changeVisible = true;
            },
            handleSuccess: function (res) {
                var vm = this;
                if (typeof res == 'string') {
                    return vm.$message.error(res);
                }
                if (res.code == 400) {
                    vm.$message.error(res.msg)
                    return;
                }
                vm.avatar = res.data.url;
                vm.$message.success(res.msg);
            },
            // 点击保存
            save: function () {
                var vm = this;
                myApi.saveUserInfo({
                    avatar: this.avatar || this.userInfo.avatar,
                    full_name: this.userInfo.full_name,
                    nickname: this.userInfo.nickname
                }).then(function (res) {
                    vm.$message.success(res.msg);
                    vm.nicknameReadonly = true;
                    vm.fullnameReadonly = true;
                    vm.$emit('update-user');
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            phoneClose: function () {
                this.phonePwdVisible = false;
                this.bindVisible = false;
                this.changeVisible = false;
            },
            loginAgain: function () {
                this.phonePwdVisible = false;
                this.bindVisible = false;
                this.changeVisible = false;
                this.$emit('login-again');
            },
            // 点击头像的修改
            updateAvatar: function () {
                this.$refs.upload.$refs['upload-inner'].$refs.input.click();
            }
        },
        template: html
    };
});