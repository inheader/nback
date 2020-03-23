<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: tianyu <tianyu@jihainet.com>
// +----------------------------------------------------------------------
namespace app\Site\controller;

use app\common\controller\Site;
use app\common\model\AreaMallBuyerCoupon;
use app\common\model\AreaMallBuyerCouponCategory;
use app\common\model\AreaMallBuyerCouponGoods;
use app\common\model\AreaMallSite;
use app\common\model\Buyer;
use think\Db;
use think\exception\PDOException;
use app\common\model\Goods as GoodsModel;
use think\facade\Request;

class Coupon extends Site
{
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有管理员才有权限打开此界面'
            ];
        }

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where=[];
                if(isset($params['name']) && !empty($params['name']))
                {
                    $where['cp_title'] = ['cp_title','like',"%".$params['name']."%"];
                }
                if(isset($params['status']) && !empty($params['status']))
                {
                    $where['status'] = $params['status'];
                }

                if(isset($params['date']))
                {
                    $theDate = explode(' 到 ',$params['date']);
                    if(!empty($theDate[0]))
                    {
                        $where['start_time'] = ['start_time','>=',strtotime($theDate[0])];
                    }

                    if(!empty($theDate[1]))
                    {
                        $where['end_time']   = ['end_time','<',strtotime($theDate[1])];
                    }

                }

                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);

                $where['site_id'] = $siteId;

                list($where,$page,$limit) = [$where,$page,$limit];

                $list = Db::name('area_mall_buyer_coupon')
                    ->where($where)
                    ->page($page,$limit)
                    ->select();
                $total = Db::name('area_mall_buyer_coupon')
                    ->where($where)
                    ->count();

                $status = ['0'=>'关闭','1'=>'正常'];
                $useType = ['1'=>'平台推送','2'=>'用户领取','3'=>'不限'];

                foreach ($list as $key=>$val) {
                    $list[$key]['useType']       = $useType[$val['use_type']];
                    $list[$key]['status']       = $status[$val['status']];
                    $list[$key]['startTime']    = date("Y-m-d",$val['start_time']);
                    $list[$key]['endTime']      = date("Y-m-d",$val['end_time']);
                }

                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);

            }
        }

        return $this->fetch();
    }


    /**
     * 添加优惠劵
     * @return array|mixed
     */
    public function add()
    {
//        $this->view->engine->layout(false);

        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有管理员才有权限打开此界面'
            ];
        }
        //店铺信息
        $siteIns = (new AreaMallSite())->where('site_id',$siteId)->find();

        if($this->request->isPost())
        {
            $params = $this->request->param('data');

            if(empty($params['cp_title']))
            {
                return ['status'=>false,'msg'=>'请填写优惠劵名称'];
            }

            $data = [];
            if(isset($params['cp_price_discount']))
            {
                $data['cp_price_discount'] = $params['cp_price_discount'];
            }

            if(isset($params['cp_money_limit']))
            {
                $data['cp_money_limit'] = $params['cp_money_limit'];
            }

            if(isset($params['cp_money_discount']))
            {
                $data['cp_money_discount'] = $params['cp_money_discount'] / 10;
            }

            if(!$params['date']){
                return error_code(15002);
            }else{
                $theDate = explode(' 到 ',$params['date']);
                if(count($theDate) != 2){
                    return error_code(15002);
                }
            }

            //查询归属区域
            $siteIns = (new AreaMallSite())->where('site_id',$siteId)->find();
            $data['mall_id'] = $siteIns['mall_id'];
            $data['site_id'] = $siteId;
            $data['start_time']     = strtotime($theDate[0]);
            $data['end_time']       = strtotime($theDate[1]);
            $data['publish_time']   = strtotime($params['publish_time']);
            $data['use_type']       = $params['use_type'];
            $data['class_type']     = $params['class_type'];
            $data['cp_title']       = $params['cp_title'];
            $data['cp_type']        = $params['cp_type'];
            $data['cp_desc']        = $params['cp_desc'];
            $data['cp_num']         = $params['cp_num'];
            $data['cp_percent_num'] = !empty($params['cp_percent_num']) ? $params['cp_percent_num'] : 1;
            $data['category_id']    = $params['category_id'];
            $data['status']         = !empty($params['status']) ? $params['status'] : 0;

            if(empty($data['cp_num'])){
                //剩余多少张
                $data['cp_remain_num'] = 9999;
            }else{
                $data['cp_remain_num'] = $data['cp_num'];
            }

            Db::startTrans();
            try{
                Db::name('area_mall_buyer_coupon')->insert($data);
                Db::commit();
                return ['status'=>true,'msg'=>'操作成功'];
            }catch (PDOException $e){
                Db::rollback();
                return ['status'=>false,'msg'=>'操作失败'];
            }

        }
        //获取分类
        $category = Db::name('area_mall_buyer_coupon_category')->select();
        //店铺的去除特快和物流劵
        foreach ($category as $key=>$value) {
            if($siteIns['site_shipping_fee_type'] != 'mall')
            {
                if($value['type'] == 7 || $value['type'] == 6)
                {
                    unset($category[$key]);
                }
            }
        }
        $this->assign('category',$category);
        return $this->fetch();
    }


    /**
     * @param null $id
     * @return array|mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($id=null)
    {
        $id = isset($id) ? $id : $this->request->get('id');
        $get = (new AreaMallBuyerCoupon())->get(['cp_id'=>$id]);
        if($this->request->isPost())
        {
            $params = $this->request->param();

            if(empty($params['cp_title']))
            {
                return ['status'=>false,'msg'=>'请填写优惠劵名称'];
            }

            $data = [];
            if(isset($params['cp_price_discount']))
            {
                $data['cp_price_discount'] = $params['cp_price_discount'];
            }

            if(isset($params['cp_money_limit']))
            {
                $data['cp_money_limit'] = $params['cp_money_limit'];
            }

            if(isset($params['cp_money_discount']))
            {
                $data['cp_money_discount'] = $params['cp_money_discount'] / 10;
            }

            if(isset($params['cp_num_limit']))
            {
                $data['cp_num_limit']   = $params['cp_num_limit'];
            }

            if(!input('?param.date') || !input('param.date')){
                return error_code(15002);
            }else{
                $theDate = explode(' 到 ',input('param.date'));
                if(count($theDate) != 2){
                    return error_code(15002);
                }
            }

            $data['cp_title']       = $params['cp_title'];
            $data['start_time']     = strtotime($theDate[0]);
            $data['end_time']       = strtotime($theDate[1]);
            $data['publish_time']   = strtotime($params['publish_time']);
            $data['cp_desc']        = $params['cp_desc'];
            $data['cp_num']         = $params['cp_num'];
            $data['cp_percent_num'] = !empty($params['cp_percent_num']) ? $params['cp_percent_num'] : 1;
            $data['status']         = !empty($params['status']) ? $params['status'] : 0;

            if(empty($data['cp_num'])){
                //剩余多少张
                $data['cp_remain_num'] = 9999;
            }else{
                $data['cp_remain_num'] = $data['cp_num'];
            }

            Db::startTrans();
            try{
                Db::name('area_mall_buyer_coupon')->where('cp_id',$params['cp_id'])->update($data);
                Db::commit();
                return ['status'=>true,'msg'=>'操作成功'];
            }catch (PDOException $e){
                Db::rollback();
                return ['status'=>false,'msg'=>'操作失败'];
            }
        }

        $category = (new AreaMallBuyerCouponCategory())->where('id',$get['category_id'])->find();
        $get['startTime'] = date("Y-m-d H:i:s",$get['start_time']);
        $get['endTime'] = date("Y-m-d H:i:s",$get['end_time']);
        $get['publishTime'] = date("Y-m-d H:i:s",$get['publish_time']);
        $get['category_name'] = $category['name'];
        $get['cp_money_discount'] = $get['cp_money_discount'] * 10;
        $this->assign('find',$get);
        return $this->fetch();
    }


    /**
     * 用户领取优惠劵记录
     * @return mixed
     */
    public function getCouponListLog($cid=null)
    {
        $this->view->engine->layout(false);

        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        $cid = isset($cid) ? $cid  : $this->request->get('cid');
        $cp = AreaMallBuyerCoupon::get(['cp_id'=>$cid]);

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where=[];
                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);
                $where['site_id']   = $siteId;
                $where['cp_id']     = $params['id'];
                list($where,$page,$limit) = [$where,$page,$limit];

                $list = Db::name('area_mall_buyer_coupon_list')
                    ->where($where)
                    ->page($page,$limit)
                    ->select();
                $total = Db::name('area_mall_buyer_coupon_list')
                    ->where($where)
                    ->count();
                $state = [0=>'停用',1=>'可用',2=>'锁定',3=>'已使用'];
                foreach ($list as $key=>$val) {
                    $list[$key]['coupon_state'] = $state[$val['coupon_state']];
                    $list[$key]['buyer_name']   = Buyer::get(['buyer_id'=>$val['buyer_id']])['buyer_name'];
                }

                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);
            }
        }
        $this->assign('cp',$cp);
        return $this->fetch();
    }


    /**
     * @return mixed
     */
    public function addGoods()
    {
        $this->view->engine->layout(false);
        $filter              = input('request.');
        $quickId = isset($filter['cid']) ? $filter['cid'] : 0;
        $this->assign('cid', $quickId);
        return $this->fetch();
    }

    /**
     * @return mixed
     */
    public function editGoods()
    {
        $this->view->engine->layout(false);
        $filter              = input('request.');
        $quickId = isset($filter['cid']) ? $filter['cid'] : 0;
        $this->assign('cid', $quickId);
        return $this->fetch();
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsQuick()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if($this->request->isAjax())
        {
            $goodsModel = new GoodsModel();

            $id = $this->request->get('cid');
            $page  = $this->request->get('page');
            $limit = $this->request->get('limit');
            $name  = $this->request->get('name');

            $filter  = (new AreaMallBuyerCouponGoods())
                ->where('cp_id',$id)
                ->field('spc_id')
                ->select();

            $ids = '';
            foreach ($filter as $key=>$val) {
                $ids .= $val['spc_id'].',';
            }
            $ids = rtrim($ids,',');
            $where = [];
            if (isset($userWhere['mall_id']) && $userWhere['mall_id'] != "") {
                $where[] = ['mall_id', 'eq', $userWhere['mall_id']];
            }

            if(isset($name) && !empty($name))
            {
                $where['name'] = ['name','LIKE','%'.$name.'%'];
            }

            if (isset($filter) && $filter != "") {
                $where[] = ['id', 'in', $ids];
            }

            $list = $goodsModel->where($where)->page($page,$limit)->field('id,image_id,name,brand_id,price,stock')->select();

            foreach ($list as $key=>$val) {
                $list[$key]['cp_id'] = $id;
                $list[$key]['image'] = _sImage($val['image_id']);
            }

            $total = $goodsModel->where($where)->count();

            return ['code'=>0,'count'=>$total,'data'=>$list];
        }
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addGoodsQuickList()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        $goodsModel = new goodsModel();
        $filter              = input('request.');
        $quickId = isset($filter['cid']) ? $filter['cid'] : 0;
        $filter['site_id'] = $siteId;
        //将店铺的其他活动添加的商品也需要隐藏
        $filter['notId'] = (new AreaMallBuyerCouponGoods())->getGoodsIdsForQuick($quickId);
        $notId = [];
        foreach ($filter['notId'] as $key=>$val) {
            $notId[] = $val;
        }
        $filter['notId'] = implode(',',$notId);
        //列表加上权限的筛选
        $filter = array_merge($filter,$userWhere);
        $this->assign('cid', $quickId);

        $list = $goodsModel->tableDataA($filter,true,$this->getMyAuthType());
        return $list;
    }



    /**
     * 添加商品
     * @return array
     */
    public function addGoodsQuick()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();

            $goodsId = $params['id'];
            $goodsIds = explode(",",$goodsId);
            $quickId = $params['cid'];

            $couponGoodsIns = (new AreaMallBuyerCouponGoods());
            collect($goodsIds)->each(function($goodsId)use($quickId,$couponGoodsIns){
                $couponGoodsIns->addQuickGoodsForQuickId($quickId,$goodsId);
            });

            return [
                'status' => true,
                'data' => url('coupon/index'),
                'msg' => '添加成功'
            ];
        }
        return [];
    }


    /**
     * 单品移除
     * @return array
     * @throws \think\Exception
     */
    public function removeQuickGoods()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if($this->request->isAjax())
        {
            $id = $this->request->param('id');

            try{
                $goodsMapping = new AreaMallBuyerCouponGoods();
                $goodsMapping->where('spc_id',$id)->delete();
                return ['status'=>true,'msg'=>'移除成功'];
            }catch (PDOException $e){
                return ['status'=>false,'msg'=>'移除失败'];
            }

        }
        return ['status'=>false,'msg'=>'请求失败'];
    }

    /**
     * 批量移除
     * @param null $ids
     * @return array
     * @throws \Exception
     */
    public function removeQuickGoodsMulti($ids=null)
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if($this->request->isAjax())
        {
            $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');

            $ids = rtrim($ids,',');

            try{
                $goodsMapping = new AreaMallBuyerCouponGoods();
                $goodsMapping->whereIn('spc_id',$ids)->delete();
                return ['status'=>true,'msg'=>'移除成功'];
            }catch (PDOException $e){
                return ['status'=>false,'msg'=>'移除失败'];
            }

        }
        return ['status'=>false,'msg'=>'请求失败'];
    }


    /**
     * 获取优惠劵分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCategory()
    {
        $this->view->engine->layout(false);

        if($this->request->isPost())
        {
            $id = $this->request->param('id');
            //查询分类
            $find = Db::name('area_mall_buyer_coupon_category')->where('id',$id)->field('type,name')->find();

            $this->assign('find',$find);

            return ['status'=>true,'msg'=>'','data'=>$this->view->fetch()];
        }
    }
}