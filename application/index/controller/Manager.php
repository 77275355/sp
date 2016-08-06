<?php
namespace app\index\controller;
use app\index\model\City;
use think\Controller;
use app\index\model\Province;
use think\Db;
use think\Validate;
use app\index\model\Manager as ManagerModel;

class Manager extends Controller {


    //代理商列表
    public function ManagerList(){
        $get=input('param.');


        //筛选条件pr、c、d、s、b
        if(isset($get['pr'])){
            $where['province_code']=['like','%'.$get['pr'].'%'];
            $res=db('auth')->where($where)->select();
            $arr=[];
            foreach($res as $v){
                array_push($arr,$v['id']);
            }

            $city = db('city')->where('province_code',$get['pr'])->select();
            $this->assign('city', $city);


            if(isset($get['c'])){
                $where['city_code']=['like','%'.$get['c'].'%'];
                $res=db('auth')->where($where)->select();
                $arr=[];
                foreach($res as $v){
                    array_push($arr,$v['id']);
                }

                $city = db('district')->where('city_code',$get['c'])->select();
                $this->assign('district', $city);




                if(isset($get['d'])){
                    $where['district_code']=['like','%'.$get['d'].'%'];
                    $res=db('auth')->where($where)->select();
                    $arr=[];
                    foreach($res as $v){
                        array_push($arr,$v['id']);
                    }



                }
            }

            if(!empty($arr)){
                $condition['auth_id']=['in',$arr];
            }else{
                $condition['auth_id']=['in',[1]];
            }

        }
        if (isset($get['s'])) {
            $condition['username'] = ['like', '%' . $get['s'] . '%'];
        }
        if (isset($get['b'])) {
            if ($get['b'] != 3) {
                $condition['status'] = $get['b'];
            }
        }


        //排序规则

        $order=['create_time'=>'desc','goods_count'=>'asc','order_count'=>'asc','able_time'=>'asc'];
        if(isset($get['sort'])){
            $status=explode('-',$get['sort']);

            switch($status[1]){
                case 1:
                    $order=[];
                    $order[$status[0]]='asc';
                    break;
                case 2:
                    $order=[];
                    $order[$status[0]]='desc';
                    break;
            }

        }


//        dump($arr);exit;


        $list=ManagerModel::where($condition)->order($order)->paginate(12);
        $this->assign('list',$list);
        $this->assign('menu', '代理商列表');

        $province = db('province')->select();
        $this->assign('province', $province);
        return $this->fetch();
    }


    //添加代理商页面
    public function addManager() {
        $province = db('province')->select();
        $this->assign('province', $province);
        $this->assign('menu', '添加代理商');
        return $this->fetch();
    }

    //编辑代理商页面
    public function editManager() {
        //代理信息
        $id=input('param.id');


        $info=ManagerModel::get($id);
        $this->assign('info', $info);
        $province_code=json_decode($info->auth->province_code);
        $city_code=json_decode($info->auth->city_code);
        $district_code=json_decode($info->auth->district_code);
        $arr=array_merge($province_code,$city_code,$district_code);
        $this->assign('area', json_encode($arr));


        if(!empty($province_code)){
            $this->assign('province_selected', $province_code);
            $this->assign('province_auth',$this->getAreaList($province_code,1));
            $this->assign('city_auth',$this->getAreaList($city_code,2));
            $this->assign('district_auth',$this->getAreaList($district_code,3));
        }


        //基础
        $province = db('province')->select();
        $this->assign('province', $province);
        $city = db('city')->where('province_code',$info['province_code'])->select();
        $this->assign('city', $city);
        $district = db('district')->where('city_code',$info['city_code'])->select();
        $this->assign('district', $district);
        $this->assign('menu', '编辑代理商');
        return $this->fetch();
    }

    //用数组获取省份、市、区列表

    public function getAreaList($arr,$status){

        switch($status){
            case 1:
                $res=Db::name('province')->where('code','in',$arr)->select();
                break;
            case 2:
                $res=Db::name('city')->where('code','in',$arr)->select();
                break;
            case 3:
                $res=Db::name('district')->where('code','in',$arr)->select();
                break;
        }

        return $res;
    }
    //保存代理商
    public function saveManager() {
        $post = input('post.');
        $auth = json_decode($post['auth']);
        unset($post['auth']);
        $validate = new Validate([
            ['username', 'require', '用户名不能为空'],
            ['password', 'require', '密码不能为空'],
            ['phone', 'require', '电话不能为空'],
            ['responsible', 'require', '负责人不能为空'],
            ['weixin', 'require', '客服微信不能为空'],
            ['qq', 'require', '客服QQ不能为空'],
            ['province_code', 'gt:1', '未选择省'],
            ['city_code', 'gt:1', '未选择市'],
            ['district_code', 'gt:1', '未选择区/县'],
            ['tel', 'require', '客服电话不能为空'],
            ['intro', 'require', '介绍不能为空'],
            ['addr', 'require', '地址不能为空'],
        ]);

        //单条验证-方便前端跳到正确提示位置
        foreach ($post as $k => $v) {
            $arr = [$k => $v];
            $validate->scene('edit', [$k]);
            if (!$validate->scene('edit')->check($arr)) {
                $data['msg'] = $validate->getError();
                $data['key'] = $k;
                if ($k == 'city_code' || $k == 'district_code') {
                    $data['key'] = 'province_code';
                }
                return error($data);
            }
        }

        //权限入库
        $area = [];
        foreach ($auth as $k => $v) {
            switch ($k) {
                case 0:
                    $area['province_code'] = json_encode($v);
                    break;
                case 1:
                    $area['city_code'] = json_encode($v);
                    break;
                case 2:
                    $area['district_code'] = json_encode($v);
                    break;
            }
        }
        $post['auth_id'] = db('auth')->insertGetId($area);
        //插入代理商数据
        if (isset($post['id'])) {
            $post['update_time'] = time();
            db('manager')->update($post);
            return success();
        } else {
            $post['create_time'] = time();
            $res = db('manager')->insert($post);
            if ($res) {
                return success();
            }
        }


    }


    //test 已废弃 TODO delete
    public function editAuth() {
        $province = db('province')->select();
        $this->assign('province', $province);
        return $this->fetch();
    }


    //获取当前省份城市列表
    public function getCity() {
        $province_code = input('post.province_code');
        $res = db('city')->where(['province_code' => $province_code])->select();
        return $res;
    }

    //获取当前城市区域列表
    public function getDistrict() {
        $city_code = input('post.city_code');
        $res = db('district')->where(['city_code' => $city_code])->select();
        return $res;
    }

    //保存权限
    public function saveAuth() {
        $post = input('post.');
        $post['province_code'] = json_encode($post['province_code']);
        $post['city_code'] = json_encode($post['city_code']);
        $post['district_code'] = json_encode($post['district_code']);
        db('auth')->insert($post);
    }

    //获取省份下所有市区code
    public function getCode() {
        $post = input('post.');
        switch ($post['cate']) {
            case '1':
                $res = Province::get($post['code']);
                $city = $res->city()->select();
                foreach ($city as $k => $v) {
                    $city[$k]['cate'] = '2';
                    $district = $v->district;
                    foreach ($district as $vd) {
                        $vd['cate'] = '3';
                        array_push($city, $vd);
                    }
                }
                return $city;
                break;
            case '2':
                $res = City::get($post['code']);
                $district = $res->district()->select();
                foreach ($district as $k => $v) {
                    $district[$k]['cate'] = '3';
                }
                return $district;
                break;
        }
    }

    //获取子元素
    public function getCodeSun() {
        $post = input('post.');
        switch ($post['cate']) {
            case '1':
                $data = db('city')->where(['province_code' => $post['code']])->select();
                return $data;
                break;
            case '2':
                $data = db('district')->where(['city_code' => $post['code']])->select();
                return $data;
                break;
            case '3':
                return null;
                break;
        }
    }

    //更改管理员开通状态
    public function updateStatus(){
        $post=input('post.');
        switch($post['status']){
            case '1':
                $post['able_time']=time();
                $post['disable_time']=null;
                break;
            case '2':
                $post['disable_time']=time();
                $post['able_time']=null;
                break;
        }
        db('manager')->update($post);
        return date('Y-m-d H:i');
    }

}
