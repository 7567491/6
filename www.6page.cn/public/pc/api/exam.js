define([
    'scripts/http'
], function (request) {
    return {
        /**
         * 课程关联的考试和练习
         * @param {object} params 
         * @returns 
         */
        get_course_exam: function (data) {
            return request({
                url: '/topic/specialTestPaper',
                method: 'post',
                data: data
            });
        },
        get_situation: function (params) {
            return request({
                url: '/topic/situationRecord',
                params: params
            });
        },
        get_questions: function (params) {
            return request({
                url: '/topic/testPaperQuestions',
                params: params
            });
        },
        get_answer: function (params) {
            return request({
                url: '/topic/userAnswer',
                params: params
            });
        },
        get_answer_again: function (params) {
            return request({
                url: '/topic/takeTheTestAgain',
                params: params
            });
        },
        get_answer_continue: function (params) {
            return request({
                url: '/topic/continueAnswer',
                params: params
            });
        },
        submit_questions: function (data) {
            return request({
                url: '/topic/submitQuestions',
                method: 'post',
                data: data
            });
        },
        get_answer_sheet: function (params) {
            return request({
                url: '/topic/answerSheet',
                params: params
            });
        },
        // 提交练习
        submit_test: function (data) {
            return request({
                url: '/topic/submitTestPaper',
                method: 'post',
                data: data
            });
        },
        // 获取练习结果
        get_examination_results: function (params) {
            return request({
                url: '/topic/examinationResults',
                params: params
            });
        },
        // 获取考试是否可以领取证书
        get_inspect: function (params) {
            return request({
                url: '/topic/inspect',
                params: params
            });
        },
        // 领取证书
        get_the_certificate: function (params) {
            return request({
                url: '/topic/getTheCertificate',
                params: params
            });
        },
        // 获取证书信息
        view_ertificate: function (params) {
            return request({
                url: '/topic/viewCertificate',
                params: params
            });
        },
        // 获取考试、测试分类
        get_test_cate: function (params) {
            return request({
                url: '/topic/testPaperCate',
                params: params
            });
        },
        get_test_list: function (data, type) {
            type = type ? type : 2
            return request({
                url: '/topic/practiceList?type=' + type,
                method: 'post',
                data: data
            });
        },

    };
});