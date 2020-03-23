<?php

namespace app\Site\controller;

use app\common\controller\Site;
use app\common\model\ActDiscount;
use app\common\model\ActFullDelivery;
use app\common\model\ActFullDeliveryGoods;
use app\common\model\ActFullDeliveryRule;
use app\common\model\BuyerCouponRule;
use app\common\model\BuyerCouponSn;
use app\common\model\Promotion as PromotionModel;
use app\common\model\PromotionCondition;
use app\common\model\PromotionResult;
use Request;
use app\common\model\GoodsCat;

class Promotion extends Site
{

    /**
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if(Request::isAjax()) {
            $request = input('param.');

            $date = isset($request['act_date']) ? $request['act_date'] : '';
            $theDate = explode(' 到 ',$date);
            $beginTime = isset($theDate[0]) ? strtotime($theDate[0]) : 0;
            $endTime = isset($theDate[1]) ? strtotime($theDate[1]) : 0;
            $isEnable = isset($request['isEnable']) ? $request['isEnable'] : 0;
            $keywords = isset($request['actName']) ? $request['actName'] : '';
            $page = isset($request['page']) ? $request['page'] : 1;
            $limit = isset($request['limit']) ? $request['limit'] : 10;

//            //0满减活动  1折扣活动
//            $actType = isset($request['actType']) ? $request['actType'] : 0;
//            if(!empty($actType)){
//                //折扣活动
//                $actModel = new ActDiscount();
//            }else{
//                //满减活动
//                $actModel = new ActFullDelivery();
//            }

            //满减活动
            $actModel = new ActFullDelivery();

            $actList = $actModel->getActListForPar($userWhere,$beginTime,$endTime,$isEnable,$keywords,$page,$limit);
            $actCount = $actModel->getActCountForPar($userWhere,$beginTime,$endTime,$isEnable,$keywords);

            $re = [];

            $re['code'] = 0;
            $re['msg'] = '';
            $re['count'] = $actCount;
            $re['data'] = $actList;

            return $re;
        }
        return $this->fetch();

    }

    /**
     * 优惠券列表
     * @return mixed
     */
    public function coupon()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if(Request::isAjax()) {
            $request = input('param.');
            $date = isset($request['date']) ? $request['date'] : '';
            $theDate = explode(' 到 ',$date);
            $beginTime = isset($theDate[0]) ? strtotime($theDate[0]) : 0;
            $endTime = isset($theDate[1]) ? strtotime($theDate[1]) : 0;
            $isEnable = isset($request['status']) ? $request['status'] : 0;
            $keywords = isset($request['name']) ? $request['name'] : '';
            $page = isset($request['page']) ? $request['name'] : 1;
            $limit = isset($request['limit']) ? $request['limit'] : 10;

            //满减活动
            $couponModel = new BuyerCouponRule();

            $actList = $couponModel->getCouponListForPar($userWhere,$beginTime,$endTime,$isEnable,$keywords,$page,$limit);
            $actCount = $couponModel->getCouponCountForPar($userWhere,$beginTime,$endTime,$isEnable,$keywords);

            $re = [];

            $re['code'] = 0;
            $re['msg'] = '';
            $re['count'] = $actCount;
            $re['data'] = $actList;

            return $re;



            $promotionModel = new PromotionModel();
            $request = input('param.');
            $request['type'] = $promotionModel::TYPE_COUPON;

            return $promotionModel->tableData($request);
        }
        return $this->fetch();

    }

    //添加促销
    public function add()
    {
//        dump(input('param'));
//        exit();

        if(Request::isPost()){
            if(!input('?param.act_name')){
                return error_code(15001);
            }
            if(!input('?param.date') || !input('param.date')){
                return error_code(15002);
            }else{
                $theDate = explode(' 到 ',input('param.act_date'));
                if(count($theDate) != 2){
                    return error_code(15002);
                }
            }
            $data['name'] = input('param.name');
            $data['stime'] = strtotime($theDate[0]);
            $data['etime'] = strtotime($theDate[1]);
            $data['act_status'] = input('param.act_status/d',1);
            $data['sort'] = input('param.sort/d',100);
            $data['exclusive'] = input('param.act_text/d',1);
            $promotionModel = new PromotionModel();
            $id = $promotionModel->insertGetId($data);
            return [
                'status' => true,
                'data' => url('promotion/edit',['id'=>$id]),
                'msg' => ''
            ];
        }
        return $this->fetch();
    }
    //添加优惠券
    public function couponAdd()
    {
        if(Request::isPost()){
            //获取权限的筛选条件
            $userWhere = $this->getMyUserAddWhere();

            if(!input('?param.cp_title')){
                return error_code(15001);
            }
            if(!input('?param.date') || !input('param.date')){
                return error_code(15002);
            }else{
                $theDate = explode(' 到 ',input('param.date'));
                if(count($theDate) != 2){
                    return error_code(15002);
                }
            }
            $data['cp_title'] = input('param.cp_title');
            $data['cp_type'] = 1;//目前只有金额券
            $data['cp_desc'] = input('param.cp_desc');
            $data['cp_num'] = input('param.cp_num');
            $data['cp_percent_num'] = input('param.cp_percent_num');
            $data['cp_is_show'] = input('param.cp_is_show',0);
            $data['cp_money_limit'] = input('param.cp_money_limit');
            $data['cp_money_discount'] = input('param.cp_money_discount');
            $data['start_time'] = strtotime($theDate[0]);
            $data['end_time'] = strtotime($theDate[1]);
            $data['publish_time'] = input('param.publish_time');
            $data = array_merge($data,$userWhere);
            $couponModel = new BuyerCouponRule();

            $id = $couponModel->insertGetId($data);
            return [
                'status' => true,
                'data' => url('promotion/couponedit',['id'=>$id]),
                'msg' => ''
            ];
        }
        //这里说明是新增，将现在的时间传到前台
        $info = [];
        $info['start_time'] = strtotime(date('Y-m-d')); //今天凌晨
        $info['end_time'] = strtotime(date('Y-m-d',strtotime('+1 day')));//今天晚上
        $this->assign('info', $info);
        return $this->fetch('couponedit');
    }

    /**
     * 活动编辑
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $actModel = new ActFullDelivery();
        $actId = input('param.actId');


        if(Request::isPost()){
            //获取权限的筛选条件
            $userAddWhere = $this->getMyUserAddWhere();

            $param = input('param.');

            $actId = isset($param['actId']) ? $param['actId'] : 0;
            $actName = isset($param['actName']) ? $param['actName'] : '';
            $isEnable = isset($param['isEnable']) ? $param['isEnable'] : 0;
            if(empty($isEnable)){
                $isEnable = 2;//默认的禁用是0，但是数据库禁用状态是2
            }
            $discountDescription = isset($param['discountDescription']) ? $param['discountDescription'] : '';
            $date = isset($param['actDate']) ? $param['actDate'] : '';
            $theDate = explode(' 到 ',$date);
            $beginTime = isset($theDate[0]) ? strtotime($theDate[0]) : 0;
            $endTime = isset($theDate[1]) ? strtotime($theDate[1]) : 0;

            $updateActInfo = [
                'actName'               =>$actName,
                'isEnable'              =>$isEnable,
                'discountDescription'   =>$discountDescription,
                'beginTime'             =>$beginTime,
                'endTime'               =>$endTime,
            ];

            $actId = $actModel->updateActInfo($actId,$updateActInfo,$userAddWhere);

            //活动的规格的信息
            $weight = isset($param['weight']) ? $param['weight'] : [];
            $actType = isset($param['actType']) ? $param['actType'] : [];
            $fullMoney = isset($param['fullMoney']) ? $param['fullMoney'] : [];
            $reduceMoney = isset($param['reduceMoney']) ? $param['reduceMoney'] : [];
            $reduceDiscount = isset($param['reduceDiscount']) ? $param['reduceDiscount'] : [];
            $isFreeShipping = isset($param['isFreeShipping']) ? $param['isFreeShipping'] : [];
            $ruleList = [];
            collect($weight)->each(function($v,$k) use($actType,$fullMoney,$reduceMoney,$reduceDiscount,$isFreeShipping,&$ruleList){
                $ruleList[$k]['weight'] = $v;
                $ruleList[$k]['actType'] = $actType[$k];
                $ruleList[$k]['fullMoney'] = $fullMoney[$k];
                $ruleList[$k]['reduceMoney'] = $reduceMoney[$k];
                $ruleList[$k]['reduceDiscount'] = $reduceDiscount[$k];
                $ruleList[$k]['isFreeShipping'] = $isFreeShipping[$k];
            });

            //删除之前的活动规则
            $actModel->deleteActRule($userWhere,$actId);

            $actRuleModel = (new ActFullDeliveryRule());
            //更新店铺地址列表
            collect($ruleList)->each(function($info)  use($actRuleModel,$actId){
                $actRuleModel->addActRuleInfo($actId,$info);
            });

            return [
                'status' => true,
                'data' => url('promotion/edit',['actId'=>$actId]),
                'msg' => ''
            ];
        }

        $actInfo = $actModel-> getActInfo($userWhere,$actId);
        if(empty($actInfo)){
            $this->error('没有找到此促销记录');
        }

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            $this->error('只有店铺管理员才有权限打开此界面');
        }

        $this->assign('actInfo',$actInfo);

        return $this->fetch();
    }

    //取消商品参加活动
    public function cancelActGoods()
    {
        $goodsId = input('param.id');
        $actId = input('param.actId');
        //删除某活动的商品
        $actGoodsModel = (new ActFullDeliveryGoods());
        $actGoodsModel->deleteActGoodsForActId($actId,$goodsId);
        return [
            'status' => true,
            'data' => url('goods/goodslistforpage?actId='),
            'msg' => '取消成功'
        ];
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addActGoods()
    {
        $goodsId = input('param.id');
        $goodsIds = explode(",",$goodsId);
        $actId = input('param.actId');

        $actModel = (new ActFullDelivery());
        //取消全部活动
        $actModel->cancelAllGoods($actId);

        //添加某活动的商品
        $actGoodsModel = (new ActFullDeliveryGoods());
        collect($goodsIds)->each(function($goodsId)use($actId,$actGoodsModel){
            $actGoodsModel->addActGoodsForActId($actId,$goodsId);
        });
        return [
            'status' => true,
            'data' => url('goods/goodslistforpage?actId='),
            'msg' => '添加成功'
        ];
    }


    /**
     * 取消选中商品
     * @return array
     */
    public function cancelChooseActGoods()
    {
        $goodsId = input('param.ids',[]);
        $goodsId = explode(",",$goodsId);

        $actId = input('param.actId');
        //删除某活动的商品
        $actGoodsModel = (new ActFullDeliveryGoods());
        $actGoodsModel->deleteActGoodsForActId($actId,$goodsId);
        return [
            'status' => true,
            'data' => url('goods/goodslistforpage?actId='),
            'msg' => '取消成功'
        ];
    }



    /**
     * 取消全部商品
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelActAllGoods()
    {
        $actId = input('param.actId');

        $actModel = (new ActFullDelivery());
        //取消全部活动
        $actModel->cancelAllGoods($actId);

        //删除某活动的商品
        $actGoodsModel = (new ActFullDeliveryGoods());
        $actGoodsModel->deleteActAllGoodsForActId($actId);
        return [
            'status' => true,
            'data' => url('goods/goodslistforpage?actId='),
            'msg' => '取消成功'
        ];

    }

    /**
     * 添加全部商品
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addActAllGoods()
    {
        $actId = input('param.actId');
        $actModel = (new ActFullDelivery());
        //添加全部商品
        $actModel->addAllGoods($actId);
        return [
            'status' => true,
            'data' => url('goods/goodslistforpage?actId='),
            'msg' => '添加成功'
        ];

    }

    /**
     * 优惠券编辑
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function couponEdit()
    {
        $couponModel = new BuyerCouponRule();
        $where['cp_id'] = input('param.id');
        $info = $couponModel->where($where)->find();
        if(!$info){
            //这里说明是新增
//            $this->error('没有找到此优惠券记录');
        }

        if(Request::isPost()){
            if(!input('?param.cp_title')){
                return error_code(15001);
            }
            if(!input('?param.date') || !input('param.date')){
                return error_code(15002);
            }else{
                $theDate = explode(' 到 ',input('param.date'));
                if(count($theDate) != 2){
                    return error_code(15002);
                }
            }

            $data['cp_title'] = input('param.cp_title');
            $data['cp_type'] = 1;//目前只有金额券
            $data['cp_desc'] = input('param.cp_desc');
            $data['cp_num'] = input('param.cp_num');
            $data['cp_percent_num'] = input('param.cp_percent_num');
            $data['cp_is_show'] = input('param.cp_is_show',0);
            $data['cp_money_limit'] = input('param.cp_money_limit');
            $data['cp_money_discount'] = input('param.cp_money_discount');
            $data['start_time'] = strtotime($theDate[0]);
            $data['end_time'] = strtotime($theDate[1]);

            if(empty($data['cp_num'])){
                //剩余多少张
                $data['cp_remain_num'] = 9999;
            }else{
                $data['cp_remain_num'] = $data['cp_num'];
            }

            //这边需要将时间转化为时间戳
            $data['publish_time'] = strtotime(input('param.publish_time'));
            $couponModel = new BuyerCouponRule();
            if(!empty($info)){
                $id = $couponModel->where($where)->update($data);
            }else{
                //获取权限的筛选条件
                $userWhere = $this->getMyUserAddWhere();
                $data = array_merge($data,$userWhere);
                $id = $couponModel->insertGetId($data);
            }
            return [
                'status' => true,
                'data' => url('promotion/edit',['id'=>$id]),
                'msg' => ''
            ];
        }

        $this->assign('info',$info);

        return $this->fetch('couponedit');
    }

    /**
     * 优惠券领取记录
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function couponLog()
    {
        $this->view->engine->layout(false);
        $cpId = input('id');
        $ajax = input('_ajax');
        if ($ajax==1) {
            $page = input('page',1);
            $limit = input('limit',10);
            $buyerCouponSnModel = new BuyerCouponSn();
            $list = $buyerCouponSnModel->getCouponLog(0,$cpId,$page,$limit);
            $count = $buyerCouponSnModel->getCouponLog(1,$cpId);
            return [
                'status' => true,
                'msg' => '获取成功',
                'data' => $list,
                'count' => $count
            ];
        }else{
            $this->assign('id', $cpId);
            return $this->fetch('couponlog');
        }

    }



    /**
     * 删除活动
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function del()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            $this->error('只有店铺管理员才有权限打开此界面');
        }
        $actModel = new ActFullDelivery();
        $actId = input('param.id');

        return $actModel ->deleteActIns($actId,$siteId);
    }
    public function couponDel()
    {
        $promotionModel = new PromotionModel();
        $where['id'] = input('param.id');
        $info = $promotionModel->where($where)->find();
        if(!$info){
            return error_code(10002);
        }
        if($promotionModel::destroy($info['id'])){
            return [
                'status' => true,
                'data' => '',
                'msg' => ''
            ];
        }else{
            return error_code(10007);
        }
    }


    //条件列表
    public function conditionList()
    {
        $conditionModel = new PromotionCondition();
        if(!input('?param.id')){
            return error_code(10003);
        }

        //校验是否有此权限
        $promotionModel = new PromotionModel();
        $pwhere['id'] = input('param.id');
        $info = $promotionModel->where($pwhere)->find();
        if(!$info){
            return error_code(10002);
        }

        //$where['id'] = input('param.id');
        $where['promotion_id'] = input('param.id');
        return $conditionModel->tableData($where);
    }
    //单纯的选择促销条件
    public function conditionAdd()
    {
        $this->view->engine->layout(false);
        $conditionModel = new PromotionCondition();
        $this->assign('code',$conditionModel->code);
        return [
            'status' => true,
            'data' => $this->fetch('conditionadd'),
            'msg' => ''
        ];
    }
    //添加促销条件
    public function conditionEdit()
    {
        $this->view->engine->layout(false);

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if(!(input('?param.condition_code')&& input('?param.promotion_id')) && !input('?param.id')){
            return error_code(15003);
        }

        //校验是否有此权限
        $promotionModel = new PromotionModel();
        $pwhere['id'] = input('param.promotion_id');
        $pinfo = $promotionModel->where($pwhere)->find();
        if(!$pinfo){
            return error_code(10002);
        }

        $conditionModel = new PromotionCondition();

        if(Request::isPOST()){
            $data = input('param.');
            return $conditionModel->addData($data);
        }

        //如果是修改，就取数据，否则就是新增，直接渲染模板
        if(input('?param.id')){
            $info = $conditionModel->getInfo(input('param.id'));
            if(!$info){
                return error_code(15004);
            }
            $code = $info['code'];
            $this->assign($info->toArray());
        }else{
            $code = input('param.condition_code');
            $this->assign('promotion_id',input('param.promotion_id/d'));
            $this->assign('code',$code);
        }

        //初始化数据
        switch ($code){
            case 'GOODS_CATS':
                $goodsCatModel = new GoodsCat();
                $catList       = $goodsCatModel->getCatByParentId(0,$userWhere);
                $this->assign('catList', $catList);

                break;
        }


        return [
            'status' => true,
            'data' => $this->fetch('promotion/condition/'.$code),
            'msg' => ''
        ];
    }
    //促销条件删除
    public function conditionDel()
    {
        //校验是否有此权限
        $promotionModel = new PromotionModel();
        $pwhere['id'] = input('param.promotion_id');
        $info = $promotionModel->where($pwhere)->find();
        if(!$info){
            return error_code(10002);
        }

        $conditionModel = new PromotionCondition();
        return $conditionModel->toDel(input('param.id'));
    }

    //促销结果列表
    public function resultList()
    {
        $resultModel = new PromotionResult();
        if(!input('?param.id')){
            return error_code(10003);
        }

        //校验是否有此权限
        $promotionModel = new PromotionModel();
        $pwhere['id'] = input('param.id');
        $info = $promotionModel->where($pwhere)->find();
        if(!$info){
            return error_code(10002);
        }



        //$where['id'] = input('param.id');
        $where['promotion_id'] = input('param.id');
        return $resultModel->tableData($where);
    }
    //单纯的选择促销结果
    public function resultAdd()
    {
        $this->view->engine->layout(false);
        $resultModel = new PromotionResult();
        $this->assign('code',$resultModel->code);
        return [
            'status' => true,
            'data' => $this->fetch('resultadd'),
            'msg' => ''
        ];
    }
    //添加促销条件
    public function resultEdit()
    {
        $this->view->engine->layout(false);

        if(!(input('?param.result_code')&& input('?param.promotion_id')) && !input('?param.id')){
            return error_code(15003);
        }

        //校验是否有此权限
        $promotionModel = new PromotionModel();
        $pwhere['id'] = input('param.promotion_id');
        $info = $promotionModel->where($pwhere)->find();
        if(!$info){
            return error_code(10002);
        }

        $resultModel = new PromotionResult();


        if(Request::isPOST()){$data = input('param.');
            return $resultModel->addData($data);
        }

        //如果是修改，就取数据，否则就是新增，直接渲染模板
        if(input('?param.id')){
            $info = $resultModel->getInfo(input('param.id'));
            if(!$info){
                return error_code(15004);
            }
            $code = $info['code'];
            $this->assign($info->toArray());
        }else{
            $code = input('param.result_code');
            $this->assign('promotion_id',input('param.promotion_id/d'));
            $this->assign('code',$code);
        }


        return [
            'status' => true,
            'data' => $this->fetch('promotion/result/'.$code),
            'msg' => ''
        ];
    }
    //促销条件删除
    public function resultDel()
    {
        //校验是否有此权限
        $promotionModel = new PromotionModel();
        $pwhere['id'] = input('param.promotion_id');
        $info = $promotionModel->where($pwhere)->find();
        if(!$info){
            return error_code(10002);
        }

        $resultModel = new PromotionResult();
        return $resultModel->toDel(input('param.id'));
    }

    //优惠券下载
    public function couponUpload()
    {
        if(!input('?param.id')){
            return error_code(10000);
        }



        $this->assign('id',input('param.id'));
        return [
            'status' => true,
            'data' => $this->fetch('resultadd'),
            'msg' => ''
        ];
    }


    /**
     * @return mixed
     */
    public function group()
    {
        if(Request::isAjax()) {
            $promotionModel = new PromotionModel();
            $request = input('param.');
            $request['type'] = $promotionModel::TYPE_GROUP;

            return $promotionModel->tableData($request);
        }
        return $this->fetch();
    }

    //添加团购秒杀
    public function groupAdd()
    {
        if (Request::isPost()) {
            if (!input('?param.name')) {
                return error_code(15001);
            }
            if (!input('?param.date') || !input('param.date')) {
                return error_code(15002);
            } else {
                $theDate = explode(' 到 ', input('param.date'));
                if (count($theDate) != 2) {
                    return error_code(15002);
                }
            }
            $promotionModel = new PromotionModel();
            $data['name']   = input('param.name');
            $data['stime']  = strtotime($theDate[0]);
            $data['etime']  = strtotime($theDate[1]);
            $data['status'] = input('param.status/d', 1);
            $data['sort']   = input('param.sort/d', 100);
            $data['type']   = input('param.type/d', 3);
            $data['params'] = json_encode(input('param.params/a', []));
            $id             = $promotionModel->insertGetId($data);
            return [
                'status' => true,
                'data'   => url('promotion/groupedit', ['id' => $id]),
                'msg'    => '',
            ];
        }
        return $this->fetch('groupadd');
    }

    //编辑团购（秒杀）
    public function groupEdit()
    {
        $promotionModel = new PromotionModel();
        $where[] = ['id','=',input('param.id/d','0')];

        $where[] = ['type','in',[$promotionModel::TYPE_GROUP,$promotionModel::TYPE_SKILL]];
        $info = $promotionModel->where($where)->find();

        if(!$info){
            $this->error('没有找到此促销记录');
        }

        if(Request::isPost()){
            if(!input('?param.name')){
                return error_code(15001);
            }
            if(!input('?param.date') || !input('param.date')){
                return error_code(15002);
            }else{
                $theDate = explode(' 到 ',input('param.date'));
                if(count($theDate) != 2){
                    return error_code(15002);
                }
            }
            $data['name'] = input('param.name');
            $data['stime'] = strtotime($theDate[0]);
            $data['etime'] = strtotime($theDate[1]);
            $data['status'] = input('param.status/d',2);
            $data['sort'] = input('param.sort/d',100);
            $data['exclusive'] = input('param.exclusive/d',1);
            $promotionModel = new PromotionModel();
            $id = $promotionModel->where($where)->update($data);
            return [
                'status' => true,
                'data' => url('promotion/edit',['id'=>$id]),
                'msg' => ''
            ];
        }
        $info['params'] = json_decode($info['params'],true);
        $this->assign('info',$info);

        return $this->fetch('groupedit');
    }
}