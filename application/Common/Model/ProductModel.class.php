<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 14:07
 */

namespace Common\Model;


class ProductModel extends CommonModel
{

    const STATUS_PUTAWAY = 1; //上架
    const STATUS_SOLDOUT = 0; //下架


    /**
     * @param $status
     * @return string
     */
    public function getStatusTostring($status)
    {
        switch ($status) {
            case self::STATUS_PUTAWAY:
                return '<span class="text-info">上架</span>';
                break;
            case self::STATUS_SOLDOUT:
                return '<span class="text-error">下架</span>';
                break;
            default:
                return '';
                break;
        }
    }


    /**
     * 检查库存是否大于某值
     * @param $product_id
     * @return bool
     */
    public function checkInventory($product_id, $quantity)
    {
        $count = $this->where(array('id'))->getField('inventory');
        if ($count && $count > $quantity) {
            return true;
        } else {
            return false;
        }
    }
}