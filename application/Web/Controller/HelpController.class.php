<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;

use Common\Model\HelpModel;
use Think\Controller;

class HelpController extends BaseController
{
    private $Help_model;

    public function __construct()
    {
        parent::__construct();
        $this->Help_model = new HelpModel();
    }

    public function index(){
        $type = I('type');
        if(!$type) $type = 1 ;
        $list = $this->Help_model->where(array('type'=>$type))->field('content,title')->find();
        $list['content'] = htmlspecialchars_decode($list['content']);
        $this->assign('list',$list);
        $this->display();
    }



}
