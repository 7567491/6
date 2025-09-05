"""
课程相关API ⭐ v1.0核心功能
"""
from typing import List, Optional
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session

from ...database import get_db
from ...services import CourseService
from ...schemas import CourseResponse, CourseListResponse, PaginationInfo


router = APIRouter()


@router.get("/courses", response_model=CourseListResponse)
async def get_courses(
    page: int = Query(1, ge=1, description="页码"),
    page_size: Optional[int] = Query(None, ge=1, le=100, description="每页大小"),
    db: Session = Depends(get_db)
):
    """
    获取课程列表
    
    支持分页查询
    """
    service = CourseService(db)
    courses = service.get_all(page=page, page_size=page_size)
    
    # 构建响应
    response = CourseListResponse(courses=courses)
    
    # 如果有分页参数，添加分页信息
    if page_size is not None:
        response.pagination = PaginationInfo(
            page=page,
            page_size=page_size,
            total=len(courses)  # 简化版本，实际应该查询总数
        )
    
    return response


@router.get("/courses/{course_id}", response_model=CourseResponse)
async def get_course_detail(
    course_id: int,
    db: Session = Depends(get_db)
):
    """
    获取课程详情
    """
    service = CourseService(db)
    course = service.get_by_id(course_id)
    
    if course is None:
        raise HTTPException(status_code=404, detail="Course not found")
    
    return CourseResponse.from_orm(course)


@router.get("/search", response_model=dict)
async def search_courses(
    q: str = Query(..., description="搜索关键词"),
    db: Session = Depends(get_db)
):
    """
    搜索课程
    """
    service = CourseService(db)
    results = service.search(q)
    
    return {
        "query": q,
        "results": [CourseResponse.from_orm(course) for course in results]
    }