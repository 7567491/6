define([
    'api/auth',
    'scripts/util',
    'text!components/my/certificate/index.html',
    'css!components/my/certificate/index.css'
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
        filters: {
            formatTime: function (time) {
                var hour = Math.floor(time / 3600000);
                var minute = Math.floor((time - hour * 3600000) / 60000);
                var second = Math.floor((time - hour * 3600000 - minute * 60000) / 1000);

                if (hour < 10) {
                    hour = '0' + hour;
                }
                if (minute < 10) {
                    minute = '0' + minute;
                }
                if (second < 10) {
                    second = '0' + second;
                }

                return hour + ':' + minute + ':' + second;
            }
        },
        data: function () {
            return {
                page: 1,
                limit: 15,
                count: 0,
                loading: false,
                finished: false,
                certificateList: [],
                loaded: false
            };
        },
        watch: {
            page: function () {
                this.getCertificateList();
            },
            currentMenu: function (value) {
                if (value === 'certificate') {
                    this.getCertificateList();
                }
            }
        },
        created: function () {
            var page = util.getParmas('page');
            if (page === 'certificate') {
                this.getCertificateList();
            }
        },
        methods: {
            getCertificateList: function () {
                if (this.loading || this.finished) {
                    return;
                }
                this.loading = true;
                var vm = this
                authApi.get_my_certificate({
                    page: this.page,
                    limit: this.limit
                }).then(function (res) {
                    vm.count = res.data.count
                    var certificateList = res.data.list;
                    vm.certificateList = certificateList;
                    vm.loading = false;
                    vm.loaded = true;
                })
            },
            changeTab: function (e) {

            }
        },
        template: html
    };
});