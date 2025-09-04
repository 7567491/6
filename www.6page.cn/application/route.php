<?php


use \think\Route;
//兼容模式 不支持伪静态可开启
//\think\Url::root('index.php?s=');
Route::group('admin',function(){
    Route::rule('/index2','admin/Index/index2','get');
//    Route::controller('index','admin/Index');
//    resource('system_menus','SystemMenus');
//    Route::rule('/menus','SystemMenus','get');
//    Route::resource('menus','admin/SystemMenus',['var'=>['menus'=>'menu_id']]);
//    Route::miss(function(){
//        return '页面不存在!';
//    });
});

//Route::domain('zs.com','web');
//Route::domain('m.zs.com','wap');
// 电脑端路由
Route::rule('index','web/index/index');
Route::rule('index-new','web/index/index_new'); // 新版主页测试
Route::rule('404','web/index/page_404');
Route::rule('error/[:msg]','web/index/page_error');
// 直播列表
Route::rule('live','web/live/special_live');
Route::rule('live-room','web/live/index');
// 课程列表
Route::rule('course','web/special/special_cate');
// 课程详情
Route::rule('view-course','web/special/details');
// 简易课程详情
Route::rule('single-course','web/special/single_details');
// 兑换码兑换
Route::rule('exchange','web/special/exchange');
// 课件资料列表
Route::rule('virtual','web/material/material_list');
// 课件资料详情
Route::rule('view-virtual/[:id]','web/material/details');
// 文章
Route::rule('articles/[:cid]','web/article/news_list');
Route::rule('view-article/:id','web/article/news_detail');

// 教师
Route::rule('teachers','web/special/teacher_list');
Route::rule('view-teacher/:id','web/special/teacher_detail');

// 课节
Route::rule('lesson','web/special/task_info');

// 练习考试
Route::rule('test','web/topic/problem_index');
Route::rule('view-test','web/topic/problem_detail');
Route::rule('test-sheet','web/topic/problem_sheet');
Route::rule('test-result','web/topic/problem_result');
Route::rule('all-test','web/topic/test_category');

Route::rule('exam','web/topic/question_index');
Route::rule('view-exam','web/topic/question_detail');
Route::rule('exam-sheet','web/topic/question_sheet');
Route::rule('exam-result','web/topic/question_result');
Route::rule('all-exam','web/topic/question_category');

Route::rule('view-wrong','web/topic/question_detail_wrong');
Route::rule('view-certificate','web/topic/certificate_detail');

// 支付
Route::rule('pay','web/index/payment');

//Route::alias('','web/special');
Route::rule('my','web/my/index');
Route::rule('login','web/login/index');
Route::rule('about','web/index/about_us');

// 手机端
Route::rule('m','wap/index/index');
Route::rule('m/search','wap/index/search');
Route::rule('m/course','wap/special/special_cate');
Route::rule('m/view-course','wap/special/details');
Route::rule('m/single-course','wap/special/single_details');
Route::rule('m/single-course-txt','wap/special/single_text_detail');
Route::rule('m/shop','wap/store/index');
Route::rule('m/virtual','wap/material/material_list');
Route::rule('m/view-virtual','wap/special/data_details');
Route::rule('m/view-product','wap/store/detail');
Route::rule('m/my','wap/my/index');
Route::rule('m/teachers','wap/special/teacher_list');
Route::rule('m/view-teacher','wap/special/teacher_detail');
Route::rule('m/pink','wap/special/group_list');
// 文章
Route::rule('m/articles','wap/article/news_list');
Route::rule('m/view-article','wap/article/details');
// 课节
Route::rule('m/lesson','wap/special/task_info');
Route::rule('m/lesson-txt','wap/special/task_text_info');

Route::rule('m/live-room','wap/live/index');

// 我的课程
Route::rule('m/my-courses','wap/special/grade_special');
// 学习记录
Route::rule('m/my-record','wap/special/record');
Route::rule('m/my-order','wap/special/order_store_list');
Route::rule('m/my-wrong','wap/topic/question_wrong');
Route::rule('m/my-topic','wap/topic/question_user');
Route::rule('m/my-sign','wap/my/sign_in');
Route::rule('m/my-vip','wap/special/member_recharge');
Route::rule('m/my-spread','wap/spread/spread');
Route::rule('m/pink-order','wap/my/order_list');
Route::rule('m/my-activity','wap/my/sign_list');
Route::rule('m/view-activity','wap/my/sign_my_order');
Route::rule('m/verify-activity','wap/my/verify_activity');

Route::rule('m/my-gift','wap/my/my_gift');
Route::rule('m/my-bill','wap/my/bill_detail');
Route::rule('m/recharge','wap/special/recharge_index');
Route::rule('m/my-certificate','wap/topic/certificate_list');
Route::rule('m/view-certificate','wap/topic/certificate_detail');
Route::rule('m/my-fav','wap/special/grade_list');
Route::rule('m/my-address','wap/my/address');
Route::rule('m/edit-address','wap/my/edit_address');
Route::rule('m/my-virtual','wap/material/my_material');
Route::rule('m/about','wap/my/about_us');
Route::rule('m/my-profile','wap/my/user_info');

Route::rule('m/test','wap/topic/problem_index');
Route::rule('m/view-test','wap/topic/problem_detail');
Route::rule('m/test-sheet','wap/topic/problem_sheet');
Route::rule('m/test-result','wap/topic/problem_result');
Route::rule('m/all-test','wap/topic/test_category');

Route::rule('m/exam','wap/special/question_index');
Route::rule('m/view-exam','wap/topic/question_detail');
Route::rule('m/exam-sheet','wap/topic/question_sheet');
Route::rule('m/exam-result','wap/topic/question_result');
Route::rule('m/all-exam','wap/topic/question_category');

Route::rule('m/view-wrong','wap/topic/question_detail_wrong');
Route::rule('m/view-certificate','wap/topic/certificate_detail');
Route::rule('m/nice','wap/index/unified_list');
Route::rule('m/login','wap/login/index');
Route::rule('m/service','wap/service/service_list');
Route::rule('m/exchange','wap/special/exchange');

// 小程序
//Route::rule('mp','mp/index/index');
Route::rule('mp/search','mp/index/search');
Route::rule('mp/course','mp/special/special_cate');
Route::rule('mp/view-course','mp/special/details');
Route::rule('mp/single-course','mp/special/single_details');
Route::rule('mp/single-course-txt','mp/special/single_text_detail');
Route::rule('mp/shop','mp/store/index');
Route::rule('mp/virtual','mp/material/material_list');
Route::rule('mp/view-virtual','mp/special/data_details');
Route::rule('mp/view-product','mp/store/detail');
Route::rule('mp/teachers','mp/special/teacher_list');
Route::rule('mp/view-teacher','mp/special/teacher_detail');
Route::rule('mp/pink','mp/special/group_list');
// 文章
Route::rule('mp/articles','mp/article/news_list');
Route::rule('mp/view-article','mp/article/details');
// 课节
Route::rule('mp/lesson','mp/special/task_info');
Route::rule('mp/lesson-txt','mp/special/task_text_info');

Route::rule('mp/live-room','mp/live/index');

// 我的课程
Route::rule('mp/my-courses','mp/special/grade_special');
// 学习记录
Route::rule('mp/my-record','mp/special/record');
Route::rule('mp/my-order','mp/special/order_store_list');
Route::rule('mp/my-wrong','mp/topic/question_wrong');
Route::rule('mp/my-topic','mp/topic/question_user');
Route::rule('mp/my-sign','mp/my/sign_in');
Route::rule('mp/my-vip','mp/special/member_recharge');
Route::rule('mp/my-spread','mp/spread/spread');
Route::rule('mp/pink-order','mp/my/order_list');
Route::rule('mp/my-activity','mp/my/sign_list');
Route::rule('mp/view-activity','mp/my/sign_my_order');
Route::rule('mp/verify-activity','mp/my/verify_activity');

Route::rule('mp/my-gift','mp/my/my_gift');
Route::rule('mp/my-bill','mp/my/bill_detail');
Route::rule('mp/recharge','mp/special/recharge_index');
Route::rule('mp/my-certificate','mp/topic/certificate_list');
Route::rule('mp/view-certificate','mp/topic/certificate_detail');
Route::rule('mp/my-fav','mp/special/grade_list');
Route::rule('mp/my-address','mp/my/address');
Route::rule('mp/edit-address','mp/my/edit_address');
Route::rule('mp/my-virtual','mp/material/my_material');
Route::rule('mp/about','mp/my/about_us');
Route::rule('mp/my-profile','mp/my/user_info');

Route::rule('mp/test','mp/topic/problem_index');
Route::rule('mp/view-test','mp/topic/problem_detail');
Route::rule('mp/test-sheet','mp/topic/problem_sheet');
Route::rule('mp/test-result','mp/topic/problem_result');
Route::rule('mp/all-test','mp/topic/test_category');

Route::rule('mp/exam','mp/special/question_index');
Route::rule('mp/view-exam','mp/topic/question_detail');
Route::rule('mp/exam-sheet','mp/topic/question_sheet');
Route::rule('mp/exam-result','mp/topic/question_result');
Route::rule('mp/all-exam','mp/topic/question_category');

Route::rule('mp/view-wrong','mp/topic/question_detail_wrong');
Route::rule('mp/view-certificate','mp/topic/certificate_detail');
Route::rule('mp/nice','mp/index/unified_list');
//Route::rule('mp/login','mp/login/index');
Route::rule('mp/service-list','mp/service/service_list');



