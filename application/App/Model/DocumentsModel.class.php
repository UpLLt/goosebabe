<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/21
 * Time: 16:55
 */

namespace App\Model;


use Common\Model\CommonModel;

class DocumentsModel extends CommonModel
{
    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('doc_class', 'require', '文档类型必填！', 1, 'regex', CommonModel:: MODEL_BOTH),
        array('name', 'require', '名称必选！', 1, 'regex', CommonModel:: MODEL_BOTH),
        array('desc', 'require', '描述必填！', 1, 'regex', CommonModel:: MODEL_BOTH),
        array('content', 'require', '内容必填！', 1, 'regex', CommonModel:: MODEL_BOTH),
    );

    //自动完成
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('update_time', 'time', 3, 'function'),
    );
}