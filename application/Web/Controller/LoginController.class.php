<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;


use Common\Model\MemberModel;
use Think\Controller;

class LoginController extends BaseController
{

    protected $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
    }

    public function index(){
        $this->assign('key',I('key'));
        $this->display('login');
    }

    /**
     * 登录
     */
    public function login(){


        if (IS_POST) {
            $userAccount = I("post.username");
            $userPassword = I("post.password");
            $key = I('post.key');
            $this->checkparam(array($userAccount, $userPassword));
            $where = array(
                'username' => $userAccount,
                'password' => sp_password($userPassword)
            );
            $field = 'id as mid,username,headimg,authentication';
            $members = $this->member_model
                ->field($field)
                ->where($where)
                ->find();
            if (!$members) exit($this->returnApiError(BaseController::FATAL_ERROR, '登录帐号或者密码错误.'));

            $members['headimg'] = $members['headimg'] ? $this->geturl($members['headimg']) : '';

            session('mid', $members['mid']);
            $save_result = session('mid');
            if (empty($save_result) ) exit($this->returnApiError(BaseController::FATAL_ERROR));


            if($key){
                exit($this->returnApiSuccess($key));
            }else{
                exit($this->returnApiSuccess());
            }

        } else {
            exit($this->returnApiError(BaseController::INVALID_INTERFACE));
        }
    }

}