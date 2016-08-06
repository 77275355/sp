<?php
namespace app\index\model;

use think\Model;

class City extends Model
{

    protected $name='city';

    public function district(){
        return $this->hasMany('District','city_code','code');
    }

    public function getCode(){

    }
}