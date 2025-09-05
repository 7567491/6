"""
课程业务逻辑服务
"""
from typing import List, Optional
from sqlalchemy.orm import Session
from sqlalchemy import or_

from ..models import Course


class CourseService:
    """课程服务类"""
    
    def __init__(self, db: Session):
        self.db = db
    
    def get_all(self, page: int = 1, page_size: Optional[int] = None) -> List[Course]:
        """
        获取所有课程
        
        Args:
            page: 页码，从1开始
            page_size: 每页大小，None表示不分页
            
        Returns:
            课程列表
        """
        query = self.db.query(Course).filter(Course.status == 1)
        
        if page_size is not None:
            offset = (page - 1) * page_size
            query = query.offset(offset).limit(page_size)
        
        return query.all()
    
    def get_by_id(self, course_id: int) -> Optional[Course]:
        """
        根据ID获取课程
        
        Args:
            course_id: 课程ID
            
        Returns:
            课程对象或None
        """
        return self.db.query(Course).filter(
            Course.id == course_id,
            Course.status == 1
        ).first()
    
    def search(self, keyword: str) -> List[Course]:
        """
        搜索课程
        
        Args:
            keyword: 搜索关键词
            
        Returns:
            匹配的课程列表
        """
        return self.db.query(Course).filter(
            Course.status == 1,
            or_(
                Course.title.contains(keyword),
                Course.subtitle.contains(keyword),
                Course.description.contains(keyword)
            )
        ).all()
    
    def get_featured(self) -> List[Course]:
        """
        获取推荐课程
        
        Returns:
            推荐课程列表
        """
        return self.db.query(Course).filter(
            Course.status == 1,
            Course.is_featured == True
        ).all()