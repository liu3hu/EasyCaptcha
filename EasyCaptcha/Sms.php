<?php
namespace EasyCaptcha\EasyCaptcha;

class Sms
{
    private $_config = [
        'access' => [],
        'cellphone' => '',
        'template_id' => '',
        'template_vars' => [],
    ];

    public function __construct($config)
    {
        if(is_array($config)){
            foreach($config as $k => $c){
                if(isset($this->_config[$k])){
                    $this->_config[$k] = $c;
                }
            }
        }
    }

    public function send($platform)
    {
        return $this->$platform();
    }

    /**
     * 聚合短信发送接口
     * @return true|string
     */
    private function juhe()
    {
        header('content-type:text/html;charset=utf-8');

        $content_arr = [];
        foreach($this->_config['template_vars'] as $param => $value){
            $content_arr[] = '#'.$param.'#='.$value;
        }

        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $smsConf = array(
            'key'       => $this->_config['access']['key'], //您申请的APPKEY
            'mobile'    => $this->_config['cellphone'], //接受短信的用户手机号码
            'tpl_id'    => $this->_config['template_id'], //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => implode('&', $content_arr) //您设置的模板变量，根据实际情况修改
        );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $ch , CURLOPT_POST , true );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , $smsConf );
        curl_setopt( $ch , CURLOPT_URL , $sendUrl );
        $response = curl_exec( $ch );
        $error = '';
        if ($response === false) {
            $error = curl_error();
        }
        curl_close( $ch );
        if($response){
            $result = json_decode($response,true);
            $error_code = $result['error_code'];
            if($error_code == 0){
                return true;
            }else{
                //状态非0，说明失败
                //$msg = $result['reason'];
                return $result['reason'];
            }
        }else{
            return $error;
        }
    }

    /**
     * 阿里大鱼短信发送接口
     * @return true|string
     */
    private function alidy() {

        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->_config['access']['accessKeyId'];
        $accessKeySecret = $this->_config['access']['accessKeySecret'];

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $this->_config['cellphone'];

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $this->_config['access']['signature'];

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $this->_config['template_id'];

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = $this->_config['template_vars'];

        /*// fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";*/


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        include_once dirname(__DIR__).'/DySDKLite/SignatureHelper.php';
        $helper = new \SignatureHelper();

        // 此处可能会抛出异常，注意catch

        try {
            $response = $helper->request(
                $accessKeyId,
                $accessKeySecret,
                "dysmsapi.aliyuncs.com",
                array_merge($params, array(
                    "RegionId" => "cn-hangzhou",
                    "Action" => "SendSms",
                    "Version" => "2017-05-25",
                ))
            );
        } catch ( \Exception $e ) {
            return $e->getMessage();
        }

        if($response === false){
            return '发送验证码出错';
        }

        if($response['Code'] == 'OK'){
            return true;
        }

        return $response['Message'];
    }
}