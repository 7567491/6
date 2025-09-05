"""
TDD测试：业务逻辑服务层
测试顺序：
6. test_course_service_get_all() ← 从这里继续
7. test_course_service_get_by_id()
8. test_course_service_search()
9. test_chapter_service_get_content()
10. test_video_service_get_stream()
"""
import pytest
from unittest.mock import Mock, patch
from sqlalchemy.orm import Session

from app.database import engine, Base, get_db
from app.models import Course, Chapter, ContentType
from app.services import CourseService, ChapterService, VideoService


class TestCourseService:
    """CourseService的TDD测试"""
    
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
    
    @pytest.fixture
    def sample_courses(self, db_session: Session):
        """创建测试用的课程数据"""
        courses = [
            Course(
                title="Python编程基础",
                subtitle="从零开始学Python",
                description="这是一门面向初学者的Python编程课程",
                category_id=1,
                difficulty_level=1,
                duration=3600,
                is_featured=True
            ),
            Course(
                title="JavaScript进阶",
                subtitle="深入理解JS核心概念",
                description="适合有一定基础的开发者",
                category_id=1,
                difficulty_level=2,
                duration=7200,
                is_featured=False
            ),
            Course(
                title="数据结构与算法",
                subtitle="程序员必备技能",
                description="系统学习数据结构和算法设计",
                category_id=2,
                difficulty_level=3,
                duration=10800,
                is_featured=True
            )
        ]
        
        for course in courses:
            db_session.add(course)
        db_session.commit()
        
        # 刷新对象以获取生成的ID
        for course in courses:
            db_session.refresh(course)
        
        return courses
    
    def test_course_service_get_all(self, db_session: Session, sample_courses):
        """
        TDD Test 6: 测试获取所有课程
        Red阶段：CourseService还不存在，应该失败
        """
        # 执行：通过服务获取所有课程
        service = CourseService(db_session)
        courses = service.get_all()
        
        # 断言：验证返回的课程列表
        assert len(courses) == 3
        assert courses[0].title == "Python编程基础"
        assert courses[1].title == "JavaScript进阶"
        assert courses[2].title == "数据结构与算法"
    
    def test_course_service_get_all_with_pagination(self, db_session: Session, sample_courses):
        """
        TDD Test 7: 测试分页获取课程
        """
        service = CourseService(db_session)
        
        # 测试分页：每页2个，获取第1页
        courses = service.get_all(page=1, page_size=2)
        assert len(courses) == 2
        
        # 测试分页：每页2个，获取第2页
        courses = service.get_all(page=2, page_size=2)
        assert len(courses) == 1
    
    def test_course_service_get_by_id(self, db_session: Session, sample_courses):
        """
        TDD Test 8: 测试根据ID获取课程
        """
        service = CourseService(db_session)
        course_id = sample_courses[0].id
        
        # 执行：根据ID获取课程
        course = service.get_by_id(course_id)
        
        # 断言：验证返回的课程信息
        assert course is not None
        assert course.title == "Python编程基础"
        assert course.subtitle == "从零开始学Python"
        assert course.difficulty_level == 1
    
    def test_course_service_get_by_id_not_found(self, db_session: Session):
        """
        TDD Test 9: 测试获取不存在的课程
        """
        service = CourseService(db_session)
        
        # 执行：获取不存在的课程
        course = service.get_by_id(999)
        
        # 断言：应该返回None
        assert course is None
    
    def test_course_service_search(self, db_session: Session, sample_courses):
        """
        TDD Test 10: 测试课程搜索功能
        """
        service = CourseService(db_session)
        
        # 测试：按标题搜索
        results = service.search("Python")
        assert len(results) == 1
        assert results[0].title == "Python编程基础"
        
        # 测试：按描述搜索
        results = service.search("基础")
        assert len(results) >= 1
        
        # 测试：搜索不存在的内容
        results = service.search("不存在的内容")
        assert len(results) == 0
    
    def test_course_service_get_featured(self, db_session: Session, sample_courses):
        """
        TDD Test 11: 测试获取推荐课程
        """
        service = CourseService(db_session)
        
        # 执行：获取推荐课程
        featured_courses = service.get_featured()
        
        # 断言：应该返回2门推荐课程
        assert len(featured_courses) == 2
        assert all(course.is_featured for course in featured_courses)


class TestChapterService:
    """ChapterService的TDD测试"""
    
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
    def sample_course_with_chapters(self, db_session: Session):
        """创建带章节的测试课程"""
        # 创建课程
        course = Course(title="Python编程基础")
        db_session.add(course)
        db_session.commit()
        db_session.refresh(course)
        
        # 创建章节
        chapters = [
            Chapter(
                course_id=course.id,
                title="第一章：Python简介",
                description="介绍Python编程语言",
                sort_order=1,
                duration=600,
                is_free=True,
                video_url="/videos/python-intro.mp4",
                video_duration=600,
                video_cover="/images/python-intro-cover.jpg",
                content_type=ContentType.video
            ),
            Chapter(
                course_id=course.id,
                title="第二章：环境搭建",
                description="搭建Python开发环境",
                sort_order=2,
                duration=900,
                is_free=True,
                video_url="/videos/python-setup.mp4",
                video_duration=900,
                content_type=ContentType.video
            ),
            Chapter(
                course_id=course.id,
                title="第三章：高级特性",
                description="Python高级编程特性",
                sort_order=3,
                duration=1800,
                is_free=False,  # 付费章节
                video_url="/videos/python-advanced.mp4",
                video_duration=1800,
                content_type=ContentType.video
            )
        ]
        
        for chapter in chapters:
            db_session.add(chapter)
        db_session.commit()
        
        for chapter in chapters:
            db_session.refresh(chapter)
        
        return course, chapters
    
    def test_chapter_service_get_by_course_id(self, db_session: Session, sample_course_with_chapters):
        """
        TDD Test 12: 测试获取课程章节列表
        """
        course, chapters = sample_course_with_chapters
        service = ChapterService(db_session)
        
        # 执行：获取课程的所有章节
        result_chapters = service.get_by_course_id(course.id)
        
        # 断言：验证章节列表
        assert len(result_chapters) == 3
        assert result_chapters[0].title == "第一章：Python简介"
        assert result_chapters[1].title == "第二章：环境搭建"
        assert result_chapters[2].title == "第三章：高级特性"
        
        # 验证排序正确
        assert result_chapters[0].sort_order == 1
        assert result_chapters[1].sort_order == 2
        assert result_chapters[2].sort_order == 3
    
    def test_chapter_service_get_content(self, db_session: Session, sample_course_with_chapters):
        """
        TDD Test 13: 测试获取章节学习内容 ⭐
        这是v1.0核心的在线学习功能
        """
        course, chapters = sample_course_with_chapters
        service = ChapterService(db_session)
        
        # 执行：获取章节学习内容
        chapter_content = service.get_content(chapters[0].id)
        
        # 断言：验证章节学习内容
        assert chapter_content is not None
        assert chapter_content.title == "第一章：Python简介"
        assert chapter_content.video_url == "/videos/python-intro.mp4"
        assert chapter_content.video_duration == 600
        assert chapter_content.video_cover == "/images/python-intro-cover.jpg"
        assert chapter_content.content_type == ContentType.video
        assert chapter_content.is_free is True
    
    def test_chapter_service_get_free_content(self, db_session: Session, sample_course_with_chapters):
        """
        TDD Test 14: 测试获取免费章节内容 ⭐
        v1.0支持无门槛访问免费内容
        """
        course, chapters = sample_course_with_chapters
        service = ChapterService(db_session)
        
        # 执行：获取免费章节
        free_chapters = service.get_free_chapters(course.id)
        
        # 断言：应该返回2个免费章节
        assert len(free_chapters) == 2
        assert all(chapter.is_free for chapter in free_chapters)
        assert free_chapters[0].title == "第一章：Python简介"
        assert free_chapters[1].title == "第二章：环境搭建"


class TestVideoService:
    """VideoService的TDD测试 ⭐ v1.0核心功能"""
    
    @patch('app.services.video_service.os.path.exists')
    def test_video_service_get_stream_info(self, mock_exists):
        """
        TDD Test 15: 测试获取视频流信息
        """
        mock_exists.return_value = True
        service = VideoService()
        
        # 执行：获取视频流信息
        video_info = service.get_stream_info("/videos/python-intro.mp4")
        
        # 断言：验证视频流信息
        assert video_info is not None
        assert "stream_url" in video_info
        assert "duration" in video_info
        assert "format" in video_info
        assert video_info["format"] == "mp4"
    
    @patch('app.services.video_service.os.path.exists')
    def test_video_service_get_stream_url(self, mock_exists):
        """
        TDD Test 16: 测试获取视频流URL ⭐
        """
        mock_exists.return_value = True
        service = VideoService()
        
        # 执行：获取视频流URL
        stream_url = service.get_stream_url("/videos/python-intro.mp4")
        
        # 断言：验证流URL
        assert stream_url is not None
        assert stream_url.startswith("http")
        assert "python-intro" in stream_url
    
    @patch('app.services.video_service.os.path.exists')
    def test_video_service_validate_video_file(self, mock_exists):
        """
        TDD Test 17: 测试视频文件验证
        """
        mock_exists.return_value = True
        service = VideoService()
        
        # 执行：验证视频文件
        is_valid = service.validate_video_file("/videos/python-intro.mp4")
        
        # 断言：文件应该有效
        assert is_valid is True
        
        # 测试不存在的文件
        mock_exists.return_value = False
        is_valid = service.validate_video_file("/videos/not-exists.mp4")
        assert is_valid is False