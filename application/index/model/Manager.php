<?php
namespace app\index\model;

use think\Model;

class Manager extends Model
{

    public function getCreateTimeAttr($value){
        return date('Y-m-d H:i',$value);
    }
    public function getAbleTimeAttr($value){
        if($value==null){
            return '';
        }else{

            return date('Y-m-d H:i',$value);
        }
    }
    public function getDisableTimeAttr($value){
        if($value==null){
            return '';
        }else{

            return date('Y-m-d H:i',$value);
        }
    }
    public function auth(){
        return $this->belongsTo('Auth','auth_id','id');
    }

    public function getStatusCnAttr($value,$data){
        $arr=['0'=>'未激活','1'=>'开通','2'=>'禁用'];
        return $arr[$data['status']];
    }
    public function getAreaAttr($value,$data){


        $info=db('auth')->find($data['auth_id']);
        $province=json_decode($info['province_code']);
        $city=json_decode($info['city_code']);
        $district=json_decode($info['district_code']);
            if(!empty($province)){
                $a1=db('province')->where('code','in',$province)->select();
            }
        if(!empty($city)){
            $a2=db('city')->where('code','in',$city)->select();
        }
        if(!empty($district)){
            $a3=db('district')->where('code','in',$district)->select();
        }

        $arr=array_merge($a1,$a2,$a3);

        return $arr;
    }
}