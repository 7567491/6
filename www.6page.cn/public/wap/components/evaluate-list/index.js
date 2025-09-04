define([
    'moment',
    'text!components/evaluate-list/index.html',
    'css!components/evaluate-list/index.css'
], function(moment, html) {
    'use strict';
    return {
        props: {
            evaluateList: {
                type: Array,
                default: function () {
                    return [];
                }
            }
        },
        filters: {
            convertName: function (value) {
                return value.replace(/^(.).+(.)$/g, '$1**$2');
            },
            convertTime: function (value) {
                return moment(value).fromNow();
            }
        },
        methods: {},
        template: html
    };
});