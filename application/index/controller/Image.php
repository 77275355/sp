<?php
namespace app\index\controller;
use think\Controller;
use think\Image as Img;
use think\Request;
class Image extends Controller
{

    //商品主图
    public function goodsHeadImg(Request $request)
    {

        $file = $request->file('file');
        $image=Img::open($file);
        $image->thumb(800,800,3);
        $save_name=md5(microtime(true)).'.'.$image->type();
        $path='./static/upload/image/goods/head_img/'.$save_name;

        if($image->save($path)){
            return $save_name;
        }else{
            return '上传失败';
        }
    }

    //编辑器上传图片
    public function editorImg(Request $request){
        $h5=$request->file('wangEditorH5File');
        $paste=$request->file('wangEditorPasteFile');
        if($h5){
            $file=$h5;
        }
        if($paste){
            $file=$paste;
        }
        $image = Img::open($file);
        $image->thumb(800,800);
        $save_name=md5(microtime(true)).'.'.$image->type();
        $path='./static/upload/image/editor/'.$save_name;

        if($image->save($path)){
            $arr=[
                'save_name'=>$save_name,
                'width'=> $image->width(),
                'height'=>$image->height(),
                'src'=>'/static/upload/image/editor/'.$save_name
            ];
            return json($arr);
        }else{
            return '上传失败';
        }
//        exit;
//        $info = $file->rule('date')->move('./static/upload/image/editor/');
//        if ($info) {
//
//
//            return '/static/upload/image/editor/'.$info->getSavename();
//        } else {
//            // 上传失败获取错误信息
//            return $file->getError();
//        }

    }


    //删除文件
    public function remove(){
        $post=input('post.');
        switch($post['cate']){
            case 'head_img':
                //表中删除
                $flag=db('head_img')->where(['save_name'=>$post['name']])->delete();

                //物理删除
               $dir='./static/upload/image/goods/head_img/'.$post['name'];
               if(unlink($dir)||$flag){
                  return success();
               }else{
                   return error();
               }
                break;
        }
    }
}
