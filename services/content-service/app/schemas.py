"""
数据模型Schema定义 (Pydantic)
"""
from typing import List, Optional
from datetime import datetime
from pydantic import BaseModel
from enum import Enum


class ContentTypeEnum(str, Enum):
    """内容类型枚举"""
    video = "video"
    article = "article" 
    audio = "audio"


class CourseResponse(BaseModel):
    """课程响应模型"""
    id: int
    title: str
    subtitle: Optional[str] = None
    description: Optional[str] = None
    cover_image: Optional[str] = None
    category_id: Optional[int] = None
    difficulty_level: int
    duration: int
    chapter_count: int
    view_count: int
    is_featured: bool
    status: int
    created_at: datetime
    updated_at: datetime
    
    class Config:
        from_attributes = True  # Pydantic v2语法


class ChapterResponse(BaseModel):
    """章节响应模型 ⭐ v1.0核心学习功能"""
    id: int
    course_id: int
    title: str
    description: Optional[str] = None
    sort_order: int
    duration: int
    is_free: bool
    
    # 视频学习相关字段 ⭐
    video_url: Optional[str] = None
    video_duration: int
    video_cover: Optional[str] = None
    content_type: ContentTypeEnum
    
    status: int
    created_at: datetime
    updated_at: datetime
    
    class Config:
        from_attributes = True


class VideoStreamResponse(BaseModel):
    """视频流响应模型 ⭐ v1.0核心功能"""
    stream_url: str
    duration: int
    format: str
    resolution: str
    bitrate: str


class VideoInfoResponse(BaseModel):
    """视频信息响应模型"""
    duration: int
    format: str
    resolution: str
    bitrate: str


class ChapterListResponse(BaseModel):
    """章节列表响应模型"""
    chapters: List[ChapterResponse]


class CourseListResponse(BaseModel):
    """课程列表响应模型"""
    courses: List[CourseResponse]
    pagination: Optional["PaginationInfo"] = None


class PaginationInfo(BaseModel):
    """分页信息"""
    page: int
    page_size: int
    total: int