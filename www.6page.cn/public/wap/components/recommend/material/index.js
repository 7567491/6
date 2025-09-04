define([
    'text!components/recommend/material/index.html',
    'css!components/recommend/material/index.css'
], function(html) {
    'use strict';
    return {
        props: {
            materialList: {
                type: Array,
                default: function () {
                    return [];
                }
            },
            typeSetting: {
                type: Number,
                default: 2
            },
            allLink: {
                type: String,
                default: 'javascript:'
            },
            cellLink: {
                type: String,
                default: 'javascript:'
            },
            materialTitle: {
                type: String,
                default: '资料下载'
            }
        },
        template: html
    };
});