define([
    'api/auth',
    'scripts/util',
    'text!components/my/balance/index.html',
    'css!components/my/balance/index.css'
], function(authApi, util, html) {
    return {
        props: {
            tab: {
                type: String,
                default: 'balance'
            },
            isLogin: {
                type: Boolean,
                default: false
            },
            isYue: {
                type: Boolean,
                default: false
            },
            currentMenu: {
                type: String,
                default: ''
            }
        },
        data: function () {
            return {
                balance: '',
                consumption: '',
                recharge: '',
                active: 'first',
                page1: 1,
                page2: 1,
                page3: 1,
                limit: 20,
                balanceList1: [],
                balanceList2: [],
                balanceList3: [],
                total1: 0,
                total2: 0,
                total3: 0,
                loaded: false
            };
        },
        watch: {
            currentMenu: function (value) {
                if (value === 'balance' && this.isYue) {
                    this.get_user_balance();
                    this.get_user_balance_list1();
                    this.get_user_balance_list2();
                    this.get_user_balance_list3();
                }
            }
        },
        created: function () {
            var page = util.getParmas('page');
            if (page === 'balance' && this.isYue) {
                this.get_user_balance();
                this.get_user_balance_list1();
                this.get_user_balance_list2();
                this.get_user_balance_list3();
            }
        },
        methods: {
            get_user_balance: function () {
                var vm = this;
                authApi.get_user_balance({}).then(function (res) {
                    var data = res.data;
                    vm.balance = data.balance;
                    vm.consumption = data.consumption;
                    vm.recharge = data.recharge;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            get_user_balance_list1: function () {
                var vm = this;
                authApi.get_user_balance_list({
                    page: this.page1,
                    limit: this.limit,
                    index: ''
                }).then(function (res) {
                    vm.balanceList1 = res.data.list;
                    vm.total1 = res.data.count;
                    vm.loaded = true
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    vm.loaded = true
                });
            },
            get_user_balance_list2: function () {
                var vm = this;
                authApi.get_user_balance_list({
                    page: this.page2,
                    limit: this.limit,
                    index: 2
                }).then(function (res) {
                    vm.balanceList2 = res.data.list;
                    vm.total2 = res.data.count;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            get_user_balance_list3: function () {
                var vm = this;
                authApi.get_user_balance_list({
                    page: this.page3,
                    limit: this.limit,
                    index: 1
                }).then(function (res) {
                    vm.balanceList3 = res.data.list;
                    vm.total3 = res.data.count;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            handleClick: function (tab, event) {
            }
        },
        template: html
    };
});