define([
    'swiper',
    'qrcode',
    'api/auth',
    'scripts/util',
    'text!components/my/member/index.html',
    'css!components/my/member/index.css'
], function (Swiper, QRCode, authApi, util, html) {
    return {
        filters: {
            priceReal: function (price) {
                return parseFloat(price);
            }
        },
        props: {
            isLogin: {
                type: Boolean,
                default: false
            },
            isAlipay: {
                type: Boolean,
                default: true
            },
            isWechat: {
                type: Boolean,
                default: true
            },
            isYue: {
                type: Boolean,
                default: true
            },
            nowMoney: {
                type: String,
                default: '0'
            },
            userInfo: {
                type: Object,
                default: function () {
                    return {};
                }
            },
            currentMenu: {
                type: String,
                default: ''
            }
        },
        data: function () {
            return {
                exchangeVisible: false,
                interests: [],
                description: [],
                memberShipList: [],
                qrcode: null,
                member_code: '',
                member_pwd: '',
                filterData: {
                    type: 0,
                    payType: 'weixin'
                },
                isReset: true,
                count: 0
            };
        },
        watch: {
            'filterData.payType': function () {
                this.isReset = true;
            },
            userInfo: function (params) {
                console.log(params);
            },
            // currentMenu: function (value) {
            //     if (value === 'member') {
            //         this.merber_data();
            //         this.member_ship_lists();
            //     }
            // }
        },
        created: function () {
            this.merber_data();
            this.member_ship_lists();
        },
        mounted: function () {
            this.$nextTick(function () {
                if (!this.isWechat) {
                    if (this.isAlipay) {
                        this.filterData.payType = 'zhifubao';
                    } else {
                        this.filterData.payType = '';
                    }
                }
            });
        },
        methods: {
            merber_data: function () {
                var vm = this
                authApi.merber_data().then(function (res) {
                    var data = res.data;
                    this.interests = data.interests;
                    this.description = data.description;
                    vm.$emit('vip-rights', data.interests);
                }.bind(this));
            },
            member_ship_lists: function () {
                authApi.member_ship_lists().then(function (res) {
                    this.memberShipList = res.data;
                }.bind(this));
            },
            // 支付
            create_order: function () {
                var vm = this;
                authApi.create_order({
                    special_id: this.memberShipList[this.filterData.type].id,
                    pay_type_num: 10,
                    payType: this.filterData.payType
                }).then(function (res) {
                    switch (res.data.status) {
                        case "PAY_ERROR":
                        case 'ORDER_EXIST':
                        case 'ORDER_ERROR':
                            vm.$message.error(res.msg);
                            break;
                        case 'WECHAT_PAY':
                            vm.isReset = false;
                            if (vm.qrcode) {
                                vm.qrcode.makeCode(res.data.result.jsConfig);
                            } else {
                                vm.$nextTick(function () {
                                    vm.qrcode = new QRCode(vm.$refs.qrcode, res.data.result.jsConfig); 
                                });
                            }
                            vm.testing_order_state(res.data.result.orderId);
                            break;
                        case 'ZHIFUBAO_PAY':
                            vm.isReset = false;
                            if (vm.qrcode) {
                                vm.qrcode.makeCode(res.data.result.jsConfig);
                            } else {
                                vm.$nextTick(function () {
                                    vm.qrcode = new QRCode(vm.$refs.qrcode, res.data.result.jsConfig); 
                                });
                            }
                            vm.testing_order_state(res.data.result.orderId);
                            break;
                        case 'SUCCESS':
                            vm.member_ship_lists();
                            vm.$message.success(res.msg);
                            if (vm.pay_type === 'yue') {
                                vm.dialogVisible = true;
                            }
                            break;
                    }
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            createQRCode: function (text) {
                this.qrcode = new QRCode(document.getElementById("qrcode"), {
                    text: text,
                    width: 250,
                    height: 250,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            },
            confirm_activation: function () {
                var vm = this;
                if (!this.member_code) {
                    return this.$message.warning('请输入卡号');
                }
                if (!this.member_pwd) {
                    return this.$message.warning('请输入卡密');
                }
                authApi.confirm_activation({
                    member_code: this.member_code,
                    member_pwd: this.member_pwd
                }).then(function (res) {
                    vm.$message.success(res.msg);
                    vm.exchangeVisible = false;
                    vm.member_code = '';
                    vm.member_pwd = '';
                    vm.$emit('update-user');
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            // 用户信息
            user_info: function () {
                var vm = this;
                authApi.user_info().then(function (res) {
                    sessionStorage.setItem('userInfo', JSON.stringify(res.data));
                    vm.userInfo = res.data;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            payAfterClick: function () {
                this.isReset = true;
                this.$emit('update-user');
            },
            // 扫码回调
            testing_order_state: function (orderId) {
                var vm = this;
                if (vm.timer) {
                    return;
                }
                this.timer = setInterval(function () {
                    vm.count++;
                    authApi.testing_order_state({
                        order_id: orderId,
                        type: 3
                    }).then(function (res) {
                        if (res.data == 1) {
                            clearInterval(vm.timer);
                            vm.count = 0;
                            vm.timer = null;
                            vm.payAfterClick();
                        }
                    }).catch(function (err) {
                        console.error(err.msg);
                    });
                    if (vm.count == 12) {
                        clearInterval(vm.timer);
                        vm.count = 0;
                        vm.timer = null;
                    }
                }, 5000);
            }
        },
        template: html
    };
});