<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/9
 * Time: 16:40
 */

namespace Appapi\Controller;


use Common\Model\MemberModel;
use Common\Model\SmslogModel;
use Think\Image;

/**
 * 用户登录注册等接口
 * Class MemberController
 * @package Appapi\Controller
 */
class MemberController extends ApibaseController
{
    private $smslog_model;
    private $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->smslog_model = new SmslogModel();
        $this->member_model = new MemberModel();
    }


    public function sendsms()
    {
        if (IS_POST) {
            $mobile = I('post.phone');
            $this->checkparam([$mobile]);

            if (strlen($mobile) != 11)
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '手机号码错误'));

            if ($this->member_model->checkUserName($mobile) > 0) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '该帐号已注册'));


            $code = $this->get_code(6, 1);
            $content = '【' . C('SMS_ACCOUNT.companyname') . '】你的验证码是:' . $code . '，15分钟内有效。';
            $Validtime = 60 * 15; //有效时间

            $data = [
                'code'        => $code,
                'content'     => $content,
                'mobile'      => $mobile,
                'create_time' => time(),
                'end_time'    => time() + $Validtime,
            ];

            vendor("Cxsms.Cxsms");
            $options = C('SMS_ACCOUNT');
            $Cxsms = new \Cxsms($options);
            $result = $Cxsms->send($mobile, $content);
            if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                $adds = $this->smslog_model->add($data);
                if ($adds) exit($this->returnApiSuccess());
                else
                    exit($this->returnApiError(ApibaseController::FATAL_ERROR, '数据库写入失败'));
            } else {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '验证码发送失败'));
            }
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }

    }


    public function forgetsendsms()
    {
        if (IS_POST) {
            $mobile = I('post.phone');
            $this->checkparam([$mobile]);

            if (strlen($mobile) != 11)
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '手机号码错误'));

            if ($this->member_model->checkUserName($mobile) == 0)
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '该帐号未注册'));

            $code = $this->get_code(6, 1);
            $content = '【' . C('SMS_ACCOUNT.companyname') . '】你的验证码是:' . $code . '，15分钟内有效。';
            $Validtime = 60 * 15; //有效时间

            $data = [
                'code'        => $code,
                'content'     => $content,
                'mobile'      => $mobile,
                'create_time' => time(),
                'end_time'    => time() + $Validtime,
            ];

            vendor("Cxsms.Cxsms");
            $options = C('SMS_ACCOUNT');
            $Cxsms = new \Cxsms($options);
            $result = $Cxsms->send($mobile, $content);
            if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                $adds = $this->smslog_model->add($data);
                if ($adds) exit($this->returnApiSuccess());
                else
                    exit($this->returnApiError(ApibaseController::FATAL_ERROR, '数据库写入失败'));
            } else {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '验证码发送失败'));
            }
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }

    }

    public function register()
    {
        if (IS_POST) {
            $password = I('post.password');
            $code = I('post.code');
            $phone = I('post.phone');
            $this->checkparam([$password, $code, $phone]);

            if ($this->member_model->checkUserName($phone) > 0)
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '该帐号已注册'));

            if (!$this->smslog_model->checkcode($phone, $code)) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '验证码错误或过期'));
            }

            $data = [
                'username'    => $phone,
                'password'    => sp_password($password),
                'sex'         => 1,
                'create_time' => time(),
                'update_time' => time(),
            ];

            if (!$this->member_model->create($data)) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->member_model->getError()));
            }
            if ($this->member_model->add($data)) {
                exit($this->returnApiSuccess());
            } else {
                exec($this->returnApiError('失败'));
            }

        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }

    /**
     * 会员登录
     */
    public function userlogin()
    {
        if (IS_POST) {
            $userAccount = I("post.userAccount");
            $userPassword = I("post.userPassword");
            $this->checkparam([$userAccount, $userPassword]);
            $where = [
                'username' => $userAccount,
                'password' => sp_password($userPassword),
            ];
            $field = 'id as mid,username,headimg,authentication';
            $members = $this->member_model
                ->field($field)
                ->where($where)
                ->find();
            if (!$members) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '登录帐号或者密码错误.'));

            $members['headimg'] = $members['headimg'] ? $this->geturl($members['headimg']) : '';
            $token = $this->createtoken();
            $token_end_time = time() + 3600 * 24 * 7;
            $save = ['token' => $token, 'token_end_time' => $token_end_time];
            $save_result = $this->member_model->where(['id' => $members['mid']])->save($save);
            $members['token'] = $token;

            if ($save_result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
            exit($this->returnApiSuccess($members, $token));
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }

    /**
     * 退出登录
     */
    public function userloginout()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $this->checkparam([$mid]);

            $result = $this->member_model
                ->where(['id' => $mid])
                ->save([
                    'token'          => '',
                    'token_end_time' => '',
                ]);
            if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }

    /**
     * 验证token
     */
    public function verifytoken()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I("post.token");
            $this->checkparam([$mid, $token]);

            if ($this->checktoken($mid, $token)) {
                $data = [
                    'authentication' => $this->member_model->where(['id' => $mid])->getField('authentication'),
                ];
                exit($this->returnApiSuccess($data));
            } else {
                exit($this->returnApiError(ApibaseController::TOKEN_ERROR, '错误'));
            }
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
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
            //
            $this->checkparam([$username, $newpwd, $oldpwd]);
            $where = ['username' => $username];
            $member = $this->member_model->where($where)->find();
            if (!$member) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '用户不存在'));

            if (!sp_compare_password($oldpwd, $member['password'])) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '旧密码错误'));

            $resutl = $this->member_model->where($where)->save(['password' => sp_password($newpwd)]);
            if ($resutl === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '密码修改失败'));
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }


    public function updateHeadimg()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $this->checkparam([$mid, $token]);
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $path = '/data/upload/header/';

            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 3145728;// 设置附件上传大小
            $upload->exts = ['jpg', 'gif', 'png', 'jpeg'];// 设置附件上传类型
            $upload->rootPath = '.' . $path; // 设置附件上传根目录
            // 上传单个文件
            $info = $upload->uploadOne($_FILES['photo']);
            if (!$info) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '上传失败'));

            $fileurl = $path . $info['savepath'] . $info['savename'];

            $image = new Image();
            $image->open('.' . $fileurl);
            $mini = $path . $info['savepath'] . 'mini_' . $info['savename'];
            $b = $image->thumb(64, 64, \Think\Image::IMAGE_THUMB_CENTER)->save('.' . $mini);

            $result = $this->member_model->where(['id' => $mid])->save(
                [
                    'headimg'     => $fileurl,
                    'headimg_min' => $mini,
                    'update_time' => time(),
                ]);

            if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '上传成功，写入数据库失败'));
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
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

            //checkparam
            $this->checkparam([$username, $code, $password]);
            if (strlen($password) < 5) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '密码长度过段'));

            $where = ['username' => $username];
            $member = $this->member_model->where($where)->find();

            if (!$member) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '查无此用户'));
            };


            $result = $this->smslog_model->checkcode($username, $code);
            if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '验证码错误或过期'));


            $update = $this->member_model->where($where)->save(['password' => sp_password($password)]);
            if ($update === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '密码修改失败'));

            exit($this->returnApiSuccess());

        } else {

            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        }

    }


}