define([
    'axios',
    'layer',
    'text!components/payment/index.html',
    'css!components/payment/index.css'
], function(axios, layer, html) {
    return {
        props: {
            payment: {
                type: Boolean,
                default: false
            },
            money: {
                type: Number,
                default: 0
            },
            now_money: {
                type: Number,
                default: 0
            },
            special_id: {
                type: Number,
                default: 0
            },
            pay_type_num: {
                type: Number,
                default: -1
            },
            pinkId: {
                type: Number,
                default: 0
            },
            link_pay_uid: {
                type: Number,
                default: 0
            },
            isWechat: {
                type: Boolean,
                default: false
            },
            isAlipay: {
                type: Boolean,
                default: false
            },
            isBalance: {
                type: Boolean,
                default: false
            },
            signs: {
                type: Object,
                default: function () {
                    return {};
                }
            },
            templateId: {
                type: String,
                default: ''
            },
            wxpayH5: {
                type: Boolean,
                default: true
            },
            priceId: {
                type: Number,
                default: 0
            }
        },
        data: function () {
            return {
                payType: ''
            };
        },
        mounted: function () {
            this.$nextTick(function () {
                if (this.isWechat) {
                    // 无法使用开放标签触发WeixinOpenTagsError事件
                    document.addEventListener('WeixinOpenTagsError', function (e) {
                        console.error(e.detail.errMsg);
                    });
                }
                if (this.isWechat || this.wxpayH5) {
                    this.payType = 'weixin';
                } else if (!this.isWechat && !this.wxpayH5 && this.isAlipay) {
                    this.payType = 'zhifubao';
                } else if (!this.isWechat && !this.wxpayH5 && !this.isAlipay && this.isBalance) {
                    this.payType = 'yue';
                }
            });
        },
        methods: {
            // 立即支付
            onPay: function () {
                var index = layer.load(1);
                var backUrlCRshlcICwGdGY = {
                    special_id: this.special_id,
                    pay_type_num: this.pay_type_num,
                    pinkId: this.pinkId,
                    link_pay_uid: this.link_pay_uid,
                    payType: this.payType,
                    from: this.isWechat ? 'weixin' : 'weixinh5'
                };

                Object.assign(backUrlCRshlcICwGdGY, this.signs);

                // 报名信息转换为JSON字符串
                if (this.pay_type_num === 20) {
                    for (var i = 0; i < backUrlCRshlcICwGdGY.event.length; i++) {
                        if (backUrlCRshlcICwGdGY.event[i].event_type === 3) {
                            backUrlCRshlcICwGdGY.event[i].event_value = backUrlCRshlcICwGdGY.event[i].event_value.join();
                        }
                    }
                    backUrlCRshlcICwGdGY.price_id = this.priceId;
                }

                axios.post('/wap/special/create_order', backUrlCRshlcICwGdGY).then(function (res) {
                    if (res.data.code === 200) {
                        this.$emit('change', {
                            action: 'pay_order',
                            value: res.data
                        });
                    } else {
                        layer.msg(res.data.msg, {
                            anim: 0
                        }, function () {
                            this.$emit('change', {
                                action: 'payClose',
                                value: true
                            });
                        }.bind(this));
                    }
                }.bind(this)).catch(function (error) {
                    console.error(error);
                }.bind(this)).then(function () {
                    layer.close(index);
                });
            },
            // 微信订阅按钮操作成功
            onSuccess: function (event) {
                if (event.detail.errMsg === 'subscribe:ok') {
                    this.onPay();
                }
            },
            // 微信订阅按钮操作失败
            onError: function (event) {
                // layer.msg('订阅通知模板ID错误', {
                //     anim: 0
                // }, function () {
                //     this.onPay();
                // }.bind(this));
                this.onPay();
            },
            // 关闭
            onClose: function () {
                this.$emit('change', {
                    action: 'payClose',
                    value: true
                });
            }
        },
        template: html
    };
});