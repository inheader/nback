<?php
namespace app\Site\controller;

use app\common\model\Brand;
use app\common\model\Goods;
use Request;
use think\Container;
use app\common\controller\Base;
use app\common\model\SiteManage;
use app\common\model\UserLog;
use think\facade\Cache;

class Common extends Base
{
    public function initialize(){
        parent::initialize();
        //此控制器不需要模板布局，所以屏蔽掉
        $this->view->engine->layout(false);
    }

    /**
     * 用户登陆页面
     * @author sin
     */
    public function login()
    {
        if (session('?site')) {
            $this->success('已经登录成功，跳转中...',redirect_url());
        }
        if(Request::isPost()){

            $SiteModel = new SiteManage();
            $result = $SiteModel->toLogin(input('param.'));
            if($result['status']){
                if(Request::isAjax()){
                    $result['data'] = redirect_url();
                    return $result;
                }else{
                    $this->redirect(redirect_url());
                }
            }else{
                return $result;
            }
        }else{
            return $this->fetch('login');
        }
    }


    /**
     * 用户退出
     * @author sin
     */
    public function logout()
    {
        //增加退出日志
        if(session('site.id')){
            $userLogModel = new UserLog();
            $userLogModel->setLog(session('site.id'),$userLogModel::USER_LOGOUT);
        }
        session('site', null);
        $this->success('退出成功','index/welcome');
    }

    
    /**
     * 清除后台全部缓存
     */
    public function clearCache()
    {
        Cache::clear();
        $this->success('清除成功...','/');
    }

}
