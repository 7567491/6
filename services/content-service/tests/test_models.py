"""
TDD测试：数据模型
测试顺序：
1. test_course_model_creation() ← 从这里开始
2. test_course_model_validation()
3. test_chapter_model_creation()
4. test_chapter_model_with_video()
5. test_database_relationships()
"""
import pytest
from datetime import datetime
from sqlalchemy.orm import Session

from app.database import engine, Base, get_db
from app.models import Course, Chapter, Category


class TestCourseModel:
    """Course模型的TDD测试"""
    
    @pytest.fixture(autouse=True)
    def setup_database(self):
        """每个测试前创建表，测试后清理"""
        Base.metadata.create_all(bind=engine)
        yield
        Base.metadata.drop_all(bind=engine)
    
    @pytest.fixture
    def db_session(self):
        """获取数据库会话"""
        db = next(get_db())
        try:
            yield db
        finally:
            db.close()
    
    def test_course_model_creation(self, db_session: Session):
        """
        TDD Test 1: 测试Course模型创建
        Red阶段：这个测试现在应该失败，因为Course模型还不存在
        """
        # 准备测试数据
        course_data = {
            "title": "Python编程基础",
            "subtitle": "从零开始学Python",
            "description": "这是一门面向初学者的Python编程课程",
            "cover_image": "/images/python-course.jpg",
            "category_id": 1,
            "difficulty_level": 1,
            "duration": 3600,  # 1小时，以秒为单位
            "chapter_count": 10,
            "is_featured": True,
            "status": 1
        }
        
        # 执行：创建Course实例
        course = Course(**course_data)
        
        # 断言：验证Course对象的属性
        assert course.title == "Python编程基础"
        assert course.subtitle == "从零开始学Python"
        assert course.description == "这是一门面向初学者的Python编程课程"
        assert course.cover_image == "/images/python-course.jpg"
        assert course.category_id == 1
        assert course.difficulty_level == 1
        assert course.duration == 3600
        assert course.chapter_count == 10
        assert course.view_count == 0  # 默认值
        assert course.is_featured is True
        assert course.status == 1
        
        # 保存到数据库
        db_session.add(course)
        db_session.commit()
        
        # 验证数据库中的数据
        saved_course = db_session.query(Course).filter(Course.title == "Python编程基础").first()
        assert saved_course is not None
        assert saved_course.id is not None  # 自动生成的ID
        assert saved_course.created_at is not None  # 自动生成的时间戳
        assert saved_course.updated_at is not None
    
    def test_course_model_validation(self, db_session: Session):
        """
        TDD Test 2: 测试Course模型数据验证
        """
        # 测试必填字段
        with pytest.raises(Exception):  # title是必填字段
            course = Course()
            db_session.add(course)
            db_session.commit()
    
    def test_course_model_defaults(self, db_session: Session):
        """
        TDD Test 3: 测试Course模型默认值
        """
        course = Course(title="测试课程")
        db_session.add(course)
        db_session.commit()
        
        # 验证默认值
        assert course.difficulty_level == 1
        assert course.duration == 0
        assert course.chapter_count == 0
        assert course.view_count == 0
        assert course.is_featured is False
        assert course.status == 1


class TestChapterModel:
    """Chapter模型的TDD测试"""
    
    @pytest.fixture(autouse=True)
    def setup_database(self):
        Base.metadata.create_all(bind=engine)
        yield
        Base.metadata.drop_all(bind=engine)
    
    @pytest.fixture
    def db_session(self):
        db = next(get_db())
        try:
            yield db
        finally:
            db.close()
    
    @pytest.fixture
    def sample_course(self, db_session: Session):
        """创建测试用的课程"""
        course = Course(title="测试课程")
        db_session.add(course)
        db_session.commit()
        db_session.refresh(course)
        return course
    
    def test_chapter_model_creation(self, db_session: Session, sample_course: Course):
        """
        TDD Test 4: 测试Chapter模型创建
        """
        chapter_data = {
            "course_id": sample_course.id,
            "title": "第一章：Python简介",
            "description": "介绍Python编程语言的基本概念",
            "sort_order": 1,
            "duration": 600,  # 10分钟
            "is_free": True,
            "status": 1
        }
        
        chapter = Chapter(**chapter_data)
        db_session.add(chapter)
        db_session.commit()
        
        # 验证基本属性
        assert chapter.course_id == sample_course.id
        assert chapter.title == "第一章：Python简介"
        assert chapter.description == "介绍Python编程语言的基本概念"
        assert chapter.sort_order == 1
        assert chapter.duration == 600
        assert chapter.is_free is True
        assert chapter.status == 1
    
    def test_chapter_model_with_video(self, db_session: Session, sample_course: Course):
        """
        TDD Test 5: 测试Chapter模型视频功能 ⭐
        这是v1.0新增的在线学习功能
        """
        chapter_data = {
            "course_id": sample_course.id,
            "title": "视频课程：Python环境搭建",
            "description": "通过视频学习如何搭建Python开发环境",
            "sort_order": 2,
            "duration": 900,  # 15分钟
            "is_free": True,
            
            # 视频相关字段 ⭐
            "video_url": "/videos/python-setup.mp4",
            "video_duration": 900,
            "video_cover": "/images/python-setup-cover.jpg",
            "content_type": "video"
        }
        
        chapter = Chapter(**chapter_data)
        db_session.add(chapter)
        db_session.commit()
        
        # 验证视频相关属性
        assert chapter.video_url == "/videos/python-setup.mp4"
        assert chapter.video_duration == 900
        assert chapter.video_cover == "/images/python-setup-cover.jpg"
        assert chapter.content_type == "video"
    
    def test_database_relationships(self, db_session: Session, sample_course: Course):
        """
        TDD Test 6: 测试数据库关系
        """
        # 创建章节
        chapter1 = Chapter(
            course_id=sample_course.id,
            title="第一章",
            sort_order=1
        )
        chapter2 = Chapter(
            course_id=sample_course.id,
            title="第二章",
            sort_order=2
        )
        
        db_session.add_all([chapter1, chapter2])
        db_session.commit()
        
        # 测试关系查询
        course_with_chapters = db_session.query(Course).filter(Course.id == sample_course.id).first()
        assert len(course_with_chapters.chapters) == 2
        
        # 测试章节的课程关系
        chapter = db_session.query(Chapter).filter(Chapter.title == "第一章").first()
        assert chapter.course.title == sample_course.title