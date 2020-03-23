<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/8/16
 * Time: 13:57
 */

namespace app\mall\controller;


use app\common\controller\Mall;
use app\common\model\AreaMallSales;
use app\common\model\AreaMallSalesCard;
use app\common\model\AreaMallSalesCash;
use app\common\model\AreaMallSalesCashLog;
use think\Db;
use think\exception\PDOException;

class Cash extends Mall
{
    protected function initialize()
    {
        parent::initialize();

        //昨日提现
        $dateZ = date("Y-m-d H:i:s",strtotime("-1 day"));
        $z = (new AreaMallSalesCashLog())->whereBetweenTime('created_at',$dateZ)->sum('price');
        $this->assign('z',$z);
        //今日提现
        $dateJ = date("Y-m-d");
        $j = (new AreaMallSalesCashLog())->whereBetweenTime('created_at',$dateJ)->sum('price');
        $this->assign('j',$j);
        //当月提现
        $dateM_1 = date("Y-m-01",strtotime(date("Y-m-d")));
        $dateM_2 = date("Y-m-d", strtotime("$dateM_1 +1 month -1 day"));
        $m = (new AreaMallSalesCashLog())->whereTime('created_at','between',[$dateM_1,$dateM_2])->sum('price');
        $this->assign('m',$m);
    }


    /**
     * 提现申请
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function salesIndex()
    {
        if($this->request->isAjax())
        {
            $where = [];
            $page = $this->request->get('page',1);
            $limit = $this->request->get('limit',10);
            list($where,$page,$limit) = [$where,$page,$limit];

            $list = (new AreaMallSalesCash())
                ->where($where)
                ->page($page,$limit)
                ->select();

            $total = (new AreaMallSalesCash())
                ->where($where)
                ->count();

            $cardType = ['ALI_PAY'=>'支付宝','WEIXIN_PAY'=>'微信','CARD_PAY'=>'银行卡'];

            foreach ($list as $key=>$value) {
                $list[$key]['cash_name']    = (new AreaMallSales())->where('sales_id',$value['sales_id'])->find()->buyer_name;
                $list[$key]['cash_type']    = $cardType[$value['cash_type']];
                $list[$key]['card_num']     = (new AreaMallSalesCard())->where('id',$value['card_id'])->find()->card_num;
                $list[$key]['createTime']   = date("Y-m-d H:i:s",$value['createtime']);
            }

            $result = array("code"=>0,"count" => $total, "data" => $list);
            return json($result);

        }

        return $this->fetch();
    }

    /**
     * @param null $ids
     * @return mixed
     */
    public function edit($ids=null)
    {
        $this->view->engine->layout(false);

        $ids = isset($ids) ? $ids : $this->request->get('id');
        if($ids)
            $sales = AreaMallSalesCash::get($ids);
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{
                    (new AreaMallSalesCash())->where('id',$params['id'])->update(['status'=>$params['status']]);

                    if($params['status'] == 1)
                    {
                        //记录日志
                        $data = [
                            'sales_id' => $sales['sales_id'],
                            'cash_sn' => $sales['cash_sn'],
                            'price' => $sales['price'],
                        ];
                        (new AreaMallSalesCashLog())->insert($data);
                    }

                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=> false,'msg'=>'操作失败'];
                }
            }
        }

        $this->assign('sales',$sales);
        return $this->fetch();
    }


    public function start()
    {
        if($this->request->isPost())
        {

        }
    }

    public function end()
    {
        if($this->request->isPost())
        {

        }
    }

    /**
     * @return array
     * @throws \think\Exception
     */
    public function statusPost()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{
                    (new AreaMallSalesCash())->where([])->update(['status'=>1]);
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }
            }
        }
    }
}