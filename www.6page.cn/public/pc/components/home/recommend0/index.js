define([
    'text!components/home/recommend0/index.html',
    'css!components/home/recommend0/index.css'
], function(html) {
    return {
        props: {
            recommend: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        template: html
    };
});