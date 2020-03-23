<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/12/19
 * Time: 13:40
 */

namespace app\mall\controller;


use app\common\controller\Mall;
use app\common\model\AreaMallBillBuyerInfo;
use app\common\model\AreaMallBillOrder;
use app\common\model\Buyer;
use app\common\model\BuyerAddress;
use think\Db;

/**
 * 区域票据管理
 * Class BillMall
 * @package app\mall\controller
 */
class BillMall extends Mall
{

    public function bill_order()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        $model = new AreaMallBillOrder();
        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $page = isset($params['page']) ? $params['page'] : 1;
                $limit = isset($params['limit']) ? $params['limit'] : 10;
                $where['mall_id'] = $mallId;
                //查询条件
                if(isset($params['order_sn']) && !empty($params['order_sn'])) //订单编号
                {
                    $where[] = ['order_sn','like',$params['order_sn'].'%'];
                }
                if(isset($params['date']) && !empty($params['date']))//日期
                {
                    $date = explode(' 到 ',$params['date']);
                    $sta = $date[0].' 00:00:00';
                    $end = $date[1].' 23:59:59';
                    $where = [
                        ['created_at','>=',$sta],
                        ['created_at','<=',$end],
                    ];
                }
                if(isset($params['status']) && !empty($params['status']))//状态
                {
                    $where['status'] = $params['status'];
                }
                list($where,$page,$limit) = [$where,$page,$limit];
                $list = $model
                    ->where($where)
                    ->page($page,$limit)
                    ->select();
                $total = $model
                    ->where($where)
                    ->count();
                foreach ($list as $key=>$item) {
                    $list[$key]['order_bill_sum'] = 0;
                    $list[$key]['buyer_name'] = Buyer::get(['buyer_id'=>$item['buyer_id']])->buyer_name;
                    $list[$key]['order_bill_sum'] += sprintf('%.2f',($item['order_price'] + $item['order_bill_price']));
                }
                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);
            }
        }
        return $this->fetch();
    }

    /**
     * 查看
     * @return mixed
     */
    public function bill_info()
    {
        $this->view->engine->layout(false);
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        //开票订单ID
        $id = $this->request->param('id');
        //查询开票订单
        $info = AreaMallBillOrder::get($id);
        if(!$info) $info = [];
        //查看关联开票用户信息
        $buyer_bill_info = AreaMallBillBuyerInfo::get($info['bill_buyer_id']);
        if(!$buyer_bill_info) $buyer_bill_info = [];
        //查询关联地址
        $address = BuyerAddress::get(['buyer_address_id'=>$buyer_bill_info['address_id']]);
        if(!$address) $address = [];

        $this->assign(compact('info','buyer_bill_info','address'));
        return $this->fetch();
    }

    public function isStatus()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                $id = isset($params['id']) ? $params['id'] : 0;
                $type = isset($params['type']) ? $params['type'] : 'false';
                switch ($type){
                    case true:
                        $status = 1;
                        break;
                    case false:
                        $status = 0;
                        break;
                    default:
                        $status = 4;
                        break;
                }
                Db::startTrans();
                try{
                    AreaMallBillOrder::update(['status'=>$status],['id'=>$id]);
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (\Exception $e)
                {
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }

            }
        }
        return [];
    }

    /**
     * 统计
     * @return mixed
     */
    public function bill_statistics()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;

        $collect = (new AreaMallBillOrder())->where(['mall_id'=>$mallId,'status'=>AreaMallBillOrder::BILL_ORDER_STATUS_OK])->sum('order_price');//开票订单额

        $collect_ok = (new AreaMallBillOrder())->where(['mall_id'=>$mallId,'status'=>AreaMallBillOrder::BILL_ORDER_STATUS_OK])->count();//已处理开票订单

        $collect_no = (new AreaMallBillOrder())->where(['mall_id'=>$mallId,'status'=>AreaMallBillOrder::BILL_ORDER_STATUS_NO])->count();//未处理开票订单

        $collect_refund = (new AreaMallBillOrder())->where(['mall_id'=>$mallId,'order_state'=>40])->count();//退货开票订单

        $this->assign(compact('collect','collect_ok','collect_no','collect_refund'));

        return $this->fetch();
    }

    /**
     * 开票订单统计
     * @return array
     */
    public function statistics()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;

        //7天内已完成订单
        $finish = AreaMallBillOrder::mall_statistics($mallId,AreaMallBillOrder::BILL_ORDER_STATUS_OK);

        //7天内未完成订单
        $no_finish = AreaMallBillOrder::mall_statistics($mallId,AreaMallBillOrder::BILL_ORDER_STATUS_NO);

        $data = [
            'legend' => [
                'data' => ['已处理', '未处理']
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => $finish['day']
                ]
            ],
            'series' => [
                [
                    'name' => '已处理',
                    'type' => 'line',
                    'data' => $finish['data']
                ],
                [
                    'name' => '未处理',
                    'type' => 'line',
                    'data' => $no_finish
                ]
            ]
        ];

        return $data;
    }

}