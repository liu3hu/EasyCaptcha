<?php
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Fearotor <769049825@qq.com>
// +----------------------------------------------------------------------
// | Github: https://github.com/fearotor-org/EasyCaptcha
// +----------------------------------------------------------------------

namespace EasyCaptcha;

class EasyCaptcha extends \EasyCaptcha\EasyCaptcha\Base
{

    public function __construct($lang = 'zh')
    {
        parent::__construct($lang);
    }

    /**
     * 获取邮箱验证码
     * @param int   $length 验证码长度 最大6个字符
     * @param array $type   验证码类型 eg: ['number', 'alpha', 'zh'] 返回数字 字母 汉字组成的字符串
     * @return string
     */
    public function getEmailCode($length, $type)
    {
        return $this->getUniqueCode('email', $length, $type);
    }

    /**
     * 发送邮件验证码
     * @param string $to      接收邮件者邮箱
     * @param string $name    接收邮件者名称
     * @param string $subject 邮件主题
     * @param string $body    邮件内容
     * @return bool
     */
    public function sendEmailCode($to, $name, $subject, $body)
    {
        //验证邮箱格式
        if(!\EasyCaptcha\EasyCaptcha\Util::emailCheck($to)){
            $this->errors[] = 'email_format_error';
            return false;
        }

        //验证邮箱配置
        foreach(['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'from_email'] as $v){
            if(empty($this->config['email'][$v])){
                $this->errors[] = 'email_config_error';
                return false;
            }
        }


        if(!$this->beforeSend('email', $to)){
            return false;
        }

        include_once __DIR__.'/PHPMailer/SendMail.php';
        $mail=new \SendMail($this->config['email']);
        try{
            $res = $mail->send_mail($to, $name, $subject, $body);
            if($res === true){
                return true;
            }
            $this->error_msg = $res;
            return false;
        }catch(\Exception $e){
            $this->error_msg = $e->getMessage();
            return false;
        }
    }

    /**
     * 校验邮箱验证码
     * @param string $email
     * @param string $code
     * @return bool
     */
    public function verifyEmailCode($email, $code)
    {
        //验证邮箱格式
        if(!\EasyCaptcha\EasyCaptcha\Util::emailCheck($email)){
            $this->errors[] = 'email_format_error';
            return false;
        }

        return $this->verifyCode('email', $email, $code);
    }

    /**
     * 获取短信验证码
     * @param int   $length 验证码长度 最大6个字符
     * @param array $type   验证码类型 eg: ['number', 'alpha', 'zh'] 返回数字 字母 汉字组成的字符串
     * @return string
     */
    public function getSmsCode($length, $type)
    {
        return $this->getUniqueCode('cellphone', $length, $type);
    }

    /**
     * 发送短信验证码
     * @param string $cellphone 手机号
     * @param array $template_vars 短信模板变量
     * @param string $template_id 短信模板代码
     * @param string $plateform 短信平台
     * @return bool
     */
    public function sendSmsCode($cellphone, $template_vars, $template_id = '', $plateform = '')
    {
        //验证手机号格式
        if(!\EasyCaptcha\EasyCaptcha\Util::cellphoneCheck($cellphone)){
            $this->errors[] = 'cellphone_format_error';
            return false;
        }

        if($plateform === ''){
            $plateform = $this->config['sms']['default_platform'];
        }
        if(!isset($this->config['sms']['platforms'][$plateform])){
            $this->errors[] = 'sms_platform_error';
            return false;
        }

        if($template_id === ''){
            $template_id = $this->config['sms']['platforms'][$plateform]['default_template_id'];
        }
        if(!is_string($template_id) || empty($template_id)){
            $this->errors[] = 'sms_template_id_error';
            return false;
        }

        if(!is_array($template_vars) || empty($template_vars)){
            $this->errors[] = 'sms_template_vars_error';
            return false;
        }

        if(!$this->beforeSend('cellphone', $cellphone)){
            return false;
        }

        $sms = new \EasyCaptcha\EasyCaptcha\Sms([
            'access' => $this->config['sms']['platforms'][$plateform]['access'],
            'cellphone' => $cellphone,
            'template_id' => $template_id,
            'template_vars' => $template_vars
        ]);
        $res = $sms->send($plateform);
        if($res === true){
            return true;
        }
        $this->error_msg = $res;
        return false;
    }

    /**
     * 校验短信验证码
     * @param string $cellphone
     * @param string $code
     * @return bool
     */
    public function verifySmsCode($cellphone, $code)
    {
        //验证手机号格式
        if(!\EasyCaptcha\EasyCaptcha\Util::cellphoneCheck($cellphone)){
            $this->errors[] = 'cellphone_format_error';
            return false;
        }

        return $this->verifyCode('cellphone', $cellphone, $code);
    }

    /*
     * 获取图形验证码
     * @param int   $length 验证码长度 最大6个字符
     * @param array $type   验证码类型 eg: ['number', 'alpha', 'zh'] 返回数字 字母 汉字组成的字符串
     * @return array
     */
    public function getImageCode($length, $type)
    {
        if(is_null($this->image_verify)){
            $this->image_verify = new \EasyCaptcha\Image\Verify();
        }

        if(!(is_int($length) && $length > 0 && $length <= 6)){
            throw new \Exception('the value of $length is error');
        }
        if(!empty(array_diff($type, ['number', 'alpha', 'zh']))){
            throw new \Exception('the value of $type is error');
        }

        $data = $this->image_verify->getCode($length, $type);

        $flag = $this->createImageCodeFlag($data['code']);

        return ['flag' => $flag, 'image_data_base64' => $data['image_data_base64']];
    }

    /**
     * 校验图形验证码
     * @param string $flag
     * @param string $code
     * @return bool
     */
    public function verifyImageCode($flag, $code)
    {
        //删除过期验证码
		$tmp_path = __DIR__.'/tmp';
        if(file_exists($tmp_path) && is_dir($tmp_path)){
            $files = scandir ($tmp_path);
			foreach($files as $file){
				if($file == '.' || $file == '..') {
					continue;
				}
				if($file < date('YmdH') && is_dir($tmp_path.'/'.$file)){
					\EasyCaptcha\EasyCaptcha\Util::deleteDir($tmp_path.'/'.$file);
				}
            }
        }

        //验证验证码格式
        if(!(is_string($code) && mb_strlen($code,'utf-8') <= 6)){
            $this->errors[] = 'code_format_error';
            return false;
        }

        if(!is_string($flag)){
            $this->errors[] = 'code_error';
            return false;
        }
        $flag_arr = explode('.', $flag);
        if(count($flag_arr) !== 6){
            $this->errors[] = 'code_error';
            return false;
        }

        $expire_h = $flag_arr[0];
        $expire_m = $flag_arr[1];
        $expire_s = $flag_arr[2];
        $create_time = $flag_arr[3];
        $rd_str = $flag_arr[4];
        $post_code = $flag_arr[5];
        $file = __DIR__.'/tmp/'.$expire_h.'/'.$expire_m.'/'.$expire_s.'.'.$create_time.'.'.$rd_str.'.'.$post_code;

        if($this->config['code_case_ignore']){
            $post_code = strtolower($post_code);
            $code = strtolower($code);
        }

        if(!file_exists($file) || $code !== $post_code || time() < $create_time){
            $this->errors[] = 'code_error';
            return false;
        }

        $expire_time = $expire_h.$expire_m.$expire_s;
        if($expire_time < date('YmdHis')){
            $this->errors[] = 'code_expire';
            return false;
        }

        unlink($file);

        return true;
    }
}