"""
File Service TDD测试 ⭐ v1.0核心文件管理功能
测试顺序：
1. test_file_storage_local() ← File Service开始
2. test_file_validation()
3. test_video_file_handling()
4. test_file_upload_api()
5. test_file_download_api()
"""
import pytest
import os
import tempfile
from unittest.mock import Mock, patch, mock_open

from app.services import FileService, VideoFileService
from app.models import FileRecord


class TestFileService:
    """FileService的TDD测试"""
    
    def test_file_storage_local(self):
        """
        TDD Test 1: 测试本地文件存储 ⭐
        Red阶段：FileService还不存在，应该失败
        """
        service = FileService()
        
        # 模拟文件上传
        file_content = b"test video content"
        filename = "test_video.mp4"
        
        # 执行：保存文件
        file_path = service.save_file(file_content, filename)
        
        # 断言：验证文件保存
        assert file_path is not None
        assert "test_video" in file_path
        assert file_path.endswith(".mp4")
        assert "/videos/" in file_path
    
    def test_file_validation(self):
        """
        TDD Test 2: 测试文件验证 ⭐
        """
        service = FileService()
        
        # 测试：有效的视频文件
        is_valid = service.validate_file("test.mp4", "video/mp4")
        assert is_valid is True
        
        # 测试：有效的图片文件
        is_valid = service.validate_file("cover.jpg", "image/jpeg")
        assert is_valid is True
        
        # 测试：无效的文件类型
        is_valid = service.validate_file("test.exe", "application/exe")
        assert is_valid is False
        
        # 测试：文件名过长
        long_filename = "a" * 300 + ".mp4"
        is_valid = service.validate_file(long_filename, "video/mp4")
        assert is_valid is False
    
    @patch('app.services.file_service.os.path.getsize')
    def test_file_size_validation(self, mock_getsize):
        """
        TDD Test 3: 测试文件大小验证
        """
        service = FileService()
        
        # 测试：正常大小的文件 (10MB)
        mock_getsize.return_value = 10 * 1024 * 1024
        is_valid = service.validate_file_size("/path/to/file.mp4")
        assert is_valid is True
        
        # 测试：过大的文件 (200MB)
        mock_getsize.return_value = 200 * 1024 * 1024
        is_valid = service.validate_file_size("/path/to/large_file.mp4")
        assert is_valid is False


class TestVideoFileService:
    """VideoFileService的TDD测试 ⭐ v1.0核心视频功能"""
    
    def test_video_file_handling(self):
        """
        TDD Test 4: 测试视频文件处理 ⭐
        """
        service = VideoFileService()
        
        # 模拟视频文件
        video_content = b"fake video content for testing"
        filename = "python-course.mp4"
        
        # 执行：处理视频文件
        result = service.process_video_file(video_content, filename)
        
        # 断言：验证处理结果
        assert result is not None
        assert "file_path" in result
        assert "thumbnail" in result
        assert "duration" in result
        assert "size" in result
    
    def test_video_thumbnail_generation(self):
        """
        TDD Test 5: 测试视频缩略图生成
        """
        service = VideoFileService()
        video_path = "/app/uploads/videos/python-course.mp4"
        
        # 执行：生成缩略图
        thumbnail_path = service.generate_thumbnail(video_path)
        
        # 断言：验证缩略图路径
        assert thumbnail_path is not None
        assert thumbnail_path.endswith(".jpg")
        assert "thumbnails" in thumbnail_path
    
    @patch('app.services.video_service.subprocess.run')
    def test_video_info_extraction(self, mock_subprocess):
        """
        TDD Test 6: 测试视频信息提取
        """
        # Mock ffprobe输出
        mock_subprocess.return_value.stdout = '{"duration": "600.0", "width": 1280, "height": 720}'
        mock_subprocess.return_value.returncode = 0
        
        service = VideoFileService()
        video_path = "/app/uploads/videos/test.mp4"
        
        # 执行：提取视频信息
        info = service.extract_video_info(video_path)
        
        # 断言：验证视频信息
        assert info is not None
        assert info["duration"] == 600.0
        assert info["width"] == 1280
        assert info["height"] == 720


class TestFileRecord:
    """FileRecord数据模型测试"""
    
    def test_file_record_creation(self):
        """
        TDD Test 7: 测试文件记录创建
        """
        # 准备测试数据
        record_data = {
            "original_filename": "python-course.mp4",
            "stored_filename": "20250905_python-course.mp4", 
            "file_path": "/app/uploads/videos/20250905_python-course.mp4",
            "file_size": 1024000,  # 1MB
            "content_type": "video/mp4",
            "file_type": "video",
            "thumbnail_path": "/app/uploads/thumbnails/20250905_python-course.jpg"
        }
        
        # 执行：创建文件记录
        record = FileRecord(**record_data)
        
        # 断言：验证文件记录属性
        assert record.original_filename == "python-course.mp4"
        assert record.stored_filename == "20250905_python-course.mp4"
        assert record.file_path == "/app/uploads/videos/20250905_python-course.mp4"
        assert record.file_size == 1024000
        assert record.content_type == "video/mp4"
        assert record.file_type == "video"
        assert record.thumbnail_path == "/app/uploads/thumbnails/20250905_python-course.jpg"