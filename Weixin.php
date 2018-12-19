<?php

require_once "src/wxBizDataCrypt.php";

class Weixin {

    private $mn_appid;
    private $mn_app_secret;
    private $mp_appid;
    private $mp_app_secret;

    /*     * 初始化传参
     * @param $mn_appid 小程序appid
     * @param $mn_app_secret 小程序app_secret
     * @param $mp_appid 公众号appid
     * @param $mp_app_secret 公众号app_secret
     */
    public function __construct($mn_appid, $mn_app_secret, $mp_appid, $mp_app_secret) {
        $this->mn_appid = $mn_appid;
        $this->mn_app_secret = $mn_app_secret;
        $this->mp_appid = $mp_appid;
        $this->mp_app_secret = $mp_app_secret;
    }

    /*     * 微信小程序解密数据
     * @param $session_key 小程序session_key密钥
     * @param $encryptedData 小程序encryptedData数据
     * @param $iv 小程序iv信息
     */
    function decryptData($session_key, $encryptedData, $iv) {
        $data = array();
        $wx_result = array();
        $wxBizDataCrypt = new WXBizDataCrypt($this->mn_appid, $session_key);
        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $wx_result);
        if ($errCode == 0) {
            $data = json_decode($wx_result, true);
        }
        return $data;
    }

    /*     * 微信小程序认证登录并取得session_key和openid
     * @param $js_code 小程序获取的js_code 
     */
    function getSessionKey($js_code) {
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $this->mn_appid . '&secret=' . $this->mn_app_secret . '&js_code=' . $js_code . '&grant_type=authorization_code';
        $response_data_str = curl_request($url);
        return json_decode($response_data_str, true);
    }

    /*     * 获取微信小程序access_token
     * 
     */
    function getAccessToken() {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->mn_appid&secret=$this->mn_app_secret";
        $response_data_str = curl_request($url);
        $response_data = json_decode($response_data_str, true);
        return $response_data["access_token"];
    }

    /*     * 微信小程序接口生成的二维码二进制流
     * @param $scene 小程序需要跳转的页面所传参数值，无须传参数名称.
     * @param $page 小程序需要跳转的页面 
     * @param $width 需要生成的大小
     */
    function getWxQRCode($scene, $page, $width, $name = "") {
        $ACCESS_TOKEN = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $ACCESS_TOKEN;
        $pushdata = array(
            'scene' => $scene, //直接传参数值
            'page' => $page,
            'width' => $width,
            'auto_color' => false
        );

        $jsonData = json_encode($pushdata);
        $result = curl_request($url, $jsonData);
        if (!$result) {
            return;
        }

        $fileName = "qrcode_" . $scene;
        if (!$name) {
            $fileName = "qrcode_" . time();
        }
        if ($fileName) {
            //判断file文件中是否存在数据库当中 
            file_put_contents("./qrcode/" . $fileName . ".png", $result);
            return "/qrcode/" . $fileName . ".png";
        }
    }

     /*     * 发送微信小程序和公众号统一的服务消息
     * @param $to_user_openid 接收用户openid.
     * @param $weapp_template_msg 小程序模版内容,示例：array("template_id" => "", "page" => "", "form_id" => "", "data" => "", "emphasis_keyword" => "");
     * @param $mp_template_msg 公众号模版内容，示例：array("appid" => "", "template_id" => "", "url" => "", "miniprogram" => "", "data" => ""); 
     */  
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

     /*     * 获取公众号access_token 
      * 
     */
    function getMpAccessTokens() {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->mp_appid&secret=$this->mp_app_secret";
        $response_data_str = curl_request($url);
        $response_data = json_decode($response_data_str, true);
        return $response_data["access_token"];
    }

     /*     * 发送公众号服务消息 
     * @param $mp_template_msg 公众号模版内容，示例：array("appid" => "", "template_id" => "", "url" => "", "miniprogram" => "", "data" => ""); 
     */ 
    function sendMessage($mp_template_msg) {
        $mp_template_msg["appid"] = $this->mp_appid;
        $access_token = $this->getMpAccessTokens();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
        $data = json_encode($mp_template_msg);
        $response_data = curl_request($url, $data);
        $result = json_decode($response_data, true);
        return $result;
    }

}
