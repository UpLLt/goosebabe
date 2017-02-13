<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 17:15
 */

namespace Appapi\Controller;


use Common\Model\MemberModel;

class RealnameController extends ApibaseController
{
    private $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
    }

    public function upload()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $real_name = I('post.real_name');
        $identity_card = I('post.identity_card');

        if (!$this->isIdCard($identity_card)) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '身份证号码不合法'));

        $this->checkparam(array($mid, $token, $identity_card, $real_name));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

//        $authentication = $this->member_model->where(array('id' => $mid))->getField('authentication');
//        if ($authentication == 1) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '已认证，不可重复提交'));

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/identity/'; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        // 上传文件
        $info = $upload->upload();
        if (!$info) {
            // 上传错误提示错误信息
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $upload->getError()));
        } else {// 上传成功 获取上传文件信息
            foreach ($info as $file) {
                $url = '/data/upload/identity/' . $file['savepath'] . $file['savename'];
                $upload_iamges[] = array(
                    'key' => $file['key'],
                    'url' => $url
                );
            }
        }

        $data = array(
            'identity_card' => $identity_card,
            'real_name' => $real_name,
            'authentication' => 1,
            'identity_phone' => json_encode($upload_iamges)
        );

        $result = $this->member_model->where(array('id' => $mid))->save($data);
        if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, 'error'));

        exit($this->returnApiSuccess());
    }
}