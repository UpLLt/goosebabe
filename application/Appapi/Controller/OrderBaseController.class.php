<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 13:49
 */

namespace Appapi\Controller;


class OrderBaseController extends ApibaseController
{
    public function getOrderNumber()
    {
        $num = $this->get_code(12, 1);
        if (D('order')->where(array('order_sn' => $num))->count() > 0) {
            return self::getOrderNumber();
        }
        return $num;
    }
}