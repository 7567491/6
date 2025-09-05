"""
视频相关API ⭐ v1.0核心在线学习功能
"""
from fastapi import APIRouter, HTTPException
from ...services import VideoService
from ...schemas import VideoStreamResponse, VideoInfoResponse


router = APIRouter()


@router.get("/videos/{video_filename}/stream", response_model=VideoStreamResponse)
async def get_video_stream(video_filename: str):
    """
    获取视频流信息 ⭐ v1.0核心功能
    
    用于前端视频播放器获取视频流URL和相关信息
    """
    service = VideoService()
    video_path = f"/videos/{video_filename}"
    
    stream_info = service.get_stream_info(video_path)
    
    if stream_info is None:
        raise HTTPException(status_code=404, detail="Video not found")
    
    return VideoStreamResponse(**stream_info)


@router.get("/videos/{video_filename}/info", response_model=VideoInfoResponse)
async def get_video_info(video_filename: str):
    """
    获取视频信息
    
    获取视频的基本信息（时长、格式等）
    """
    service = VideoService()
    video_path = f"/videos/{video_filename}"
    
    stream_info = service.get_stream_info(video_path)
    
    if stream_info is None:
        raise HTTPException(status_code=404, detail="Video not found")
    
    # 返回视频信息（不包含stream_url）
    return VideoInfoResponse(
        duration=stream_info["duration"],
        format=stream_info["format"], 
        resolution=stream_info["resolution"],
        bitrate=stream_info["bitrate"]
    )