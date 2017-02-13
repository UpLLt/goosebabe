<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:38
 */

namespace Appapi\Controller;

use Common\Model\AddressModel;

/**
 * 管理收货地址
 * Class AddressController
 * @package Appapi\Controller
 */
class AddressController extends ApibaseController
{
    private $address_model;

    public function __construct()
    {
        parent::__construct();
        $this->address_model = new AddressModel();
    }


    /**
     * 获取收货地址
     */
    public function getAddress()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');

        if ($this->checkparam(array($mid, $token))) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $result = $this->address_model
            ->where(array('mid' => $mid))
            ->order('id desc')
            ->select();

        $data['datas'] = $result;
        exit($this->returnApiSuccess($data));
    }

    /**
     * 增加收货地址
     */
    public function addAddress()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $address = I('post.address');

        if ($this->checkparam(array($mid, $token))) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


    }
}