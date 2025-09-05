"""
文件服务业务逻辑 ⭐ v1.0核心文件管理功能
"""
import os
import hashlib
from datetime import datetime
from typing import Optional


class FileService:
    """文件服务类"""
    
    def __init__(self):
        # 文件存储基础路径（测试环境使用临时目录）
        self.upload_base_path = os.getenv("UPLOAD_PATH", "/tmp/test_uploads")
        # 支持的文件类型
        self.allowed_extensions = {
            "video": [".mp4", ".avi", ".mov", ".wmv", ".flv"],
            "image": [".jpg", ".jpeg", ".png", ".gif", ".webp"],
            "audio": [".mp3", ".wav", ".aac", ".flac"]
        }
        # 最大文件大小 (100MB)
        self.max_file_size = 100 * 1024 * 1024
    
    def save_file(self, file_content: bytes, filename: str) -> Optional[str]:
        """
        保存文件到本地存储 ⭐
        
        Args:
            file_content: 文件内容
            filename: 原始文件名
            
        Returns:
            保存后的文件路径
        """
        if not self.validate_file(filename, self._get_content_type(filename)):
            return None
        
        # 生成存储文件名（避免重复）
        stored_filename = self._generate_stored_filename(filename)
        
        # 确定文件类型和存储路径
        file_type = self._get_file_type(filename)
        file_dir = os.path.join(self.upload_base_path, f"{file_type}s")
        file_path = os.path.join(file_dir, stored_filename)
        
        # 确保目录存在
        os.makedirs(file_dir, exist_ok=True)
        
        # 保存文件
        try:
            with open(file_path, 'wb') as f:
                f.write(file_content)
            return file_path
        except Exception:
            return None
    
    def validate_file(self, filename: str, content_type: str) -> bool:
        """
        验证文件是否有效 ⭐
        
        Args:
            filename: 文件名
            content_type: 文件MIME类型
            
        Returns:
            是否有效
        """
        # 检查文件名长度
        if len(filename) > 255:
            return False
        
        # 检查文件扩展名
        file_ext = os.path.splitext(filename)[1].lower()
        
        # 检查是否为允许的文件类型
        for file_type, extensions in self.allowed_extensions.items():
            if file_ext in extensions:
                return True
        
        return False
    
    def validate_file_size(self, file_path: str) -> bool:
        """
        验证文件大小
        
        Args:
            file_path: 文件路径
            
        Returns:
            是否在允许范围内
        """
        try:
            file_size = os.path.getsize(file_path)
            return file_size <= self.max_file_size
        except OSError:
            return False
    
    def _generate_stored_filename(self, original_filename: str) -> str:
        """
        生成存储文件名（包含时间戳，避免重复）
        """
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        name, ext = os.path.splitext(original_filename)
        return f"{timestamp}_{name}{ext}"
    
    def _get_file_type(self, filename: str) -> str:
        """
        根据文件名获取文件类型
        """
        file_ext = os.path.splitext(filename)[1].lower()
        
        for file_type, extensions in self.allowed_extensions.items():
            if file_ext in extensions:
                return file_type
        
        return "unknown"
    
    def _get_content_type(self, filename: str) -> str:
        """
        根据文件名推断MIME类型
        """
        file_ext = os.path.splitext(filename)[1].lower()
        
        content_types = {
            ".mp4": "video/mp4",
            ".avi": "video/avi",
            ".mov": "video/quicktime",
            ".jpg": "image/jpeg",
            ".jpeg": "image/jpeg",
            ".png": "image/png",
            ".gif": "image/gif",
            ".mp3": "audio/mp3",
            ".wav": "audio/wav"
        }
        
        return content_types.get(file_ext, "application/octet-stream")