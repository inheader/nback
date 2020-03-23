<?php
/**
 * Created by PhpStorm.
 * User: tian yu
 * Date: 2018/1/18 0018
 * Time: 10:49
 */

namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\OperationLog as LogModel;
use Request;

class OperationLog extends Manage
{
    public function index()
    {
        $logModel = new LogModel();
        if(Request::isAjax())
        {
            $request = input('param.');
            return $logModel->tableData($request);
        }
        return $this->fetch();
    }


    /**
     * 后台操作日志
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLastLog(){
        $logModel = new LogModel();
        $limit = 10;//最近10条数据

        $request['manage_id'] = $this->getUserId();

//        $list = $logModel->tableData($request);
        $list =  (new LogModel())
            ->where('manage_id',$request['manage_id'])
            ->page(1,$limit)
            ->order('id','desc')
            ->select();

        $list = collect($list)->map(function($info){
            return [
                'username'=>$this->getUserName(),
                'id'=>$info['id'],
                'manage_id'=>$info['manage_id'],
                'controller'=>$info['controller'],
                'method'=>$info['method'],
                'desc'=>$info['desc'],
                'content'=>$info['content'],
                'ip'=>$info['ip'],
                'ctime'=>date("Y-m-d H:i:s",$info['ctime']),
            ];
        })->all();


        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $limit;
        $re['data'] = $list;
        return $re;
    }

}