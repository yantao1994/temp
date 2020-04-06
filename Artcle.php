<?php
/**
 * Created by PhpStorm.
 * User: 闫涛
 * Date: 2019/11/14
 * Time: 10:55
 * 文章详情
 */

namespace app\api\controller;

use think\Lang;
use think\Config;
use think\Db;

header(Config::get('header.acao'));
header(Config::get('header.acac'));

class Artcle
{

    /*************************************************首页文章 start****************************************************/
    //首页文章的详情1-文章内容
    public function index(){
        $article_id = input('artcle_id');
        $member_id = input('member_id');

        //1.该文章阅读数+1
        Db::name('article')->where('article_id',$article_id)->setInc('viewNum',1);

        //2.文章全部数据
        $artinfo = Db::name('article')->where('article_id',$article_id)->find();

        //3.将文章时间戳转成字符串
        $time = date('Y-m-d H:i:s',$artinfo['article_time']);

        //4.我赞了吗
        $zan = Db::name('article_dianzan')->where(['member_id'=>$member_id,'article_id'=>$article_id])->find();
        if ($zan){
            $yizan = 1;
        }else{
            $yizan = 0;
        }


        //6.member_name
        $member_name = Db::name('member')->where('member_id',$member_id)->field('member_name')->find();

        return json(['artinfo'=>$artinfo,'time'=>$time,'yizan'=>$yizan,'member_name'=>$member_name['member_name']]);

    }
    //首页文章的详情2-评论列表
    public function pinglunlist(){
        $article_id = input('artcle_id');
        $page = input('page');
        //评论list
        $tmp = Db::name('article_reply')->where('article_id',$article_id)->limit(10 * $page)->select();
        if ($tmp){
            foreach ($tmp as $v){

                //5.1查找用户信息：昵称，头像
                $info = Db::name('member')->where('member_id',$v['member_id'])->field('member_nickname,member_avatar')->select();
                $v['member_nickname'] = $info[0]['member_nickname'];
                $v['member_avatar'] = $info[0]['member_avatar'];
                $pinglist[] = $v;

            }
        }else{
            $pinglist = [];
        }

        return json(['pinglist'=>$pinglist]);

    }

    //首页文章点赞
    public function homeArticleZan(){
        $article_id = input('artcle_id');
        $member_id = input('member_id');

        // 启动事务
        Db::startTrans();
        try{

            //1.点赞表写入用户id
            Db::name('article_dianzan')->insert(['member_id'=>$member_id,'article_id'=>$article_id]);

            //2.文章表like +1
            Db::name('article')->where('article_id',$article_id)->setInc('likeNum',1);

            //3.chuangyezs表
            Db::name('wgm_chuangyezs')->insert(['riqi'=>date('Y-m-d H:i:s'),'member_id'=>$member_id,'amount'=>2,'eventtype'=>'首页文章点赞']);

            // 提交事务
            Db::commit();
            return json(['code'=>10000]);
        } catch (\Exception $e) {

            // 回滚事务
            Db::rollback();
            return json(['code'=>10001]);
        }
    }


    //首页文章评论
    public function homeArticlePing(){
        $article_id = input('artcle_id');
        $member_id = input('member_id');
        $input_v = input('input_v');

        // 启动事务
        Db::startTrans();
        try{

            //1.写入article_reply表
            Db::name('article_reply')->insert(['member_id'=>$member_id,'article_id'=>$article_id,'content'=>$input_v]);

            //2.文章表 replynum +1
            Db::name('article')->where('article_id',$article_id)->setInc('replyNum',1);

            //3.wgm_chuangyezs表
            Db::name('wgm_chuangyezs')->insert(['riqi'=>date('Y-m-d H:i:s'),'member_id'=>$member_id,'amount'=>5,'eventtype'=>'首页文章评论']);

            // 提交事务
            Db::commit();
            return json(['code'=>10000]);
        } catch (\Exception $e) {

            // 回滚事务
            Db::rollback();
            return json(['status'=>10001]);
        }

    }


    //分享之后会调取这个方法，文章分享数+1.用户增加信用指数
    public function homeArticleShareInc(){
        $member_id = input('member_id');
        $article_id = input('artcle_id');

        $res = Db::name('article')->where('article_id',$article_id)->setInc('shareNum',1);
        //创业指数+5
        Db::name('wgm_chuangyezs')->insert(['riqi'=>date('Y-m-d'),'member_id'=>$member_id,'amount'=>5,'eventtype'=>'文章分享']);
        return json($res);

    }

    //更多新闻公告
    public function moreArticleNotice(){
       $artlist = Db::name('article')->where(['ac_id'=>11,'article_show'=>1])->order('article_sort asc')->select();
       
        return json(['artlist'=>$artlist]);
    }
    /*************************************************首页文章 end****************************************************/


    //创业指数提升指南
    public function tszn(){
        $artinfo = Db::name('article')->where('article_title','创业指数攻略')->find();
        return json(['artinfo'=>$artinfo]);

    }


    //物格码企业协议
    public function wgmxy(){
        $artinfo = Db::name('document')->where('document_code','qiyerenzheng')->find();
        return json(['artinfo'=>$artinfo]);

    }


    //个人协议
    public function grxy(){
        $artinfo = Db::name('document')->where('document_code','gerenrenzheng')->find();
        return json(['artinfo'=>$artinfo]);

    }


    //注册协议
    public function zcxy(){
        $artinfo = Db::name('document')->where('document_code','zhucexieyi')->find();
        return json(['artinfo'=>$artinfo]);

    }


    //我的帮助
    public function myhelp(){
        $list = Db::name('article')->where(['ac_id'=>12,'article_show'=>1])->field('article_id,article_title')->order('article_sort')->select();
                $yhlist = Db::name('article')->where(['ac_id'=>8,'article_show'=>1])->field('article_id,article_title')->order('article_sort')->select();
        return json(['list'=>$list,'yhlist'=>$yhlist]);
    }


    //易货我的帮助
    public function yhmyhelp(){
        $list = Db::name('article')->where(['ac_id'=>8,'article_show'=>1])->field('article_id,article_title')->order('article_sort')->select();
        return json(['list'=>$list]);
    }


    //我的帮助详情
    public function helpcontent(){
        $article_id = input('article_id');
        $info = Db::name('article')->where('article_id',$article_id)->find();
        return json(['info'=>$info]);
    }
}