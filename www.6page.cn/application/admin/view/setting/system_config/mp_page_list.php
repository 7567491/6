<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>系统页面索引</title>

    <link href="{__FRAME_PATH}/css/bootstrap.min.css?v=3.4.0" rel="stylesheet">
    <link href="{__ADMIN_PATH}/css/layui-admin.css" rel="stylesheet">
    <link href="{__FRAME_PATH}/css/style.min.css?v=3.0.0" rel="stylesheet">
    <link href="{__FRAME_PATH}css/font-awesome.min.css?v=4.3.0" rel="stylesheet">
    <script src="{__PLUG_PATH}vue/dist/vue.min.js"></script>
    <link href="{__PLUG_PATH}iview/dist/styles/iview.css" rel="stylesheet">
    <script src="{__PLUG_PATH}iview/dist/iview.min.js"></script>
    <script src="{__PLUG_PATH}jquery/jquery.min.js"></script>
    <script src="{__PLUG_PATH}form-create/province_city.js"></script>
    <script src="{__PLUG_PATH}form-create/form-create.min.js"></script>
    <link href="{__PLUG_PATH}layui/css/layui.css" rel="stylesheet">
    <script src="{__PLUG_PATH}layui/layui.all.js"></script>
    <style>
        /*弹框样式修改*/
        .ivu-modal{top: 20px;}
        .ivu-modal .ivu-modal-body{padding: 10px;}
        .ivu-modal .ivu-modal-body .ivu-modal-confirm-head{padding:0 0 10px 0;}
        .ivu-modal .ivu-modal-body .ivu-modal-confirm-footer{display: none;padding-bottom: 10px;}
        .ivu-date-picker {display: inline-block;line-height: normal;width: 280px;}
        .ivu-modal-footer{display: none;}
        .ivu-poptip-popper{text-align: left;}
        .ivu-icon{padding-left: 5px;}
        .ivu-btn-long{width: 10%;min-width:100px;margin-left: 18%;}
        .layui-fluid{padding:15px;}
        .layui-tab-title .layui-this:after{border-bottom-color: #fff!important;}
        .td-link{
            cursor: pointer;
        }
        .td-link:hover{
            text-decoration: underline;
        }
    </style>
</head>
<body class="gray-bg">
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            网页及小程序页面链接索引<span style="color: #f00">（点击链接即可复制）</span>
            <table cellspacing="0" cellpadding="0" border="0" class="layui-table">
                <thead>
                <tr>
                    <th>名称</th>
                    <th>电脑端链接</th>
                    <th>移动端链接</th>
                    <th>小程序链接</th>
                    <th>参数说明</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>首页</td>
                    <td class="td-link" title="点击复制链接">/</td>
                    <td class="td-link" title="点击复制链接">/m.html</td>
                    <td class="td-link" title="点击复制链接">/pages/index/index</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>课程列表</td>
                    <td class="td-link" title="点击复制链接">/course.html</td>
                    <td class="td-link" title="点击复制链接">/m/course.html</td>
                    <td class="td-link" title="点击复制链接">/pages/course/list</td>
                    <td>无参数或一级分类id，cate_id，如：/m/course.html?cate_id=1</td>
                </tr>
                <tr>
                    <td>常规课/直播课/套餐课详情</td>
                    <td class="td-link" title="点击复制链接">/view-course.html?id=课程id</td>
                    <td class="td-link" title="点击复制链接">/m/view-course.html?id=课程id</td>
                    <td class="td-link" title="点击复制链接">/pages/course/detail?id=课程id</td>
                    <td>课程id</td>
                </tr>
                <tr>
                    <td>精简课详情</td>
                    <td class="td-link" title="点击复制链接">/single-course.html?id=课程id</td>
                    <td class="td-link" title="点击复制链接">/m/single-course.html?id=课程id</td>
                    <td class="td-link" title="点击复制链接">/pages/course/single-detail?id=课程id</td>
                    <td>课程id</td>
                </tr>
                <tr>
                    <td>实物商城商品列表</td>
                    <td class="td-link" title="点击复制链接">暂不支持</td>
                    <td class="td-link" title="点击复制链接">/m/shop.html</td>
                    <td class="td-link" title="点击复制链接">/pages/store/list</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>实物商城商品详情</td>
                    <td class="td-link" title="点击复制链接">暂不支持</td>
                    <td class="td-link" title="点击复制链接">/m/view-product.html?id=商品id</td>
                    <td class="td-link" title="点击复制链接">/pages/store/detail?id=商品id</td>
                    <td>商品id</td>
                </tr>
                <tr>
                    <td>新闻动态列表</td>
                    <td class="td-link" title="点击复制链接">/articles.html</td>
                    <td class="td-link" title="点击复制链接">/m/articles.html</td>
                    <td class="td-link" title="点击复制链接">/pages/article/list</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>新闻动态详情</td>
                    <td class="td-link" title="点击复制链接">/view-article/文章id.html</td>
                    <td class="td-link" title="点击复制链接">/m/view-article.html?id=文章id</td>
                    <td class="td-link" title="点击复制链接">/pages/article/detail?id=文章id</td>
                    <td>文章id</td>
                </tr>
                <tr>
                    <td>虚拟资料列表</td>
                    <td class="td-link" title="点击复制链接">/virtual.html</td>
                    <td class="td-link" title="点击复制链接">/m/virtual.html</td>
                    <td class="td-link" title="点击复制链接">/pages/download/list</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>虚拟资料详情</td>
                    <td class="td-link" title="点击复制链接">/view-virtual/资料id.html</td>
                    <td class="td-link" title="点击复制链接">/m/view-virtual.html?id=资料id</td>
                    <td class="td-link" title="点击复制链接">/pages/download/detail?id=资料id</td>
                    <td>资料id</td>
                </tr>
                <tr>
                    <td>考试列表</td>
                    <td class="td-link" title="点击复制链接">/all-exam.html</td>
                    <td class="td-link" title="点击复制链接">/m/all-exam.html?type=2</td>
                    <td class="td-link" title="点击复制链接">/pages/exam/list</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>考试详情</td>
                    <td class="td-link" title="点击复制链接">/exam.html?id=考试id</td>
                    <td class="td-link" title="点击复制链接">/m/exam.html?id=考试id</td>
                    <td class="td-link" title="点击复制链接">/pages/exam/detail?id=考试id</td>
                    <td>考试id</td>
                </tr>
                <tr>
                    <td>练习列表</td>
                    <td class="td-link" title="点击复制链接">/all-test.html</td>
                    <td class="td-link" title="点击复制链接">/m/all-exam.html?type=1</td>
                    <td class="td-link" title="点击复制链接">/pages/test/list</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>考试详情</td>
                    <td class="td-link" title="点击复制链接">/test.html?id=练习id</td>
                    <td class="td-link" title="点击复制链接">/m/test.html?id=练习id</td>
                    <td class="td-link" title="点击复制链接">/pages/test/detail?id=练习id</td>
                    <td>练习id</td>
                </tr>
                <tr>
                    <td>个人中心</td>
                    <td class="td-link" title="点击复制链接">/my.html</td>
                    <td class="td-link" title="点击复制链接">/m/my.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/my</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>个人资料</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=account</td>
                    <td class="td-link" title="点击复制链接">/m/my-profile.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/profile</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>我的课程</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=course</td>
                    <td class="td-link" title="点击复制链接">/m/my-courses.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/courses</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>学习记录</td>
                    <td class="td-link" title="点击复制链接">暂无</td>
                    <td class="td-link" title="点击复制链接">/m/my-record.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/record</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>商品订单</td>
                    <td class="td-link" title="点击复制链接">暂无</td>
                    <td class="td-link" title="点击复制链接">/m/my-order.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/order</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>我的错题</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=wrong</td>
                    <td class="td-link" title="点击复制链接">/m/my-wrong.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/wrong</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>签到打卡</td>
                    <td class="td-link" title="点击复制链接">暂不支持</td>
                    <td class="td-link" title="点击复制链接">/m/my-sign.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/sign</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>VIP充值</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=member</td>
                    <td class="td-link" title="点击复制链接">/m/my-vip.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/vip</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>推广赚钱</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=spread</td>
                    <td class="td-link" title="点击复制链接">/m/my-spread.html</td>
                    <td class="td-link" title="点击复制链接">暂不支持</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>拼团课程</td>
                    <td class="td-link" title="点击复制链接">暂不支持</td>
                    <td class="td-link" title="点击复制链接">/m/pink-order.html</td>
                    <td class="td-link" title="点击复制链接">暂不支持</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>我的礼物</td>
                    <td class="td-link" title="点击复制链接">暂不支持</td>
                    <td class="td-link" title="点击复制链接">/m/my-gift.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/gift</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>我的余额</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=balance</td>
                    <td class="td-link" title="点击复制链接">/m/my-bill.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/bill</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>充值金币</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=coin</td>
                    <td class="td-link" title="点击复制链接">/m/recharge.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/gold</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>我的证书</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=certificate</td>
                    <td class="td-link" title="点击复制链接">/m/my-certificate.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/cert</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>我的收藏</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=favor</td>
                    <td class="td-link" title="点击复制链接">/m/my-fav.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/fav</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>我的下载</td>
                    <td class="td-link" title="点击复制链接">/my.html?page=material</td>
                    <td class="td-link" title="点击复制链接">/m/my-virtual.html</td>
                    <td class="td-link" title="点击复制链接">/pages/my/download</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>关于我们</td>
                    <td class="td-link" title="点击复制链接">/about.html</td>
                    <td class="td-link" title="点击复制链接">/m/about.html</td>
                    <td class="td-link" title="点击复制链接">/pages/about/about</td>
                    <td>无参数</td>
                </tr>
                <tr>
                    <td>客服咨询</td>
                    <td class="td-link" title="点击复制链接">暂无</td>
                    <td class="td-link" title="点击复制链接">/m/service.html</td>
                    <td class="td-link" title="点击复制链接">/pages/service/service</td>
                    <td>无参数</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>

<script>
    $(document).ready(function() {
        $('.td-link').click(function() {
            var textToCopy = $(this).text();
            var tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(textToCopy).select();
            document.execCommand('copy');
            tempInput.remove();
            layer.msg('复制成功', {time: 1000});
        });
    });
</script>
</html>