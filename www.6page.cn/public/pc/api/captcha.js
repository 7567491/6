define([
    'scripts/http'
], function(request) {
    return {
        reqGet: function (data) {
            return request({
                url: '/captcha/get',
                method: 'post',
                data
            })
        },
        reqCheck: function (data) {
            return request({
                url: '/captcha/check',
                method: 'post',
                data
            })
        }
    };
});