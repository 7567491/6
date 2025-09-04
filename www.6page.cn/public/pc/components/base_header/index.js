define([
    'mixins/router',
    'scripts/util',
    'text!components/base_header/index.html',
    'css!components/base_header/index.css'
], function (routerMixin, util, html) {
    return {
        mixins: [routerMixin],
        props: {
            publicData: {
                type: Object,
                default: function () {
                    return {};
                }
            },
            userInfo: {
                type: Object,
                default: function () {
                    return {
                        avatar: ''
                    };
                }
            }
        },
        data: function () {
            return {
                selected: 1,
                options: [
                    {
                        label: '课程',
                        value: 1
                    },
                    {
                        label: '资料',
                        value: 2
                    }
                ],
                searchValue: '',
                activeIndex: '1',
                active: 1,
                currentURL: window.location.pathname,
                categoryVisible: false,
                menuOn: -1,
                isUserPage: false,
                isHomePage: false,
                code_url: code_url
            };
        },
        created: function () {
            if (location.pathname === "/") {
                this.isHomePage = true;
            } else if (this.currentURL.includes('/virtual') || this.currentURL.includes('/view-virtual')) {
                this.selected = 2;
                this.setSearchValue();
            } else if (this.currentURL.includes('/course')) {
                this.setSearchValue();
            } else if (this.currentURL.includes('/my')) {
                this.isUserPage = true;
            }
            if (code_url) {
                this.code_url = code_url;
            }
        },
        methods: {
            goPage: function (page) {
                switch (page) {
                    case 'home':
                        window.location.href = this.router.home;
                        break;
                    case 'member':
                    case 'course':
                        if (!this.userInfo.account) {
                            return this.$emit('login-open');
                        }
                        window.location.href = this.router.user + '?tab=' + page;
                        break;
                }
            },
            onSearch: function (hot) {
                if (!hot && !this.searchValue) {
                    return;
                }
                if (hot) {
                    this.searchValue = hot;
                }
                if (this.currentURL.includes('/web/special/special_cate')) {
                    if (this.selected === 1) {
                        this.$emit('submit-search', this.searchValue);
                    } else {
                        window.location.href = this.router.material_list + '?search=' + this.searchValue;
                    }
                } else if (this.currentURL.includes('/web/material/material_list')) {
                    if (this.selected === 2) {
                        this.$emit('submit-search', this.searchValue);
                    } else {
                        window.location.href = this.router.special_cate + '?search=' + this.searchValue;
                    }
                } else {
                    window.location.href = (this.selected === 1 ? this.router.special_cate : this.router.material_list) + '?search=' + this.searchValue;
                }
            },
            categoryMouseenter: function () {
                this.categoryMouse = true;
                this.categoryVisible = true;
            },
            categoryMouseleave: function () {
                this.categoryMouse = false;
                if (!(this.contentMouse || this.menuMouse)) {
                    this.menuOn = -1;
                    this.categoryVisible = false;
                }
            },
            menuMouseenter: function () {
                this.menuMouse = true;
            },
            menuMouseleave: function () {
                this.menuMouse = false;
                if (!(this.contentMouse || this.categoryMouse)) {
                    this.menuOn = -1;
                    this.categoryVisible = false;
                }
            },
            contentMouseenter: function () {
                this.contentMouse = true;
            },
            contentMouseleave: function () {
                this.contentMouse = false;
                if (!(this.menuMouse || this.categoryMouse)) {
                    this.menuOn = -1;
                    this.categoryVisible = false;
                }
            },
            setSearchValue: function () {
                var search = util.getParmas('search');
                if (search) {
                    this.searchValue = search;
                }
            },
            // 收藏本站
            addFavorite: function () {
                try {
                    window.external.addFavorite(window.location.origin + this.router.home, this.publicData.site_name);
                } catch (error) {
                    try {
                        window.sidebar.addPanel(this.publicData.site_name, window.location.origin + this.router.home, '');
                    } catch (error) {
                        this.$message('抱歉，您所使用的浏览器无法完成此操作，请使用Ctrl+D进行添加！');
                    }
                }
            }
        },
        template: html
    };
});