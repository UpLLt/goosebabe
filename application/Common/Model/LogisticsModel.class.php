<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/10/20
 * Time: 15:27
 */

namespace Common\Model;


class LogisticsModel extends CommonModel
{
    //自动验证
    protected $_validate = [
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        ['order_id', 'require', '参数不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['logistics_number', 'require', '物流单号' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['logistics_company', 'require', '物流公司' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
    ];
}