<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/4
 * Time: 下午4:13
 */

namespace Appapi\Controller;


use Common\Model\NoticeModel;

class NoticeController extends ApibaseController
{
    private $notice_model;

    public function __construct()
    {
        parent::__construct();
        $this->notice_model  =  new NoticeModel();
    }

    public function index()
    {
        if (!IS_POST)  exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $result = $this->notice_model->field('id,smeta')->where(array('status'=> 1))->find();
        $result['smeta'] = sp_asset_relative_url($result['smeta']);
        $result['url'] = $this->geturl('/Wap/Notice/index/id/'.$result['id']);
        exit($this->returnApiSuccess($result));
    }
}