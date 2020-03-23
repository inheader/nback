<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: tianyu <tianyu@jihainet.com>
// +----------------------------------------------------------------------

namespace app\Mall\controller;

use app\common\controller\Mall;
use app\common\model\Notice as noticeModel;
use think\Db;
use think\facade\Request;

class Notice extends Mall
{

    /**
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {

        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        $noticeModel = new noticeModel();
        if(Request::isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $whereTime = [];
                if(isset($params['title']) && !empty($params['title']))
                {
                    $where[] = ['title','like','%'.$params['title'].'%'];
                }

                if(isset($params['date']) && !empty($params['date']))
                {
                    $theDate = explode(' 到 ',$params['date']);

                    if(!empty($theDate[0]))
                    {
                        $whereTime[] = ['ctime','>=',strtotime($theDate[0])];
                    }

                    if(!empty($theDate[1]))
                    {
                        $whereTime[]   = ['ctime','<',strtotime($theDate[1].' 23:59:59')];
                    }
                }

                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);
                $where['mall_id'] = $areaId;

                list($where,$page,$limit) = [$where,$page,$limit];

                $list = Db::name('notice')
                    ->where($where)
                    ->where('isdel','eq',null)
                    ->where($whereTime)
//                    ->whereTime('ctime','between',[$theDate[0],$theDate[1]])
                    ->page($page,$limit)
                    ->select();
                $total = Db::name('notice')
                    ->where($where)
                    ->where('isdel','eq',null)
                    ->where($whereTime)
//                    ->whereTime('ctime','between',[$theDate[0],$theDate[1]])
                    ->count();

                foreach ($list as $key=>$val) {
                    $list[$key]['ctime'] = date("Y-m-d H:i:s",$val['ctime']);
                }

                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);

            }

//            return $noticeModel->tableData(input('param.'));
        }


        return $this->fetch();
    }

    /*
     * 添加公告
     * */
    public function add()
    {
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        $noticeModel = new noticeModel();
        if(Request::isPost())
        {

            $data = [
                'mall_id' => $areaId,
                'title' => $this->request->param('title'),
                'type' => $this->request->param('type'),
                'image' => $this->request->param('image'),
                'desc' => $this->request->param('desc'),
                'sort' => $this->request->param('sort'),
                'author_id' => $this->request->param('author_id'),
                'author_title' => $this->request->param('author_title'),
                'is_pub' => $this->request->param('is_pub'),
                'content' => $this->request->param('content'),
            ];

            return $noticeModel->addData($data);
        }
        return $this->fetch();

    }


    /**
     *
     *  公告编辑
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $noticeModel = new noticeModel();
        if(Request::isPost())
        {
            return $noticeModel->saveData(input('param.'));
        }
        $noticeInfo = $noticeModel->where('id',input('param.id/d'))->find();
        if (!$noticeInfo)
        {
            return error_code(10002);
        }
        return $this->fetch('edit',['noticeInfo'=>$noticeInfo]);
    }

    /**
     *  公告软删除
     * User:tianyu
     * @return array
     */
    public function del()
    {

        $result = ['status' => true, 'msg' => '删除成功', 'data'  => ''];
        $noticeModel = new noticeModel();
        if (!$noticeModel->destroy(input('param.id/d')))
        {
            $result['status'] = false;
            $result['msg'] = '删除失败';
        }

        return $result;
    }
}