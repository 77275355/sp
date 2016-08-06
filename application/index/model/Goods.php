<?php
namespace app\index\model;
use think\Model;

class Goods extends Model {


    public function getCreateTimeAttr($value){
        return date('Y-m-d H:i',$value);
    }

    public function getUpdateTimeAttr($value){
        if($value==null){
            return null;
        }
        return date('Y-m-d H:i',$value);
    }
    public function getStatusCnAttr($value,$data){
        $arr=['1'=>'草稿箱','2'=>'审核中','3'=>'审核通过','4'=>'审核不通过'];
        return $arr[$data['status']];
    }
    public function getStatusCnClassAttr($value,$data){
        $arr=['1'=>'label-primary','2'=>'label-info','3'=>'label-success','4'=>'label-danger'];
        return $arr[$data['status']];
    }


    public function headImg() {
        return $this->hasMany('HeadImg', 'goods_id', 'goods_id');
    }

}