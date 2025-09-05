"""
File Service数据模型定义
"""
from sqlalchemy import Column, Integer, String, Text, BigInteger, DateTime
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.sql import func

Base = declarative_base()


class FileRecord(Base):
    """文件记录模型"""
    __tablename__ = "files"
    
    # 基础字段
    id = Column(Integer, primary_key=True, index=True)
    original_filename = Column(String(255), nullable=False)  # 原始文件名
    stored_filename = Column(String(255), nullable=False)    # 存储文件名
    file_path = Column(String(500), nullable=False)          # 文件路径
    file_size = Column(BigInteger, nullable=False)           # 文件大小(字节)
    content_type = Column(String(100), nullable=False)       # MIME类型
    file_type = Column(String(50), nullable=False)           # 文件类型(video/image/audio)
    
    # 视频相关字段
    thumbnail_path = Column(String(500))                     # 缩略图路径
    duration = Column(Integer, default=0)                    # 视频时长(秒)
    width = Column(Integer)                                  # 视频宽度
    height = Column(Integer)                                 # 视频高度
    
    # 状态和时间戳
    status = Column(Integer, default=1)                      # 1:正常, 0:删除
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    
    def __init__(self, **kwargs):
        """初始化FileRecord对象"""
        for key, value in kwargs.items():
            if hasattr(self, key):
                setattr(self, key, value)