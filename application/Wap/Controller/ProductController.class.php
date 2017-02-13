<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 9:56
 */

namespace Wap\Controller;


use Common\Model\ProductModel;

class ProductController extends BaseController
{
    private $product_model;

    public function __construct()
    {
        parent::__construct();
        $this->product_model = new ProductModel();
    }

    public function detail()
    {
        layout(false);
        $id = I('id');
        $data = $this->product_model->field('content')->find($id);
        if (!$data) throw new \Think\Exception('404');
        $this->assign('data', $data);
        $this->display();
    }
}