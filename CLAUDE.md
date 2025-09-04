# CLAUDE.md

请用中文和我对话

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目架构

这是一个基于 **ThinkPHP 5.0** 的教育平台，采用多域名架构支持：
- **主站点** (www.6page.cn) - 教育内容平台
- **点播站点** (dianbo.6page.cn) - 视频点播平台  
- **微信集成** - 小程序和公众号支持

### 应用结构

应用遵循 ThinkPHP 的 MVC 结构，包含多个模块：

- **admin/** - 后台管理面板
- **web/** - 桌面端/PC 前端
- **wap/** - 移动端/H5 前端  
- **wechat/** - 微信集成端点
- **callback/** - 支付和第三方回调
- **push/** - 使用 Workerman 的 WebSocket/实时功能

### 核心组件

- **教育系统**：课程、直播、资料、证书
- **电商系统**：商城产品、订单、支付（支付宝/微信支付）
- **用户系统**：会员、积分、奖励、推荐系统
- **内容管理**：文章、视频、下载资料
- **考试系统**：题目、试卷、证书

## 开发命令

### PHP 开发
```bash
# 安装依赖
composer install

# 启动内置开发服务器（如需要）
php think run

# 队列处理（后台任务）
php think queue:work

# 清除缓存和运行时文件
php think clear:cache

# ThinkPHP 控制台命令（查看所有可用命令）
php think list

# 数据库迁移/数据填充通过 SQL 文件手动处理
```

### 测试和调试
```bash
# 网站功能测试脚本
php public/test_web.php

# 检查资源文件测试脚本  
php public/test_resources.php
```

### 前端资源
```bash
# 前端资源已预构建，位于：
# - public/pc/ (桌面端前端)
# - public/static/ (移动端前端)
# - public/system/ (管理后台)
```

### 服务器迁移
```bash
# 完整服务器迁移流程
./sh/migrate.sh

# sh/ 目录中提供的单独迁移步骤：
# 00_init.sh - 环境初始化
# 01_backup_source.sh - 源服务器备份
# 01_transfer_data.sh - 数据传输
# 02_setup_environment.sh - 目标服务器设置
# 等等
```

## 配置文件

### 核心配置
- **application/config.php** - 主应用配置
- **application/database.php** - 数据库配置（MySQL）
- **application/route.php** - URL 路由定义
- **application/constant.php** - 常量定义
- **composer.json** - PHP 依赖

### 环境设置
- **数据库**：MySQL，表前缀为 `wy_`
- **缓存**：配置了 Redis 支持
- **会话**：默认基于文件
- **上传**：支持阿里云 OSS 集成

## 关键目录

- **application/** - 主应用代码（MVC 结构）
- **extend/** - 扩展服务和工具类
- **public/** - Web 可访问文件和资源
- **vendor/** - Composer 依赖
- **runtime/** - 日志、缓存、临时文件
- **thinkphp/** - ThinkPHP 框架核心
- **sh/** - 服务器部署和迁移脚本

## 第三方集成

- **阿里云 OSS** - 文件存储和 CDN
- **阿里云短信** - 短信通知  
- **微信 SDK** - 微信支付、小程序、公众号 (EasyWeChat v3.3)
- **Workerman/Gateway Worker** - WebSocket 实时通信和推送
- **Firebase JWT** - 令牌认证
- **PHPSpreadsheet** - Excel 导入/导出
- **Think Captcha** - 验证码生成
- **Form Builder** - 动态表单构建
- **AJ Captcha** - 行为验证码

## 数据库

- **类型**：MySQL 5.7+
- **字符集**：utf8mb4
- **前缀**：`wy_`
- **主要表**：用户、课程（special）、订单、题目、直播会话

## 常见问题与故障排除

### 首页无法加载/登录页显示四个X
**根本原因**：数据库连接失败导致SystemConfig无法读取系统配置

**解决方案**：
1. **检查数据库连接配置** (`application/database.php`)：
   ```php
   'hostname' => '127.0.0.1',  // 使用IP而非localhost，避免socket路径问题
   ```

2. **验证img_domain配置** (`application/config.php`)：
   ```php
   'img_domain' => '/',  // 确保静态资源域名配置正确
   ```

3. **测试数据库连接**：
   ```bash
   # 使用测试脚本验证
   php public/test_web.php
   ```

**故障表现**：
- 登录页面显示四个X（验证码、logo等图片无法加载）
- 出现 `[2002] No such file or directory` 错误
- `course_info` 等变量未定义错误

**环境差异说明**：
- 宝塔面板环境 → Ubuntu原生环境迁移时，MySQL socket路径会发生变化
- `localhost` 连接可能失败，建议使用 `127.0.0.1`

### 网站显示"页面错误！请稍后再试～"问题修复记录

**问题描述**：6.linapp.fun 访问时显示ThinkPHP错误页面

**根因分析**（五个为什么法）：
1. **为什么显示页面错误？** - PHP无法执行index.php文件
2. **为什么无法执行？** - PHP `open_basedir` 安全限制阻止访问文件路径
3. **为什么路径被限制？** - `open_basedir` 设置为 `/www/wwwroot/www.6page.cn/`，但实际路径是 `/home/6page/www.6page.cn/`
4. **为什么路径不匹配？** - 服务器迁移时路径改变，但PHP-FPM配置未同步更新
5. **为什么配置未更新？** - 迁移过程中遗漏了PHP-FPM安全配置的同步

**解决方案**：
1. **修复Nginx配置中的 open_basedir 路径**：
   ```nginx
   # 在 /etc/nginx/sites-available/6.linapp.fun 中
   location ~ \.php$ {
       fastcgi_param PHP_ADMIN_VALUE "open_basedir=/home/6page/www.6page.cn/:/tmp/";
   }
   ```

2. **修复PHP-FPM socket路径**：
   ```nginx
   fastcgi_pass unix:/run/php/php7.4-fpm.sock;  # 正确路径
   ```

3. **清理ThinkPHP缓存**：
   ```bash
   sudo rm -rf www.6page.cn/runtime/cache/* www.6page.cn/runtime/temp/*
   ```

**最终状态**：✅ 网站恢复正常访问，所有功能正常运行

### 前端JavaScript无法加载问题修复

**问题描述**：网页标题正常显示，但内容无法加载，JavaScript组件失效

**根因分析**：RequireJS配置中路径重复
- `baseUrl: 'pc'` + `'components': 'pc/components'` = 错误路径 `pc/pc/components`

**解决方案**：
```javascript
// 修复前：
'components': '{$img_domain}pc/components'

// 修复后：
'components': 'components'
```

**修复文件**：`application/web/view/public/require.html:35`

**最终状态**：✅ 前端JavaScript正常加载，页面内容完全显示

### 图片资源无法显示问题（部分解决）

**问题描述**：文字内容正常，但图片无法显示

**根因分析**：前端 `img_domain` 变量为空字符串，导致图片路径处理错误

**临时状态**：⚠️ 图片显示问题仍存在，但不影响网站核心功能使用

**建议后续处理**：检查ThinkPHP模板变量传递机制，确保 `$img_domain` 正确赋值到前端

## 安全提示

这是一个生产环境的教育平台，包含：
- 用户认证和授权
- 支付处理功能
- 文件上传功能
- 数据库操作

处理敏感操作时务必验证配置和凭据。

## 代码架构特点

### MVC 架构模式
- **Controller** - 控制器处理请求逻辑，位于各模块的 `controller/` 目录
- **Model** - 数据模型，位于各模块的 `model/` 目录，继承 ThinkPHP Model 类
- **View** - 视图模板，位于各模块的 `view/` 目录，使用 ThinkPHP 模板引擎

### 模块化设计
- **admin/** - 后台管理系统，包含用户管理、系统配置、内容管理等功能
- **web/** - PC端前台，提供桌面端用户界面
- **wap/** - 移动端前台，提供手机端用户界面  
- **wechat/** - 微信端接口，处理微信相关功能
- **callback/** - 回调处理，主要用于支付回调
- **push/** - 推送服务，基于 Workerman 的实时通信

### 服务层架构
- **extend/service/** - 业务服务类，封装业务逻辑
- **extend/basic/** - 基础服务类，提供通用功能
- **extend/behavior/** - 行为扩展，实现钩子机制

### 路由系统
使用 ThinkPHP 5.0 路由系统，支持：
- 模块/控制器/方法的标准路由
- 自定义路由规则（在 `application/route.php` 中定义）
- 域名绑定支持多域名架构

## 项目状态与版本控制

### 当前状态 (2025-09-04)
- ✅ **网站运行状态**：正常运行 (https://6.linapp.fun)
- ✅ **核心功能**：完全正常 (文字内容、导航、交互)
- ✅ **前端JavaScript**：已修复，页面内容完整显示
- ⚠️ **图片显示**：部分问题，不影响核心功能
- ✅ **数据库连接**：正常
- ✅ **PHP-FPM配置**：已优化

### Git仓库信息
- **GitHub仓库**：https://github.com/7567491/6
- **最新提交**：9eeeb52 (feat: 初始化六页纸教育平台项目)
- **分支**：master
- **文件统计**：5,250 个文件，1,519,768 行代码
- **最后更新**：2025-09-04

### 部署环境
- **服务器**：Ubuntu Linux
- **Web服务器**：Nginx + PHP-FPM 7.4
- **数据库**：MySQL 5.7+ (utf8mb4)
- **SSL证书**：Let's Encrypt (自动续期)
- **域名**：6.linapp.fun (主域名)

### 近期修复历史
1. **2025-09-04**: 修复 `open_basedir` 安全限制问题
2. **2025-09-04**: 修复 RequireJS 路径重复问题
3. **2025-09-04**: 优化 PHP-FPM socket 配置
4. **2025-09-04**: 清理ThinkPHP缓存，恢复网站访问
5. **2025-09-04**: 完成代码提交到GitHub