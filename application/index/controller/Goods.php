<?php
namespace app\index\controller;
use think\Controller;
use think\Validate;
use app\index\model\Goods as GoodsModel;
use think\Request;

class Goods extends Controller {


    //商品列表页面
    public function goodsList(Request $request) {

        $get=$request->param();



        //筛选条件pr、c、d、s、b
        if(isset($get['pr'])){
            $condition['province_code']=['=',$get['pr']];
            $city = db('city')->where('province_code',$get['pr'])->select();
            $this->assign('city', $city);
            if(isset($get['c'])){
                $condition['city_code']=['=',$get['c']];
                $city = db('district')->where('city_code',$get['c'])->select();
                $this->assign('district', $city);
                if(isset($get['d'])){
                    $condition['district_code']=['=',$get['d']];
                }
            }
        }
        if (isset($get['gn'])) {
            $condition['goods_name'] = ['like', '%' . $get['gn'] . '%'];
        }

        if (isset($get['b'])) {
            if ($get['b'] != 5) {
                $condition['status'] = $get['b'];
            }
        }

        if (isset($get['put'])) {

                $condition['put'] = $get['put'];

        }
        if (isset($get['mn'])) {
            $mid=db('manager')->where(['username'=>$get['mn']])->value('id');


                $condition['manager_id'] = ['=', $mid];

        }

        //排序规则

        $order=['create_time'=>'desc','price'=>'asc','show_count'=>'asc','true_sales'=>'asc'];
        if (isset($get['sort'])) {
            $status = explode('-', $get['sort']);
            if ($get['sort'] != 'normal-1') {
                switch ($status[1]) {
                    case 1:
                        $order = [];
                        $order[$status[0]] = 'asc';
                        break;
                    case 2:
                        $order = [];
                        $order[$status[0]] = 'desc';
                        break;
                }
            }
        }

        $list = GoodsModel::where($condition)->order($order)->paginate(10);
        foreach ($list as $k => $v) {
            $a = $v->headImg()->where(['status'=>0])->find();
            $list[$k]['head_img'] = $a->save_name;
        }
        $province = db('province')->select();
        $this->assign('province', $province);
        $this->assign('list', $list);
        $this->assign('menu', '商品列表');
        return $this->fetch();
    }

    //添加商品
    public function addGoods() {
        $this->assign('menu', '添加商品');
        $province = db('province')->select();
        $this->assign('province', $province);
        return $this->fetch();
    }

    //添加商品
    public function editGoods($id) {
        $goods = GoodsModel::get($id);
        $goods['headImg']=$goods->headImg()->order('status')->select();
        $this->assign('info', $goods);
        //基础
        $this->assign('menu', '编辑商品');
        $province = db('province')->select();
        $this->assign('province', $province);
        $city = db('city')->where('province_code', $goods['province_code'])->select();
        $this->assign('city', $city);
        $district = db('district')->where('city_code', $goods['city_code'])->select();
        $this->assign('district', $district);
        return $this->fetch();
    }

    //保存商品
    public function saveGoods() {
        $post = input('post.');
        $head_img = json_decode($post['img']);
        $intro_img = json_decode($post['intro_img']);
        //验证图片是否上传
        if (empty($head_img)) {
            $data['msg'] = '请上传主图';
            $data['key'] = 'head_img';
            return error($data);
        }
        unset($post['img']);
        unset($post['intro_img']);
        //验证表单参数
        $validate = new Validate([
            ['goods_name', 'require', '商品名不能为空'],
            ['feature', 'require', '特色不能为空'],
            ['standard', 'require', '规格不能为空'],
            ['price', 'require', '价格不能为空'],
            ['freight', 'require', '运费不能为空'],
            ['virtual_sales', 'require', '虚拟销量不能为空'],
            ['province_code', 'gt:1', '未选择省'],
            ['city_code', 'gt:1', '未选择市'],
            ['district_code', 'gt:1', '未选择区/县'],
            ['outer_num', 'require', '外部编号不能为空'],
            ['intro', 'require', '详情不能为空'],
        ]);
        //单条验证-方便前端跳到正确提示位置
        foreach ($post as $k => $v) {
            $arr = [$k => $v];
            $validate->scene('edit', [$k]);
            if (!$validate->scene('edit')->check($arr)) {
                $data['msg'] = $validate->getError();
                $data['key'] = $k;
                if ($k == 'city_code' || $k == 'district_code' || $k == 'province_code') {
                    $data['key'] = 'province_code';
                }
                return error($data);
            }
        }
        //表单入库
        if (isset($post['goods_id'])) {
            $post['update_time'] = time();
            db('goods')->update($post);
            //详情图片入库
            $arr_intro=[];
            $arr_intro_name = db('img_goods_intro')->where(['goods_id' => $post['goods_id']])->column('save_name');
            foreach ($intro_img as $v) {

                if (!@in_array($v, $arr_intro_name)) {
                    $arr_intro[] = ['save_name' => $v, 'goods_id' => $post['goods_id']];
                }
            }
            db('img_goods_intro')->insertAll($arr_intro);
            //详情图片垃圾清理
            foreach($arr_intro_name as $v){
                if (!in_array($v, $intro_img)) {
                    db('img_goods_intro')->where(['save_name'=>$v])->delete();
                    unlink('./static/upload/image/editor/'.$v);
                }
            }

            //头像入库
            $arr_head = [];
            $arr_name = db('head_img')->where(['goods_id' => $post['goods_id']])->column('save_name');

            foreach ($head_img as $k=>$v) {

                if (!in_array($v, $arr_name)) {
                        $arr_head[] = ['save_name' => $v, 'goods_id' => $post['goods_id'],'status'=>$k];
                }else{
                    db('head_img')->where(['save_name' => $v])->update(['status'=>$k]);
                }
            }
            db('head_img')->insertAll($arr_head);
            return success();
        } else {
            $post['create_time'] = time();
            $id = db('goods')->insertGetId($post);
            if ($id) {
                //详情图片入库
                $arr_intro = [];
                foreach ($intro_img as $v) {
                    $arr_intro[] = ['save_name' => $v, 'goods_id' => $id];
                }
                db('img_goods_intro')->insertAll($arr_intro);
                //头像入库
                $arr_head = [];
                foreach ($head_img as $k=>$v) {
                    $arr_head[] = ['save_name' => $v, 'goods_id' => $id,'status'=>$k];
                }
                db('head_img')->insertAll($arr_head);
                return success();
            }
        }
    }

    //保存草稿
    public function saveDraft(Request $request){

        $post = input('post.');
        $head_img = json_decode($post['img']);
        $intro_img = json_decode($post['intro_img']);

        unset($post['img']);
        unset($post['intro_img']);
        //验证表单参数
        $validate = new Validate([
            ['goods_name', 'require', '商品名不能为空'],
            ['feature', 'require', '特色不能为空'],
            ['standard', 'require', '规格不能为空'],
            ['price', 'require', '价格不能为空'],
            ['freight', 'require', '运费不能为空'],
            ['virtual_sales', 'require', '虚拟销量不能为空'],
            ['province_code', 'gt:1', '未选择省'],
            ['city_code', 'gt:1', '未选择市'],
            ['district_code', 'gt:1', '未选择区/县'],
            ['outer_num', 'require', '外部编号不能为空'],
            ['intro', 'require', '详情不能为空'],
        ]);
        //单条验证-方便前端跳到正确提示位置
        foreach ($post as $k => $v) {
            $arr = [$k => $v];
            $validate->scene('edit', [$k]);
            if (!$validate->scene('edit')->check($arr)) {
                unset($post[$k]);
            }
        }
        //表单入库
        if (isset($post['goods_id'])) {
            $post['update_time'] = time();
            db('goods')->update($post);
            //详情图片入库
            $arr_intro=[];
            $arr_intro_name = db('img_goods_intro')->where(['goods_id' => $post['goods_id']])->column('save_name');
            foreach ($intro_img as $v) {

                if (!@in_array($v, $arr_intro_name)) {
                    $arr_intro[] = ['save_name' => $v, 'goods_id' => $post['goods_id']];
                }
            }
            db('img_goods_intro')->insertAll($arr_intro);
            //详情图片垃圾清理
            foreach($arr_intro_name as $v){
                if (!in_array($v, $intro_img)) {
                    db('img_goods_intro')->where(['save_name'=>$v])->delete();
                    unlink('./static/upload/image/editor/'.$v);
                }
            }

            //头像入库
            $arr_head = [];
            $arr_name = db('head_img')->where(['goods_id' => $post['goods_id']])->column('save_name');

            foreach ($head_img as $k=>$v) {

                if (!in_array($v, $arr_name)) {
                    $arr_head[] = ['save_name' => $v, 'goods_id' => $post['goods_id'],'status'=>$k];
                }else{
                    db('head_img')->where(['save_name' => $v])->update(['status'=>$k]);
                }
            }
            db('head_img')->insertAll($arr_head);
            return ['status'=>2];
        } else {
            $post['create_time'] = time();
            $post['status'] = 1;
            $id = db('goods')->insertGetId($post);
            if ($id) {
                //详情图片入库
                $arr_intro = [];
                foreach ($intro_img as $v) {
                    $arr_intro[] = ['save_name' => $v, 'goods_id' => $id];
                }
                db('img_goods_intro')->insertAll($arr_intro);
                //头像入库
                $arr_head = [];
                foreach ($head_img as $k=>$v) {
                    $arr_head[] = ['save_name' => $v, 'goods_id' => $id,'status'=>$k];
                }
                db('head_img')->insertAll($arr_head);
                return ['status'=>1,'id'=>$id];
            }
        }



    }

    //更新商品状态
    public function updateStatus(Request $request) {
        $post = $request->post();
        db('goods')->update($post);
    }

    //下架商品
    public function putGoods(Request $request) {
        $post = $request->post();
        db('goods')->update($post);
    }


}