<!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<title><?php echo $Title; ?> - <?php echo $Powered; ?></title>
<link rel="stylesheet" href="./css/install.css?v=9.0" />
<script src="js/jquery.js"></script>
<?php
$uri = $_SERVER['REQUEST_URI'];
$root = substr($uri, 0,strpos($uri, "install"));
$admin = $root."../admin/index/index";
?>
</head>
<body>
<div class="install-outer">
    <div class="install-inner">
        <div class="wrap">
            <?php require './templates/header.php';?>
            <section class="section">
                <div class="">
                    <div class="success_tip cc"> <a href="<?php echo $admin;?>" class="f16 b">安装完成，进入后台管理</a>
                    </div>
                    <div class="bottom tac">
                        <a href="<?php echo 'http://'.$host;?>" class="btn">进入前台</a>
                        <a href="<?php echo 'http://'.$host;?>/admin/login/index" class="btn btn_submit J_install_btn">进入后台</a>
                    </div>
                    <div class=""> </div>
                </div>
            </section>
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
