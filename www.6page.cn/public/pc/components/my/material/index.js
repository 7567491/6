define([
    'api/auth',
    'scripts/util',
    'text!components/my/material/index.html',
    'css!components/my/material/index.css'
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
                total: 0,
                materialList: [],
                finished: false,
                loaded: false
            };
        },
        watch: {
            page: function () {
                this.my_material_list();
            },
            currentMenu: function (value) {
                if (value === 'material') {
                    this.my_material_list();
                }
            }
        },
        created: function () {
            var page = util.getParmas('page');
            if (page === 'material') {
                this.my_material_list();
            }
        },
        methods: {
            // 资料列表
            my_material_list: function () {
                var vm = this;
                authApi.my_material_list({
                    page: this.page,
                    limit: this.limit
                }).then(function (res) {
                    var data = res.data;
                    vm.total = data.count;
                    vm.materialList = data.data;
                    vm.finished = vm.limit > data.data.length;
                    vm.loaded = true
                });
            },
            handleSelectDetils(item) {
                var base_url = router.view_virtual;
                var base_url_arr = base_url.split('');
                base_url_arr.splice(base_url_arr.length - 5, 0, '/' + item.id);
                return  base_url_arr.join('')
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