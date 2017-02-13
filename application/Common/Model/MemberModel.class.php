<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/9
 * Time: 14:39
 */

namespace Common\Model;


class MemberModel extends CommonModel
{

    const SEX_DEFAULT = 0;
    const SEX_BOY = 1;
    const SEX_GIRL = 2;

    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
//        array('username', 'require', '用户名必填！', 1, 'regex', CommonModel:: MODEL_INSERT),
        array('username', '', '该手机号码已经被注册，不能重复注册。', 0, 'unique', CommonModel:: MODEL_INSERT),
        array('paypassword', '6,20', '密码长度为6-20位', 0, 'length', CommonModel:: MODEL_UPDATE),
    );

    public function getCodeNameByid($id)
    {
        return $this->where(array('id' => $id))->getField('code');
    }


    public function getUserNameByid($id)
    {
        return $this->where(array('id' => $id))->getField('username');
    }

    public function getUseridBybindcode($bindcode)
    {
        return $this->where(array('code' => $bindcode))->getField('id');
    }

    public function getUseridByCode($code)
    {
        return $this->where(array('code' => $code))->getField('id');
    }

    public function getUserDataByCode($code, $filed = '')
    {
        return $this->field($filed)->where(array('code' => $code))->find();
    }

    public function checkUserName($phone)
    {
        return $this->where(array('username' => $phone))->count();
    }

    /**
     * 获取剩余抽奖次数
     * @param $mid
     * @return mixed
     */
    public function getRaffleByMid($mid)
    {
        return $this->where(array('id' => $mid))->getField('raffle');
    }

    public function raffleAdd($mid)
    {
        return $this->save(array("id" => $mid, "raffle" => array("exp", "raffle+1")));
    }

    public function raffleSub($mid)
    {
        if ($this->getRaffleByMid($mid) < 1) return false;
        return $this->save(array("id" => $mid, "raffle" => array("exp", "raffle-1")));
    }


    /**
     * 获取userid
     * @param $bindcode_store
     * @return mixed
     */
    public function getUseridBystorecode($bindcode_store)
    {
        return $this->where(array('bindcode_store' => $bindcode_store))->getField('id');
    }


    public function getSexTostring($sex)
    {
        switch ($sex) {
            case self::SEX_BOY:
                return '男';
                break;
            case self::SEX_GIRL:
                return '女';
                break;
            default:
                return '未知';
                break;
        }
    }
}