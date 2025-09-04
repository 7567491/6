define([
    'mixins/router',
    'api/home',
    'api/auth',
    'api/public',
    'api/special',
    'api/login'
], function (routerMixin, homeApi, authApi, publicApi, specialApi, loginApi) {
    return {
        mixins: [routerMixin],
        data: function () {
            return {
                userInfo: {
                    avatar: ''
                },
                publicData: {},
                agreeContent: {},
                loginVisible: false,
                agreeVisible: false,
                isLogin: false,
                searchSelected: 1,
                searchValue: '',
                searchResultShow: false,
                backtopShow: false
            };
        },
        watch: {},
        created: function () {
            var vm = this;
            // this.public_data();
            this.get_host_search();
            this.get_home_navigation();
            this.agree_content();
            // this.get_grade_cate();

            if (window.location.href.includes('/course') || window.location.href.includes('/live')) {
                this.searchSelected = 1
            }
            if (window.location.href.includes('/virtual')) {
                this.searchSelected = 2
            }
            if (window.location.href.includes('/all-exam')) {
                this.searchSelected = 3
            }
            if (window.location.href.includes('/all-test')) {
                this.searchSelected = 4
            }
            // 登陆状态
            homeApi.user_login().then(function () {
                vm.isLogin = true;
                vm.user_info();
            }).catch(function (err) {
                if (window.location.href.includes('/my')) {
                    vm.$message.error(err.msg);
                    vm.loginVisible = true;
                }
            });
        },
        mounted: function () {
            var vm = this
            window.addEventListener("scroll", function () {
                if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                    vm.backtopShow = true
                } else {
                    vm.backtopShow = false
                }
            });
        },
        methods: {
            backtop: function () {
                this.smoothScrollToTop()
            },
            openService: function () {
                wyChatInstance.getFxChat()
            },
            smoothScrollToTop: function() {
                var currentScroll = document.documentElement.scrollTop || document.body.scrollTop;
                if (currentScroll > 0) {
                    window.requestAnimationFrame(this.smoothScrollToTop);
                    window.scrollTo(0, currentScroll - currentScroll / 8);
                }
            },
            // 公共数据
            public_data: function () {
                var vm = this;
                publicApi.public_data().then(function (res) {
                    for (var key in res.data) {
                        if (Object.hasOwnProperty.call(res.data, key)) {
                            vm.$set(vm.publicData, key, res.data[key]);
                        }
                    }
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
            // 热搜
            get_host_search: function () {
                var vm = this;
                publicApi.get_host_search().then(function (res) {
                    vm.$set(vm.publicData, 'host_search', res.data);
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            // 用户协议
            agree_content: function () {
                var vm = this;
                publicApi.agree().then(function (res) {
                    vm.agreeContent = res.data;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            // 大分类
            get_grade_cate: function () {
                var vm = this;
                specialApi.get_grade_cate().then(function (res) {
                    vm.$set(vm.publicData, 'grade_cate', res.data);
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            loginOpen: function () {
                this.loginVisible = true;
            },
            // 关闭登录弹窗
            loginClose: function (state) {
                if (state) {
                    this.isLogin = true;
                    this.user_info();
                }
                this.loginVisible = false;
                location.reload();
            },
            agreeOpen: function () {
                this.agreeVisible = true;
            },
            agreeClose: function () {
                this.agreeVisible = false;
            },
            // 用户信息
            user_info: function () {
                var vm = this;
                authApi.user_info().then(function (res) {
                    vm.userInfo = res.data;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            // 顶部导航
            get_home_navigation: function () {
                var vm = this;
                publicApi.get_home_navigation().then(function (res) {
                    vm.$set(vm.publicData, 'navList', res.data);
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
            onSearch: function (hot) {
                if (!hot && !this.searchValue) {
                    return;
                }
                if (hot) {
                    this.searchValue = hot;
                }
                var href = ''
                if (this.searchSelected === 1) {
                    href = this.router.course
                }
                if (this.searchSelected === 2) {
                    href = this.router.virtual
                }
                if (this.searchSelected === 3) {
                    href = this.router.all_exam
                }
                if (this.searchSelected === 4) {
                    href = this.router.all_test
                }
                window.location.href = href + '?search=' + this.searchValue;
            },
            hideSearchResult: function () {
                var vm = this;
                setTimeout(function() {
                    vm.searchResultShow = false
                }, 200);
            },
            highLightNav: function (url) {
                const currentBrowserURL = window.location.href;
                const currentNavURLObj = new URL(url);
                const currentBrowserURLObj = new URL(currentBrowserURL);
                return currentNavURLObj.origin === currentBrowserURLObj.origin && currentNavURLObj.pathname === currentBrowserURLObj.pathname
            },
            // 退出登录
            logout: function () {
                var vm = this;
                loginApi.logout().then(function () {
                    sessionStorage.removeItem('userInfo');
                    vm.userInfo = {};
                    window.location.href = vm.router.home;
                }).catch(function (err) {
                    vm.$message.error(err.msg);
                });
            },
        }
    };
});