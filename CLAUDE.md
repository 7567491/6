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