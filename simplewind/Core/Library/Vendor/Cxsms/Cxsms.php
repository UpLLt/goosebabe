<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/17
 * Time: 14:10
 */
class Cxsms
{
    const BaseUrl = 'http://dc.28inter.com/sms.aspx';

    protected $userid = '';
    protected $account = '';
    protected $password = '';
    protected $extno = '';

    public function __construct($options)
    {
        if (is_array($options) && !empty($options)) {

            $this->userid = isset($options['userid']) ? $options['userid'] : '';
            $this->account = isset($options['account']) ? $options['account'] : '';
            $this->password = isset($options['password']) ? $options['password'] : '';
            $this->extno = isset($options['extno']) ? $options['extno'] : '';

        } else {
            throw new Exception("非法参数");
        }
    }

    /**
     * 发送短信
     * @param $mobile 全部被叫号码 发送目标号码，多个号码之间用半角逗号隔开
     * @param $content 发送内容 短信的内容，内容需要UTF-8编码
     * @param string $sendTime 定时发送时间 为空表示立即发送，定时发送格式2010-10-24 09:08:10
     * @param string $action 发送任务命令 设置为固定的:send
     * @param string $extno 扩展子号 请先询问配置的通道是否支持扩展子号，如果不支持，请填空。子号只能为数字，且最多5位数
     * @return bool|mixed
     */
    public function send($mobile, $content, $sendTime = '', $action = 'send', $extno = '')
    {
        if (empty($mobile) || empty($content)) return false;
        $data = array(
            'userid' => $this->userid,
            'account' => $this->account,
            'password' => $this->password,
            'mobile' => $mobile,
            'content' => $content,
            'sendTime' => $sendTime,
            'extno' => $extno,
        );
        return self::xml_to_array($this->curl(self::BaseUrl . '?action=' . $action, $data));
    }

    /**
     * 余额及已发送量查询接口
     * @return array
     */
    public function overage()
    {
        $action = 'overage';
        $data = array(
            'userid' => $this->userid,
            'account' => $this->account,
            'password' => $this->password,
        );
        return self::xml_to_array($this->curl(self::BaseUrl . '?action=' . $action, $data));
    }


    private function curl($url, $data, $header = false, $method = "POST")
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $ret = curl_exec($ch);
        return $ret;
    }

    private function xml_to_array($xml)
    {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            $arr = array();
            for ($i = 0; $i < $count; $i++) {
                $key = $matches[1][$i];
                $val = self::xml_to_array($matches[2][$i]);  // 递归
                if (array_key_exists($key, $arr)) {
                    if (is_array($arr[$key])) {
                        if (!array_key_exists(0, $arr[$key])) {
                            $arr[$key] = array($arr[$key]);
                        }
                    } else {
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                } else {
                    $arr[$key] = $val;
                }
            }
            return $arr;
        } else {
            return $xml;
        }
    }
}