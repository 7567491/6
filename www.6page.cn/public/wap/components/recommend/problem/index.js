define([
    'text!components/recommend/problem/index.html',
    'css!components/recommend/problem/index.css'
], function(html) {
    return {
        props: {
            obj: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        template: html
    };
});