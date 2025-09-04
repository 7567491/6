define([
    'api/auth',
    'api/special',
    'api/material',
    'scripts/util',
    'text!components/my/favor/index.html',
    'css!components/my/favor/index.css'
], function(authApi, specialApi, materialApi, util, html) {
    return {
        props: {
            tab: {
                type: String,
                default: 'favor'
            },
            isLogin: {
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
                page1: 1,
                page2: 1,
                limit: 15,
                active: '0',
                list1: [],
                list2: [],
                finished1: false,
                finished2: false,
                count1: 0,
                count2: 0,
                loaded: false
            };
        },
        watch: {
            page1: function () {
                this.get_grade_list1();
            },
            page2: function () {
                this.get_grade_list2();
            },
            currentMenu: function (value) {
                if (value === 'favor') {
                    this.get_grade_list1();
                    this.get_grade_list2();
                }
            }
        },
        created: function () {
            var page = util.getParmas('page');
            if (page === 'favor') {
                this.get_grade_list1();
                this.get_grade_list2();
            }
        },
        methods: {
            // 课程
            get_grade_list1: function () {
                var vm = this;
                authApi.get_grade_list({
                    page: this.page1,
                    limit: this.limit,
                    active: 0
                }).then(function (res) {
                    var data = res.data;
                    vm.count1 = data.count;
                    vm.list1 = data.list;
                    vm.loaded = true
                    // vm.finished1 = vm.limit > data.list.length;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                    vm.loaded = true
                });
            },
            // 资料
            get_grade_list2: function () {
                var vm = this;
                authApi.get_grade_list({
                    page: this.page2,
                    limit: this.limit,
                    active: 1
                }).then(function (res) {
                    var data = res.data;
                    vm.count2 = data.count;
                    vm.list2 = data.list;
                    // vm.finished2 = vm.limit > data.list.length;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            // 取消课程收藏
            specialCollect: function (id) {
                var vm = this;
                specialApi.collect({
                    id: id
                }).then(function () {
                    vm.$message.success('取消收藏成功');
                    if (!(vm.list1.length - 1)) {
                        if (vm.page1 > 1) {
                            vm.page1--;
                        }
                    }
                    vm.get_grade_list1();
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            // 取消资料收藏
            materialCollect: function (id) {
                var vm = this;
                materialApi.collect({
                    id: id
                }).then(function () {
                    vm.$message.success('取消收藏成功');
                    if (!(vm.list2.length - 1)) {
                        if (vm.page2 > 1) {
                            vm.page2--;
                        }
                    }
                    vm.get_grade_list2();
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            tabClick: function (params) {
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
            handleSelectDetils(item) {
                var base_url = router.view_virtual;
                var base_url_arr = base_url.split('');
                base_url_arr.splice(base_url_arr.length - 5, 0, '/' + item.id);
                return  base_url_arr.join('')
            },
        },
        template: html
    };
});