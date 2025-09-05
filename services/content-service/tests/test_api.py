"""
TDD测试：API接口层
测试顺序：
11. test_get_courses_api() ← API层开始
12. test_get_course_detail_api()
13. test_get_chapter_content_api()  
14. test_get_video_stream_api()
15. test_search_courses_api()
"""
import pytest
from fastapi.testclient import TestClient
from sqlalchemy.orm import Session

from app.main import app
from app.database import engine, Base, get_db
from app.models import Course, Chapter, ContentType


# 创建测试客户端
client = TestClient(app)


class TestCourseAPI:
    """Course API的TDD测试"""
    
    @pytest.fixture(autouse=True)
    def setup_database(self):
        """每个测试前创建表，测试后清理"""
        Base.metadata.create_all(bind=engine)
        yield
        Base.metadata.drop_all(bind=engine)
    
    @pytest.fixture
    def sample_courses(self):
        """创建测试用的课程数据"""
        db = next(get_db())
        
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
            )
        ]
        
        for course in courses:
            db.add(course)
        db.commit()
        
        for course in courses:
            db.refresh(course)
        
        db.close()
        return courses
    
    def test_get_courses_api(self, sample_courses):
        """
        TDD Test 11: 测试获取课程列表API
        Red阶段：API还不存在，应该失败
        """
        # 执行：调用API获取课程列表
        response = client.get("/api/v1/courses")
        
        # 断言：验证响应
        assert response.status_code == 200
        
        data = response.json()
        assert "courses" in data
        assert len(data["courses"]) == 2
        
        # 验证第一个课程数据
        first_course = data["courses"][0]
        assert first_course["title"] == "Python编程基础"
        assert first_course["subtitle"] == "从零开始学Python"
        assert first_course["difficulty_level"] == 1
    
    def test_get_courses_api_with_pagination(self, sample_courses):
        """
        TDD Test 12: 测试分页获取课程API
        """
        # 执行：分页获取课程
        response = client.get("/api/v1/courses?page=1&page_size=1")
        
        # 断言：验证分页响应
        assert response.status_code == 200
        
        data = response.json()
        assert len(data["courses"]) == 1
        assert "pagination" in data
        assert data["pagination"]["page"] == 1
        assert data["pagination"]["page_size"] == 1
    
    def test_get_course_detail_api(self, sample_courses):
        """
        TDD Test 13: 测试获取课程详情API
        """
        course_id = sample_courses[0].id
        
        # 执行：获取课程详情
        response = client.get(f"/api/v1/courses/{course_id}")
        
        # 断言：验证课程详情
        assert response.status_code == 200
        
        data = response.json()
        assert data["id"] == course_id
        assert data["title"] == "Python编程基础"
        assert data["description"] == "这是一门面向初学者的Python编程课程"
    
    def test_get_course_detail_api_not_found(self, sample_courses):
        """
        TDD Test 14: 测试获取不存在课程的API
        """
        # 执行：获取不存在的课程
        response = client.get("/api/v1/courses/999")
        
        # 断言：应该返回404
        assert response.status_code == 404
        assert "detail" in response.json()
    
    def test_search_courses_api(self, sample_courses):
        """
        TDD Test 15: 测试搜索课程API
        """
        # 执行：搜索课程
        response = client.get("/api/v1/search?q=Python")
        
        # 断言：验证搜索结果
        assert response.status_code == 200
        
        data = response.json()
        assert "results" in data
        assert len(data["results"]) == 1
        assert data["results"][0]["title"] == "Python编程基础"
        assert "query" in data
        assert data["query"] == "Python"


class TestChapterAPI:
    """Chapter API的TDD测试 ⭐ v1.0核心学习功能"""
    
    @pytest.fixture(autouse=True)
    def setup_database(self):
        Base.metadata.create_all(bind=engine)
        yield
        Base.metadata.drop_all(bind=engine)
    
    @pytest.fixture 
    def sample_course_with_chapters(self):
        """创建带章节的测试课程"""
        db = next(get_db())
        
        # 创建课程
        course = Course(title="Python编程基础")
        db.add(course)
        db.commit()
        db.refresh(course)
        
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
                title="第二章：高级特性",
                description="Python高级编程特性",
                sort_order=2,
                duration=1800,
                is_free=False,
                video_url="/videos/python-advanced.mp4",
                video_duration=1800,
                content_type=ContentType.video
            )
        ]
        
        for chapter in chapters:
            db.add(chapter)
        db.commit()
        
        for chapter in chapters:
            db.refresh(chapter)
        
        db.close()
        return course, chapters
    
    def test_get_chapters_api(self, sample_course_with_chapters):
        """
        TDD Test 16: 测试获取课程章节列表API
        """
        course, chapters = sample_course_with_chapters
        
        # 执行：获取课程章节
        response = client.get(f"/api/v1/courses/{course.id}/chapters")
        
        # 断言：验证章节列表
        assert response.status_code == 200
        
        data = response.json()
        assert "chapters" in data
        assert len(data["chapters"]) == 2
        
        # 验证章节顺序
        assert data["chapters"][0]["title"] == "第一章：Python简介"
        assert data["chapters"][1]["title"] == "第二章：高级特性"
        assert data["chapters"][0]["sort_order"] == 1
        assert data["chapters"][1]["sort_order"] == 2
    
    def test_get_chapter_content_api(self, sample_course_with_chapters):
        """
        TDD Test 17: 测试获取章节学习内容API ⭐
        这是v1.0的核心在线学习功能
        """
        course, chapters = sample_course_with_chapters
        chapter_id = chapters[0].id
        
        # 执行：获取章节学习内容
        response = client.get(f"/api/v1/courses/{course.id}/chapters/{chapter_id}")
        
        # 断言：验证章节学习内容
        assert response.status_code == 200
        
        data = response.json()
        assert data["id"] == chapter_id
        assert data["title"] == "第一章：Python简介"
        assert data["video_url"] == "/videos/python-intro.mp4"
        assert data["video_duration"] == 600
        assert data["video_cover"] == "/images/python-intro-cover.jpg"
        assert data["content_type"] == "video"
        assert data["is_free"] is True
    
    def test_get_free_chapters_api(self, sample_course_with_chapters):
        """
        TDD Test 18: 测试获取免费章节API ⭐
        v1.0支持无门槛访问免费内容
        """
        course, chapters = sample_course_with_chapters
        
        # 执行：获取免费章节
        response = client.get(f"/api/v1/courses/{course.id}/chapters?free_only=true")
        
        # 断言：验证免费章节
        assert response.status_code == 200
        
        data = response.json()
        assert "chapters" in data
        assert len(data["chapters"]) == 1  # 只有一个免费章节
        assert data["chapters"][0]["is_free"] is True
        assert data["chapters"][0]["title"] == "第一章：Python简介"


class TestVideoAPI:
    """Video API的TDD测试 ⭐ v1.0核心功能"""
    
    def test_get_video_stream_api(self):
        """
        TDD Test 19: 测试获取视频流API ⭐
        """
        # 执行：获取视频流信息
        response = client.get("/api/v1/videos/python-intro.mp4/stream")
        
        # 断言：验证视频流信息
        assert response.status_code == 200
        
        data = response.json()
        assert "stream_url" in data
        assert "duration" in data
        assert "format" in data
        assert data["format"] == "mp4"
    
    def test_get_video_info_api(self):
        """
        TDD Test 20: 测试获取视频信息API
        """
        # 执行：获取视频信息
        response = client.get("/api/v1/videos/python-intro.mp4/info")
        
        # 断言：验证视频信息
        assert response.status_code == 200
        
        data = response.json()
        assert "duration" in data
        assert "format" in data
        assert "resolution" in data
        assert "bitrate" in data