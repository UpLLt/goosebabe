<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/7/19
 * Time: 10:32
 */

namespace App\Controller;


use Common\Controller\AdminbaseController;

class ServiceCenterController extends AdminbaseController
{
    protected $options_model;

    function _initialize()
    {
        parent::_initialize();
        $this->options_model = D("Common/Options");
    }

    public function index()
    {
        $app_version = $this->options_model->where("option_name='app_service_phone'")->getField("option_value");
        if ($app_version) $app_version = json_decode($app_version, true);
        $this->assign('data', $app_version);
        $this->display();
    }

    public function edit_post()
    {
        if (IS_POST) {
            $options = I('options');
            $options = json_encode($options);
            if ($this->options_model->where("option_name='app_service_phone'")->find())
                $app_version = $this->options_model->where("option_name='app_service_phone'")->save(array('option_value' => $options));
            else  $this->options_model->where("option_name='app_service_phone'")->add(array(
                    'option_name' => 'app_service_phone',
                    'option_value' => $options)
            );

            if ($app_version === false) $this->error('失败');
            $this->success('成功');
        }
    }

}