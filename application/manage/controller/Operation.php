<?php
namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\Operation as OperationModel;

use Request;
use think\Db;
use think\exception\PDOException;


class Operation extends Manage
{
    /**
     * @return mixed
     */
    public function index()
    {
        if(Request::isAjax()){
            $operationModel = new OperationModel();
            $data = input('param.');
            if(isset($data['parent_id']) && $data['parent_id'] != ""){
            }else{
                $data['parent_id'] = $operationModel::MENU_START;
            }
            return $operationModel->tableData($data);
        }else{
            return $this->fetch('index');
        }


    }

    // 删除导航
    public function del()
    {
        if(!input('?param.id')){
            return error_code(10000);
        }
        $operationModel = new OperationModel();
        return $operationModel->toDel(input('param.id'));

    }

    // 新增和编辑
    public function add($pid=null)
    {
        $this->view->engine->layout(false);
        $pid = isset($pid) ? $pid : $this->request->param('pid');
        $operationModel = new OperationModel();
        if($pid > 1){
            $info = $operationModel->where(['parent_id'=>$pid])->find();
            if(!$info){
                $infos = $operationModel->where(['id'=>$pid])->find();
                if($infos)
                {
                    $info = $operationModel->where(['id'=>$pid,'parent_id'=>$infos['parent_id']])->find();
                }
            }

            $this->assign('info',$info);
        }
        //取全树
        $list = $operationModel->where('type','neq','a')->order('sort asc')->select()->toArray();
        $tree = $operationModel->createTree($list,$operationModel::MENU_START,'parent_id');
        $this->assign('tree',$tree);
        //取菜单树
        $menuList = $operationModel->where('perm_type','lt',3)->order('sort asc')->select()->toArray();
        $menuTree = $operationModel->createTree($menuList,$operationModel::MENU_START,'parent_menu_id');
        $this->assign('menuTree',$menuTree);

        if(Request::isPost()){
            return $operationModel->toAdd(input('param.'));
        }else{
            return $this->fetch('add');
        }
    }

    /**
     * 编辑
     * @param null $pid
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($pid=null)
    {
        $this->view->engine->layout(false);
        $pid = isset($pid) ? $pid : $this->request->param('pid');
        $operationModel = new OperationModel();
        //如果是编辑，取数据
        if($pid){
            $info = $operationModel->where(['id'=>$pid])->find();
            if(!$info){
                return error_code(10000);
            }
            $this->assign('info',$info);
        }
        //取全树
        $list = $operationModel->where('type','neq','a')->order('sort asc')->select()->toArray();
        $tree = $operationModel->createTree($list,$operationModel::MENU_START,'parent_id');
        $this->assign('tree',$tree);
        //取菜单树
        $menuList = $operationModel->where('perm_type','lt',3)->order('sort asc')->select()->toArray();
        $menuTree = $operationModel->createTree($menuList,$operationModel::MENU_START,'parent_menu_id');
        $this->assign('menuTree',$menuTree);

        if(Request::isPost()){
            return $operationModel->toAdd(input('param.'));
        }else{
            return $this->fetch('edit');
        }
    }

    /**
     * 排序
     * @param null $id
     * @param null $sort
     * @return array
     * @throws \think\Exception
     */
    public function sort($id=null,$sort=null)
    {
        $id = isset($id) ? $id : $this->request->get('id');
        $sort = isset($sort) ? $sort : $this->request->get('sort');
        if(!$id)
            return ['status'=>false,'msg'=>'参数异常'];
        if(!is_numeric($sort))
            return ['status'=>false,'msg'=>'请输入整数'];
        Db::startTrans();
        try{
            (new OperationModel())->where('id',$id)->update(['sort'=>$sort]);
            Db::commit();
            return ['status'=>true,'msg'=>'排序成功'];
        }catch (PDOException $e){
            Db::rollback();
            return ['status'=>false,'msg'=>'排序失败'];
        }

    }

}