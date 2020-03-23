<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/8/13
 * Time: 13:20
 */

namespace app\mall\controller;


use app\common\controller\Mall;
use app\common\model\AreaMallSales;
use app\common\model\AreaMallSalesCard;
use app\common\model\AreaMallSalesCash;
use app\common\model\AreaMallSalesCashLog;
use app\common\model\AreaMallSalesFlow;
use app\common\model\AreaMallSalesOrder;
use app\common\model\AreaMallSalesRelation;
use app\common\model\AreaMallSalesReport;
use app\common\model\Buyer;
use think\Db;
use think\exception\PDOException;
use think\Validate;

class Sales extends Mall
{
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 销售列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $whereTime = [];
                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);

                if(isset($params['mobile']) && !empty($params['mobile']))
                {
                    $where['mobile'] = ['eq',$params['mobile']];
                }

                if(isset($params['date']) && !empty($params['date']))
                {
                    $theDate = explode(' 到 ',$params['date']);
                    if(!empty($theDate[0]))
                    {
                        $whereTime[]    = ['created_at','>=',$theDate[0]];
                    }

                    if(!empty($theDate[1]))
                    {
                        $whereTime[]    = ['created_at','<',$theDate[1]];
                    }

                }

                $where['type'] = 1;

                list($where,$page,$limit) = [$where,$page,$limit];
                $list = (new AreaMallSales())
                    ->where($where)
                    ->where($whereTime)
                    ->page($page,$limit)
                    ->select();
                $total = (new AreaMallSales())
                    ->where($where)
                    ->where($whereTime)
                    ->count();
                $type = [1=>'销售',2=>'招商',3=>'运营'];
                foreach ($list as $key=>$val) {
                    $list[$key]['type'] = $type[$val['type']];
                }
                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);

            }
        }
        return $this->fetch();
    }

    /**
     * 添加销售
     * @return mixed
     */
    public function add()
    {
        $this->view->engine->layout(false);

        if($this->request->isPost()) {
            $params = $this->request->param();
            if ($params) {

                //根据手机号搜索用户ID昵称
                $buyer = (new Buyer())->where('buyer_tel',$params['phone'])->find();
                if(!$buyer)
                    return ['status'=>false,'msg'=>'用户不存在'];
                //规则验证
                $rule = [
                    'mobile' => 'unique:area_mall_sales',
                ];
                $msg = [
                    'mobile.unique' => '手机号码已存在',
                ];
                $data = [
                    'mobile' => $params['phone'],
                ];
                $validate = Validate::make($rule,$msg);
                $result = $validate->check($data);
                if(!$result)
                {
                    return ['status'=>false,'msg'=>$validate->getError()];
                }
                Db::startTrans();
                try{
                    //组装入库数据
                    $datas = [
                        'buyer_id' => $buyer['buyer_id'],
                        'buyer_name' => $buyer['buyer_name'],
                        'mobile' => $params['phone'],//手机号
                        'avatar' => $params['avatar'],//标准照片
                        'wechat' => $params['wechat'],//微信号
                        'real_name' => $params['real_name'],//真实姓名
                        'type' => $params['type'],//销售类型
                        'intro'     => $params['intro'],//销售类型
                    ];
                    //重复检测
                    (new AreaMallSales())->insert($datas);
                    Db::commit();
                    return ['status'=>true,'msg'=>'保存成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'保存失败-'.$e->getMessage()];
                }
            }
            return ['status' => false, 'msg' => '参数错误'];
        }

        $type = $this->request->get('type');
        $this->assign('type',$type);
        return $this->fetch();
    }

    /**
     * 编辑销售
     * @return mixed
     */
    public function edit($id=null)
    {
        $this->view->engine->layout(false);

        $id = isset($id) ? $id : $this->request->get('sid');
        if($id)
            $sales = AreaMallSales::get(['sales_id'=>$id]);
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{
                    $datas = [
                        'mobile' => $params['phone'],//手机号
                        'wechat' => $params['wechat'],//微信号
                        'real_name' => $params['real_name'],//真实姓名
                        'type'      => $params['type'],//销售类型
                        'intro'     => $params['intro'],//销售类型
                    ];
                    if(!empty($params['avatar']))
                    {
                        $datas['avatar'] = $params['avatar'];//标准照片
                    }
                    (new AreaMallSales())->where('sales_id',$params['sales_id'])->update($datas);
                    Db::commit();
                    return ['status'=>true,'msg'=>'保存成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'保存失败-'.$e->getMessage()];
                }
            }
            return ['status'=>false,'msg'=>'参数错误'];
        }
        $this->assign('sales',$sales);
        return $this->fetch();
    }

    public function target($id=null)
    {
        $this->view->engine->layout(false);
        $id = isset($id) ? $id : $this->request->get('sid');
        if($id)
            $sales = AreaMallSales::get(['sales_id'=>$id]);
        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);
                $where['sales_id']= $sales['sales_id'];
                list($where,$page,$limit) = [$where,$page,$limit];
                $list = (new AreaMallSalesReport())
                    ->where($where)
                    ->page($page,$limit)
                    ->select();
                $total = (new AreaMallSalesReport())
                    ->where($where)
                    ->count();

                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);
            }
        }
        $this->assign('sales',$sales);
        return $this->fetch();
    }

    /**
     * 添加销售目标
     * @return array|mixed
     */
    public function report()
    {
        $this->view->engine->layout(false);
        $res = $this->request->get();

        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{
                    //判断是够存在相同
                    $sales = (new AreaMallSalesReport())->where(['sales_id'=>$params['salesId'],'date'=>$params['date']])->field('sales_id')->find();
                    //存在就编辑
                    if($sales)
                    {
                        (new AreaMallSalesReport())->where(['sales_id'=>$params['salesId'],'date'=>$params['date']])->update(['target'=>$params['target']]);
                    }else{
                        $data = [
                            'sales_id' => $params['salesId'],
                            'date' => $params['date'],
                            'target' => $params['target'],
                        ];
                        (new AreaMallSalesReport())->insert($data,true);
                    }
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }
            }
        }
        $this->assign('res',$res);
        return $this->fetch();
    }

    /**
     * 修改目标金额
     * @return array
     * @throws \think\Exception
     */
    public function editReport()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                //验证金额
                if(!preg_match("/^(-?\d+)(\.\d+)?/",$params['target']))
                {
                    return ['status'=>false,'msg'=>'金额类型不正确'];
                }
                Db::startTrans();
                try{
                    (new AreaMallSalesReport())->where('id',$params['id'])->update(['target'=>$params['target']]);
                    Db::commit();
                    return ['status'=>true,'msg'=>'修改成功'];
                }catch ( PDOException $e)
                {
                    Db::rollback();
                    return ['status'=>false,'msg'=>'修改失败'];
                }
            }
        }
    }

    /**
     * 删除 销售
     * @param null $id
     * @return array|mixed
     * @throws \think\Exception
     */
    public function del($id=null)
    {
        $salesId = isset($id) ? $id : $this->request->get('sid');

        if($salesId)
        {
            $sales = AreaMallSales::get(['sales_id',$salesId]);
            if(!$sales)
                return ['status'=>false,'msg'=>'该销售不存在'];
            if($this->request->isAjax())
            {
                Db::startTrans();
                try{
                    (new AreaMallSales())->where('sales_id',$salesId)->delete();
                    //连带删除  相关信息绑定卡 流水 提现 订单 报表 关系
                    (new AreaMallSalesCard())->where('sales_id',$salesId)->delete();
                    (new AreaMallSalesCash())->where('sales_id',$salesId)->delete();
                    (new AreaMallSalesCashLog())->where('sales_id',$salesId)->delete();
                    (new AreaMallSalesOrder())->where('sales_id',$salesId)->delete();
                    (new AreaMallSalesFlow())->where('sales_id',$salesId)->delete();
                    (new AreaMallSalesRelation())->where('sales_id',$salesId)->delete();
                    (new AreaMallSalesReport())->where('sales_id',$salesId)->delete();
                    Db::commit();
                    return ['status'=>true,'msg'=>'删除成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'删除失败'];
                }
            }
        }

        return $this->fetch();
    }

    /**
     * 我的用户--销售跟用户之间的关系
     * @return mixed
     */
    public function myBuyer($salesId=null)
    {
        $this->view->engine->layout(false);

        $salesId = isset($salesId) ? $salesId : $this->request->get('sid');

        if($this->request->isAjax())
        {
            $sid = $this->request->get('sid');
            $type = $this->request->get('type');
            $page = $this->request->get('page',1);
            $limit = $this->request->get('limit',10);

            $list = (new AreaMallSalesRelation())
                ->where(['sales_id'=>$sid,'type'=>$type])
                ->page($page,$limit)
                ->select();
            $total = (new AreaMallSalesRelation())
                ->where(['sales_id'=>$sid,'type'=>$type])
                ->count();

            foreach ($list as $key=>$value) {
                $list[$key]['buyer_name'] = (new Buyer())->where('buyer_id',$value['buyer_id'])->find()['buyer_name'];
            }

            $result = array("code"=>0,"count" => $total, "data" => $list);
            return json($result);
        }
        $sales = AreaMallSales::get(['sales_id'=>$salesId]);
        $this->assign('sales',$sales);
        return $this->fetch();
    }

    public function addBuyer($sid=null)
    {
        $this->view->engine->layout(false);

        $sid = isset($sid) ? $sid : $this->request->get('sid');
        if($sid)
            $sales = AreaMallSales::get(['sales_id'=>$sid]);
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{
                    $data = [
                        'sales_id'  => $params['sales_id'],
                        'buyer_id'  => $params['buyer_id'],
                        'type'      => $params['type'],
                        'created_at' => date("Y-m-d H:i:s"),
                    ];
                    (new AreaMallSalesRelation())->insert($data,true);
                    Db::commit();
                    return ['status'=>true,'msg'=>'保存成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'保存失败'];
                }
            }
        }

        //查询可添加的下线客户
        $myBuyer = (new AreaMallSalesRelation())->field('sales_id,buyer_id')->select();
        if(empty($myBuyer))
        {
            $ids = [];
        }else{
            foreach ($myBuyer as $k=>$v) {
                $ids[] = $v['buyer_id'];
            }
        }
        //取出以上不存在的客户
        $buyerList = (new Buyer())->whereNotIn('buyer_id',$ids)->select();
        $this->assign('buyers',$buyerList);
        $this->assign('sales',$sales);
        return $this->fetch();
    }

    /**
     * 移除下线客户
     * @param null $ids
     * @return array
     * @throws \think\Exception
     */
    public function removeBuyer($ids=null)
    {
       if($this->request->isPost())
       {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{

                    (new AreaMallSalesRelation())->where(['sales_id'=>$params['sid'],'buyer_id'=>$params['buyer_id']])->delete();

                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }
            }
       }
    }

    /**
     * 获取销售下线订单
     * @param null $salesId
     * @param null $buyerId
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function salesOrderList($salesId=null,$buyerId=null)
    {
        $salesId = isset($salesId) ? $salesId : $this->request->get('salesId');
        $buyerId = isset($buyerId) ? $buyerId : $this->request->get('buyerId');

        if($salesId && $buyerId)
            $relation = (new AreaMallSalesRelation())->where(['sales_id'=>$salesId,'buyer_id'=>$buyerId])->find();
        if($this->request->isAjax())
        {
            $salesId = $this->request->get('salesId');
            $buyerId = $this->request->get('buyerId');
            $page = $this->request->get('page',1);
            $limit = $this->request->get('limit',10);

            $list = (new AreaMallSalesOrder())
                ->where(['sales_id'=>$salesId,'buyer_id'=>$buyerId])
                ->page($page,$limit)
                ->select();
            $total = (new AreaMallSalesOrder())
                ->where(['sales_id'=>$salesId,'buyer_id'=>$buyerId])
                ->count();

            foreach ($list as $key=>$val) {
                $list[$key]['buyer_name'] = Buyer::get(['buyer_id'=>$val['buyer_id']])->buyer_name;
                $list[$key]['order_time'] = date("Y-m-d H:i:s",$val['order_time']);
            }

            $result = array("code"=>0,"count" => $total, "data" => $list);
            return json($result);
        }
        $this->assign('relation',$relation);
        return $this->fetch();
    }


    /**
     * 冻结订单操作
     * @return array
     * @throws \think\Exception
     * @throws \think\Exception\DbException
     */
    public function frozenOrder()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                //查询需冻结订单
                $salesOrder = AreaMallSalesOrder::where('status','neq',3)->get(['order_id'=>$params['order_id']]);
                if(!$salesOrder)
                    return ['status'=>false,'msg'=>'暂无此单'];
                //读取订单时间
                $orderTime = date("Y-m",$salesOrder['order_time']);
                //根据订单时间查询月报表
                $salesReport = AreaMallSalesReport::where(['date'=>$orderTime])->get(['sales_id'=>$salesOrder['sales_id']]);
                if(!$salesReport)
                    return ['status'=>false,'msg'=>'暂无此报表'];
                //查询流水
                $salesFlow = AreaMallSalesFlow::get(['order_sn'=>$salesOrder['order_sn']]);
                //计算冻结金额 更改订单状态 设为无效  并加入流水日志 流水状态 设为无效
                $freezeFee = $salesReport['forecast'] - $salesOrder['money']; //佣金
                $salesFee  = $salesReport['sale'] - $salesOrder['total']; //销售额

                ///
                $forecast = $salesReport['forecast'] - $freezeFee; //预估
                $upFree  = $salesReport['freeze'] + $salesOrder['money']; //冻结金额
                $saleFee = $salesReport['sale'] - $salesFee; //销售

                Db::startTrans();
                try{
                    //新增流水记录
                    (new AreaMallSalesFlow())->addData([
                            'salesId' => $salesFlow['sales_id'],
                            'orderSn' => $salesFlow['order_sn'],
                            'type' => 4,
                            'total' => $salesFlow['total'],
                            'price' => $salesFlow['price'],
                            'balance' => $salesFlow['balance'],
                            'remark' => $salesFlow['remark'],
                    ]);
                    //更新金额
                    (new AreaMallSalesReport())->where(['sales_id'=>$salesOrder['sales_id'],'date'=>$orderTime])->update(['forecast'=>$forecast,'sale'=>$saleFee,'freeze'=>$upFree]);
                    //更新订单状态
                    (new AreaMallSalesOrder())->where('order_id',$salesOrder['order_id'])->update(['status'=>4]);
                    Db::commit();
                    return ['status'=>true,'msg'=>'冻结成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'冻结失败'];
                }
            }
        }
    }


    /**
     * 销售入账提成流水
     * @param null $id
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function salesFlow($id=null)
    {
        $this->view->engine->layout(false);
        $id = isset($id) ? $id : $this->request->get('sid');
        if($id)
            $sales = AreaMallSales::get(['sales_id'=>$id]);
        if($this->request->isAjax())
        {
            $salesId = $this->request->get('sid');
            $page = $this->request->get('page',1);
            $limit = $this->request->get('limit',10);

            $list = (new AreaMallSalesFlow())
                ->where(['sales_id'=>$salesId])
                ->page($page,$limit)
                ->select();
            $total = (new AreaMallSalesFlow())
                ->where(['sales_id'=>$salesId])
                ->count();
            $type = [1=>'入账',2=>'提现'];
            foreach ($list as $key=>$value) {
                $list[$key]['type'] = $type[$value['type']];
            }
            $result = array("code"=>0,"count" => $total, "data" => $list);
            return json($result);
        }
        $this->assign('sales',$sales);
        return $this->fetch();
    }

    /**
     * 销售提现记录
     * @param null $id
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function salesCashLog($id=null)
    {
        $this->view->engine->layout(false);
        $id = isset($id) ? $id : $this->request->get('sid');
        if($id)
            $sales = AreaMallSales::get(['sales_id'=>$id]);
        if($this->request->isAjax())
        {
            $salesId = $this->request->get('sid');
            $page = $this->request->get('page',1);
            $limit = $this->request->get('limit',10);

            $list = (new AreaMallSalesCashLog())
                ->where(['sales_id'=>$salesId])
                ->page($page,$limit)
                ->select();
            $total = (new AreaMallSalesCashLog())
                ->where(['sales_id'=>$salesId])
                ->count();

            $result = array("code"=>0,"count" => $total, "data" => $list);
            return json($result);
        }
        $this->assign('sales',$sales);
        return $this->fetch();
    }
    
    //----------------招商-----------------

    /**
     * 招商列
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function investment()
    {

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $whereTime = [];
                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);


                if(isset($params['mobile']) && !empty($params['mobile']))
                {
                    $where['mobile'] = ['eq',$params['mobile']];
                }

                if(isset($params['date']) && !empty($params['date']))
                {
                    $theDate = explode(' 到 ',$params['date']);
                    if(!empty($theDate[0]))
                    {
                        $whereTime[]    = ['created_at','>=',$theDate[0]];
                    }

                    if(!empty($theDate[1]))
                    {
                        $whereTime[]    = ['created_at','<',$theDate[1]];
                    }

                }

                $where['type'] = 2;//招商

                list($where,$page,$limit) = [$where,$page,$limit];
                $list = (new AreaMallSales())
                    ->where($where)
                    ->where($whereTime)
                    ->page($page,$limit)
                    ->select();
                $total = (new AreaMallSales())
                    ->where($where)
                    ->where($whereTime)
                    ->count();
                $type = [1=>'销售',2=>'招商',3=>'运营'];
                foreach ($list as $key=>$val) {
                    $list[$key]['type'] = $type[$val['type']];
                }
                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);

            }
        }

        return $this->fetch();
    }

    //-------------------------运营-------------------------------

    /**
     * 运营列
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function operation()
    {
        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $whereTime = [];
                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);

                if(isset($params['mobile']) && !empty($params['mobile']))
                {
                    $where['mobile'] = ['eq',$params['mobile']];
                }

                if(isset($params['date']) && !empty($params['date']))
                {
                    $theDate = explode(' 到 ',$params['date']);
                    if(!empty($theDate[0]))
                    {
                        $whereTime[]    = ['created_at','>=',$theDate[0]];
                    }

                    if(!empty($theDate[1]))
                    {
                        $whereTime[]    = ['created_at','<',$theDate[1]];
                    }

                }

                $where['type'] = 3;//运营

                list($where,$page,$limit) = [$where,$page,$limit];
                $list = (new AreaMallSales())
                    ->where($where)
                    ->where($whereTime)
                    ->page($page,$limit)
                    ->select();
                $total = (new AreaMallSales())
                    ->where($where)
                    ->where($whereTime)
                    ->count();
                $type = [1=>'销售',2=>'招商',3=>'运营'];
                foreach ($list as $key=>$val) {
                    $list[$key]['type'] = $type[$val['type']];
                }
                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);

            }
        }
        return $this->fetch();
    }
}


