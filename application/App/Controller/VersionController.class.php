<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/22
 * Time: 11:34
 */

namespace App\Controller;


use Common\Controller\AdminbaseController;
use Think\Controller;

class VersionController extends AdminbaseController
{
    protected $options_model;

    function _initialize()
    {
        parent::_initialize();
        $this->options_model = D("Common/Options");
    }

    public function index()
    {
        $app_version = $this->options_model->where("option_name='app_version'")->getField("option_value");
        if ($app_version) $app_version = json_decode($app_version, true);
        $this->assign('data', $app_version);
        $this->display();
    }

    public function edit_post()
    {
        if (IS_POST) {
            $options = I('options');
            $options = json_encode($options);
            if ($this->options_model->where("option_name='app_version'")->find())
                $app_version = $this->options_model->where("option_name='app_version'")->save(array('option_value' => $options));
            else  $this->options_model->where("option_name='app_version'")->add(array(
                    'option_name' => 'app_version',
                    'option_value' => $options)
            );

            if ($app_version === false) $this->error('失败');
            $this->success('成功');
        }
    }
}