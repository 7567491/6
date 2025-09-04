define([
    'scripts/http'
], function(request) {
    return {
        /**
         * 短信登录、注册
         * @param {*} data 
         * @returns 
         */
        phoneCheck: function (data) {
            return request({
                url: '/login/phone_check',
                method: 'post',
                data: data
            });
        },
        /**
         * 账号密码登录
         * @param {*} data 
         * @returns 
         */
        heck: function (data) {
            return request({
                url: '/login/check',
                method: 'post',
                data: data
            });
        },
        /**
         * 账号密码注册、找回密码
         * @param {*} data 
         * @returns 
         */
        register: function (data) {
            return request({
                url: '/login/register',
                method: 'post',
                data: data
            });
        },
        /**
         * 退出登录
         * @returns 
         */
        logout: function () {
            return request({
                url: '/login/logout'
            });
        },
        // 验证码
        code: function (data) {
            return request({
                url: '/auth_api/code',
                method: 'post',
                data: data
            });
        }
    };
});