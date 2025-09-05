"""
视频服务 ⭐ v1.0核心在线学习功能
"""
import os
from typing import Dict, Optional


class VideoService:
    """视频服务类"""
    
    def __init__(self):
        # 视频文件基础路径
        self.video_base_path = "/app/videos"
        # 视频流基础URL
        self.stream_base_url = "http://localhost:8007/api/v1/videos"
    
    def get_stream_info(self, video_path: str) -> Optional[Dict]:
        """
        获取视频流信息
        
        Args:
            video_path: 视频文件路径
            
        Returns:
            视频流信息字典
        """
        if not self.validate_video_file(video_path):
            return None
        
        # 提取文件名
        filename = os.path.basename(video_path)
        
        return {
            "stream_url": f"{self.stream_base_url}/stream/{filename}",
            "duration": self._get_video_duration(video_path),
            "format": self._get_video_format(video_path),
            "resolution": "720p",  # 默认分辨率
            "bitrate": "1000k"     # 默认比特率
        }
    
    def get_stream_url(self, video_path: str) -> Optional[str]:
        """
        获取视频流URL ⭐
        
        Args:
            video_path: 视频文件路径
            
        Returns:
            视频流URL
        """
        if not self.validate_video_file(video_path):
            return None
        
        filename = os.path.basename(video_path)
        return f"{self.stream_base_url}/stream/{filename}"
    
    def validate_video_file(self, video_path: str) -> bool:
        """
        验证视频文件是否存在且有效
        
        Args:
            video_path: 视频文件路径
            
        Returns:
            是否有效
        """
        # 检查文件是否存在
        full_path = os.path.join(self.video_base_path, video_path.lstrip('/'))
        return os.path.exists(full_path)
    
    def _get_video_duration(self, video_path: str) -> int:
        """
        获取视频时长（秒）
        实际项目中会使用ffprobe等工具获取真实时长
        这里返回模拟值用于测试
        """
        # 模拟根据文件名返回时长
        if "intro" in video_path:
            return 600  # 10分钟
        elif "setup" in video_path:
            return 900  # 15分钟
        else:
            return 1800  # 30分钟
    
    def _get_video_format(self, video_path: str) -> str:
        """
        获取视频格式
        """
        ext = os.path.splitext(video_path)[1].lower()
        return ext[1:] if ext else "mp4"