<?php
namespace app\index\controller;
use think\Controller;
use think\Request;

class Delete extends Controller {


    //删除草稿
    public function draft(Request $request) {
        $post = $request->post();
        //表中删除
        db('goods')->where(['goods_id' => $post['goods_id']])->delete();
        $this->headImg($post['goods_id']);
        $this->introImg($post['goods_id']);
        return true;
    }

    //删除主图
    public function headImg($goods_id) {
        $dir = db('head_img')->where(['goods_id' => $goods_id])->column('save_name');
        //表中删除
        db('head_img')->where(['goods_id' => $goods_id])->delete();
        //物理删除
        foreach ($dir as $v) {
            $dir = './static/upload/image/goods/head_img/' . $v;
            if (unlink($dir)) {
                return true;
            } else {
                return false;
            }
        }
    }

    //删除详情图片
    public function introImg($goods_id) {
        $dir = db('img_goods_intro')->where(['goods_id' => $goods_id])->column('save_name');
        //表中删除
        db('img_goods_intro')->where(['goods_id' => $goods_id])->delete();
        //物理删除
        foreach ($dir as $v) {
            $dir = './static/upload/image/editor/' . $v;
            if (unlink($dir)) {
                return true;
            } else {
                return false;
            }
        }
    }
}
