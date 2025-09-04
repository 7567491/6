define([
    'mixins/router',
    'text!components/home/recommend4/index.html',
    'css!components/home/recommend4/index.css'
], function(routerMixin, html) {
    return {
        mixins: [routerMixin],
        props: {
            recommend: {
                type: Object,
                default: function () {
                    return {};
                }
            },
            articleList: {
                type: Array,
                default: function () {
                    return [];
                }
            }
        },
        data: function () {
            return {};
        },
        methods: {

        },
        template: html
    };
});