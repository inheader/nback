<?php
namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\OrderReturn;
use Request;
use app\common\model\BillReship as BillReshipModel;


class BillReship extends Manage
{

    /**
     * 获取退货列表
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
            return $returnOrderModel->tableListData($data);
        }
        return $this->fetch('index');
    }

    public function view()
    {
        $this->view->engine->layout(false);
        if(!input('?param.reship_id')){
            return error_code(13220);
        }
        $billReshipModel = new BillReshipModel();
        $where['reship_id'] = input('param.reship_id');
        $info = $billReshipModel->where($where)->find();
        if(!$info){
            return error_code(13221);
        }
        if($info->items){
            $info['items_json'] = json_encode($info->items);
        }

        $this->assign('info',$info);
        return [
            'status' => true,
            'data' => $this->fetch('view'),
            'msg' => ''
        ];
    }
}
