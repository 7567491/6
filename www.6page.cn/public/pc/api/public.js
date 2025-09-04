define([
    'scripts/http'
], function(request) {
    return {
        /**
         * 页面公共数据
         * @returns 
         */
        public_data: function () {
            return request({
                url: '/public_api/public_data'
            });
        },
        /**
         * 用户协议
         * @returns 
         */
        agree: function () {
            return request({
                url: '/public_api/agree'
            });
        },
        /**
         * 热搜词
         * @returns 
         */
        get_host_search: function () {
            return request({
                url: '/public_api/get_host_search'
            });
        },
        /**
         * 顶部导航
         * @returns 
         */
        get_home_navigation: function () {
            return request({
                url: '/public_api/get_home_navigation'
            });
        }
    };
});