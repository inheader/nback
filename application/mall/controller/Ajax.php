<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/4
 * Time: 12:26
 */

namespace app\manage\controller;

use think\Controller;
use think\Db;
use think\Request;

class Ajax extends Controller
{

    protected $request;

    /**
     * Ajax constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     *获取区域 
     */
    public function getArea()
    {
        if($this->request->isPost())
        {
            $area = $this->request->param('keyword');

            if(empty($area))
            {
                $where = [];
            }else{
                $where = ['name','lick','%{$area}%'];
            }

            $page = $this->request->get('page');

            $limit = $this->request->get('limit');

            $list = Db::name('area')->where($where)->where(['depth'=>2])->field('id,name,parent_id')->limit($page,$limit)->select();

            $total = Db::name('area')->where($where)->where(['depth'=>2])->limit($page,$limit)->count();

            return json(['code'=>0,'msg'=>'','count'=>$total,'data'=>$list]);

        }

    }


    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAreaList()
    {
        $params = $this->request->param();

        if(!empty($params))
        {
            $pro = isset($params['province']) ? $params['province'] : '';
            $city = isset($params['city']) ? $params['city'] : '';
        }else{
            $pro = $this->request->param('province');
            $city = $this->request->param('city');
        }
        $where = ['parent_id' => 0, 'depth' => 1];
        $provincelist = null;
        if($pro !== '')
        {
            if($pro)
            {
                $where['parent_id'] = $pro;
                $where['depth'] = 2;
            }

            $provincelist = Db::name('area')->where($where)->field('id as value,name')->select();

        }

        if($city !== ''){
            if($city)
            {
                $where['parent_id'] = $city;
                $where['depth'] = 3;
            }

            $provincelist = Db::name('area')->where($where)->field('id as value,name')->select();
        }

        $this->success('', null, $provincelist);

    }


    public function upload()
    {
        if($this->request->isAjax())
        {
            $file = $this->request->file('file');
            $info = $file->move( ROOT_PATH.'public'.DS.'static'.DS.'uploads');
            if($info)
            {
//                dump($info->getSaveName());
                $res['code'] = 0;
                $res['msg'] = 'success';
                $res['src'] = 'uploads'.DS.$info->getSaveName();
                return json($res);
            }
        }
    }

}