<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/12/17
 * Time: 8:46
 */

namespace app\site\controller;

use app\common\model\AreaMallBill;
use app\common\model\AreaMallBillBuyerInfo;
use app\common\model\AreaMallBillOrder;
use app\common\model\AreaMallSite;
use app\common\model\Buyer;
use app\common\model\BuyerAddress;
use think\Db;
use think\Exception;

/**
 * 店铺票据管理
 * Class MallBill
 * @package app\site\controller
 */
class BillSite extends \app\common\controller\Site
{
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 票据基础设置
     * @return mixed
     */
    public function setting()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if (empty($siteId)) {
            return [
                'status' => false,
                'msg' => '只有店铺管理员有权打开此页面'
            ];
        }

        //查询票据信息
        $info = AreaMallBill::get(['site_id'=>$siteId]);
        if(empty($info)) $info = [];
        $this->assign(compact('info'));
        return $this->fetch();
    }

    /**
     * 保存票据设置
     */
    public function save()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        //获取店铺归属的区域ID
        $siteIns = AreaMallSite::get(['site_id'=>$siteId]);
        $params = $this->request->param();
        $data = [
            'mall_id'   => !empty($siteIns) ? $siteIns->mall_id : 0,
            'site_id'   => $siteId,
            'ordinary_tax'      => isset($params['ordinary_tax']) ? $params['ordinary_tax'] : 0,
            'special_tax'       => isset($params['special_tax']) ? $params['special_tax'] : 0,
            'bill_content'      => isset($params['bill_content']) ? $params['bill_content'] : 0,
            'is_bill'           => isset($params['is_bill']) ? $params['is_bill'] : 0,
        ];
        Db::startTrans();
        try{
            //查看是否存在数据 不存在存入
            $info = AreaMallBill::get(['site_id'=>$siteId]);
            if(empty($info))
            {
                $data['created_at'] = date("Y-m-d H:i:s");
                AreaMallBill::create($data);
            }else{
                AreaMallBill::update($data,['site_id'=>$siteId]);
            }
            Db::commit();
            return ['status'=>true,'msg'=>'保存成功'];
        }catch (\Exception $e){
            Db::rollback();
            return ['status'=>false,'msg'=>'保存失败'];
        }
    }

    /**
     * 票据订单
     * @return mixed
     */
    public function bill_order()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;

        $model = new AreaMallBillOrder;
        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $page = isset($params['page']) ? $params['page'] : 1;
                $limit = isset($params['limit']) ? $params['limit'] : 10;
                $where['site_id'] = $siteId;
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
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
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
     * 票据统计
     * @return mixed
     */
    public function bill_statistics()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;

        $collect = (new AreaMallBillOrder())->where(['site_id'=>$siteId,'status'=>AreaMallBillOrder::BILL_ORDER_STATUS_OK])->sum('order_price');//开票订单额

        $collect_ok = (new AreaMallBillOrder())->where(['site_id'=>$siteId,'status'=>AreaMallBillOrder::BILL_ORDER_STATUS_OK])->count();//已处理开票订单

        $collect_no = (new AreaMallBillOrder())->where(['site_id'=>$siteId,'status'=>AreaMallBillOrder::BILL_ORDER_STATUS_NO])->count();//未处理开票订单

        $collect_refund = (new AreaMallBillOrder())->where(['site_id'=>$siteId,'order_state'=>40])->count();//退货开票订单

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
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;

        //7天内已完成订单
        $finish = AreaMallBillOrder::site_statistics($siteId,AreaMallBillOrder::BILL_ORDER_STATUS_OK);

        //7天内未完成订单
        $no_finish = AreaMallBillOrder::site_statistics($siteId,AreaMallBillOrder::BILL_ORDER_STATUS_NO);

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