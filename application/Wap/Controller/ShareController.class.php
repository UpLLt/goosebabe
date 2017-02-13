<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/10/19
 * Time: 15:41
 */

namespace Wap\Controller;


class ShareController extends BaseController
{
    public function index()
    {
        layout(false);
        $this->display();
    }
}