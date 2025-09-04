define([
    'vue',
    'text!components/question_guide/index.html',
    'css!components/question_guide/index.css'
], function(Vue, html) {
    'use strict';
    Vue.component('question-guide', {
        template: html,
        methods: {
            onRecord: function () {
                this.$emit('record-guide');
            }
        }
    });
});