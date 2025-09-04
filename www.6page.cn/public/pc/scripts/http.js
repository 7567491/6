define([
    'axios',
    'ELEMENT'
], function (axios, ELEMENT) {
    var instance = axios.create({
        baseURL: window.location.origin + '/web',
        timeout: 60000,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        withCredentials: true
    });
    var loadingInstance = null;
    instance.interceptors.request.use(function (config) {
        loadingInstance = ELEMENT.Loading.service({
            background: 'transparent'
        });
        return config;
    }, function (error) {
        return Promise.reject(error);
    });
    instance.interceptors.response.use(function (response) {
        loadingInstance.close();
        if (response.data.code === 200) {
            return response.data;
        }
        return Promise.reject(response.data || {msg: '未知错误'});
    }, function (error) {
        ELEMENT.Message({
            message: '网络错误' + (error.Message !== undefined ? error.Message : ''),
            type: 'error',
            duration: 3000
        });
        loadingInstance.close();
        return Promise.reject(error);
    });
    return instance;
});