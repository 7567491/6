define([
    'api/auth',
    'api/special',
    'scripts/util',
    'qrcode',
    'text!components/my/spread/index.html',
    'css!components/my/spread/index.css'
], function (authApi, specialApi, util, QRCode, html) {
    return {
        props: {
            tab: {
                type: String,
                default: 'coin'
            },
            isYue: {
                type: Boolean,
                default: true
            },
            isLogin: {
                type: Boolean,
                default: false
            },
            userInfo: {
                type: Object,
                default: null
            },
            currentMenu: {
                type: String,
                default: ''
            }
        },
        data: function () {
            return {
                spread: {
                    income: 0,
                    order_count: 0,
                    spread_count: 0
                },
                activeName: '0',
                spreadCate: [],
                spreadList: [],
                page: 1,
                limit: 5,
                count: 0,
                loaded: false
            };
        },
        watch: {
            page: function () {
                this.get_spread_list();
            },
            currentMenu: function (value) {
                if (value === 'spread') {
                    this.get_my_spread();
                    this.get_spread_cate()
                    this.get_spread_list();
                }
            }
        },
        created: function () {
            var page = util.getParmas('page');
            if (page === 'spread') {
                this.get_my_spread();
                this.get_spread_cate()
                this.get_spread_list();
            }
        },
        methods: {
            tabChange: function (e) {
                var id = e.name
                this.activeName = id
                this.get_spread_list();
            },
            copySpreadLink: function (link) {
                var dom = document.createElement("input");
                dom.value = link;
                document.body.appendChild(dom);
                dom.select();
                document.execCommand("copy");
                document.body.removeChild(dom);
                this.$message.success('复制成功');
            },
            get_my_spread: function () {
                var vm = this;
                authApi.get_my_spread().then(function (res) {
                    vm.spread.income = res.data.income
                    vm.spread.order_count = res.data.order_count
                    vm.spread.spread_count = res.data.spread_count
                })
            },
            get_spread_cate: function () {
                var vm = this;
                specialApi.get_grade_list().then(function (res) {
                  let spreadCate = [
                      {
                          name: '全部',
                          id: 0
                      }
                  ]
                  res.data.forEach(function (item) {
                      spreadCate.push(item)
                  })
                    vm.spreadCate = spreadCate
                })
            },
            get_spread_list: function () {
                var vm = this;
                authApi.get_spread_production({
                    grade_id: this.activeName,
                    page: this.page,
                    limit: this.limit
                }).then(function (res) {
                    var data = res.data;
                    vm.count = data.count;
                    vm.spreadList = data.data;
                    vm.loaded = true
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    vm.loaded = true
                });
            },
            replace_url: function (url) {
                if (!url) return ''
                let domain = img_domain;
                // 判断url开头是否是http
                if (url.indexOf('http') === 0) {
                    domain = ''
                }
                return domain + url.replace(/\\/g, '/')
            },
        },
        template: html
    };
});