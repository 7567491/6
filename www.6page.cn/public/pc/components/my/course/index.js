define([
    'api/auth',
    'text!components/my/course/index.html',
    'css!components/my/course/index.css'
], function(authApi, html) {
    return {
        props: {
            isLogin: {
                type: Boolean,
                default: false
            }
        },
        data: function () {
            return {
                page: 1,
                limit: 15,
                count: 0,
                specialList: [],
                finished: false,
                loaded: false
            };
        },
        watch: {
            isLogin: function (value) {
                if (value) {
                    this.my_special_list();   
                }
            },
            page: function () {
                this.my_special_list();
            }
        },
        methods: {
            // 课程列表
            my_special_list: function () {
                var vm = this;
                authApi.my_special_list({
                    page: this.page,
                    limit: this.limit
                }).then(function (res) {
                    var data = res.data;
                    vm.count = data.count;
                    vm.specialList = data.list;
                    vm.finished = vm.limit > data.list.length;
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