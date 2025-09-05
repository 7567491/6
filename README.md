# 六页纸教育平台 v1.0 MVP

## 🎯 项目目标

基于现代微服务架构，实现教育平台v1.0版本，包含：
- 在线学习：视频播放器、进度控制、章节跳转 ⭐⭐⭐
- 无门槛访问：免费内容无需登录，直接学习 ⭐⭐⭐
- 课程展示：课程列表、详情页、章节浏览
- 文章学习：文章列表、详情页、分类浏览
- 内容搜索：基础关键词搜索功能
- 响应式设计：PC端和移动端适配
- SEO优化：搜索引擎友好

## 🏗️ 技术架构

### 微服务架构
- **content-service**: 内容管理服务 (Python FastAPI)
- **file-service**: 文件存储服务 (Python FastAPI)
- **frontend**: 前端界面 (HTML5 + 现代JavaScript + Bootstrap 5)
- **nginx**: 反向代理
- **sqlite**: 数据库存储

### 开发方法
- **TDD (测试驱动开发)**: 先写测试，再实现功能
- **Docker**: 容器化部署
- **CI/CD**: 自动化测试和部署

## 📁 项目结构

```
6page-v1.0/
├── services/              # 微服务
│   ├── content-service/   # 内容服务
│   │   ├── app/          # 应用代码
│   │   ├── tests/        # 单元测试
│   │   └── requirements.txt
│   └── file-service/     # 文件服务
│       ├── app/          # 应用代码
│       ├── tests/        # 单元测试
│       └── requirements.txt
├── frontend/             # 前端应用
│   ├── src/             # 源码
│   ├── public/          # 静态资源
│   ├── tests/           # 前端测试
│   └── package.json
├── database/            # 数据库
│   ├── migrations/      # 数据迁移
│   └── seeds/           # 测试数据
├── docker/              # 容器配置
├── tests/               # 集成测试
└── scripts/             # 部署脚本
```

## 🚀 快速开始

### 环境要求
- Docker 20.0+
- Docker Compose 2.0+
- Python 3.11+
- Node.js 18+

### 启动开发环境
```bash
# 克隆项目
git clone <repository-url>
cd 6page-v1.0

# 启动所有服务
docker-compose up -d

# 访问应用
# - 前端: http://localhost:3000
# - 内容服务API: http://localhost:8002/docs
# - 文件服务API: http://localhost:8007/docs
```

### TDD开发流程
1. 编写测试用例
2. 运行测试（确认失败）
3. 编写最少代码让测试通过
4. 重构优化
5. 重复流程

## 📊 开发进度

- [x] ✅ 创建项目结构
- [x] ✅ 搭建测试框架  
- [x] ✅ TDD实现content-service (18个测试通过)
- [x] ✅ TDD实现file-service (7个测试通过)
- [ ] 🚧 TDD实现前端学习界面 (下一步重点)
- [ ] ⏳ 容器化部署
- [ ] ⏳ 端到端测试

**当前状态**: v1.0 MVP 70%完成 - 后端微服务全部完成 ⭐⭐⭐

## 📝 API文档

### Content Service API
- `GET /api/v1/courses` - 获取课程列表
- `GET /api/v1/courses/{id}` - 获取课程详情
- `GET /api/v1/courses/{id}/chapters/{chapter_id}` - 获取章节内容
- `GET /api/v1/videos/{id}/stream` - 视频流接口

### File Service API
- `GET /api/v1/files/{id}` - 获取文件信息
- `POST /api/v1/files/upload` - 上传文件

## 🧪 测试

```bash
# 运行所有测试
./scripts/run_tests.sh

# 运行单元测试
pytest services/content-service/tests/
pytest services/file-service/tests/

# 运行集成测试
pytest tests/integration/

# 运行端到端测试
pytest tests/e2e/
```

---
**开发时间**: 3周  
**目标上线**: 2025年10月  
**版本**: v1.0 MVP