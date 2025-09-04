define([
    'text!components/exchange-guide/index.html',
    'css!components/exchange-guide/index.css'
], function(html) {
    return {
        props: {
            href: {
                type: String,
                default: ''
            }
        },
        template: html
    };
});