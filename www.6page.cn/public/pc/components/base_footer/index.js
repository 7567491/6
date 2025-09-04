define([
    'text!components/base_footer/index.html',
    'css!components/base_footer/index.css'
], function (html) {
    return {
        props: {
            publicData: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        data: function () {
            return {
                year: new Date().getFullYear(),
                code_url: ''
            };
        },
        mounted: function () {
            this.$nextTick(function () {
                if (code_url) {
                    this.code_url = code_url;
                }
            });               
        },
        methods: {
            goPage: function () {
                window.canCustomerServer.getCustomeServer();
            }
        },
        template: html
    };
});