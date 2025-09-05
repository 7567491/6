"""
数据模型定义
"""
from sqlalchemy import Column, Integer, String, Text, Boolean, DateTime, ForeignKey, Enum
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
import enum

from .database import Base


class ContentType(str, enum.Enum):
    """内容类型枚举"""
    video = "video"
    article = "article"
    audio = "audio"


class Course(Base):
    """课程模型"""
    __tablename__ = "courses"
    
    # 基础字段
    id = Column(Integer, primary_key=True, index=True)
    title = Column(String(200), nullable=False)
    subtitle = Column(String(300))
    description = Column(Text)
    cover_image = Column(Text)
    category_id = Column(Integer)
    difficulty_level = Column(Integer, default=1)
    duration = Column(Integer, default=0)  # 总时长（秒）
    chapter_count = Column(Integer, default=0)
    view_count = Column(Integer, default=0)
    is_featured = Column(Boolean, default=False)
    status = Column(Integer, default=1)  # 1:正常, 0:禁用
    
    def __init__(self, **kwargs):
        """初始化Course对象，设置默认值"""
        # 设置默认值
        self.difficulty_level = kwargs.get('difficulty_level', 1)
        self.duration = kwargs.get('duration', 0)
        self.chapter_count = kwargs.get('chapter_count', 0)
        self.view_count = kwargs.get('view_count', 0)
        self.is_featured = kwargs.get('is_featured', False)
        self.status = kwargs.get('status', 1)
        
        # 设置其他字段
        for key, value in kwargs.items():
            if hasattr(self, key):
                setattr(self, key, value)
    
    # 时间戳
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    
    # 关系
    chapters = relationship("Chapter", back_populates="course")


class Chapter(Base):
    """章节模型"""
    __tablename__ = "chapters"
    
    # 基础字段
    id = Column(Integer, primary_key=True, index=True)
    course_id = Column(Integer, ForeignKey("courses.id"), nullable=False)
    title = Column(String(200), nullable=False)
    description = Column(Text)
    sort_order = Column(Integer, default=0)
    duration = Column(Integer, default=0)  # 章节时长（秒）
    is_free = Column(Boolean, default=False)
    
    # 在线学习相关字段 ⭐
    video_url = Column(Text)  # 视频文件URL
    video_duration = Column(Integer, default=0)  # 视频时长（秒）
    video_cover = Column(Text)  # 视频封面图
    content_type = Column(Enum(ContentType), default=ContentType.video)
    
    # 状态和时间戳
    status = Column(Integer, default=1)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    
    # 关系
    course = relationship("Course", back_populates="chapters")


class Category(Base):
    """分类模型"""
    __tablename__ = "categories"
    
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(100), nullable=False)
    description = Column(Text)
    sort_order = Column(Integer, default=0)
    status = Column(Integer, default=1)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())