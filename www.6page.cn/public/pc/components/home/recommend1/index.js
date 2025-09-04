define([
    'mixins/router',
    'text!components/home/recommend1/index.html',
    'css!components/home/recommend1/index.css'
], function (routerMixin, html) {
    return {
        mixins: [routerMixin],
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