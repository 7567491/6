"""
Content Service FastAPI应用主程序
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .api.v1 import courses, chapters, videos
from .database import engine, Base


# 创建数据表
Base.metadata.create_all(bind=engine)

# 创建FastAPI应用
app = FastAPI(
    title="Content Service API",
    description="六页纸教育平台 - 内容服务API",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# 添加CORS支持
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # 生产环境应该限制具体域名
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# 注册API路由
app.include_router(courses.router, prefix="/api/v1", tags=["courses"])
app.include_router(chapters.router, prefix="/api/v1", tags=["chapters"]) 
app.include_router(videos.router, prefix="/api/v1", tags=["videos"])


@app.get("/")
async def root():
    """根路径"""
    return {
        "service": "Content Service",
        "version": "1.0.0",
        "status": "running",
        "docs": "/docs"
    }


@app.get("/health")
async def health_check():
    """健康检查"""
    return {
        "status": "healthy",
        "service": "content-service"
    }