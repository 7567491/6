define([
    'mixins/router',
    'text!components/home/recommend3/index.html',
    'css!components/home/recommend3/index.css'
], function (router, html) {
    return {
        mixins: [router],
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