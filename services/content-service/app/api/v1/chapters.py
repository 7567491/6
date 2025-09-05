"""
章节相关API ⭐ v1.0核心在线学习功能
"""
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session

from ...database import get_db
from ...services import ChapterService
from ...schemas import ChapterResponse, ChapterListResponse


router = APIRouter()


@router.get("/courses/{course_id}/chapters", response_model=ChapterListResponse)
async def get_course_chapters(
    course_id: int,
    free_only: bool = Query(False, description="仅获取免费章节"),
    db: Session = Depends(get_db)
):
    """
    获取课程章节列表
    
    支持获取免费章节 ⭐ v1.0无门槛访问功能
    """
    service = ChapterService(db)
    
    if free_only:
        # 获取免费章节 ⭐
        chapters = service.get_free_chapters(course_id)
    else:
        # 获取所有章节
        chapters = service.get_by_course_id(course_id)
    
    return ChapterListResponse(chapters=chapters)


@router.get("/courses/{course_id}/chapters/{chapter_id}", response_model=ChapterResponse)
async def get_chapter_content(
    course_id: int,
    chapter_id: int,
    db: Session = Depends(get_db)
):
    """
    获取章节学习内容 ⭐ v1.0核心在线学习功能
    
    返回完整的章节学习内容，包括视频信息
    """
    service = ChapterService(db)
    chapter = service.get_content(chapter_id)
    
    if chapter is None:
        raise HTTPException(status_code=404, detail="Chapter not found")
    
    # 验证章节属于指定课程
    if chapter.course_id != course_id:
        raise HTTPException(status_code=404, detail="Chapter not found in this course")
    
    return ChapterResponse.from_orm(chapter)