<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/10/9
 * Time: 16:29
 */

namespace Common\Model;


class AttrModel extends CommonModel
{
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('attr_name', 'require', '名称不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH),
    );
}