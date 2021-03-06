<?php

namespace app\common\model;

use think\model\concern\SoftDelete;

class Promotion extends Common
{
    use SoftDelete;
    protected $deleteTime = 'isdel';

    const EXCLUSIVE_NO = 1;
    const EXCLUSIVE_YES = 2;
    const STATUS_OPEN = 1;
    const STATUS_CLOSE = 2;

    const TYPE_PROMOTION = 1;           //类型，促销
    const TYPE_COUPON = 2;              //类型，优惠券
    const TYPE_GROUP = 3;              //类型，团购&秒杀
    const TYPE_SKILL = 4;              //类型，团购&秒杀

    const AUTO_RECEIVE_YES = 1;     //自动领取
    const AUTO_RECEIVE_NO = 2;      //不自动领取



    // 购物车的数据传过来，然后去算促销
    public function toPromotion($cart){
        //return $cart;       //暂时先返回去，不做促销，做好了之后再放开。
        //按照权重取所有已生效的促销列表
        $where[] = ['status','eq',self::STATUS_OPEN];
        $where[] = ['stime','lt',time()];
        $where[] = ['etime','gt',time()];
        $where[] = ['type','eq',self::TYPE_PROMOTION];
        $list = $this->where($where)->order('sort','asc')->select();
        foreach($list as $v){
            $this->setPromotion($v,$cart);
            //如果排他，就跳出循环，不执行下面的促销了
            if($v['exclusive'] == self::EXCLUSIVE_YES){
                break;
            }
        }
        return $cart;
    }

    //购物车的数据传过来，然后去算优惠券
    public function toCoupon(&$cart,$promotion_arr){
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''

        ];
        foreach($promotion_arr as $k => $v){
            //按照权重取所有已生效的促销列表
            $where = [];
            $where[] = ['status','eq',self::STATUS_OPEN];
            $where[] = ['stime','lt',time()];
            $where[] = ['etime','gt',time()];
            $where[] = ['type','eq',self::TYPE_COUPON];
            $where[] = ['id','eq',$v['promotion_id']];
            $info = $this->where($where)->find();

            if(!$info){
                return error_code(15014);
            }
            if($this->setPromotion($info,$cart)){
                $cart['coupon'][$k] = $v['name'];
            }else{
                return error_code(15014);
            }
        }
        $result['status'] = true;
        return $result;
    }


    //根据促销信息，去计算购物车的促销情况
    private function setPromotion ($promotionInfo,&$cart){
        $conditionModel = new PromotionCondition();
        $where['promotion_id'] = $promotionInfo['id'];
        $conditionList = $conditionModel->field('*')->where($where)->select();
        //循环取出所有的促销条件，有一条不满足，就不行，就返回false
        $key = true;
        foreach($conditionList as $v){
            $re = $conditionModel->check($v,$cart,$promotionInfo);
            if($key){
                if(!$re){
                    $key = false;    //多个促销条件中，如果有一个不满足，整体就不满足，但是要运算完所有的促销条件
                }
            }
        }
        if($key){
            //走到这一步就说明所有的促销条件都符合，那么就去计算结果
            $resultModel = new PromotionResult();
            $resultList = $resultModel->where($where)->select();
            foreach($resultList as $v){
                $resultModel->toResult($v,$cart,$promotionInfo);
            }
        }else{
            //如果不满足需求，就要统一标准，把有些满足条件的（2），变成1
            $conditionModel->promotionFalse($cart,$promotionInfo);
        }
        return $key;

    }

    /**
     * 返回layui的table所需要的格式
     * @author sin
     * @param $post
     * @return mixed
     */
    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        $re['sql'] = $this->getLastSql();

        return $re;
    }

    protected function tableWhere($post)
    {

        $where = [];
        $where[] = ['type', 'eq', $post['type']];

        if(isset($post['name']) && $post['name'] != ""){
            $where[] = ['name', 'like', '%'.$post['name'].'%'];
        }
        if(isset($post['status']) && $post['status'] != ""){
            $where[] = ['status', 'eq', $post['status']];
        }
        if(isset($post['exclusive']) && $post['exclusive'] != ""){
            $where[] = ['exclusive', 'eq', $post['exclusive']];
        }
        if(input('?param.date')){
            $theDate = explode(' 到 ',input('param.date'));
            if(count($theDate) == 2){
                $where[] = ['stime', '<', strtotime($theDate[1])];
                $where[] = ['etime', '>', strtotime($theDate[0])];
            }
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = [];
        return $result;
    }
    /**
     * 根据查询结果，格式化数据
     * @author sin
     * @param $list  array格式的collection
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach($list as $k => $v) {
            if($v['stime']){
                $list[$k]['stime'] = getTime($v['stime']);
            }
            if($v['etime']){
                $list[$k]['etime'] = getTime($v['etime']);
            }
            if($v['status']){
                $list[$k]['status'] = config('params.promotion.status')[$v['status']];
            }
            if($v['exclusive']){
                $list[$k]['exclusive'] = config('params.promotion.exclusive')[$v['exclusive']];
            }
        }
        return $list;
    }

    /**
     *
     *  获取可领取的优惠券
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function receiveCouponList()
    {
        $where[] = ['etime','>',time()];                //判断优惠券失效时间 是否可领取
        $where[] = ['status','eq',self::STATUS_OPEN];   //启用状态
        $where[] = ['type','eq',self::TYPE_COUPON];     //促销 类型
        $where[] = ['auto_receive','eq',self::AUTO_RECEIVE_YES];    //自动领取状态
        return $this->field('id,name,status,exclusive,stime,etime')->where($where)->select();
    }

    /**
     *
     *  获取指定id 的优惠券是否可以领取
     * @param $promotion_id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function receiveCoupon($promotion_id)
    {
        $where[] = ['etime','>',time()];                //判断优惠券失效时间 是否可领取
        $where[] = ['status','eq',self::STATUS_OPEN];   //启用状态
        $where[] = ['type','eq',self::TYPE_COUPON];     //促销 类型
        $where[] = ['auto_receive','eq',self::AUTO_RECEIVE_YES];    //自动领取状态
        $where[] = ['id','eq',$promotion_id];
        return $this->field('id,name,status,exclusive,stime,etime')->where($where)->find();
    }

}