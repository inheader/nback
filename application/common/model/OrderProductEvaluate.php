<?php


namespace app\common\model;



class OrderProductEvaluate extends Common
{

    const USER_LOGIN = 1;     //登录
    const USER_LOGOUT = 2;    //退出
    const USER_REG = 3;    //注册
    const USER_EDIT = 4;    //用户编辑信息


    /**
     * 后台的评论列表
     * @param $goods_id
     * @param int $page
     * @param int $limit
     * @param string $display
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($goods_id, $page = 1, $limit = 10, $display = 'all')
    {
        $where = [
            'spc_common_id'=>$goods_id
        ];
        $query = $this->where($where)->order('updated_at');

        $count = $this->where($where)->count();
        $list = $query->page($page, $limit)->select();


        $list = collect($list)->map(function($info){
            return[
                'id'                => $info['ose_id'],
                'buyerId'           => $info['buyer_id'],
                'buyerName'         => $info['buyer_name'],
                'buyerHeader'       => $info['buyer_header'],
                'evaluateScore'     => $info['geval_scores'],//评分
                'evaluateContent'   => $info['evaluate_content'],
                'evaluatePics'      => $info['evaluate_pics'],
                'goodsSpecValue'    => $info['goods_spec_value'],
                'evaluateRemark'    => $info[''],
                'orderSn'           => $info['order_sn'],
                'evaluateTime'      => date("Y-m-d",$info['evaluate_time']),
                'isDelete'          => $info['is_delete'],
            ];
        });

        return[
            'count' =>$count,
            'list'  =>$list,
        ];

    }


    /**
     * 获取评价详细
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCommentInfo($id)
    {
        $where[] = ['ose_id', 'eq', $id];
        $res = $this->where($where)->find();

        if($res)
        {
            $return = [
                'status' => true,
                'msg' => '获取成功',
                'data' => [
                    'evaluateContent' =>$res['evaluate_content'],
                    'evaluatePics'    =>!empty($res['evaluate_pics']) ? unserialize($res['evaluate_pics']) : [],
                    'evaluateRemark'  =>$res['evaluate_remark'],
                ]
            ];
        }
        else
        {
            $return = [
                'status' => false,
                'msg' => '获取失败',
                'data' => $res
            ];
        }
        return $return;
    }

    /**
     * 上架回复
     * @param $id
     * @param $content
     * @return array
     */
    public function sellerComment($id, $content)
    {
        $where[] = ['ose_id', 'eq', $id];
        $data['evaluate_remark'] = $content;
        $res = $this->where($where)->update($data);
        if($res !== false)
        {
            $return_data = [
                'status' => true,
                'msg' => '保存成功',
                'data' => $res
            ];
        }
        else
        {
            $return_data = [
                'status' => false,
                'msg' => '保存失败',
                'data' => $res
            ];
        }
        return $return_data;
    }


}