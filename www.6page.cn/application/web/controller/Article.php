<?php


namespace app\web\controller;

use app\web\model\article\Article as ArticleModel;
use app\web\model\article\ArticleCategory;
use service\GroupDataService;
use service\JsonService;
use service\UtilService;
use think\Db;


/**
 * 新闻控制器
 * Class Article
 * @package app\web\controller
 */
class Article extends AuthController
{

    /**
     * 白名单
     */
    public static function WhiteList()
    {
        return [
            'get_article_list',
            'get_article_cate',
            'article_details',
            'news_list',
            'news_detail',
            'news_data'
        ];
    }

    /**
     * 新闻列表
     */
    public function get_article_list()
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 10],
            ['cid', 0],
            ['search', '']
        ]);
        return JsonService::successful(ArticleModel::getUnifiendList($where));
    }
    /**
     * 新闻分类列表
     */
    public function get_article_cate()
    {
        $category=ArticleCategory::where(['status'=>1,'is_del'=>0])->order('sort DESC,add_time DESC')->select();
        $category=count($category)>0 ? $category->toArray() : [];
        return JsonService::successful($category);
    }

    /**
     * 新闻详情内容
     */
    public function article_details($id=0)
    {
        $article = ArticleModel::where(['id'=>$id,'is_show'=>1])->find();
        if (!$article) return JsonService::fail('您查看的文章不存在');
        $content = Db::name('articleContent')->where('nid', $article['id'])->value('content');
        $article["content"] =htmlspecialchars_decode($content);
        //增加浏览次数
        $article["visit"] = $article["visit"] + 1;
        $article["add_time"] =date('Y-m-d',$article['add_time']);
        ArticleModel::where('id', $id)->setInc('visit');
        return JsonService::successful($article);
    }

    /**
     * 新闻列表
     */
    public function news_list($cid=0)
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 16],
            ['search', '']
        ]);
        $where['cid'] = $cid;
        $model=ArticleModel::setWhere($where);
        $articles=$model->paginate((int)$where['limit']);
        $items = $articles->items();
        foreach ($items as &$item){
            $item['add_time']=date('Y-m-d',$item['add_time']);
            $item['visit']=(int)$item['visit'];
        }
        $count= ArticleModel::setWhere($where)->count();

        $category=ArticleCategory::where(['status'=>1,'is_del'=>0])->order('sort DESC,add_time DESC')->select();
        $category=count($category)>0 ? $category->toArray() : [];

        $this->assign([
            'category' => $category,
            'articles' => $articles,
            'count' => $count,
            'cid' => $cid
        ]);
        return $this->fetch();
    }

    /**
     * 新闻详情
     */
    public function news_detail($id=0)
    {
        if (!$id) {
            return $this->redirect(url('/404'));
        }
        $article = ArticleModel::where(['id'=>$id,'is_show'=>1])->find();
        if (!$article) return $this->redirect(url('/404'));
        $content = Db::name('articleContent')->where('nid', $article['id'])->value('content');
        $article["content"] =htmlspecialchars_decode($content);
        //增加浏览次数
        $article["visit"] = $article["visit"] + 1;
        $article["add_time"] =date('Y-m-d',$article['add_time']);
        $article["category"] = ArticleCategory::where(['id' => $article['cid']])->find();
        ArticleModel::where('id', $id)->setInc('visit');

        // 推荐阅读列表
        $where = [
            'cid' => $article['cid'],
            'limit' => 6
        ];
        $recommend_list = ArticleModel::setWhere($where)->where('id', '<>', $id)->order('sort DESC,add_time DESC')->select()->toArray();

//        dump($recommend_list);
        $this->assign([
            'id' => $id,
            'article' => $article,
            'recommend_list' => $recommend_list
        ]);
//        dump($article["category"]['title']);

        return $this->fetch();
    }

    /**
     * 新闻广告
     */
    public function news_data()
    {
        $data['pc_news_list_rotation_chart']=GroupDataService::getData('pc_news_list_rotation_chart');//pc端新闻列表轮播图
        return JsonService::successful($data);
    }
}
