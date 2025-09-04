define([
    'text!components/home/recommend2/index.html',
    'css!components/home/recommend2/index.css'
], function (html) {
    return {
        props: {
            recommend: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        data: function () {
            return {};
        },
        methods: {},
        template: html
    };
});