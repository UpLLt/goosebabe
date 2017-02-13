<?php

namespace Common\Model;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/9
 * Time: 16:15
 */
class SmslogModel extends CommonModel
{

    //自动完成
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
    );

    /**
     * @param $mid
     * @param $code
     * @return bool
     */
    public function checkcode($mobile, $code)
    {
        $result = $this->where(array('mobile' => $mobile, 'code' => $code))->order('id desc')->select();
        if ($result && $result[0]['end_time'] > time()) {
            return true;
        }
        return false;
    }
}