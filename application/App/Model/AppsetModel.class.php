<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/13
 * Time: 10:41
 */

namespace App\Model;


use Common\Model\CommonModel;

class AppsetModel extends CommonModel
{
    const TYPE_INDEX = 1; //首页轮播
    const TYPE_ADVERTISEMENT = 2; //广告

    protected $tableName = "appset";
    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('name', 'require', '名称必填！', 1, 'regex', CommonModel:: MODEL_BOTH),
        array('type', 'require', '类型必选！', 1, 'regex', CommonModel:: MODEL_BOTH),
        array('images', 'require', '你还没有上传图片！', 1, 'regex', CommonModel:: MODEL_BOTH),
    );

    //自动完成
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('update_time', 'time', 3, 'function'),
    );

    public function getTypeValues($id)
    {
        switch ($id) {
            case self::TYPE_INDEX:
                return '首页轮播';
                break;
            case self::TYPE_ADVERTISEMENT:
                return '广告';
                break;
            case self::TYPE_CROUSE_OTHER:
                return '其它轮播';
                break;
            default :
                return '';
                break;
        }
    }
}