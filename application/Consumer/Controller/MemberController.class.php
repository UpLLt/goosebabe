<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/19
 * Time: 15:46
 */

namespace Consumer\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\AddressModel;
use Common\Model\MemberModel;

class MemberController extends AdminbaseController
{
    private $member_model;
    private $address_model;

    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
        $this->address_model = new AddressModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $fields = array(
            'keyword' => array("field" => "username", "operator" => "=", 'datatype' => 'string'),
        );
        $where_ands = array();
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }
        $where = join(" and ", $where_ands);

        $count = $this->member_model
            ->where($where)
            ->join($join)
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->member_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {


            $result[$k]['identity_phone'] = '<a href="javascript:open_iframe_dialog(\'' . U('Member/activityinfo', array('id' => $v['id'])) . '\',\'证件照\',\'\',\'90%\')">证件照</a>';


//            $result[$k]['headimg'] = '<a target="_blank" href="' . $v['headimg'] . '">查看</a>';

            $result[$k]['str_manage'] = '<a class="" href="' . U('Member/address', array('id' => $v['id'])) . '">收货地址</a> |
                                         <a class="js-ajax-delete" href="' . U('Member/delete', array('id' => $v['id'])) . '">删除</a>';
//            $result[$k]['str_manage'] .= ' | ';
//            $result[$k]['str_manage'] .= '<a class="" href="' . U('Member/authAction', array('id' => $v['id'])) . '">身份证认证</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['username'] . '</td>
            <td>' . $result[$k]['nickname'] . '</td>
            <td>' . $this->member_model->getSexTostring($v['sex']) . '</td>
            <td>' . $result[$k]['real_name'] . '</td>
            <td>' . $result[$k]['identity_card'] . '</td>
            <td>' . $result[$k]['identity_phone'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';

        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function delete(){
        $mid = intval(I('id'));
        $result = $this->member_model->where(array('id'=>$mid))->delete();
        if($result) {
            $this->success('success');
        }else{
            $this->error('error');
        }

    }

    public function address()
    {
        $this->_addresslist();
        $this->display();
    }

    private function _addresslist()
    {
        $mid = intval(I('id'));
        if (empty($mid)) $this->error('empty');

        $where['mid'] = $mid;
        $count = $this->address_model
            ->where($where)
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->address_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] = '';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['fullname'] . '</td>
            <td>' . $result[$k]['address'] . '</td>
            <td>' . $result[$k]['city'] . '</td>
            <td>' . $result[$k]['postcode'] . '</td>
            <td>' . $result[$k]['shopping_telephone'] . '</td>
            <td>' . $result[$k]['status'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function authAction()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');
        $result = $this->member_model->find($id);
        if ($result['authentication'] == 1) {
            $this->error('已审核');
        }

        $save = $this->member_model->where(array('id' => $id))->save(array('authentication' => '1'));
        if ($save === false)
            $this->error('操作失败');
        else
            $this->success('操作成功');

    }

    public function activityinfo()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $result = $this->member_model->find($id);
        $identity_phone = json_decode($result['identity_phone'], true);

        $front = '';
        $back = '';

        foreach ($identity_phone as $k => $v) {
            if ($v['key'] == 'front') {
                $front = $v;
            }
            if ($v['key'] == 'back') {
                $back = $v;
            }
        }

        $this->assign('member_data', $result);
        $this->assign('front', $front);
        $this->assign('back', $back);
        $this->display();
    }
}