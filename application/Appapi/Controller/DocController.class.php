<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/22
 * Time: 11:06
 */

namespace Appapi\Controller;


use App\Model\DocumentsModel;

class DocController extends ApibaseController
{
    protected $document_model;

    public function __construct()
    {
        parent::__construct();
        $this->document_model = new DocumentsModel();
    }

    public function lists()
    {
        $key = I('key');
        $this->checkparam(array($key));

        $result = $this->document_model->where(array('doc_class' => $key))->find();
        if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '无数据'));

        $url = $this->geturl(array(
            '/Wap/Document/index/key',
            $result['doc_class']
        ));

        exit($this->returnApiSuccess($url));
    }
}
