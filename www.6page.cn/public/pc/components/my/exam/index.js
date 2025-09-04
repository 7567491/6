define([
    'api/auth',
    'scripts/util',
    'text!components/my/exam/index.html',
    'css!components/my/exam/index.css'
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
                examList: [],
                finished: false,
                loaded: false
            };
        },
        watch: {
            page: function () {
                this.my_exam_list();
            },
            currentMenu: function (value) {
                if (value === 'exam') {
                    this.my_exam_list();
                }
            }
        },
        created: function () {
            var page = util.getParmas('page');
            if (page === 'exam') {
                this.my_exam_list();
            }
        },
        methods: {
            // 课程列表
            my_exam_list: function () {
                var vm = this;
                authApi.my_exam({
                    page: this.page,
                    limit: this.limit,
                    type: 2
                }).then(function (res) {
                    var data = res.data;
                    vm.count = data.count;
                    vm.examList = data.list;
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
            goQuestion: function (data) {
                location.href = router.exam + '?id=' + data.id
            }
        },
        template: html
    };
});