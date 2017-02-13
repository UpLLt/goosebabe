<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/7/19
 * Time: 15:45
 */

namespace App\Controller;


use Common\Controller\AdminbaseController;

/**
 * APP请求记录
 * Class RequestrecordController
 * @package App\Controller
 */
class RequestrecordController extends AdminbaseController
{
    protected $appapi_model;

    public function __construct()
    {
        parent::__construct();
        $this->appapi_model = D('appapi');
    }

    public function lists()
    {
        $this->_list();
        $this->_getRequestCount();
        $this->display();
    }

    private function _list()
    {

        $fields = array(
            'start_time' => array("field" => "create_time", "operator" => ">"),
            'end_time' => array("field" => "create_time", "operator" => "<"),
            'r_type' => array("field" => "r_type", "operator" => "="),
            'model' => array("field" => "model", "operator" => "="),
            'request_m_f' => array("field" => "request_m_f", "operator" => "="),
        );

        $where_ands = array();

        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($get);
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($get);
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where = join(" and ", $where_ands);

        $count = $this->appapi_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->appapi_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Requestrecord/delete', array('id' => $v['id'])) . '">删除</a>';

            $categorys .= '<tr>
            <td style="white-space:nowrap;">' . ($k + 1) . '</td>
            <td style="white-space:nowrap;">' . ($result[$k]['r_type'] == 'post' ? '<span class="text-error">' . $result[$k]['r_type'] . '</span>' : '<span class="text-info">' . $result[$k]['r_type'] . '</span>') . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['model'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['request_m_f'] . '</td>
            <td>' . ($this->arrayToString(json_decode($result[$k]['values'], true))) . '</td>
            <td style="white-space:nowrap;">' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        unset($v);

        $r_type = $this->appapi_model->field('r_type')->group('r_type')->select();
        $request_m_f = $this->appapi_model->field('request_m_f,count(id) as count')->group('request_m_f')->select();
        $model = $this->appapi_model->field('model')->group('model')->select();


        foreach ($r_type as $k => $v) {
            $selete = $_GET['r_type'] == $v['r_type'] ? 'selected' : '';
            $r_type_options .= '<option ' . $selete . ' value="' . $v['r_type'] . '">' . $v['r_type'] . '</option>';
        }
        unset($v);
        foreach ($request_m_f as $k => $v) {
            $selete = $_GET['request_m_f'] == $v['request_m_f'] ? 'selected' : '';
            $request_m_f_options .= '<option ' . $selete . ' value="' . $v['request_m_f'] . '">' . $v['request_m_f'] . '-' . $v['count'] . '</option>';
        }
        unset($v);
        foreach ($model as $k => $v) {
            $selete = $_GET['model'] == $v['model'] ? 'selected' : '';
            $model_options .= '<option ' . $selete . ' value="' . $v['model'] . '">' . $v['model'] . '</option>';
        }
        unset($v);

        $this->assign('r_type', $r_type_options);
        $this->assign('request_m_f', $request_m_f_options);
        $this->assign('model', $model_options);

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    private function arrayToString(array  $array)
    {
        $str = '{';
        foreach ($array as $k => $v) {
            $str .= "'$k'='$v'";
            if (count($array) > $v) $str .= ',';
        }
        $str .= '}';
        return $str;
    }

    private function _getRequestCount()
    {
        $count = $this->appapi_model
            ->where(array('create_time' => array('gt', strtotime(date('Y-m-d', time())))))
            ->count();
        $this->assign('requestCount', $count);
    }

    public function delete()
    {
        $id = I('id');
        if (empty($id)) $this->error('empty');
        $result = $this->appapi_model->delete($id);
        if ($result) $this->success('success');
        else $this->error('error');
    }
}