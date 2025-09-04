<!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<title><?php echo $Title; ?> - <?php echo $Powered; ?></title>
<link rel="stylesheet" href="./css/install.css?v=9.0" />
</head>
<body>
<div class="install-outer">
    <div class="install-inner">
        <div class="wrap">
            <?php require './templates/header.php';?>
            <div class="section">
                <div class="main cc">
                    <pre class="pact" readonly="readonly">凡星知识付费系统使用协议

版权所有 (c)2013-2024，凡星网络科技 保留所有权利。
感谢您选择无忧速建网出品的凡星知识付费系统，系统基于 PHP + MySQL 的技术，采用ThinkPHP5.0框架开发。
为了使你正确并合法的使用本软件，请你在使用前务必阅读清楚下面的协议条款：
本授权协议适用且仅适用于凡星知识付费系统任何版本，无忧速建官方对本授权协议的最终解释权和修改权。

一、协议许可的权利
1、您可以在完全遵守本最终用户授权协议的基础上，将本软件应用于商业用途。
2、您可以在协议规定的约束和限制范围内修改源代码或界面风格以适应您的网站要求。
3、您拥有使用本软件构建的网站全部内容所有权，并独立承担与这些内容的相关法律义务。
4、获得商业授权之后，您可以将本软件应用于商业用途，同时依据所购买的授权类型中确定的技术支持内容。商业授权用户享有反映和提出意见的权力，相关意见将被作为首要考虑，但没有一定被采纳的承诺或保证。

二、协议许可的权利和限制
1、未获商业授权之前，不得删除网站底部及相应的官方版权信息和链接。购买商业授权请联系无忧速建网了解最新说明。
2、未经官方许可，不得对本软件或与之关联的商业授权进行出租、出售、抵押或发放子许可证。
3、未经官方许可，禁止在凡星知识付费系统的整体或任何部分基础上以发展任何派生版本、修改版本或第三方版本用于重新分发。
5、如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回，并承担相应法律责任。

三、有限担保和免责声明
1、本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的。
2、用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未购买产品技术服务之前，我们不承诺对免费用户提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任。
3、电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和等同的法律效力。您一旦开始确认本协议并安装凡星知识付费系统，即被视为完全理解并接受本协议的各项条款，在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。

协议发布时间： 2024年02月26日
版本最新更新： 2024年02月26日

官方网站：https://www.5usujian.com
-----------------------------------------------------
运营团队: 凡星网络科技

</pre>
                </div>
                <div class="bottom tac"> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?step=2" class="btn">接 受</a> </div>
            </div>
        </div>
        <?php require './templates/footer.php';?>
    </div>
    <div class="install-animate">
        <div class="ia-circle-1"></div>
        <div class="ia-circle-2"></div>
        <div class="ia-circle-3"></div>
        <div class="ia-circle-4"></div>
        <div class="waves-wrapper">
            <svg class="waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
                <defs>
                    <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z"></path>
                </defs>
                <g class="parallax">
                    <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,255,255,0.7"></use>
                    <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,255,255,0.5)"></use>
                    <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,255,255,0.3)"></use>
                    <use xlink:href="#gentle-wave" x="48" y="7" fill="#fff"></use>
                </g>
            </svg>
        </div>
    </div>
</div>
</body>
</html>
