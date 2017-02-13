<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/4
 * Time: 下午4:08
 */

namespace Wap\Controller;


use Common\Model\NoticeModel;

class NoticeController extends BaseController
{
    private $notice_model;

    public function __construct()
    {
        parent::__construct();
        $this->notice_model = new NoticeModel();
    }

    public function index()
    {
        layout(false);
        $id = I('get.id');
        $data = $this->notice_model->where(array('id' => $id))->getField('content');
        $this->assign('data',$data);
        $this->display();
    }
}