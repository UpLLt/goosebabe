<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/13
 * Time: 15:49
 */

namespace App\Model;


use Common\Model\CommonModel;

class ApppageModel extends CommonModel
{
    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('pname', 'require', '版面名称必填！', 1, 'regex', CommonModel:: MODEL_BOTH),
        array('sign', '', '标记不能重复！', 0, 'unique', CommonModel:: MODEL_INSERT), //
    );

    //自动完成
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('update_time', 'time', 3, 'function'),
    );

    /**
     * @param $sign
     * @return mixed
     */
    public function getNameBysign($sign)
    {
        return $this->where(array('sign' => $sign))->getField('pname');
    }
}