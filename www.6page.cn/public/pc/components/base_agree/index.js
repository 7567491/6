define([
    'text!components/base_agree/index.html',
    'css!components/base_agree/index.css'
], function(html) {
    return {
        props: {
            agreeVisible: {
                type: Boolean,
                default: false
            },
            agreeContent: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        template: html
    };
});