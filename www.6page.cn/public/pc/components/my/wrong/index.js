define([
    'api/auth',
    'scripts/util',
    'text!components/my/wrong/index.html',
    'css!components/my/wrong/index.css'
], function(authApi, util, html) {
    return {
        props: {
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
                page: 1,
                limit: 15,
                count: 0,
                wrongList: [],
                finished: false,
                activeTab: 'all',
                loaded: false
            };
        },
        watch: {
            page: function () {
                this.getWrongList();
            },
            activeTab: function () {
                this.getWrongList();
            },
            currentMenu: function (value) {
                if (value === 'wrong') {
                    this.getWrongList();
                }
            }
        },
        created: function () {
            var page = util.getParmas('page');
            if (page === 'wrong') {
                this.getWrongList();
            }
        },
        methods: {
            // 课程列表
            getWrongList: function () {
                var is_master = ''
                if (this.activeTab == 'all') {
                    is_master = ''
                } else if (this.activeTab == 'mastered') {
                    is_master = 1
                } else if (this.activeTab == 'notmastered') {
                    is_master = 0
                }
                var vm = this;
                authApi.my_wrong({
                    page: this.page,
                    limit: this.limit,
                    is_master: is_master
                }).then(function (res) {
                    var data = res.data;
                    for (var i = data.list.length; i--;) {
                        switch (data.list[i].question_type) {
                            case 1:
                                data.list[i].question_type_text = '单选题';
                                break;
                            case 2:
                                data.list[i].question_type_text = '多选题';
                                break;
                            case 3:
                                data.list[i].question_type_text = '判断题';
                                break;
                        }
                    }
                    vm.count = data.count;
                    vm.wrongList = data.list;
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
            changeTab: function (e) {
                
            }
        },
        template: html
    };
});