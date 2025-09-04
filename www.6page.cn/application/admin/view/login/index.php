<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="renderer" content="webkit">
    <title>登录{$site_name}</title>
    <meta name="generator" content="FANSTAR! v2.5" />
    <meta name="author" content="FANSTAR! Team and FANSTAR UI Team" />
    <meta name="copyright" content="2001-2013 FANSTAR Inc." />
    <link href="{__FRAME_PATH}css/bootstrap.min.css?v=3.4.0" rel="stylesheet">
    <link href="{__FRAME_PATH}css/font-awesome.min.css?v=4.3.0" rel="stylesheet">
    <link href="{__FRAME_PATH}css/animate.min.css" rel="stylesheet">
    <link href="{__FRAME_PATH}css/style.min.css?v=3.0.0" rel="stylesheet">
    <link href="{__STATIC_PATH}plug/layer/mobile/need/layer.css" rel="stylesheet">
    <script src="{__STATIC_PATH}plug/layer/mobile/layer.js"></script>
    <script>
        top != window && (top.location.href = location.href);
    </script>
</head>
<body class="gray-bg login-bg">
<div class="middle-box text-center loginscreen  animated fadeInDown">
    <div class="login-group">
<!--        <h3 class="login-logo">-->
<!--            <img src="">-->
<!--        </h3>-->
        <h3 class="login-logo" style="color: #333; padding-top: 20px">登录后台</h3>
        <form role="form"  method="post">
            <div class="form-group">
                <div class="input-group m-b"><span class="input-group-addon"><i class="fa fa-user"></i> </span>
                    <input type="text" id="account" name="account"  placeholder="用户名" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="input-group m-b"><span class="input-group-addon"><i class="fa fa-unlock-alt"></i> </span>
                    <input type="password" class="form-control" id="pwd" name="pwd" placeholder="密码" required="">
                </div>

            </div>
            <div class="form-group">
                <div class="input-group">
                    <input type="text" class="form-control" id="verify" name="verify" placeholder="验证码" required="">
                    <span class="input-group-btn" style="padding: 0;margin: 0;">
                        <img id="verify_img" src="{:Url('captcha')}" alt="验证码" style="padding: 0;height: 34px;margin: 0;">
                    </span>
                </div>
            </div>
            <div class="form-group">
                <strong>
                    <p class="text-danger" id="err" style="display: none;"></p>
                </strong>
            </div>
            <button type="button" class="btn btn-primary block full-width m-b">登 录</button>
        </form>
    </div>
</div>
<!--<div class="footer" style="position: fixed;bottom: 0;width: 100%;left: 0;margin: 0;opacity: 0.8;">-->
<!--    <div class="pull-right">Copyright © 2023 FANSTAR 版本号：<a href="http://www.fanstar.com/" target="_blank">FANSTAR-ZSFF</a>-->
<!--    </div>-->
<!--</div>-->
<script src="{__PLUG_PATH}jquery-1.10.2.min.js"></script>
<script src="{__FRAME_PATH}js/bootstrap.min.js?v=3.4.0"></script>
<script src="{__MODULE_PATH}login/index.js"></script>
<script>
    $('.btn').click(function () {
        var name=$(" #account ").val();
        var pwd=$(" #pwd ").val();
        var verify=$(" #verify ").val();
        if(!name) {
           return  layer.open({ content: '请输入用户名', skin: 'msg', time: 1});
        }
        if(!pwd){
            return layer.open({ content: '请输入密码', skin: 'msg', time: 1});
        }
        if(!verify){
            return layer.open({ content: '请输入验证码', skin: 'msg', time: 1});
        }
        $.ajax({
            url:"{:url('verify')}",
            data:{account:name,pwd:pwd,verify:verify},
            type:'post',
            datatype:'json',
            success:function (res) {
                if(res.code==1){
                    window.location.href="{:url('admin/index/index')}";
                }else if(res.code==2){
                    var $captcha = $('#verify_img'),src = $captcha[0].src;
                    $captcha[0].src = src+'?'+Date.parse(new Date());
                    return layer.open({ content: '验证码错误，请重新输入！', skin: 'msg', time: 1});
                }else if(res.code==3){
                    return layer.open({ content: '错误次数过多,请稍候再试！', skin: 'msg', time: 1});
                }else if(res.code==4){
                    return layer.open({ content: '请登陆!', skin: 'msg', time: 1});
                }else if(res.code==0){
                    return layer.open({ content: '用户名密码错误，请重新输入！', skin: 'msg', time: 1});
                }
            },
            error:function (err) {
                return layer.open({ content: '登录失败！', skin: 'msg', time: 1});
            }
        })
    });
    document.addEventListener('keydown', function (event) {
        if (event.keyCode == 13) {
            var name=$(" #account ").val();
            var pwd=$(" #pwd ").val();
            var verify=$(" #verify ").val();
            if(!name) {
            return  layer.open({ content: '请输入用户名', skin: 'msg', time: 1});
            }
            if(!pwd){
                return layer.open({ content: '请输入密码', skin: 'msg', time: 1});
            }
            if(!verify){
                return layer.open({ content: '请输入验证码', skin: 'msg', time: 1});
            }
            $.ajax({
                url:"{:url('verify')}",
                data:{account:name,pwd:pwd,verify:verify},
                type:'post',
                datatype:'json',
                success:function (res) {
                    if(res.code==1){
                        window.location.href="{:url('admin/index/index')}";
                    }else if(res.code==2){
                        return layer.open({ content: '验证码错误，请重新输入！', skin: 'msg', time: 1});
                    }else if(res.code==3){
                        return layer.open({ content: '错误次数过多,请稍候再试！', skin: 'msg', time: 1});
                    }else if(res.code==4){
                        return layer.open({ content: '请登陆!', skin: 'msg', time: 1});
                    }else if(res.code==0){
                        return layer.open({ content: '用户名或密码错误，请重新输入！', skin: 'msg', time: 1});
                    }
                },
                error:function (err) {
                    return layer.open({ content: '登录失败！', skin: 'msg', time: 1});
                }
            })
        }
    });

</script>
</body>
</html>
