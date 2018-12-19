<?php

require_once "src/wxBizDataCrypt.php";

/**
 * Title:微信接口信息处理
 * Author:CSHYUNHUI
 * Date:2018-9-30
 * 
 * 
 * ** */
class Weixin {

    private $mn_appid;
    private $mn_app_secret;
    private $mp_appid;
    private $mp_app_secret;

    ///$mn_appid:小程序appid $mn_app_secret:小程序app_secret $mp_appid:公众号appid  $mp_app_secret:公众号app_secret
    public function __construct($mn_appid, $mn_app_secret, $mp_appid, $mp_app_secret) {
        $this->mn_appid = $mn_appid;
        $this->mn_app_secret = $mn_app_secret;
        $this->mp_appid = $mp_appid;
        $this->mp_app_secret = $mp_app_secret;
    }

    ///微信小程序认证获取用户信息
    function getUserinfo($indata) {
        $data = array();
        $wx_result = array();
        $wxBizDataCrypt = new WXBizDataCrypt($this->mn_appid, $indata["session_key"]);
        $errCode = $wxBizDataCrypt->decryptData($indata['encryptedData'], $indata['iv'], $wx_result);

        if ($errCode == 0) {
            $userinfo = json_decode($wx_result, true);
            $icon = $userinfo["avatarUrl"];
            $data["head_img"] = str_replace("/0", "/132", $icon);
            $data["nickname"] = $userinfo["nickName"];
            $data["unionid"] = isset($userinfo["unionId"]) ? $userinfo["unionId"] : "";
            $data["openid"] = $userinfo["openId"];
            $data["sex"] = $userinfo["gender"];
            $data["country"] = $userinfo["country"];
            $data["province"] = $userinfo["province"];
            $data["city"] = $userinfo["city"];
        }
        return $data;
    }

    //微信小程序认证登录并取得session_key和openid
    function getSessionKey($js_code) {
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $this->mn_appid . '&secret=' . $this->mn_app_secret . '&js_code=' . $js_code . '&grant_type=authorization_code';
        $response_data_str = curl_request($url);
        $response_data = json_decode($response_data_str, true);
        return $response_data;
    }

    //获取小程序access_token
    function getAccessToken() {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->mn_appid&secret=$this->mn_app_secret";
        $response_data_str = curl_request($url);
        $response_data = json_decode($response_data_str, true);
        return $response_data["access_token"];
    }

    //获取小程序二维码
    function getWxQRCode($data) {
        $ACCESS_TOKEN = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $ACCESS_TOKEN;
        $pushdata = array(
            'scene' => $data["scene"], //直接传参数值
            'page' => $data["page"],
            'width' => $data["width"],
            'auto_color' => false
        );

        $jsonData = json_encode($pushdata);
        $result = curl_request($url, $jsonData);
        if (!$result) {
            return;
        }
        $fileName = "lefaqie_" . $data["name"];
        if ($fileName) {
            //判断file文件中是否存在数据库当中 
            file_put_contents("./uploads/qrcode/" . $fileName . ".png", $result);
            return "/uploads/qrcode/" . $fileName . ".png";
        }
    }

    //下发小程序和公众号统一的服务消息 
    //$weapp_template_msg = array("template_id" => "", "page" => "", "form_id" => "", "data" => "", "emphasis_keyword" => ""); //小程序消息模板
    //$mp_template_msg = array("appid" => "", "template_id" => "", "url" => "", "miniprogram" => "", "data" => ""); //公众号消息模板
    function sendUniformMessage($to_user_openid, $weapp_template_msg = "", $mp_template_msg = "") {
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token=' . $access_token;
        $data["touser"] = $to_user_openid;
        if ($weapp_template_msg) {//小程序消息
            $data["weapp_template_msg"] = $weapp_template_msg;
        }
        if ($mp_template_msg) {//公众号消息
            $mp_template_msg["appid"] = $this->mp_appid;
            $mp_template_msg["miniprogram"]["appid"] = $this->mn_appid;
            $data["mp_template_msg"] = $mp_template_msg;
        }
        $jsonData = json_encode($data);
        $response_data = curl_request($url, $jsonData);
        $result = json_decode($response_data, true);
        return $result;
    }

    //获取公众号access_token
    function getAccessTokens() {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->mp_appid&secret=$this->mp_app_secret";
        $response_data_str = curl_request($url);
        $response_data = json_decode($response_data_str, true);
        return $response_data["access_token"];
    }

    //发送公众号模板消息
    function sendMessage($mp_template_msg) {
        $mp_template_msg["appid"] = $this->mp_appid;
        $access_token = $this->getAccessTokens();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
        $data = json_encode($mp_template_msg);
        $response_data = curl_request($url, $data);
        $result = json_decode($response_data, true);
        return $result;
    }

    //解密数据
    function decryptData($indata) {
        $wx_result = array();
        $wxBizDataCrypt = new WXBizDataCrypt($this->mn_appid, $indata["session_key"]);
        $errCode = $wxBizDataCrypt->decryptData($indata["encryptedData"], $indata["iv"], $wx_result);
        if ($errCode == 0) {
            return json_decode($wx_result, true);
        } else {
            return;
        }
    }

}
