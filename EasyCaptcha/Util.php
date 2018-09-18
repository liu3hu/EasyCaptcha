<?php
namespace EasyCaptcha\EasyCaptcha;

class Util
{
    /**
     * 获取客户端IP地址
     * @param integer   $type   返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean   $adv    是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    public static function ip($type = 0, $adv = false)
    {
        $type      = $type ? 1 : 0;
        static $ip = null;
        if (null !== $ip) {
            return $ip[$type];
        }

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }

    /**
     * 验证手机号
     * @param $telephone 手机号
     * @return bool
     */
    public static function cellphoneCheck($cellphone)
    {
        $phone_prefix=array(
            'unicom' =>array('130','131','132','145','155','156','176','185','186'),
            'telecom'=>array('133','153','173','175','177','180','181','189'),
            'mobile' =>array('134','135','136','137','138','139','147','150','151','152','157','158','159','178','182','183','184','187','188'),
            'virtual'=>array('170','171'),
        );
        $all_phone_prefix_arr=array();
        foreach($phone_prefix as $arr){
            $all_phone_prefix_arr=array_merge($all_phone_prefix_arr,$arr);
        }
        $all_phone_prefix=implode('|',$all_phone_prefix_arr);

        if(preg_match("/^($all_phone_prefix)[0-9]{8}$/", $cellphone)){
            return true;
        }
        return false;
    }

    /**
     * 邮箱验证
     * @param $email
     * @return bool
     */
    public static function emailCheck($email)
    {
        if(!preg_match("/^[0-9a-zA-Z]+(?:[\_\.\-][a-z0-9\-]+)*@[a-zA-Z0-9]+(?:[-.][a-zA-Z0-9]+)*\.[a-zA-Z]+$/i", $email)){
            return false;
        }
        return true;
    }

    /**
     * 删除目录(包括子目录和文件)
     * @param  string $path 目录路径
     * @return boolean
     */
    public static function deleteDir($path)
    {
        if(!is_string($path)){
            return false;
        }
        $path = trim($path);
        $end_char = substr($path, -1);
        if($end_char == '/' || $end_char == '\\'){
            $path = substr($path, 0, -1);
        }
        if(!(file_exists($path) && is_dir($path))){
            return false;
        }

        $files = scandir ($path);
        foreach($files as $file){
            if($file == '.' || $file == '..') {
                continue;
            }
            $file = $path.'/'.$file;
            if(is_dir($file)) {
                if(false === self::deleteDir($file)){
                    return false;
                }
            } else {
                if(false === unlink($file)){
                    return false;
                }
            }
        }
        $res = rmdir($path);
        return !!$res;
    }
}