<?php
namespace app\index\model;

use think\Model;

class Province extends Model
{

    protected $name='province';

    public function city(){
        return $this->hasMany('City','province_code','code');
    }

    public function getCodeCnAttr(){

    }
}