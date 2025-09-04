{extend name="public/container"}
{block name='head_top'}
<link rel="stylesheet" href="https://g.alicdn.com/apsara-media-box/imp-web-player/2.16.3/skins/default/aliplayer-min.css" />
<style>
    .layui-table {
        width: 100%!important;
    }

    .layui-form-item .special-label {
        width: 50px;
        float: left;
        height: 30px;
        line-height: 38px;
        margin-left: 10px;
        margin-top: 5px;
        border-radius: 5px;
        background-color: #10952a;
        text-align: center;
    }

    .layui-form-item .special-label i {
        display: inline-block;
        width: 18px;
        height: 18px;
        font-size: 18px;
        color: #fff;
    }

    .layui-form-item .label-box {
        border: 1px solid;
        border-radius: 10px;
        position: relative;
        padding: 10px;
        height: 30px;
        color: #fff;
        background-color: #393D49;
        text-align: center;
        cursor: pointer;
        display: inline-block;
        line-height: 10px;
    }

    .layui-form-item .label-box p {
        line-height: inherit;
    }

    .edui-default .edui-for-image .edui-icon {
        background-position: -380px 0px;
    }

    .layui-tab-title .layui-this:after {
        border-bottom-color: #fff!important;
    }
    .upload-image-box .mask p{width: 50px;}

    .layui-form-switch, .layui-form-onswitch {
        box-sizing: content-box;
    }
</style>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/third-party/zeroclipboard/ZeroClipboard.js"></script>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/ueditor.config.js"></script>
<script type="text/javascript" charset="utf-8" src="{__ADMIN_PATH}plug/ueditor/ueditor.all.min.js"></script>
<script src="{__ADMIN_PATH}plug/aliyun-upload-sdk/aliyun-upload-sdk-1.5.0.min.js"></script>
<script src="{__ADMIN_PATH}plug/aliyun-upload-sdk/lib/es6-promise.min.js"></script>
<script src="{__ADMIN_PATH}plug/aliyun-upload-sdk/lib/aliyun-oss-sdk-5.3.1.min.js"></script>
<script src="https://g.alicdn.com/de/prismplayer/2.9.17/aliplayer-min.js"></script>
{/block}
{block name="content"}
<div v-cloak id="app" class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            <form class="layui-form" action="">
                <div class="layui-form-item top-save">
                    <div class="layui-input-block">
                        <button class="layui-btn layui-btn-normal" type="button" lay-filter="formDemo" @click="save">{$id ?
                            '确认修改':'立即提交'}
                        </button>
                        <button class="layui-btn layui-btn-primary clone" type="button" @click="clone_form">取消
                        </button>
                    </div>
                </div>
                <div class="layui-tab" lay-filter="tab">
                    <ul class="layui-tab-title">
                        <li class="layui-this">基本设置</li>
                        <li>添加内容</li>
                        <li>价格设置</li>
                    </ul>
                    <div class="layui-tab-content">
                        <div class="layui-tab-item layui-show">
                            <div class="layui-form-item required">
                                <label class="layui-form-label">课程名称：</label>
                                <div class="layui-input-block">
                                    <input type="text" name="title" required v-model.trim="formData.title" autocomplete="off" placeholder="请输入课程名称" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">课程分类：</label>
                                <div class="layui-input-block" style="width: 300px">
                                    <select name="subject_id" v-model="formData.subject_id" lay-search="" lay-filter="subject_id" lay-verify="required">
                                        <option value="0">请选分类</option>
                                        <option  v-for="item in subject_list" :value="item.id" :disabled="item.grade_id==0 ? true : false">{{item.html}}{{item.name}}</option>
                                    </select>
                                </div>
                                <div class="layui-form-mid layui-word-aux">一级分类不可选中，需二级分类</div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">讲师选择：</label>
                                <div class="layui-input-block" style="width: 300px">
                                    <select name="subject_id" v-model="formData.lecturer_id" lay-search="" lay-filter="lecturer_id">
                                        <option value="0">请选讲师</option>
                                        <option  :value="item.id"  v-for="item in lecturer_list">{{item.lecturer_name}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">课程排序：</label>
                                <div class="layui-input-inline">
                                    <input type="number" name="sort" v-model="formData.sort" autocomplete="off" min="0" class="layui-input" v-sort>
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">课程标签：</label>
                                <div class="layui-input-inline">
                                    <input type="text" v-model="label" name="price_min" placeholder="最多6个字" autocomplete="off" maxlength="6" class="layui-input">
                                </div>
                                <div class="layui-input-inline" style="width: auto;">
                                    <button type="button" class="layui-btn layui-btn-normal" @click="addLabrl" >
                                        <i class="layui-icon">&#xe654;</i>
                                    </button>
                                </div>
                                <div class="layui-form-mid layui-word-aux">输入标签名称后点击”+“号按钮添加；最多写入6个字；点击标签即可删除</div>
                            </div>
                            <div v-if="formData.label.length" class="layui-form-item">
                                <div class="layui-input-block">
                                    <button v-for="(item,index) in formData.label" :key="index" type="button" class="layui-btn layui-btn-normal layui-btn-sm" @click="delLabel(index)">{{item}}</button>
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">课程封面：（800*450）</label>
                                <div class="layui-input-block">
                                    <div class="upload-image-box" v-if="formData.image" @mouseenter="mask.image = true" @mouseleave="mask.image = false">
                                        <img :src="formData.image" alt="">
                                        <div class="mask" v-show="mask.image" style="display: block">
                                            <p>
                                                <i class="fa fa-eye" @click="look(formData.image)"></i>
                                                <i class="fa fa-trash-o" @click="delect('image')"></i>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="upload-image" v-show="!formData.image" @click="upload('image')">
                                        <div class="fiexd"><i class="fa fa-plus"></i></div>
                                        <p>选择图片</p>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">推广海报：（600*740）</label>
                                <div class="layui-input-block">
                                    <div class="upload-image-box" v-if="formData.poster_image" @mouseenter="mask.poster_image = true" @mouseleave="mask.poster_image = false">
                                        <img :src="formData.poster_image" alt="">
                                        <div class="mask" v-show="mask.poster_image" style="display: block">
                                            <p><i class="fa fa-eye" @click="look(formData.poster_image)"></i>
                                                <i class="fa fa-trash-o" @click="delect('poster_image')"></i></p>
                                        </div>
                                    </div>
                                    <div class="upload-image" v-show="!formData.poster_image" @click="upload('poster_image')">
                                        <div class="fiexd"><i class="fa fa-plus"></i></div>
                                        <p>选择图片</p>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">客服二维码：（200*200）</label>
                                <div class="layui-input-block">
                                    <div class="upload-image-box" v-if="formData.service_code" @mouseenter="mask.service_code = true" @mouseleave="mask.service_code = false">
                                        <img :src="formData.service_code" alt="">
                                        <div class="mask" v-show="mask.service_code" style="display: block">
                                            <p><i class="fa fa-eye" @click="look(formData.service_code)"></i>
                                                <i class="fa fa-trash-o" @click="delect('service_code')"></i></p>
                                        </div>
                                    </div>
                                    <div class="upload-image" v-show="!formData.service_code" @click="upload('service_code')">
                                        <div class="fiexd"><i class="fa fa-plus"></i></div>
                                        <p>选择图片</p>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item required">
                                <label class="layui-form-label">课程详情：</label>
                                <div class="layui-input-block">
                                    <textarea id="editor2">{{formData.abstract}}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="layui-tab-item">
                            <div class="layui-form-item">
                                <label class="layui-form-label" >课节类型：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="light_type" lay-filter="light_type" v-model="formData.light_type" value="1" title="图文">
                                    <input type="radio" name="light_type" lay-filter="light_type" v-model="formData.light_type" value="2" title="音频">
                                    <input type="radio" name="light_type" lay-filter="light_type" v-model="formData.light_type" value="3" title="视频">
                                </div>
                            </div>
                            <div class="layui-form-item"  v-show="formData.light_type==3">
                                <div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">视频素材：</label>
                                        <div class="layui-input-inline" style="width: 600px">
                                            <input v-model.trim="link" type="text" name="title" autocomplete="off" placeholder="请输入视频链接" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <div class="layui-btn-container">
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-normal" @click="confirmAdd()" v-show="is_upload==false && formData.video_type!=4 && formData.video_type!=1">确认添加</button>
                                            <label class="layui-btn layui-btn-sm layui-btn-normal" v-show="is_upload==false && formData.video_type!=4 && formData.video_type!=1">
                                                <input style="display: none;" type="file" id="ossupload_video" class="ossupload">上传视频
                                            </label>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-danger" v-show="is_upload && formData.video_type!=4 && formData.video_type!=1" @click="delVideo()">删除</button>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-warm" v-show="link && formData.video_type!=4 && formData.video_type!=1" @click="previewSource">预览</button>
                                            <button v-show="link && formData.video_type!=4 && formData.video_type!=1" type="button" class="layui-btn layui-btn-sm" @click="reUpload">重传</button>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-left: 120px;" class="layui-word-aux">输入链接将视为添加视频直接添加，请确保视频链接的正确性</div>
                                <div style="margin: 15px 0 0 120px;" v-show="is_video">
                                    <div style="width: 600px;" class="layui-progress">
                                        <div class="layui-progress-bar layui-bg-blue" :style="{width: videoWidth + '%'}"></div>
                                    </div>
                                    <div style="margin: 15px 0 0;" class="layui-btn-container">
                                        <button type="button" class="layui-btn layui-btn-sm layui-btn-danger" v-show="demand_switch==2 && is_video" @click="cancelUpload">取消</button>
                                        <button type="button" class="authUpload layui-btn layui-btn-sm layui-btn-danger" v-show="demand_switch==1 && is_video">开始上传</button>
                                        <button type="button" class="pauseUpload layui-btn layui-btn-sm layui-btn-danger" v-show="demand_switch==1 && is_video">暂停</button>
                                        <button type="button" class="resumeUpload layui-btn layui-btn-sm layui-btn-danger" v-show="is_suspend">恢复上传</button>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item"  v-show="(formData.light_type==3 || formData.light_type==2) && formData.video_type!=4 && formData.video_type!=1">
                                <label class="layui-form-label">视频ID：</label>
                                <div class="layui-input-block">
                                    <input type="text" name="videoId" v-model="formData.videoId" style="width:50%;display:inline-block;margin-right: 10px;" autocomplete="off" placeholder="请输入视频ID" class="layui-input">
                                </div>
                                <div class="layui-form-mid layui-word-aux" style="margin-left: 0;">输入阿里云点播平台上传视频得到的videoID</div>
                            </div>
                            <div class="layui-form-item"  v-show="formData.light_type==3 || formData.light_type==2">
                                <label class="layui-form-label">音视频模式：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="video_type" lay-filter="video_type" v-model="formData.video_type" value="1" title="普通模式（视频链接必填）">
                                    <input type="radio" name="video_type" lay-filter="video_type" v-model="formData.video_type" value="2" title="阿里云点播平台非加密模式（视频ID必填）">
                                    <input type="radio" name="video_type" lay-filter="video_type" v-model="formData.video_type" value="3" title="阿里云点播平台加密模式（视频ID必填）">
                                    {if condition="config('fxdisk')"}
                                    <input type="radio" name="video_type" lay-filter="video_type" v-model="formData.video_type" value="4" title="免流量模式">
                                    {/if}
                                </div>
                                <div class="layui-form-mid layui-word-aux" style="margin-left: 0;">
                                    普通模式：必须填写视频链接，不必填写视频ID，可填写任意来源的h264编码的mp4格式视频链接；<br />
                                    阿里云点播平台非加密模式：必须填写视频ID，视频链接可不填，视频ID从<a href="https://vod.console.aliyun.com/" target="_blank">阿里云点播平台</a>获取，<strong>建议使用“视频链接”选项右侧的“上传视频”，这会将视频自动上传到点播平台并自动获取视频ID</strong>，为保证视频安全，该模式不保存视频链接；<br/ >
                                    阿里云点播平台加密模式：加密模式必须先到阿里云点播平台按<a href="https://help.aliyun.com/zh/vod/user-guide/alibaba-cloud-proprietary-cryptography?spm=a2c4g.11186623.0.0.fadd77ba5ohSkI#section-nyr-2re-hzh" target="_blank">这篇教程</a>配置好加密转码模板，必须填写视频ID，视频链接可不填，视频ID从<a href="https://vod.console.aliyun.com/" target="_blank">阿里云点播平台</a>获取，<strong>建议使用“视频链接”选项右侧的“上传视频”，这会将视频自动上传到点播平台并自动获取视频ID</strong>，为保证视频安全，该模式不保存视频链接；<br/ >
                                    {if condition="config('fxdisk')"}
                                    免流量模式：必须填写视频链接，视频ID不必填写，视频链接从凡星免流系统中复制获取，形式为斜杠开头的相对地址，如：/onedrive/video/fx.mp4，该模式暂不支持后台上传和预览；
                                    {/if}
                                </div>
                            </div>
                            <div class="layui-form-item"  v-show="formData.light_type==2">
                                <div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">音频素材：</label>
                                        <div class="layui-input-inline" style="width: 600px">
                                            <input v-model.trim="link" type="text" name="title" autocomplete="off" placeholder="请输入音频链接" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <div class="layui-btn-container">
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-normal" @click="confirmAdd()" v-show="is_upload==false">确认添加</button>
                                            <label class="layui-btn layui-btn-sm layui-btn-normal" v-show="is_upload==false">
                                                <input style="display: none;" type="file" id="ossupload_video" class="ossupload">上传音频
                                            </label>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-danger" v-show="is_upload" @click="delVideo">删除</button>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-warm" v-show="link" @click="previewSource">预览</button>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-left: 120px;" class="layui-word-aux">输入链接将视为添加音频直接添加，请确保音频链接的正确性</div>
                                <div style="margin: 15px 0 0 120px;" v-show="is_video">
                                    <div style="width: 600px;" class="layui-progress">
                                        <div class="layui-progress-bar layui-bg-blue" :style="{width: videoWidth + '%'}"></div>
                                    </div>
                                    <div style="margin: 15px 0 0;" class="layui-btn-container">
                                        <button type="button" class="layui-btn layui-btn-sm layui-btn-danger" v-show="demand_switch==2 && is_video" @click="cancelUpload">取消</button>
                                        <button type="button" class="authUpload layui-btn layui-btn-sm layui-btn-danger" v-show="demand_switch==1 && is_video">开始上传</button>
                                        <button type="button" class="pauseUpload layui-btn layui-btn-sm layui-btn-danger" v-show="demand_switch==1 && is_video">暂停</button>
                                        <button type="button" class="resumeUpload layui-btn layui-btn-sm layui-btn-danger" v-show="is_suspend">恢复上传</button>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">试看：</label>
                                <div class="layui-input-block">
                                    <input :checked="formData.is_try" type="checkbox" name="is_try" lay-skin="switch" lay-text="开启|关闭" lay-filter="isTry">
                                </div>
                            </div>
                            <div v-show="formData.is_try && formData.light_type != 1" class="layui-form-item required">
                                <label class="layui-form-label">试看时间：</label>
                                <div class="layui-input-inline">
                                    <input v-model.number="formData.try_time" type="number" min="0" max="5" class="layui-input">
                                </div>
                                <div class="layui-form-mid layui-word-aux">分钟</div>
                            </div>
                            <div v-show="formData.is_try && formData.light_type == 1" class="layui-form-item required">
                                <label class="layui-form-label">试看内容：</label>
                                <div class="layui-input-block">
                                    <textarea id="editor3">{{ formData.try_content }}</textarea>
                                </div>
                            </div>
                            <div v-show="formData.light_type == 1" class="layui-form-item required" >
                                <label class="layui-form-label">课节内容：</label>
                                <div class="layui-input-block">
                                    <textarea id="editor1">{{formData.content}}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="layui-tab-item">
                            <div class="layui-form-item">
                                <label class="layui-form-label">仅会员可见：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="is_mer_visible" lay-filter="is_mer_visible" v-model="formData.is_mer_visible" value="1" title="是">
                                    <input type="radio" name="is_mer_visible" lay-filter="is_mer_visible" v-model="formData.is_mer_visible" value="0" title="否">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">有效期：</label>
                                <div class="layui-input-inline">
                                    <input type="number" name="validity" lay-verify="number" v-model="formData.validity" autocomplete="off" class="layui-input" min="0" max="99999">
                                </div>
                                <div class="layui-form-mid">天</div>
                                <div class="layui-form-mid layui-word-aux" style="float: none;display: inline-block;">有效期为用户购买后可以观看时间，0为时间不限；</div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">付费方式：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="pay_type" lay-filter="pay_type" v-model="formData.pay_type" value="1" title="付费">
                                    <input type="radio" name="pay_type" lay-filter="pay_type" v-model="formData.pay_type" value="0" title="免费">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.pay_type == 1">
                                <label class="layui-form-label">购买金额：</label>
                                <div class="layui-input-inline">
                                    <input type="number" name="money" lay-verify="number" v-model="formData.money" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">会员付费方式：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="member_pay_type" lay-filter="member_pay_type" v-model="formData.member_pay_type" value="1" title="付费">
                                    <input type="radio" name="member_pay_type" lay-filter="member_pay_type" v-model="formData.member_pay_type" value="0" title="免费">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.member_pay_type == 1">
                                <label class="layui-form-label">会员购买金额：</label>
                                <div class="layui-input-inline">
                                    <input type="number" name="member_money" lay-verify="number" v-model="formData.member_money" autocomplete="off" class="layui-input" min="0">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">单独分销：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="is_alone" lay-filter="is_alone" v-model="formData.is_alone" :disabled="formData.pay_type == 0" value="1" title="开启">
                                    <input type="radio" name="is_alone" lay-filter="is_alone" v-model="formData.is_alone" :disabled="formData.pay_type == 0" value="0" title="关闭">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.is_alone == 1">
                                <label class="layui-form-label">一级返佣比例[5%=5]：</label>
                                <div class="layui-input-block">
                                    <input style="width: 300px" type="number" name="brokerage_ratio" lay-verify="number" v-model="formData.brokerage_ratio" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.is_alone == 1">
                                <label class="layui-form-label">二级返佣比例[5%=5]：</label>
                                <div class="layui-input-block">
                                    <input style="width: 300px" type="number" name="brokerage_two" lay-verify="number" v-model="formData.brokerage_two" autocomplete="off" class="layui-input" min="0">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.pay_type == 1">
                                <label class="layui-form-label">拼团状态：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="is_pink" lay-filter="is_pink" v-model="formData.is_pink" value="0" title="关闭" checked="">
                                    <input type="radio" name="is_pink" lay-filter="is_pink" v-model="formData.is_pink" value="1" title="开启">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.is_pink">
                                <div class="layui-inline">
                                    <label class="layui-form-label">拼团金额：</label>
                                    <div class="layui-input-inline">
                                        <input type="number" name="pink_money" v-model="formData.pink_money" autocomplete="off" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">拼团人数：</label>
                                    <div class="layui-input-inline">
                                        <input type="number" name="pink_number" v-model="formData.pink_number" autocomplete="off" class="layui-input">
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.is_pink">
                                <div class="layui-inline">
                                    <label class="layui-form-label">开始时间：</label>
                                    <div class="layui-input-inline">
                                        <input type="text" name="pink_strar_time" v-model="formData.pink_strar_time" id="start_time" autocomplete="off" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">结束时间：</label>
                                    <div class="layui-input-inline">
                                        <input type="text" name="pink_end_time" v-model="formData.pink_end_time" id="end_time" autocomplete="off" class="layui-input">
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.is_pink">
                                <label class="layui-form-label">拼团时间：</label>
                                <div class="layui-input-inline">
                                    <input type="number" v-model="formData.pink_time" autocomplete="off" class="layui-input">
                                </div>
                                <div class="layui-form-mid">小时</div>
                            </div>
                            <div class="layui-form-item" v-show="formData.is_pink">
                                <label class="layui-form-label">模拟成团：</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="is_fake_pink" lay-filter="is_fake_pink" v-model="formData.is_fake_pink" value="1" title="开启" checked="">
                                    <input type="radio" name="is_fake_pink" lay-filter="is_fake_pink" v-model="formData.is_fake_pink" value="0" title="关闭">
                                </div>
                            </div>
                            <div class="layui-form-item" v-show="formData.is_fake_pink && formData.is_pink">
                                <label class="layui-form-label">补齐比例：</label>
                                <div class="layui-input-inline">
                                    <input type="number" v-model="formData.fake_pink_number" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn layui-btn-normal" type="button" lay-filter="formDemo" @click="save">{$id ?
                            '确认修改':'立即提交'}
                        </button>
                        <button class="layui-btn layui-btn-primary clone" type="button" @click="clone_form">取消
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="prism-player" id="J_prismPlayer"></div>
</div>
<script type="text/javascript" src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name='script'}
<script>
    var id = {$id}, special =<?=isset($special) ? $special : "{}"?>, special_type ="{$special_type}",alicloud_account_id="{$alicloud_account_id}",configuration_item_region="{$configuration_item_region}",demand_switch="{$demand_switch}";
    require(['vue','helper','zh-cn','request','plupload','aliyun-oss','OssUpload'], function (Vue,$h) {
        new Vue({
            el: "#app",
            directives: {
                sort: {
                    bind: function (el, binding, vnode) {
                        var vm = vnode.context;
                        el.addEventListener('change', function () {
                            if (!this.value || this.value < 0) {
                                vm.formData.sort = 0;
                            } else if (this.value > 9999) {
                                vm.formData.sort = 9999;
                            } else {
                                vm.formData.sort = parseInt(this.value);
                            }
                        });
                    }
                }
            },
            data: {
                subject_list: [],
                lecturer_list: [],
                formData: {
                    label: special.label || [],
                    abstract: special.abstract || '',
                    title: special.title || '',
                    subject_id: special.subject_id || 0,
                    lecturer_id: special.lecturer_id || 0,
                    is_light: special.is_light || 0,
                    light_type: special.light_type || 1,
                    image: special.image || '',
                    banner: special.banner || [],
                    poster_image: special.poster_image || '',
                    service_code: special.service_code || '',
                    money: special.money || 0.00,
                    pink_money: special.pink_money || 0.00,
                    pink_number: special.pink_number || 0,
                    pink_strar_time: special.pink_strar_time || '',
                    pink_end_time: special.pink_end_time || '',
                    fake_pink_number: special.fake_pink_number || 0,
                    sort: special.sort || 0,
                    is_mer_visible: special.is_mer_visible || 0,
                    is_pink: special.is_pink || 0,
                    is_fake_pink: special.is_fake_pink || 1,
                    fake_sales: special.fake_sales || 0,
                    validity: special.validity || 0,
                    browse_count: special.browse_count || 0,
                    pink_time: special.pink_time || 0,
                    pay_type: special.pay_type == 1 ? 1 : 0,
                    member_pay_type: special.member_pay_type == 1 ? 1 : 0,
                    member_money: special.member_money || 0.00,
                    content: special.singleProfile ? (special.singleProfile.content || '') : '',
                    link:special.singleProfile ? (special.singleProfile.link || '') : '',
                    videoId:special.singleProfile ? (special.singleProfile.videoId || '') : '',
                    video_type:special.singleProfile && special.singleProfile.video_type ? special.singleProfile.video_type : 1,
                    file_type:special.singleProfile ? (special.singleProfile.file_type || '') : '',
                    file_name:special.singleProfile ? (special.singleProfile.file_name || '') : '',
                    is_alone:special.pay_type == 1 ? (special.is_alone == 1 ? 1 : 0) : 0,
                    brokerage_ratio:special.pay_type == 1 ? (special.brokerage_ratio || 0) : 0,
                    brokerage_two:special.pay_type == 1 ? (special.brokerage_two || 0) : 0,
                    is_try: special.singleProfile ? (special.singleProfile.is_try || 0) : 0,
                    try_time: special.singleProfile ? (special.singleProfile.try_time || 0) : 0,
                    try_content: special.singleProfile ? (special.singleProfile.try_content || '') : ''
                },
                demand_switch:demand_switch,
                link: '',
                label: '',
                host: ossUpload.host + '/',
                mask: {
                    poster_image: false,
                    image: false,
                    service_code: false,
                },
                ue: null,
                is_video: false,
                is_upload:false,
                is_suspend:false,
                //上传类型
                mime_types: {
                    Image: "jpg,gif,png,JPG,GIF,PNG",
                    Video: "mp4,MP4",
                    Audio: "mp3,MP3",
                },
                videoWidth: 0,
                uploader: null
            },
            watch:{
                'formData.validity':function (v) {
                    if (v.indexOf('.')!=-1) {
                        return layList.msg('不能输入小数');
                    }
                    if(v<0) return layList.msg('不能小于0');
                    if(v>99999) return layList.msg('不能大于99999');
                },
                link: function (newLink) {
                    this.formData.link = newLink
                }
            },
            methods: {
                reUpload: function() {
                    this.is_upload = false
                    this.link = ''
                    this.formData.link = ''
                    this.formData.videoId = ''
                },
                // 预览音视频
                previewSource: function () {
                    if (this.link.indexOf('http')) {
                        layList.msg('请输入正确的'+ (this.formData.light_type == '2' ? '音' : '视') +'频链接');
                    } else {
                        new Aliplayer({
                            id: 'J_prismPlayer',
                            source: this.link || '',
                            height: '100%',
                            cover: this.formData.light_type == '2' ? '' : this.formData.image,
                            autoplay: false,
                            format: this.formData.light_type == '2' ? 'mp3' : ''
                        }, function (player) {
                            layList.layer.open({
                                type: 1,
                                title: false,
                                resize: false,
                                content: $('#J_prismPlayer'),
                                area: ['500px', '300px'],
                                end: function () {
                                    player.dispose();
                                }
                            }); 
                        });
                    }
                },
                //取消
                cancelUpload: function () {
                    this.uploader.stop();
                    this.is_video = false;
                    this.videoWidth = 0;
                    this.is_upload = false;
                },
                //删除图片
                delect: function (key, index) {
                    var that = this;
                    if (index != undefined) {
                        that.formData[key].splice(index, 1);
                        that.$set(that.formData, key, that.formData[key]);
                    } else {
                        that.$set(that.formData, key, '');
                    }
                },
                //查看图片
                look: function (pic) {
                    parent.$eb.openImage(pic);
                },
                //鼠标移入事件
                enter: function (item) {
                    if (item) {
                        item.is_show = true;
                    } else {
                        this.mask = true;
                    }
                },
                //鼠标移出事件
                leave: function (item) {
                    if (item) {
                        item.is_show = false;
                    } else {
                        this.mask = false;
                    }
                },
                changeIMG: function (key, value, multiple) {
                    if (multiple) {
                        var that = this;
                        value.map(function (v) {
                            that.formData[key].push({pic: v, is_show: false});
                        });
                        this.$set(this.formData, key, this.formData[key]);
                    } else {
                        this.$set(this.formData, key, value);
                    }
                },
                confirmAdd:function(){
                    var that = this;
                    if(that.link.substr(0,7).toLowerCase() == "http://" || that.link.substr(0,8).toLowerCase() == "https://"){
                        that.is_upload=true;
                        that.uploadVideo();
                    }else{
                        if(this.formData.light_type==2){
                            layList.msg('请输入正确的音频链接');
                        }else if(this.formData.light_type==3){
                            layList.msg('请输入正确的视频链接');
                        }
                    }
                },
                uploadVideo: function () {
                    if (this.link.substr(0, 7).toLowerCase() == "http://" || this.link.substr(0, 8).toLowerCase() == "https://") {
                        this.setContent(this.link);
                    } else {
                        layList.msg('请输入正确的视频链接');
                    }
                },
                setContent:function(link){
                    this.formData.link = link;
                },
                //上传图片
                upload: function (key, count) {
                    ossUpload.createFrame('请选择图片', {fodder: key, max_count: count === undefined ? 0 : count},{w:800,h:550});
                },
                //获取分类
                get_subject_list: function () {
                    var that = this;
                    layList.baseGet(layList.U({a: 'get_subject_list'}), function (res) {
                        that.$set(that, 'subject_list', res.data);
                        that.$nextTick(function () {
                            layList.form.render('select');
                        })
                    });
                },
                //获取讲师
                get_lecturer_list: function () {
                    var that = this;
                    layList.baseGet(layList.U({a: 'get_lecturer_list'}), function (res) {
                        that.$set(that, 'lecturer_list', res.data);
                        that.$nextTick(function () {
                            layList.form.render('select');
                        })
                    });
                },
                delLabel: function (index) {
                    this.formData.label.splice(index, 1);
                    this.$set(this.formData, 'label', this.formData.label);
                },
                addLabrl: function () {
                    if (this.label) {
                        if (this.label.length > 6) return layList.msg('您输入的标签字数太长');
                        var length = this.formData.label.length;
                        if (length >= 2) return layList.msg('标签最多添加2个');
                        for (var i = 0; i < length; i++) {
                            if (this.formData.label[i] == this.label) return layList.msg('请勿重复添加');
                        }
                        this.formData.label.push(this.label);
                        this.$set(this.formData, 'label', this.formData.label);
                        this.label = '';
                    }
                },
                save: function () {
                    var that = this;
                    that.formData.content = that.editor1.getContent();
                    that.formData.abstract = that.editor2.getContent();
                    that.formData.try_content = that.editor3.getContent();
                    that.$nextTick(function () {
                    if (!that.formData.title) return layList.msg('请输入课程名称');
                    if (!that.formData.subject_id) return layList.msg('请选择分类');
                    if (!that.formData.label.length) return layList.msg('请输入标签');
                    if (!that.formData.image) return layList.msg('请上传课程封面');
                    if (!that.formData.poster_image) return layList.msg('请上传推广海报');
                    // if (!that.formData.service_code) return layList.msg('请上传客服二维码');
                    if (!that.formData.abstract) return layList.msg('请输入课程简介');
                    if (that.formData.light_type == 1) {
                        if (!that.formData.content) return layList.msg('请编辑内容在进行保存');   
                    } else {
                        if (!that.formData.link && !that.formData.videoId) return layList.msg('请上传素材');
                    }
                    if (that.formData.validity < 0) return layList.msg('课程有效期不能小于0');
                    if (that.formData.validity > 99999) return layList.msg('课程有效期不能大于99999');
                    if ((that.formData.validity+'').indexOf('.')!=-1) return layList.msg('课程有效期不能为小数');
                    if (that.formData.is_pink) {
                        if (!that.formData.pink_money) return layList.msg('请填写拼团金额');
                        if (!that.formData.pink_number) return layList.msg('请填写拼团人数');
                        if (!that.formData.pink_strar_time) return layList.msg('请选择拼团开始时间');
                        if (!that.formData.pink_end_time) return layList.msg('请选择拼团结束时间');
                        if (!that.formData.pink_time) return layList.msg('请填写拼团时间');
                        if (that.formData.is_fake_pink && !that.formData.fake_pink_number) return layList.msg('请填写补齐比例');
                    }
                    if (that.formData.pay_type == 1) {
                        if (!that.formData.money || that.formData.money == 0.00) return layList.msg('请填写购买金额');
                    }
                    if (that.formData.member_pay_type == 1) {
                        if (!that.formData.member_money || that.formData.member_money == 0.00) return layList.msg('请填写会员购买金额');
                    }
                    if (that.formData.is_alone == 1) {
                        if (!that.formData.brokerage_ratio || !that.formData.brokerage_two) return layList.msg('请填写推广人返佣比例');
                    }
                    if (this.formData.is_try) {
                        if (this.formData.light_type == 1) {
                            if (!this.formData.try_content) {
                                layList.msg('请输入试看内容');
                                return;
                            }
                        } else {
                            if (typeof this.formData.try_time == 'number') {
                                if (this.formData.try_time > 30) {
                                    layList.msg('试看时间不能大于30分钟');
                                    return;
                                }
                            } else {
                                layList.msg('请输入正确的试看时间');
                                return;
                            }
                        }
                    }
                    layList.loadFFF();
                    layList.basePost(layList.U({
                        a: 'save_single_special',
                        q: {id: id, special_type: special_type}
                    }), that.formData, function (res) {
                        layList.loadClear();
                        if (parseInt(id) == 0) {
                            if (that.demand_switch == '1') {
                                layList.layer.alert('添加成功。', { closeBtn: 0 }, function (index) {
                                    parent.layer.closeAll();
                                });
                            } else {
                                layList.layer.confirm('添加成功，您要继续添加课程吗？', {
                                    btn: ['继续添加', '立即提交'] //按钮
                                }, function (index) {
                                    layList.layer.close(index);
                                }, function () {
                                    parent.layer.closeAll();
                                });
                            }
                        } else {
                            layList.msg('修改成功', function () {
                                parent.layer.closeAll();
                                window.location.href = layList.U({
                                    a: 'index',
                                    p: {type: 1, special_type:special_type}
                                });
                            })
                        }
                    }, function (res) {
                        layList.msg(res.msg);
                        layList.loadClear();
                    });
                    })
                },
                clone_form: function () {
                    if (parseInt(id) == 0) {
                        var that = this;
                        if (that.formData.image.pic) return layList.msg('请先删除上传的图片在尝试取消');
                        if (that.formData.poster_image.pic) return layList.msg('请先删除上传的图片在尝试取消');
                        if (that.formData.service_code.pic) return layList.msg('请先删除上传的图片在尝试取消');
                        parent.layer.closeAll();
                    }
                    parent.layer.closeAll();
                },
                createUploader:function () {
                    var that=this;
                    var uploader = new AliyunUpload.Vod({
                        timeout: 60000,//请求过期时间（配置项 timeout, 默认 60000）
                        partSize: 1048576,//分片大小（配置项 partSize, 默认 1048576）
                        parallel: 5,//上传分片数（配置项 parallel, 默认 5）
                        retryCount:3,//网络失败重试次数（配置项 retryCount, 默认 3）
                        retryDuration:2,//网络失败重试间隔（配置项 retryDuration, 默认 2）
                        region: configuration_item_region,//配置项 region, 默认 cn-shanghai
                        userId: alicloud_account_id,//阿里云账号ID
                        // 添加文件成功
                        addFileSuccess: function (uploadInfo) {
                            if (alicloud_account_id=='') {
                                return layList.msg('请配置阿里云账号ID！');
                            }
                            var type=uploadInfo.file.type;
                            var arr=type.split('/');
                            if(that.formData.light_type==2 && arr[0]!='audio'){
                                that.is_video=false;
                                that.videoWidth = 0;
                                return layList.msg('请上传音频');
                            }else if(that.formData.light_type==3 && arr[0]!='video'){
                                that.is_video=false;
                                that.videoWidth = 0;
                                return layList.msg('请上传视频');
                            }else{
                                that.is_video=true;
                                that.videoWidth = 0;
                            }
                        },
                        // 开始上传
                        onUploadstarted: function (uploadInfo) {
                            var videoId='';
                            if(uploadInfo.videoId){
                                videoId= uploadInfo.videoId;
                            }
                            layList.basePost(layList.U({a: 'video_upload_address_voucher'}),
                                {
                                    FileName:uploadInfo.file.name,type:1,image:that.formData.image,videoId:videoId
                                }, function (res) {
                                    var url=res.msg;
                                    $.ajax({
                                        url:url,
                                        data:{},
                                        type:"GET",
                                        dataType:'json',
                                        success:function (data) {
                                            if(data.RequestId){
                                                var uploadAuth = data.UploadAuth;
                                                var uploadAddress = data.UploadAddress;
                                                var videoId = data.VideoId;
                                                uploader.setUploadAuthAndAddress(uploadInfo, uploadAuth, uploadAddress,videoId)
                                            }
                                        },
                                        error:function (err) {
                                            return layList.msg(err.responseJSON.Message);
                                        }
                                    });
                                });
                        },
                        // 文件上传成功
                        onUploadSucceed: function (uploadInfo) {
                            that.formData.videoId=uploadInfo.videoId;
                            that.formData.file_name=uploadInfo.file.name;
                            that.formData.file_type=uploadInfo.file.type;
                            that.videoWidth = 0;
                            that.is_video = false;
                            that.is_suspend = false;
                            that.is_upload = true;
                            that.playbackAddress(uploadInfo.videoId);
                        },
                        // 文件上传失败
                        onUploadFailed: function (uploadInfo, code, message) {
                        },
                        // 取消文件上传
                        onUploadCanceled: function (uploadInfo, code, message) {
                            that.formData.file_name='';
                            that.is_suspend = false;
                        },
                        // 文件上传进度，单位：字节, 可以在这个函数中拿到上传进度并显示在页面上
                        onUploadProgress: function (uploadInfo, totalSize, progress) {
                            that.videoWidth = Math.ceil(progress * 100);
                        },
                        // 上传凭证超时
                        onUploadTokenExpired: function (uploadInfo) {
                            var videoId='';
                            if(uploadInfo.videoId){
                                videoId= uploadInfo.videoId;
                            }
                            layList.basePost(layList.U({a: 'video_upload_address_voucher'}),{
                                FileName:uploadInfo.file.name,type:1,image:that.formData.image,videoId:videoId
                            }, function (res) {
                                var url=res.msg;
                                $.ajax({
                                    url:url,
                                    data:{},
                                    type:"GET",
                                    dataType:'json',
                                    success:function (data) {
                                        if(data.RequestId){
                                            var uploadAuth = data.UploadAuth;
                                            uploader.resumeUploadWithAuth(uploadAuth);
                                        }
                                    },
                                    error:function (err) {
                                        return layList.msg(err.responseJSON.Message);
                                    }
                                });
                            });
                        },
                        // 全部文件上传结束
                        onUploadEnd: function (uploadInfo) {
                            that.videoWidth = 0;
                            that.is_video = false;
                            that.is_suspend = false;
                            that.is_upload = true;
                            console.log("onUploadEnd: uploaded all the files")
                        }
                    });
                    return uploader;
                },
                delVideo:function(){
                    if (window.confirm('删除后会同步删除阿里云点播视频，如不想删除云端视频请点重传按钮，确定删除吗？')) {
                        var that=this;
                        that.is_upload = false
                        if(that.demand_switch=='1' && that.formData.videoId){
                            layList.basePost(layList.U({a: 'video_upload_address_voucher'}),{
                                FileName:'',type:4,image:'',videoId:that.formData.videoId
                            }, function (res) {
                                var url=res.msg;
                                $.ajax({
                                    url:url,
                                    data:{},
                                    type:"GET",
                                    dataType:'json',
                                    success:function (data) {
                                        if(data.RequestId){
                                            that.link='';
                                            that.formData.content='';
                                            that.formData.videoId='';
                                            that.formData.file_type='';
                                            that.formData.file_name='';
                                            that.editor1.setContent('');
                                            $("input[type='file']").val('');
                                            that.is_upload = false;
                                        }
                                    },
                                    error:function (err) {
                                        return layList.msg(err.responseJSON.Message);
                                    }
                                });
                            });
                        }else{
                            that.formData.videoId='';
                            that.link='';
                            that.editor1.setContent('');
                            that.is_upload = false;
                        }
                    }
                },
                playbackAddress:function (videoId) {
                    var that=this;
                    if(videoId=='') return false;
                    layList.basePost(layList.U({a: 'video_upload_address_voucher'}), {
                        FileName: '', type: 3, image: '', videoId: videoId
                    }, function (res) {
                        var url = res.msg;
                        $.ajax({
                            url: url,
                            data: {},
                            type: "GET",
                            dataType: 'json',
                            success: function (data) {
                                that.link = data.PlayInfoList.PlayInfo[0].PlayURL;
                                that.uploadVideo();
                            },
                            error: function (err) {
                                // that.link = '';
                                // that.formData.content = '';
                                // that.formData.videoId = '';
                                // that.formData.file_type = '';
                                // that.formData.file_name = '';
                                that.is_upload = false;
                                return layList.msg(err.responseJSON.Message);
                            }
                        });
                    });
                },
                audio_video_upload:function () {
                    var that=this;
                    var id='ossupload_video';
                    if(that.formData.light_type==2){
                        id='ossupload_audio'
                    }
                    that.uploader = ossUpload.upload({
                        id: id,
                        FilesAddedSuccess: function () {
                            that.is_video = true;
                        },
                        uploadIng: function (file) {
                            that.videoWidth = file.percent;
                        },
                        success: function (res) {
                            layList.msg('上传成功');
                            that.videoWidth = 0;
                            that.is_video = false;
                            that.formData.videoId='';
                            that.is_upload = true;
                            that.link = res.url;
                            that.uploadVideo();
                        },
                        fail: function (err) {
                            that.videoWidth = 0;
                            that.is_video = false;
                            that.is_upload = false;
                            layList.msg(err);
                        }
                    })
                }
            },
            mounted: function () {
                var that = this;
                window.changeIMG = that.changeIMG;
                layList.date({
                    elem: '#start_time',
                    theme: '#393D49',
                    type: 'datetime',
                    done: function (value) {
                        that.formData.pink_strar_time = value;
                    }
                });
                layList.date({
                    elem: '#end_time',
                    theme: '#393D49',
                    type: 'datetime',
                    done: function (value) {
                        that.formData.pink_end_time = value;
                    }
                });
                //选择图片
                function changeIMG(index, pic) {
                    $(".image_img").css('background-image', "url(" + pic + ")");
                    $(".active").css('background-image', "url(" + pic + ")");
                    $('#image_input').val(pic);
                }

                //选择图片插入到编辑器中
                window.insertEditor = function(list,fodder){
                    that[fodder].execCommand('insertimage', list);
                };
                if(that.formData.link && that.formData.videoId=='' && that.formData.light_type!=1){
                    that.is_upload=true;
                    that.link = that.formData.link;
                }else if(that.formData.videoId && that.formData.link== '' && that.formData.light_type!=1 && that.formData.video_type != 1 && that.formData.video_type != 4){
                    that.is_upload=true;
                    that.playbackAddress(that.formData.videoId);
                }
                if (that.formData.light_type != 1 && (that.formData.video_type == 1 || that.formData.video_type == 4)) {
                    that.is_upload=true;
                    that.link = that.formData.link;
                }
                this.$nextTick(function () {
                    var form = layList.form;
                    form.render();
                    form.on('switch(isTry)', function (data) {
                        that.formData.is_try = data.elem.checked ? 1 : 0;
                    });

                    //实例化编辑器
                    UE.registerUI('选择图片', function (editor, uiName) {
                        var btn = new UE.ui.Button({
                            name: uiName,
                            title: uiName,
                            cssRules: 'background-position: -380px 0;',
                            onclick: function() {
                                ossUpload.createFrame(uiName, { fodder: editor.key }, { w: 800, h: 550 });
                            }
                        });
                        return btn;
                    });
                    var textareaElements = document.getElementsByTagName('textarea');
                    var textareaId = '';
                    for (var i = textareaElements.length; i--;) {
                        textareaId = textareaElements[i].id;
                        that[textareaId] = UE.getEditor(textareaId);
                    }
                });
                //获取科目
                that.get_subject_list();
                //获取讲师
                that.get_lecturer_list();
                //图片上传和视频上传
                layList.form.on('radio(is_pink)', function (data) {
                    that.formData.is_pink = parseInt(data.value);
                });
                layList.form.on('radio(is_mer_visible)', function (data) {
                    that.formData.is_mer_visible = parseInt(data.value);
                });
                layList.form.on('radio(video_type)', function (data) {
                    that.formData.video_type = parseInt(data.value);
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                layList.form.on('radio(pay_type)', function (data) {
                    that.formData.pay_type = parseInt(data.value);
                    if (that.formData.pay_type != 1) {
                        that.formData.is_pink = 0;
                        that.formData.member_pay_type = 0;
                        that.formData.member_money = 0;
                        that.formData.is_alone = 0;
                        that.formData.brokerage_ratio = 0;
                        that.formData.brokerage_two = 0;
                    };
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                layList.form.on('radio(is_alone)', function (data) {
                    that.formData.is_alone = parseInt(data.value);
                    if (that.formData.is_alone != 1) {
                        that.formData.brokerage_ratio = 0;
                        that.formData.brokerage_two = 0;
                    };
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                layList.form.on('radio(member_pay_type)', function (data) {
                    that.formData.member_pay_type = parseInt(data.value);
                    if (that.formData.member_pay_type != 1) {
                        that.formData.member_money = 0;
                    };
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                layList.select('subject_id', function (obj) {
                    that.formData.subject_id = obj.value;
                });
                layList.select('lecturer_id', function (obj) {
                    that.formData.lecturer_id = obj.value;
                });
                layList.form.on('radio(is_fake_pink)', function (data) {
                    that.formData.is_fake_pink = parseInt(data.value);
                });
                layList.form.on('radio(light_type)', function (data) {
                    that.formData.light_type = parseInt(data.value);
                    if(that.demand_switch=='2') {
                        that.audio_video_upload();
                    }
                    that.$nextTick(function () {
                        layList.form.render('radio');
                    });
                });
                //图片上传和视频上传
                var uploader = null;
                if(that.demand_switch=='1'){
                    $('.ossupload').on('change', function (e) {
                        var file = e.target.files[0];
                        if (!file) {
                            return layList.msg('请先选择需要上传的文件！');
                        }
                        var Title = file.name;
                        var userData = '{"Vod":{}}';
                        uploader = that.createUploader();
                        uploader.addFile(file, null, null, null, userData);
                    });
                    // 第一种方式 UploadAuth 上传
                    $('.authUpload').on('click', function () {
                        if (uploader !== null) {
                            uploader.startUpload();
                        }
                    });
                    // 暂停上传
                    $('.pauseUpload').on('click', function () {
                        if (uploader !== null) {
                            uploader.stopUpload();
                            that.is_suspend = true;
                            that.formData.file_name='';
                            layList.msg('暂停上传！');
                        }
                    });
                    //恢复上传
                    $('.resumeUpload').on('click', function () {
                        if (uploader !== null) {
                            uploader.startUpload();
                            that.is_suspend = false;
                            layList.msg('恢复上传成功！');
                        }
                    });
                }else if(that.demand_switch=='2' && id>0){
                    that.audio_video_upload();
                }
            }
        })
    })
</script>
{/block}
