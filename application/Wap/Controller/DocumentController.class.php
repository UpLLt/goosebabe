<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/22
 * Time: 10:41
 */

namespace Wap\Controller;


use App\Model\DocumentsModel;
use Think\Controller;

class DocumentController extends BaseController
{
    protected $document_model;

    public function __construct()
    {
        parent::__construct();
        $this->document_model = new DocumentsModel();
    }

    public function index()
    {
        layout(false);
        $key = I('key');
        $result = $this->document_model->where(array('doc_class' => $key))->find();
        if (!$result) throw new \Think\Exception('404');
        $this->assign('data', $result);
        $this->display();
    }
}