<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;


use Common\Model\MemberModel;
use Common\Model\SmslogModel;
use Think\Controller;
use Web\Controller\BaseController;

class RegisterController extends BaseController
{

    private $smslog_model;
    private $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->smslog_model = new SmslogModel();
        $this->member_model = new MemberModel();
    }



    public function index(){
        $this->display();
    }


    public function backpass(){
        $this->display('getpwd');
    }

    public function backpass_l(){
        $this->display('getpass');
    }

    /**
     * 注册
     */
    public function register()
    {
        if (IS_POST) {
            $password = I('post.password');
            $code = I('post.code');
            $phone = I('post.phone');
            $vcode = I('post.vcode');
            $this->checkparam(array($password, $code, $phone));

            $vode = $this->check_verify($vcode);
            if(!$vode){
                exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码不正确'));
            }
            if ($this->member_model->checkUserName($phone) > 0)
                exit($this->returnApiError(BaseController::FATAL_ERROR, '该帐号已注册'));

            if (!$this->smslog_model->checkcode($phone, $code)) {
                exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码错误或过期'));
            }

            $data = array(
                'username' => $phone,
                'password' => sp_password($password),
                'create_time' => time(),
                'update_time' => time(),
            );

            if (!$this->member_model->create($data)) {
                exit($this->returnApiError(BaseController::FATAL_ERROR, $this->member_model->getError()));
            }
            if ($this->member_model->add($data)) {
                exit($this->returnApiSuccess());
            } else {
                exec($this->returnApiError('失败'));
            }

        } else {
            exit($this->returnApiError(BaseController::INVALID_INTERFACE));
        }
    }

    /**
     * @param $code
     * @param string $id
     * @return bool
     */
    function check_verify($code, $id = ''){
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    /**
     * 修改密码
     */
    public function modifypasswd()
    {
        if (IS_POST) {
            $username = I('post.username');
            $oldpwd = I('post.oldpwd');
            $newpwd = I('post.newpwd');

            $this->checkparam(array($username, $newpwd, $oldpwd));
            $where = array('username' => $username);
            $member = $this->member_model->where($where)->find();
            if (!$member) exit($this->returnApiError(BaseController::FATAL_ERROR, '用户不存在'));

            if (!sp_compare_password($oldpwd, $member['password'])) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '旧密码错误'));

            $resutl = $this->member_model->where($where)->save(array('password' => sp_password($newpwd)));
            if ($resutl === false) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码修改失败'));
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(BaseController::INVALID_INTERFACE));
        }
    }

    /**
     * 找回密码
     */
    public function backpasswd()
    {
        if (IS_POST) {
            $username = I('post.username');
            $code = I('post.code');
            $password = I('post.password');
            $vcode = I('post.vcode');

            //checkparam
            $this->checkparam(array($username, $code, $password,$vcode));

            $vode = $this->check_verify($vcode);
            if(!$vode){
                exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码不正确'));
            }
            if (strlen($password) < 5) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码长度不正确'));

            $where = array('username' => $username);
            $member = $this->member_model->where($where)->find();

            if (!$member) {
                exit($this->returnApiError(BaseController::FATAL_ERROR, '查无此用户'));
            };


            $result = $this->smslog_model->checkcode($username, $code);
            if (!$result) exit($this->returnApiError(BaseController::FATAL_ERROR, '验证码错误或过期'));


            $update = $this->member_model->where($where)->save(array('password' => sp_password($password)));
            if ($update === false) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码修改失败'));

            exit($this->returnApiSuccess());

        } else {

            exit($this->returnApiError(BaseController::INVALID_INTERFACE));

        }

    }




}