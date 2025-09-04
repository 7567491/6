define([
    'text!components/rebate-guide/index.html',
    'css!components/rebate-guide/index.css'
], function(html) {
    return {
        props: ['rebateMoney'],
        methods: {
            rebateAction: function (value) {
                this.$emit('rebate-action', value);
            }
        },
        template: html
    };
});