<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 2018/3/18
 * Time: 下午3:23
 */

namespace app\agree\controller;
use think\Controller;
use think\Db;

class Index extends Controller
{
    public function index()
    {
        $listData = Db::table('ev_notice')->where(['type'=>3,'is_pub'=>1,])->select();

        $this->assign('listData',$listData);

        return $this->fetch();
    }


    public function item($id)
    {
        if(!isset($id) && empty($id)){
            exit('参数错误');
        }else{
            $id = intval($id);
            $itemData = Db::table('ev_notice')->where(['type'=>3,'is_pub'=>1,'id'=>$id])->find();
            if($itemData){
                $itemData['content'] = $itemData['content'];
                // dump($itemData['content']);
                $this->assign('itemData',$itemData);
            }else{
                exit('未找到相关内容');
            }
        }

        return $this->fetch();

    }
}