# 六页纸教育平台课程资源存储清单

## 📊 数据库配置信息

**数据库连接配置**:
- **数据库类型**: MySQL
- **服务器地址**: 127.0.0.1:3306
- **数据库名**: `6page`
- **用户名**: `6page` 
- **密码**: `5NsFLLBFFsnZb3fh`
- **表前缀**: `wy_`
- **字符集**: utf8mb4

## 🗃️ 核心数据表结构

### 1. 课程主表 (`wy_special`)
**存储课程基础信息**
```sql
-- 主要字段说明
id              INT         课程ID (主键)
title           VARCHAR     课程名称
subject_id      INT         课程分类ID
admin_id        INT         管理员ID
lecturer_id     INT         讲师ID
type            TINYINT     课程类型 (1-企业培训 3-录播课程 5-训练营 6-编程课程)
abstract        TEXT        课程简介
phrase          VARCHAR     课程短语描述
image           VARCHAR     课程封面图片路径
banner          VARCHAR     横幅图片
money           DECIMAL     课程价格
member_money    DECIMAL     会员价格
sales           INT         销量
fake_sales      INT         虚拟销量
browse_count    INT         浏览次数
is_show         TINYINT     是否显示 (0-隐藏 1-显示)
is_del          TINYINT     是否删除 (0-正常 1-删除)
add_time        INT         添加时间戳
```

### 2. 课程内容表 (`wy_special_content`)
**存储课程详细内容和视频信息**
```sql
-- 主要字段说明
id              INT         内容ID (主键)
special_id      INT         关联课程ID
link            VARCHAR     外部视频链接
videoId         VARCHAR     内部视频ID
video_type      TINYINT     视频类型
file_name       VARCHAR     文件名
file_type       VARCHAR     文件类型
content         TEXT        HTML格式课程内容
is_try          TINYINT     是否试听
try_content     TEXT        试听内容
try_time        INT         试听时长
add_time        INT         添加时间戳
```

### 3. 课程资源关联表 (`wy_special_source`)
**存储课程与资源文件的关联关系**
```sql
-- 主要字段说明
id              INT         关联ID (主键)
special_id      INT         课程ID
source_id       INT         资源ID
pay_status      TINYINT     付费状态
play_count      INT         播放次数
sort            INT         排序
add_time        INT         添加时间戳
```

### 4. 课程章节表 (`wy_special_course`)
**存储课程章节信息**
```sql
-- 主要字段说明
id              INT         章节ID (主键)
special_id      INT         课程ID
course_name     VARCHAR     章节名称
is_show         TINYINT     是否显示
sort            INT         排序
number          INT         章节编号
add_time        INT         添加时间戳
```

## 🖼️ 图片资源存储

### 存储位置
**根目录**: `/home/6page/www.6page.cn/public/uploads/`

### 目录结构
按日期分类存储，格式为 `YYYYMMDD/`
```
uploads/
├── 20241210/ (2024年12月10日上传)
│   ├── 1d387321761fb5ac6e8888be71189319.png (01 招聘与绩效)
│   ├── ad10d57975dde147049125db50e7c027.png (02 产品与营销)  
│   ├── da151197dc140f60b7f54e96a2958f71.png (03 项目与复盘)
│   └── da3d7547df6b7efccc9ce0938e92abf5.png (04 运营与增长)
├── 20241220/ (2024年12月20日上传)
│   ├── 62de4ae1abca47830c805d7d6d62d4b8.png (六页纸AI写作营)
│   └── c27eabb7997112a5b602e34eb4f29939.jpg (AI编程自动化)
├── 20241223/
│   └── 3e81e2762af879c04bf26199e2f42c19.png (贝索斯书单读书会)
├── 20241224/
│   ├── 93566e1a7605f0acc2294202610c224b.png (《逆向工作法》之六大机制)
│   ├── 525fcf801cdcdea0cdd958a9630aa6d1.png (亚马逊30年复盘)
│   └── 416a646df67401cb855e66a9f0186693.JPG (企业线下培训)
├── 20250228/
├── 20250306/
└── 20250312/
```

### 访问URL
**图片域名配置**: `config.php` 中 `img_domain` = `'/'`
**完整访问路径**: `https://6.linapp.fun/uploads/日期目录/文件名`

## 🎥 视频资源存储

### 存储方式

#### 1. 外部视频服务 (主要方式)
- **CDN服务商**: 第三方视频平台
- **示例域名**: `http://vod.5usujian.com/`
- **存储字段**: `wy_special_content.link`
- **使用场景**: 大部分课程视频

#### 2. 内部视频管理
- **存储字段**: `wy_special_content.videoId` 
- **文件名字段**: `wy_special_content.file_name`
- **示例数据**:
  - videoId: `1047777ebebc71efbfac5107e0c90102`
  - file_name: `AI编程自动化.mp4`
- **可能位置**: 阿里云OSS或内部文件系统

## 📁 前端静态资源

### PC端资源
**路径**: `/home/6page/www.6page.cn/public/pc/`
```
pc/
├── components/     # Vue.js组件
├── api/           # API接口文件
├── styles/        # CSS样式文件
├── images/        # 静态图片资源
├── font/          # 字体文件
└── scripts/       # JavaScript脚本
```

### 移动端资源
**路径**: `/home/6page/www.6page.cn/public/static/`
```
static/
├── css/           # 移动端样式
├── js/            # 移动端脚本
├── aliplayer/     # 阿里播放器
└── wechat/        # 微信相关资源
```

### 管理后台资源
**路径**: `/home/6page/www.6page.cn/public/system/`
```
system/
├── css/           # 后台样式
├── js/            # 后台脚本
├── images/        # 后台图片
└── module/        # 后台模块
```

## 📚 当前课程列表 (有效课程共13个)

| ID | 课程名称 | 类型 | 价格 | 封面图片 | 视频存储 |
|---|---------|------|------|----------|---------|
| 14 | 01 招聘与绩效 | 3 | ¥599 | `/uploads/20241210/1d387321...png` | 外部CDN |
| 15 | 02 产品与营销 | 3 | ¥599 | `/uploads/20241210/ad10d579...png` | 外部CDN |
| 16 | 03 项目与复盘 | 3 | ¥599 | `/uploads/20241210/da151197...png` | 外部CDN |
| 17 | 04 运营与增长 | 3 | ¥599 | `/uploads/20241210/da3d7547...png` | 外部CDN |
| 18 | 六页纸AI写作营 | 5 | ¥1980 | `/uploads/20241220/62de4ae1...png` | 外部CDN |
| 19 | AI编程自动化 | 6 | ¥99 | `/uploads/20241220/c27eabb7...jpg` | videoId |
| 20 | 贝索斯书单读书会 | 3 | ¥199 | `/uploads/20241223/3e81e276...png` | 外部CDN |
| 21 | 《逆向工作法》之六大机制 | 3 | ¥199 | `/uploads/20241224/93566e1a...png` | 外部CDN |
| 22 | 亚马逊30年复盘 | 3 | ¥9.9 | `/uploads/20241224/525fcf80...png` | 外部CDN |
| 24 | 企业线下培训 | 1 | ¥40000 | `/uploads/20241224/416a646d...JPG` | 外部CDN |
| 25 | 六页纸免费模板 | 1 | ¥0.1 | 未设置 | 外部CDN |
| 26 | 六页纸AI读书会 | 5 | ¥258 | 未设置 | 外部CDN |
| 27 | 《亚马逊六页纸》导入精讲 | 3 | ¥49 | 未设置 | 外部CDN |

## 🔧 技术配置

### 数据库连接
配置文件: `/home/6page/www.6page.cn/application/database.php`

### 图片域名配置
配置文件: `/home/6page/www.6page.cn/application/config.php`
```php
'img_domain' => '/',  // 相对路径配置
```

### 课程类型说明
- **Type 1**: 企业培训/免费资源
- **Type 3**: 录播课程 (主力产品)
- **Type 5**: 训练营/读书会
- **Type 6**: 编程相关课程

## 📊 统计信息
- **数据库总课程**: 25个 (包含已删除)
- **有效课程**: 13个 (`is_del=0`)
- **已删除课程**: 12个
- **课程状态**: 全部设为可见 (`is_show=1`)
- **当前销量**: 所有课程销量为0
- **最高浏览量**: 六页纸免费模板 (428次浏览)

---
*文档更新时间: 2025-01-06*
*数据库状态: 正常运行*
*网站访问: https://6.linapp.fun*