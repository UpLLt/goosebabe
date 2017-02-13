<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/21
 * Time: 15:42
 */

namespace Common\Model;


class HelpModel extends CommonModel
{
    const SHOPP__PROCESS = 1;
    const COMMON_PROBLEM = 2;
    const PAY_MONEY = 3;
    const FREIGHT = 4;
    const CONNECT_CUSTOMER = 5;
    const ABOUT_US = 6;


        //自动验证
    protected $_validate = array(
//        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
////        array('username', 'require', '用户名必填！', 1, 'regex', CommonModel:: MODEL_INSERT),
//        array('username', '', '该手机号码已经被注册，不能重复注册。', 0, 'unique', CommonModel:: MODEL_INSERT),
//        array('paypassword', '6,20', '密码长度为6-20位', 0, 'length', CommonModel:: MODEL_UPDATE),
    );

    public function getHelpTostring($type)
    {
        switch ($type) {
            case self::SHOPP__PROCESS:
                return '购物流程';
                break;
            case self::COMMON_PROBLEM:
                return '常见问题';
                break;
            case self::PAY_MONEY:
                return '在线支付';
                break;
            case self::FREIGHT:
                return '运费及时效';
                break;
            case self::CONNECT_CUSTOMER:
                return '联系客服';
                break;
            case self::ABOUT_US:
                return '关于我们';
                break;

        }
    }

}