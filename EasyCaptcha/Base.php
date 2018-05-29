<?php
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Fearotor <769049825@qq.com>
// +----------------------------------------------------------------------
// | Github: https://github.com/fearotor-org/EasyCaptcha
// +----------------------------------------------------------------------

namespace EasyCaptcha\EasyCaptcha;

class Base
{
    protected $config = [];

    protected $errors = [];

    protected $error_msg = '';

    protected $image_verify = null;

    private $_lang = 'zh';

    private $_db_instance = null;

    //邮箱验证码
    private $_email_code = '';

    //短信验证码
    private $_sms_code = '';

    //限制规则
    private $_limit_rule = [
        'ip' => [],
        'email' => [],
        'cellphone' => []
    ];

    public function __construct($lang)
    {
        $this->_lang = $lang;
        $this->config = include __DIR__ . '/config.php';
    }

    public function __get($name = null)
    {
        if($name == '_db'){
            if(is_null($this->_db_instance)){
                $this->_db_instance = new Db($this->config['database']);
            }

            return $this->_db_instance;
        }
        throw new \InvalidArgumentException('property not exists:' . static::class . '->' . $name);
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        if(!empty($this->error_msg)){
            return $this->error_msg;
        }

        $lang_config = include __DIR__ . '/Lang.php';
        if(!isset($lang_config[$this->_lang])){
            return 'unexpected lang';
        }

        $errors = [];
        foreach($this->errors as $error){
            if(isset($lang_config[$this->_lang][$error])){
                $errors[] = $lang_config[$this->_lang][$error];
            }
        }

        return implode('\r\n', $errors);
    }

    /**
     * 设置数据库配置
     * @param array $config
     * @return void
     */
    public function setDatabaseConfig($config)
    {
        if(is_array($config)){
            foreach($config as $k => $c){
                if(isset($this->config['database'][$k])){
                    $this->config['database'][$k] = $c;
                }
            }
        }
        $this->_db_instance = new Db($this->config['database']);
    }

    /**
     * 设置邮箱服务配置
     * @param array $config
     * @return void
     */
    public function setEmailConfig($config)
    {
        if(is_array($config)){
            foreach($config as $k => $c){
                if(isset($this->config['email'][$k])){
                    $this->config['email'][$k] = $c;
                }
            }
        }
    }

    /**
     * 设置图形验证码配置
     * @param array $config
     * @return void
     */
    public function setImageConfig($config)
    {
        $this->image_verify = new \EasyCaptcha\Image\Verify($config);
    }

    /**
     * 设置指定短信平台的授权信息
     * @param string $platform
     * @param array $access
     * @return void
     */
    public function setSmsAccess($platform, $access)
    {
        if(is_array($access) && !empty($access) && isset($this->config['sms']['plateforms'][$platform])){
            $this->config['sms']['plateforms'][$platform]['access'] = $access;
        }
    }

    /*
     * 设置验证码过期时间
     * @param int $seconds
     * @return void
     */
    public function setCodeExpireTime($seconds)
    {
        if(is_int($seconds) && $seconds > 0){
            $this->config['code_expire'] = $seconds;
        }else{
            throw new \Exception('the value of $seconds is error');
        }
    }

    /*
     * 设置验证码发送时间间隔
     * @param int $seconds
     * @return void
     */
    public function setSendInterval($seconds)
    {
        if(is_int($seconds) && $seconds > 0){
            $this->config['send_interval'] = $seconds;
        }else{
            throw new \Exception('the value of $seconds is error');
        }
    }

    /**
     * IP在单位时间的发送验证码次数
     * @param int $seconds 单位时间 秒
     * @param int $times   单位时间的最大发送次数
     * @param string $ip 指定ip 默认为空 不指定ip
     * @return void
     */
    public function setTimesLimitOfIp($seconds, $times, $ip = '')
    {
        $this->_setTimesLimit('ip', $seconds, $times, $ip);
    }

    /**
     * 邮箱账号在单位时间的发送验证码次数
     * @param int $seconds 单位时间 秒
     * @param int $times   单位时间的最大发送次数
     * @param string $email 指定email 默认为空 不指定email
     * @return void
     */
    public function setTimesLimitOfEmail($seconds, $times, $email = '')
    {
        $this->_setTimesLimit('email', $seconds, $times, $email);
    }

    /**
     * 手机账号在单位时间的发送验证码次数
     * @param int $seconds 单位时间 秒
     * @param int $times   单位时间的最大发送次数
     * @param string $cellphone 指定cellphone 默认为空 不指定$cellphone
     * @return void
     */
    public function setTimesLimitOfTelephone($seconds, $times, $cellphone = '')
    {
        $this->_setTimesLimit('cellphone', $seconds, $times, $cellphone);
    }

    //发送验证码前置操作
    protected function beforeSend($account_type, $account = '')
    {
        //验证发送限制
        if(!$this->_checkTimesLimit($account_type, $account)){
            return false;
        }

        //验证码写入记录
        if(!$this->_writeCode($account_type, $account)){
            return false;
        }

        $this->_db->deleteExpireCode();

        return true;
    }

    //校验验证码
    protected function verifyCode($account_type, $account, $code)
    {
        //验证验证码格式
        if(!(is_string($code) && mb_strlen($code,'utf-8') <= 6)){
            $this->errors[] = 'code_format_error';
            return false;
        }

        //校验验证码
        $ip = Util::ip(0,true);
        $info = $this->_db->getCodeInfo(['account_type' => $account_type, 'code' => $code]);
		if(empty($info)){
			$this->errors[] = 'code_error';
            return false;
        }
        $origin_code = $info['code'];
        if($this->config['code_case_ignore']){
            $code = strtolower($code);
            $origin_code = strtolower($origin_code);
        }

        if(!($info['account'] === strtolower($account) && $origin_code === $code && $info['ip'] == $ip)){
            $this->errors[] = 'code_error';
            return false;
        }

        if($info['expire_time'] < time()){
            $this->errors[] = 'code_expire';
            return false;
        }

        $this->_db->deleteCode($account_type, $code);

        return true;
    }

    //获取唯一验证码
    protected function getUniqueCode($account_type, $length, $type)
    {
        if(!(is_int($length) && $length > 0 && $length <= 6)){
            throw new \Exception('the value of $length is error');
        }

        if(!empty(array_diff($type, ['number', 'alpha', 'zh']))){
            throw new \Exception('the value of $type is error');
        }

        $code = $this->_getCode($length, $type);
        if(empty($code)){
            throw new \Exception('get empty code');
        }

        if(!empty($this->_db->getCodeInfo(['account_type' => $account_type, 'code' => $code]))){
            return $this->getUniqueCode($account_type, $length, $type);
        }

        if($account_type == 'email'){
            $this->_email_code = $code;
        }else{
            $this->_sms_code = $code;
        }

        return $code;
    }

    //创建图形验证码标识
    protected function createImageCodeFlag($code)
    {
        $time = time();
        $expire_time = $time + $this->config['code_expire'];

        $expire_h = date('YmdH', $expire_time);
        $expire_m = date('i', $expire_time);

        $expire_s = date('s', $expire_time);
        $create_time = $time;
        $rd_str = substr(str_shuffle('ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 16);

        $flag = implode('.', [$expire_h, $expire_m, $expire_s, $create_time, $rd_str, $code]);

        $dirname = dirname(__DIR__).'/tmp/'.$expire_h.'/'.$expire_m;
        $this->_createImageCodeFlagDir($dirname);
        $filename = $expire_s.'.'.$create_time.'.'.$rd_str.'.'.$code;
        $handle = fopen($dirname.'/'.$filename, "w");
        fclose($handle);

        return $flag;
    }

    //创建图形验证码标识目录
    private function _createImageCodeFlagDir($dir)
    {
        if(is_dir($dir)){
            return true;
        }
        if(!is_dir(dirname($dir))){
            $this->_createImageCodeFlagDir(dirname($dir));
        }

        mkdir($dir);

        return true;
    }

    //获取验证码
    private function _getCode($length, $type)
    {
        $use_zh = false;
        if(in_array('zh', $type)){
            $use_zh = true;
        }

        $code_set = $this->_getCodeSet($type);

        $codes = []; // 验证码
        if($use_zh){ // 中文验证码
            for ($i = 0; $i < $length; $i++) {
                $codes[$i] = iconv_substr($code_set,floor(mt_rand(0,mb_strlen($code_set,'utf-8')-1)),1,'utf-8');
            }
        }else{
            for ($i = 0; $i < $length; $i++) {
                $codes[$i] = $code_set[mt_rand(0, strlen($code_set)-1)];
            }
        }

        return implode('',$codes);
    }

    //获取验证码字符集
    private function _getCodeSet($type)
    {
        $code_set = '';
        if(in_array('number', $type) && in_array('alpha', $type)){
            $code_set = $this->config['code_set']['number_alpha'];
            unset($type['number'], $type['alpha']);
        }
        foreach($type as $t){
            $code_set = $code_set.$this->config['code_set'][$t];
        }
        return $code_set;
    }

    //设置验证码发送频率规则
    private function _setTimesLimit($type, $seconds, $times, $value)
    {
        //单位时间 $seconds 最多设置为一天
        if(
            is_int($seconds) &&
            $seconds > 0 &&
            is_int($times) &&
            $times > 0 &&
            is_string($value)
        ){
            if($seconds > 24*3600){
                throw new \Exception('max value of $seconds is 86400');
            }else{
                $this->_limit_rule[$type][] = [
                    'value' => $value,
                    'seconds' => $seconds,
                    'times' => $times
                ];
            }
        }else{
            throw new \Exception('params error');
        }
    }

    //校验验证码发送频率
    private function _checkTimesLimit($account_type, $account)
    {
        $map = [
            'account' => $account,
            'account_type' => $account_type,
            'ip' => Util::ip(0,true)
        ];
        $r = $this->_db->getCodeInfo($map);
        if(!empty($r) && (time() - $r['send_time']) < $this->config['send_interval'] ){
            $this->errors[] = 'send_too_fast';
            return false;
        }

        $ip = Util::ip(0,true);

        if(!empty($this->_limit_rule['ip'])){
            foreach($this->_limit_rule['ip'] as $rule){
                if($rule['value'] == 'all' || $rule['value'] == $ip){
                    $send_time = time() - $rule['seconds'];
                    $c = $this->_db->getCodeCount(['ip' => $ip, 'send_time' => ['>=', $send_time]]);
                    if($c >= $rule['times']){
                        $this->errors[] = 'send_too_many';
                        return false;
                    }
                }
            }
        }

        if(!empty($this->_limit_rule[$account_type])){
            foreach($this->_limit_rule[$account_type] as $rule){
                if($rule['value'] == 'all' || $rule['value'] == $account){
                    $send_time = time() - $rule['seconds'];
                    $c = $this->_db->getCodeCount(['account' => $account, 'account_type' =>$account_type, 'send_time' => ['>=', $send_time]]);
                    if($c >= $rule['times']){
                        $this->errors[] = 'send_too_many';
                        return false;
                    }
                }
            }
        }

        return true;
    }

    //验证码写入记录表
    private function _writeCode($account_type, $account)
    {
        $code = $account_type == 'email'?$this->_email_code:$this->_sms_code;
        if(empty($code)){
            return true;
        }
        $time = time();
        $data=[
            'account' => strtolower($account),
            'account_type' => $account_type,
            'code' => $code,
            'ip' => Util::ip(0,true),
            'send_time' => $time,
            'expire_time' => $time + $this->config['code_expire']
        ];

        $this->_db->insertCode($data);

        return true;
    }
}