"""
章节业务逻辑服务 ⭐ v1.0核心在线学习功能
"""
from typing import List, Optional
from sqlalchemy.orm import Session

from ..models import Chapter


class ChapterService:
    """章节服务类"""
    
    def __init__(self, db: Session):
        self.db = db
    
    def get_by_course_id(self, course_id: int) -> List[Chapter]:
        """
        获取课程的所有章节
        
        Args:
            course_id: 课程ID
            
        Returns:
            章节列表，按sort_order排序
        """
        return self.db.query(Chapter).filter(
            Chapter.course_id == course_id,
            Chapter.status == 1
        ).order_by(Chapter.sort_order).all()
    
    def get_content(self, chapter_id: int) -> Optional[Chapter]:
        """
        获取章节学习内容 ⭐
        
        Args:
            chapter_id: 章节ID
            
        Returns:
            章节学习内容
        """
        return self.db.query(Chapter).filter(
            Chapter.id == chapter_id,
            Chapter.status == 1
        ).first()
    
    def get_free_chapters(self, course_id: int) -> List[Chapter]:
        """
        获取课程的免费章节 ⭐
        v1.0支持无门槛访问免费内容
        
        Args:
            course_id: 课程ID
            
        Returns:
            免费章节列表
        """
        return self.db.query(Chapter).filter(
            Chapter.course_id == course_id,
            Chapter.status == 1,
            Chapter.is_free == True
        ).order_by(Chapter.sort_order).all()