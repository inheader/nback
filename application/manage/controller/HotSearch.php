<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/4/26
 * Time: 11:22
 */

namespace app\manage\controller;

use app\common\controller\Manage;
use think\facade\Request;
use think\facade\Validate;

class HotSearch extends Manage
{

    protected $model = null;

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->model = model('HotSearch');
    }

    /**
     * 查看
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if(!empty($userWhere)){
            return [
                'status' => false,
                'msg' => '只有最高权限管理员有权限打开此界面'
            ];
        }


        //调用数据
        if(Request::isAjax())
        {
            $re = $this->model->tableData(array_merge(input('param.'),['order'=>'weight desc'],$userWhere));
            $data = isset($re['data']) ? $re['data'] : [];
            $re['data'] = collect($data)->map(function($info){
                //前台需要显示状态，这边在后台处理一下
                $status = ['1'=> '启用','2'=>'禁用'];
                $info['is_status'] = $status[$info['status']];
                return $info;
            });

            return $re;
        }

        return $this->fetch();
    }

    /**
     * 添加
     * @return mixed
     */
    public function add()
    {

        if(Request::isPost())
        {
            $params = Request::param('');
            if($params)
            {
                //验证
                $rule = [
                    'keywords'  => 'require|unique:hot_search',
                ];
                $msg = [
                    'keywords.require' => '热门词不能为空',
                    'keywords.unique' => '热门词已存在',
                ];
                $data = [
                    'keywords'  => $params['keywords'],
                ];
                $validate = Validate::make($rule,$msg);
                if (!$validate->check($data)) {
                    return json(['code'=>0,'msg'=>$validate->getError()]);
                }
                $res = $this->model->insert($params);
                if($res)
                {
                    return json(['code'=>1,'status'=>true,'msg'=>'添加成功']);
                }
            }
            return json(['code'=>0,'msg'=>'请求参数异常']);
        }

        return $this->fetch();
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit()
    {
        $res = $this->model->get(Request::get('id'));
        if(!$res)
            $this->error('数据异常');

        $this->assign('list',$res);
        return $this->fetch();
    }

    /**
     * 编辑处理
     * @return \think\response\Json
     */
    public function edit_post()
    {
        if(Request::isPost())
        {
            $params = Request::param('');

            if($params)
            {
                //验证
                $rule = [
                    'keywords'  => 'require',
                ];
                $msg = [
                    'keywords.require' => '热门词不能为空',
                ];
                $data = [
                    'keywords'  => $params['keywords'],
                ];
                $validate = Validate::make($rule,$msg);
                if (!$validate->check($data)) {
                    return json(['code'=>0,'msg'=>$validate->getError()]);
                }
                $re = $this->model->where('id',Request::param('id'))->update($params);
                if($re){
                    return json(['code'=>1,'status'=>true,'msg'=>'更新成功']);
                }
                return json(['code'=>0,'status'=>true,'msg'=>'更新失败']);
            }

        }
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = Request::get('id');
        if($ids)
        {
            $res = $this->model->where('id',$ids)->delete();
            if(!$res)
            {
                return ['code'=>0,'msg'=>'删除失败'];
            }
            return [
                'msg'    => '删除成功',
                'status' => true,
            ];
        }

    }

    /**
     * 权重
     * @return \think\response\Json
     */
    public function weight()
    {
        if(Request::isAjax())
        {
            $params = Request::param('');
            if($params)
            {
                $res = $this->model->where('id',$params['id'])->update(['weight'=>$params['weight']]);
                if($res)
                {
                    return json(['code'=>1,'status'=>true,'msg'=>'success']);
                }
            }
            return json(['code'=>0,'msg'=>'参数异常']);
        }
        return json(['code'=>0,'msg'=>'请求异常']);
    }
    
    /**
     * 停用
     */
    public function stop()
    {
            if(Request::isGet())
            {
                $id = Request::param('id');
                if($id)
                {
                    $now = $this->model->where('id',$id)->update(['status'=>2]);
                    if($now)
                    {
                        return json(['code'=>1,'status'=>true,'msg'=>'success']);
                    }
                }

            }
            return json(['code'=>0,'msg'=>'请求异常']);
    }

    /**
     * 启用
     */
    public function open()
    {
        if(Request::isGet())
        {
            $id = Request::param('id');
            if($id)
            {
                $now = $this->model->where('id',$id)->update(['status'=>1]);
                if($now)
                {
                    return json(['code'=>1,'status'=>true,'msg'=>'success']);
                }
            }
        }
        return json(['code'=>0,'msg'=>'请求异常']);
    }

}