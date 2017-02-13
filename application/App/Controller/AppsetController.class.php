<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/13
 * Time: 10:07
 */

namespace App\Controller;


use App\Model\ApppageModel;
use App\Model\AppsetModel;
use Common\Controller\AdminbaseController;
use Store\Model\StoreModel;

class AppsetController extends AdminbaseController
{
    protected $appset_model;
    protected $store_model;
    protected $apppage_model;

    public function __construct()
    {
        parent::__construct();
        $this->appset_model = new AppsetModel();
        $this->store_model = new StoreModel();
        $this->apppage_model = new ApppageModel();
    }

    public function index()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) $where['name'] = $keyword;
        $where['type'] = AppsetModel::TYPE_INDEX;

        $count = $this->appset_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->appset_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="js-ajax-dialog-btn" href="' . U('Appset/show_post', array('id' => $v['id'])) . '">' . ($v['isshow'] ? '隐藏' : '显示') . '</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Appset/top_post', array('id' => $v['id'])) . '">置顶</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="" href="' . U('Appset/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Appset/delete', array('id' => $v['id'])) . '">删除</a>';
            $result[$k]['status'] = ($v['istop'] == 1 ? '<span class="text-info">置顶</span>' : '默认') . '/' . ($v['isshow'] == 1 ? '<span class="text-info">显示</span>' : '<span class="">隐藏</span>');
            if (strpos($result[$k]['images'], 'http://') != -1) {
                $result[$k]['images'] = $v['images'] ? $result[$k]['images'] = "<a href='" . sp_get_asset_upload_path($v['images']) . "' target='_blank'>查看</a>" : "";
            }


            $result[$k]['type'] = $this->appset_model->getTypeValues($result[$k]['type']);

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['name'] . '</td>
            <td>' . $result[$k]['city'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['type'] . '</td>
            <td style="white-space:nowrap;">' . $this->apppage_model->getNameBysign($result[$k]['pages']) . '</td>
            <td>' . ($result[$k]['isdraw'] ? '<a href="javascript:open_iframe_dialog_w(\'' . U('Appset/activityinfo', array('id' => $v['id'])) . '\',\'活动详情\',\'\',\'50%\')">活动详情</a>' : '') . '</td>
            <td>' . $result[$k]['images'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['status'] . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['update_time']) . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }




        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
        $this->display();
    }

    /**
     * 广告
     */
    public function advertisement()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) $where['name'] = $keyword;
        $where['type'] = AppsetModel::TYPE_ADVERTISEMENT;
        $count = $this->appset_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->appset_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="" href="' . U('Appset/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Appset/delete', array('id' => $v['id'])) . '">删除</a>';
            $result[$k]['status'] = ($v['istop'] == 1 ? '置顶' : '默认') . '/' . ($v['isshow'] == 1 ? '显示' : '隐藏');
            $result[$k]['images'] = "http://" . $_SERVER['HTTP_HOST'] . $result[$k]['images'];


            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['name'] . '</td>
            <td>' . $result[$k]['describe'] . '</td>
            <td><img width="120" height="80" src="' . $result[$k]['images'] . '"></td>
            <td>' . $result[$k]['url'] . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['update_time']) . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['status'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
        $this->display();
    }

    public function add()
    {
        $result = $this->store_model
            ->group('store_city')
            ->field('store_city as city')
            ->where('store_city IS NOT NULL')
            ->select();
        foreach ($result as $v) {
            $options .= '<option value="' . $v['city'] . '">' . $v['city'] . '</option>';
        }

        $pagelist = $this->apppage_model->select();
        foreach ($pagelist as $v) {
            $options_page .= '<option value="' . $v['sign'] . '">' . $v['pname'] . '</option>';
        }

        $this->assign('optionsPage', $options_page);
        $this->assign('options', $options);
        $this->display();
    }

    public function add_post()
    {
        if (IS_POST) {

            $post = I('post.');
            $post['start_time'] = strtotime($post['start_time']);
            $post['end_time'] = strtotime($post['end_time']);
            $post['content'] = htmlspecialchars_decode($post['content']);

            if ($this->appset_model->create($post)) {
                $result = $this->appset_model->add();
                if ($result) {
                    if (!empty($post['jpush'])) {
                        jpush($post['jpush'], '活动');
                    }
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            } else {
                $this->error($this->appset_model->getError());
            }
        }
    }

    public function delete()
    {
        $id = I('id');
        if (empty($id)) $this->error('参数错误');
        $result = $this->appset_model->delete($id);
        if (!$result) $this->error('操作失败');
        $this->success('操作成功');
    }

    public function edit()
    {
        $id = I('id');
        if (empty($id)) $this->error('参数错误');
        $result = $this->appset_model->find($id);
        if (!$result) $this->error('数据不存在');

        $store = $this->store_model
            ->group('store_city')
            ->field('store_city as city')
            ->where('store_city IS NOT NULL')
            ->select();
        foreach ($store as $k => $v) {
            $store[$k]['selected'] = $v['city'] == $result['city'] ? 'selected' : '';
            $options .= '<option ' . $store[$k]['selected'] . ' value="' . $v['city'] . '">' . $v['city'] . '</option>';
        }

        unset($v);

        $pagelist = $this->apppage_model->select();
        foreach ($pagelist as $v) {
            $selects = $v['sign'] == $result['pages'] ? 'selected' : '';
            $options_page .= '<option ' . $selects . ' value="' . $v['sign'] . '">' . $v['pname'] . '</option>';
        }

//        $result['content'] = htmlspecialchars_decode($result['content']);

        $this->assign('optionsPage', $options_page);
        $this->assign('options', $options);
        $this->assign('data', $result);
//        dump($result);
        $this->display();
    }

    public function edit_post()
    {
        if (IS_POST) {

            $post = I('post.');
            $post['start_time'] = strtotime($post['start_time']);
            $post['end_time'] = strtotime($post['end_time']);
            $post['content'] = htmlspecialchars_decode($post['content']);

            if ($this->appset_model->create($post)) {
                $result = $this->appset_model->save();
                if ($result === false) {
                    $this->error('失败');
                } else {
                    $this->success('成功');
                }
            } else {
                $this->error($this->appset_model->getError());
            }
        }
    }


    /**
     * 版面
     */
    public function pagelists()
    {
        $count = $this->apppage_model->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->apppage_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="js-ajax-delete" href="' . U('Appset/pagedelete', array('id' => $v['id'])) . '">删除</a>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $result);
        $this->assign("Page", $page->show());
        $this->display();
    }

    public function pageadd_post()
    {
        if ($this->apppage_model->create()) {
            if ($this->apppage_model->add()) {
                $this->success();
            } else {
                $this->error('失败');
            }
        } else {
            $this->error($this->apppage_model->getError());
        }
    }

    public function pagedelete()
    {
        //缺少一个判断。
        if (isset($_GET['id'])) {
            $id = intval(I("get.id"));
            if ($this->apppage_model->where("id=$id")->delete() !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
        if (isset($_POST['ids'])) {
            $ids = join(",", $_POST['ids']);
            if ($this->apppage_model->where("id in ($ids)")->delete() !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }

    public function award()
    {
        $this->display();
    }


    public function activityinfo()
    {
        $id = I('get.id');
        $result = $this->appset_model->find($id);
        $result['status'] = '未开始';
        if (time() > $result['start_time']) {
            $result['status'] = '进行中';
            if ($result['parts_hit'] > ($result['hits'] - 1)) {
                $result['status'] = '进行中/结束';
            }
        }
        if (time() > $result['end_time']) {
            $result['status'] = '已下线';
        }
        $result['start_time'] = date('Y-m-d H:i:s');
        $result['end_time'] = date('Y-m-d H:i:s');
        $this->assign('data', $result);
        $this->display();
    }


    public function show_post()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('参数错误');
        $where = array('id' => $id);
        $isshow = $this->appset_model->where($where)->getField('isshow');
        if ($this->appset_model->where($where)->save(array('isshow' => $isshow ? 0 : 1)) !== false) $this->success();
        else $this->error('失败');
    }

    public function top_post()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('参数错误');
        $adv = $this->appset_model->find($id);

        if ($adv['city']) $where['city'] = $adv['city'];
        if ($adv['type']) $where['type'] = $adv['type'];
        if ($adv['pages']) $where['pages'] = $adv['pages'];

        $b1 = $this->appset_model->where($where)->save(array('istop' => 0));
        $b2 = $this->appset_model->where(array('id' => $id))->save(array('istop' => 1));
        $this->success();
    }


}