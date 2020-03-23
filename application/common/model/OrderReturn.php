<?php
namespace app\common\model;

use think\Validate;
use think\Db;
use think\model\concern\SoftDelete;

/**
 * 退货单列表
 * Class OrderReturn
 * @package app\common\model
 */
class OrderReturn extends Common
{
    protected $pk = 'return_id';

    const TYPE_REFUND = 0;        //售后类型 退款
    const TYPE_RESHIP = 1;       //售后类型 退货


    const AUDITED_BEFORE        = 1;    //待审核
    const RETURN_ING            = 2;   //退货中
    const AUDITED_GOODS_ERROR   = 3;   //审核退货失败
    const RETURN_GOODS_SUCCESS  = 4;   //退货成功
    const RETURN_GOODS_ERROR    = 5;  //退货失败
    const RETURN_MONEY_ING      = 6;  //申请退款中
    const RETURN_MONEY_WAIT     = 7;  //等待退款
    const RETURN_MONEY_SUCCESS  = 8;  //退款成功
    const RETURN_MONEY_ERROR    = 9;  //退款失败
    const CANCEL_RETURN_GOODS   = 10;  //取消退货
    const CANCEL_RETURN_MONEY   = 11;  //取消退款

    //
    const WEIXIN_PAY_CODE       = 'WEIXIN_PAY';//微信支付
    const POINT_PAY_CODE        = 'POINT_PAY';//积分支付
    const VCOUNT_PAY_CODE       = 'VCOUNT_PAY';//余额支付
    const CREDIT_PAY_CODE       = 'CREDIT_PAY';//店铺赊账支付
    const AREA_CREDIT_PAY_CODE  = 'AREA_CREDIT_PAY';//平台赊账支付
    const COUPON_PAY_CODE       = 'COUPON_PAY';//优惠劵支付

    /**
     * 订单状态名称
     * @var array
     */
    private static $status_name = [
        self::AUDITED_BEFORE        => '待审核',
        self::RETURN_ING            => '退货中',
        self::AUDITED_GOODS_ERROR   => '审核退货失败',
        self::RETURN_GOODS_SUCCESS  => '退货成功',
        self::RETURN_GOODS_ERROR    => '退货失败',
        self::RETURN_MONEY_ING      => '申请退款中',
        self::RETURN_MONEY_WAIT     => '等待退款',
        self::RETURN_MONEY_SUCCESS  => '退款成功',
        self::RETURN_MONEY_ERROR    => '退款失败',
        self::CANCEL_RETURN_GOODS   => '取消退货',
        self::CANCEL_RETURN_MONEY   => '取消退款',
    ];

    private static $pay_code = [
        self::WEIXIN_PAY_CODE       => '微信退款',
        self::POINT_PAY_CODE        => '积分退款',
        self::VCOUNT_PAY_CODE       => '余额退款',
        self::CREDIT_PAY_CODE       => '店铺赊账退款',
        self::AREA_CREDIT_PAY_CODE  => '平台赊账退款',
        self::COUPON_PAY_CODE       => '优惠劵退款',
    ];

    //时间自动存储
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    public function getMyBuyerId(){ return isset($this->buyer_id) ? $this->buyer_id : null;}
    public function getMyStatus() { return isset($this->status) ? $this->status : null;}
    public function getMyStatusName(){ return OrderReturn::$status_name[$this->getMyStatus()];}
    public function getMyReturnTypeName() { return $this->refund_type == 0 ? '退款' : '退货';}

    /**
     * @param $returnId
     * @return OrderReturn
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myInstanceByReturnId($returnId){
        return $this->where('return_id', $returnId)->find();
    }



    /**
     * @param $returnId
     * @return OrderReturn
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myInstanceByReturnInfo($orderId,$orderSn){
        return $this->where(['order_id'=>$orderId,'order_sn'=>$orderSn])->find();
    }

    /**
     * 返回layui的table所需要的格式
     * @author sin
     * @param $post
     * @return mixed
     */
    public function tableListData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = 20;
        }
        $tableWhere = $this->sqlWhere($post);
        $list = $this->field($tableWhere['field'])
            ->where($tableWhere['where'])
            ->order($tableWhere['order'])
            ->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        $re['sql'] = $this->getLastSql();

        return $re;
    }

    protected function sqlWhere($post)
    {
        $where = [];
        //用户是店铺权限
        if(isset($post['site_id']) && $post['site_id'] != ""){
            $where[] = ['site_id', 'eq', $post['site_id']];
        }
        //用户是区域权限
        if(isset($post['mall_id']) && $post['mall_id'] != ""){
            $where[] = ['mall_id', 'eq', $post['mall_id']];
        }

        if(isset($post['return_id']) && $post['return_id'] != ""){
            $where[] = ['return_id', 'eq', $post['return_id']];
        }

        if(isset($post['order_id']) && $post['order_id'] != ""){
            $where[] = ['order_id', 'eq', $post['order_id']];
        }
        if(isset($post['phone']) && $post['phone'] != ""){
            $where[] = ['phone', 'eq', $post['phone']];
        }

        //退货类型
        if(isset($post['refund_type']) && $post['refund_type'] != ""){
            $where[] = ['refund_type', 'eq', $post['refund_type']];
        }


// 1：待审核
// 2：退货中
// 3:审核退货失败
// 4:退货成功
// 5：退货失败
// 6:申请退款中（已收货【退货流程】）
// 7:审核退款成功，等待退款
// 8：退款成功
// 9: 退款失败
// 10: 取消退货
// 11: 取消退款


        if(isset($post['status']) && $post['status'] != ""){
            $where[] = ['status', 'eq', $post['status']];
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = "create_time DESC";
        return $result;
    }

    /**
     * 退货处理的数据
     * @param $post
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function tableReturnListData($post)
    {

        if(isset($post['page']))
        {
            $page = $post['page'];
        }else{
            $page = 1;
        }

        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = 20;
        }

        $tableWhere = $this->sqlReturnWhere($post);

//        $list = $this->field($tableWhere['field'])
//            ->where($tableWhere['where'])
//            ->order($tableWhere['order'])
//            ->page($page,$limit)
//            ->select();
//
//        $total = $this->field($tableWhere['field'])
//            ->where($tableWhere['where'])
//            ->order($tableWhere['order'])
//            ->count();

        $list = $this->field($tableWhere['field'])
            ->where($tableWhere['where'])
            ->order($tableWhere['order'])
            ->paginate($limit);


        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $data = collect($data)->map(function($info){
//            dump($info['status']);
//            $info['status_name'] = isset($this->status_name[$info['status']]) ? $this->status_name[$info['status']] : '';
//            dump($info['status_name']);
            $info['status_name'] = OrderReturn::$status_name[$info['status']];
            $info['refund_type_name'] = !empty($info['refund_type']) ? '退款退货' : '只退款';
            $info['return_pay_code']    = OrderReturn::$pay_code[$info['return_pay_code']];
            $info['create_time'] = date("Y-m-d H:i:s",$info['create_time']);
            return $info;
        })->all();

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        $re['sql'] = $this->getLastSql();

        return $re;
    }

    /**
     * 退货的SQL条件
     * @param $post
     * @return mixed
     */
    protected function sqlReturnWhere($post)
    {
        $where = [];
        //退货类型是退货
        //$where[] = ['refund_type', 'eq', 1];

        //状态
        if(isset($post['status']) && !empty($post['status']))
        {
            $where[] = ['status','eq',$post['status']];
        }

        //退货类型是退货退款且是已经通过审核的
        if(isset($post['refund_type']) && $post['refund_type'] != ""){
            $where[] = ['refund_type', 'eq', $post['refund_type']];
        }

        //用户是店铺权限
        if(isset($post['site_id']) && $post['site_id'] != ""){
            $where[] = ['site_id', 'eq', $post['site_id']];
        }
        //用户是区域权限
        if(isset($post['mall_id']) && $post['mall_id'] != ""){
            $where[] = ['mall_id', 'eq', $post['mall_id']];
        }

        if(isset($post['order_sn']) && !empty($post['order_sn'])){
            $where[] = ['order_sn', 'eq', $post['order_sn']];
        }

        if(isset($post['return_sn']) && $post['return_sn'] != ""){
            $where[] = ['return_sn', 'eq', $post['return_sn']];
        }
        if(isset($post['phone']) && $post['phone'] != ""){
            $where[] = ['phone', 'eq', $post['phone']];
        }

        if(isset($post['buyer_id']) && $post['buyer_id'] != ""){
            $where[] = ['buyer_id', 'eq', $post['buyer_id']];
        }

        //物流单号
        if(isset($post['shipping_code']) && $post['shipping_code'] != ""){
            $where[] = ['shipping_code', 'like', '%'.$post['shipping_code'].'%'];
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = "create_time DESC";
        return $result;
    }

    /**
     * @param $returnId
     * @param $status : 审核结果：1通过，2不通过
     * @param $remark
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function audit($returnId,array $data){
        $returnIns = $this->myInstanceByReturnId($returnId);
        if(!in_array($returnIns->getMyStatus(), [1, 2, 6]) || !in_array($data['status'], [1, 2])){
            return error_code(13207);
        }

        $result = [];
        switch($returnIns->getMyStatus()){
            case 1: //申请退货，待审核
                $result['status'] = $data['status'] == 1 ? 2 : 5;
                break;
            case 2: //申请退货成功，退货中，需改成收货成功，待退款或者退款失败
                $result['status'] = $data['status'] == 1 ? 7 : 9;
                break;
            case 6: //申请退款中
                $result['status'] = $data['status'] == 1 ? 7 : 9;
                break;
            default:
                break;
        }

        if(isset($data['reason']) && !empty($data['reason']))
        {
            $result['reason'] = $data['reason'];
        }

        if(isset($data['refund_price']))
        {
            $result['refund_price'] = $data['refund_price'];
        }

        if(isset($data['refund_shipping_fee']))
        {
            $result['return_shipping_fee'] = $data['refund_shipping_fee'];
        }
        if(isset($data['refund_floor_fee']))
        {
            $result['return_floor_fee'] = $data['refund_floor_fee'];
        }
        if(isset($data['refund_transport_fee']))
        {
            $result['return_transport_fee'] = $data['refund_transport_fee'];
        }
        if(isset($data['is_return_express']))
        {
            $result['is_return_express'] = $data['is_return_express'];
        }
        $this->updateData($returnId, $result);

        return [
            'status' => true,
            'data' => [],
            'msg' => ''
        ];
    }

    /**
     * @param array $returnId
     * @param array $updateInfo
     * @return false|int|static
     */
    public function updateData($returnId, $updateInfo = []){
        return $this->where('return_id', $returnId)->update($updateInfo);
    }

}