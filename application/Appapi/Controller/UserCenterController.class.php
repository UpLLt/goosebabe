<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 19:13
 */

namespace Appapi\Controller;


use Common\Model\MemberModel;
use Think\Image;

class UserCenterController extends ApibaseController
{
    private $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
    }

    public function getHeadimg()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $this->checkparam(array($mid, $token));
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $result = $this->member_model
                ->where(array('id' => $mid))
                ->field('headimg,sex,nickname')
                ->find();

            $result['headimg'] = $this->geturl($result['headimg']);


            exit($this->returnApiSuccess($result));
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }


    public function updateHeadimg()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $this->checkparam(array($mid, $token));
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $path = '/data/upload/header/';

            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 3145728;// 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath = '.' . $path; // 设置附件上传根目录
            // 上传单个文件
            $info = $upload->uploadOne($_FILES['photo']);
            if (!$info) exit($this->returnApiError(ApibaseController::FATAL_ERROR, $upload->getError()));

            $fileurl = $path . $info['savepath'] . $info['savename'];

//            $image = new Image();
//            $image->open('.' . $fileurl);
//            $mini = $path . $info['savepath'] . 'mini_' . $info['savename'];
//            $b = $image->thumb(64, 64, \Think\Image::IMAGE_THUMB_CENTER)->save('.' . $mini);

            $result = $this->member_model->where(array('id' => $mid))->save(array('headimg' => $fileurl));

            if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '上传成功，写入数据库失败'));
            exit($this->returnApiSuccess($this->member_model->getLastSql()));
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }


    public function updataSex()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $sex = I('post.sex');

        $this->checkparam(array($mid, $token, $sex));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


        $result = $this->member_model->where(array('id' => $mid))->save(array('sex' => $sex));
        if ($result === false)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR));

        exit($this->returnApiSuccess());
    }


    public function updataNickname()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $nickname = I('post.nickname');

        $this->checkparam(array($mid, $token, $nickname));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->member_model->where(array('id' => $mid))->save(array('nickname' => $nickname));
        if ($result === false)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        exit($this->returnApiSuccess());
    }
}