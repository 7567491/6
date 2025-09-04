define(['vue', 'helper', 'store'], function (Vue, $h, api) {
    'use strict';
    Vue.component('quick-menu', {
        data: function () {
            return {
                top: '50%',
                open: false,
                menuList: []
            };
        },
        created: function () {
            this.onReady();
        },
        methods: {
            onReady: function() {
            },
            onMove: function(event) {
            }
        },
        template: ''
    });
});