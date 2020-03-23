<?php
namespace app\Site\controller;

use app\common\controller\Site;
use app\common\model\BillAftersales as BillAfterSalesModel;
use app\common\model\Buyer;
use app\common\model\OrderGoods;
use app\common\model\OrderReturn;
use app\common\model\OrderReturnGoods;
use app\common\model\Payments;
use Request;

class BillAftersales extends Site
{
    /**
     * 获取售后单列表
     * @return mixed
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if(Request::isAjax()){
            $data = input('param.');
            $data = array_merge($data,$userWhere);

            $returnOrderModel = new OrderReturn();
            return $returnOrderModel->tableReturnListData($data);

        }
        return $this->fetch('index');
    }

    /**
     * 售后单处理
     * @return array|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function audit()
    {
        $this->view->engine->layout(false);
        if(!input('?param.return_id')){
            return error_code(13215);
        }

        $returnId = input('param.return_id');
        $returnModel = new OrderReturn();

        if(Request::isPOST()){
            if(!input('?param.status')){
                return error_code(10000);
            }

            $status   = input('param.status','');
            $mark     = input('param.mark','');

            return $returnModel->audit($returnId, input('param.status'), $mark);
        }

        $info = (new OrderReturn())->myInstanceByReturnId($returnId);
        if(!$info){
            return error_code(13207);
        }

        $buyerIns = (new Buyer())->getBuyerInfoForId($info->getMyBuyerId());
        $info['status'] = $info->getMyStatusName();
        $info['buyer_name'] = $buyerIns['buyer_name'];
        $info['refund_type'] = $info->getMyReturnTypeName();
        $returnGoods = (new OrderReturnGoods())->myInstanceByReturnId($returnId);
        foreach ($returnGoods as $key=>$item) {
            $ordGoods    = (new OrderGoods())->myInstanceByOspId($item['osp_id']);
            $goods[] = [
                'goods_name'  => $ordGoods['goods_name'],
                'goods_num'   => $ordGoods['goods_num'],
                'return_num'  => $item['return_num']
            ];
        }
        $info['items_json'] = json_encode($goods);
        $this->assign('info',$info);
        return [
            'status' => true,
            'data' => $this->fetch('audit'),
            'msg' => ''
        ];
    }


    /**
     * 售后单查看
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view()
    {
        $this->view->engine->layout(false);
        if(!input('?param.return_id')){
            return error_code(13217);
        }
        $orderReturnModel = new OrderReturn();

        $returnId = input('param.return_id');
        $info = $orderReturnModel->myInstanceByReturnId($returnId);
        if(!$info){
            return error_code(13218);
        }

        $info['status'] = $info->getMyStatusName();

        $buyerIns = (new Buyer())->getBuyerInfoForId($info->getMyBuyerId());
        $info['buyer_name'] = $buyerIns['buyer_name'];
        $info['refund_type'] = $info->getMyReturnTypeName();
        $returnGoods = (new OrderReturnGoods())->myInstanceByReturnId(input('param.return_id'));
        foreach ($returnGoods as $row) {
            $ordGoods    = (new OrderGoods())->myInstanceByOspId($row->getMyOspId());

            $goods[] = [
                'goods_id'  => $ordGoods->getMyGoodsId(),
                'goods_name'  => $ordGoods->getMyGoodsName(),
                'goods_num'   => $ordGoods->getMyGoodsNum(),
                'return_num'  => $row->getMyReturnNum(),
            ];

        }

        $info['items_json'] = json_encode($goods);

        $this->assign('info',$info);
        return [
            'status' => true,
            'data' => $this->fetch('view'),
            'msg' => ''
        ];
    }



}
