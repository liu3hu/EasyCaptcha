## 描述

EasyCaptcha是一个处理验证码的PHP库，可便捷处理短信验证码的发送和校验、邮箱验证码的发送和校验、图形验证码的生成和校验。

## 快速开始

```php
<?php
    include 'EasyCaptcha/autoload.php';
    $easy_captcha = new \EasyCaptcha\EasyCaptcha();

    //发送短信验证码
    $code = $easy_captcha->getSmsCode(4, ['number']);
    if(!empty($code)){
        //使用默认短信平台和模板ID
        $easy_captcha->sendSmsCode('13011111111', ['code'=>$code]);
        
        //指定短信平台和模板ID
        $easy_captcha->sendSmsCode('13011111112', ['code'=>$code], '1111', 'alidy');
        
        //设置授权信息
        $alidy_access = [
            'accessKeyId' => 'xxx',
            'accessKeySecret' => 'yyy',
            'signature'=>'aaa'
        ];
        $easy_captcha->setSmsAccess('alidy',$alidy_access);
        $easy_captcha->sendSmsCode('13011111113', ['code'=>$code], '1111', 'alidy');

        $juhe_access = [
            'key' => 'xxx',
        ];
        $easy_captcha->setSmsAccess('juhe',$juhe_access);
        $easy_captcha->sendSmsCode('13011111114', ['code'=>$code], '1111', 'juhe');
    }

    //校验短信验证码
    $verify_res = $easy_captcha->verifySmsCode('13011111111', 'xxxx');
    if($verify_res){
        //your logic
    }else{
        echo $easy_captcha->getError();
        exit;
    }
```

## 配置文件

配置文件位于 EasyCaptcha/EasyCaptcha/config.php

- database 数据库配置 当前仅支持mysql
- email  邮箱服务配置
- sms  短信平台配置 当前仅支持聚合和阿里大鱼的验证码发送，更多平台后续会陆续支持
- code_expire 验证码过期时间
- send_interval 验证发送时间间隔

以上配置也可根据库提供的方法在运行时设置

## 全部可用方法

#### getError 
捕获错误信息

#### setDatabaseConfig(\$config) 
设置数据库配置
```php
$config = [
    'hostname' => '127.0.0.1',
    'hostport' => '3306',
    'username' => 'root',
    'password' => '123456',
    'database' => 'demo',
    'table'    => 'easy_captcha'
];
$easy_captcha->setDatabaseConfig($config);
```

#### setEmailConfig(\$config) 
设置邮箱服务配置
```php
$config = [
    'smtp_host'   => 'smtp.ym.163.com', //邮箱服务器地址
    'smtp_port'   => '465', //邮箱服务器端口
    'smtp_user'   => 'service@xx.com', //邮箱服务器用户名
    'smtp_pass'   => 'xxxxxx', //邮箱服务器密码
    'from_email'  => 'service@xx.com', //邮件发送方地址
    'from_name'   => 'xx服务账号', //邮件发送方名称
];
$easy_captcha->setEmailConfig($config);
```

#### setImageConfig(\$config) 
设置图形验证码配置
```php
$config = [
    'useImgBg'	=> false, // 使用背景图片
    'fontSize'  => 25, // 验证码字体大小(px)
    'useCurve'  => true, // 是否画混淆曲线
    'useNoise'  => true, // 是否添加杂点	
    'imageH' 	=> 30, // 验证码图片高度
    'imageW'   	=> 120, // 验证码图片宽度
    'fontttf' 	=> '', // 验证码字体，不设置随机获取
    'bg'  		=> array(243, 251, 254) // 背景颜色
];
$easy_captcha->setImageConfig($config);
```

#### setSmsAccess(\$access) 
设置指定短信平台的授权信息
```php
$alidy_access = [
    'accessKeyId' => 'xxx',
    'accessKeySecret' => 'yyy',
    'signature'=>'aaa'
];
$easy_captcha->setSmsAccess('alidy',$alidy_access);

$juhe_access = ['key' => 'xxx'];
$easy_captcha->setSmsAccess('juhe',$juhe_access);
```

#### setCodeExpireTime(\$seconds) 
设置验证码过期时间

#### setSendInterval(\$seconds)  
设置验证码发送时间间隔

#### setTimesLimitOfIp(\$seconds, \$times, \$ip) 
IP在单位时间的获取验证码次数
```php
//同一个IP在60秒内最多可获取1次验证码
$easy_captcha->setTimesLimitOfIp(60, 1);

//IP 180.173.199.221 在3600秒内最多可获取5次验证码
$easy_captcha->setTimesLimitOfIp(3600, 5, '180.173.199.221');
```

#### setTimesLimitOfEmail(\$seconds, \$times, \$email) 
邮箱账号在单位时间的获取验证码次数
```php
//同一个邮箱地址在60秒内最多可获取1次验证码
$easy_captcha->setTimesLimitOfEmail(60, 1);

//邮箱123@demo.com 在3600秒内最多可获取5次验证码
$easy_captcha->setTimesLimitOfEmail(3600, 5, '123@demo.com');
```

#### setTimesLimitOfTelephone(\$seconds, \$times, \$telephone) 
手机号在单位时间的获取验证码次数
```php
//同一个手机号在60秒内最多可获取1次验证码
$easy_captcha->setTimesLimitOfTelephone(60, 1);

//手机号13011111111 在3600秒内最多可获取5次验证码
$easy_captcha->setTimesLimitOfTelephone(3600, 5, '13011111111');
```

#### getEmailCode (\$length, \$type)
获取邮箱验证码
\$type 是验证码类型数组 数组元素的可选值为 number(数字)、alpha(字母)、zh(汉字)
```php
//获取数字字母组合的验证码
$code = $easy_captcha->getEmailCode(6, ['number', 'alpha']);

//获取汉字字母组合的验证码
$code = $easy_captcha->getEmailCode(6, ['zh', 'alpha']);
```

#### sendEmailCode (\$to, \$name, \$subject, \$body)
发送邮件验证码
```php
//同一个邮箱一天最多获取5次验证码
$easy_captcha->setTimesLimitOfEmail(3600*24, 5);
$easy_captcha->setCodeExpireTime(600);
$code = $easy_captcha->getEmailCode(6, ['number']);
$to = '123@demo.com';
$name = 'EasyCaptcha用户';
$subject = '邮箱验证码';
$body = "您的邮箱验证码为：{$code}，有效期为10分钟";
$easy_captcha->sendEmailCode($to, $name, $subject, $body);
if(!$res){
    echo $easy_captcha->getError();
    exit;
}
//your logic
```

#### verifyEmailCode (\$email, \$code)
校验邮箱验证码
```php
$email = $_POST['email'];
$code = $_POST['code'];
$res = $easy_captcha->verifyEmailCode($email, $code);
if(!$res){
    echo '验证码错误';
    exit;
}
//your logic
```

#### getSmsCode (\$length, \$type)
获取短信验证码
用法参见 getEmailCode

#### sendSmsCode (\$telephone, \$template_vars, \$template_id, \$plateform)
发送短信验证码
```php
//设置验证码10分钟后过期
$easy_captcha->setCodeExpireTime(600);
//设置发送时间最小间隔60秒
$easy_captcha->setSendInterval(60);
//同一个IP一天最多获取10次验证码
$easy_captcha->setTimesLimitOfIp(3600*24, 10);
//同一个手机号一天最多获取5次验证码
$easy_captcha->setTimesLimitOfTelephone(3600*24, 5);
$code = $easy_captcha->getSmsCode(6, ['number']);
$template_vars = [
    'code' => $code,
    'expire_in' => '10分钟'
];
$res = $easy_captcha->sendSmsCode('13011111111', $template_vars, '1111', 'juhe');
if(!$res){
    echo $easy_captcha->getError();
    exit;
}
//your logic
```

#### verifySmsCode (\$telephone, \$code)
校验短信验证码
用法参见 verifyEmailCode

#### getImageCode (\$length, \$type)
获取图形验证码
\$type 是验证码类型数组 数组元素的可选值为 number(数字)、alpha(字母)、zh(汉字)
```php
$config = [
    'imageH' 	=> 30, // 验证码图片高度
    'imageW'   	=> 120, // 验证码图片宽度
];
$easy_captcha->setImageConfig($config);

//获取数字字母组合的验证码
$data = $easy_captcha->getImageCode(6, ['number', 'alpha']);

//$data['flag'] 此值需同用户输入的验证码一同提交到后端验证
//$data['image_data_base64'] 此值是验证码图片的base64编码 可直接传入img标签的src属性
```

#### verifyImageCode (\$flag, \$code)
校验图形验证码
```php
$flag = $_POST['flag'];
$code = $_POST['code'];
$res = $easy_captcha->verifyImageCode($flag, $code);
if(!$res){
    echo '验证码错误';
    exit;
}
//your logic
```

## 错误信息多语言
EasyCaptcha/EasyCaptcha/Lang.php 文件中可进行错误信息的多语言配置
创建对象时传入语言项即可 默认为 zh 中文
```php
$easy_captcha = new \EasyCaptcha\EasyCaptcha('en');
```



## 注意
- EasyCaptcha/tmp 目录需设置为可写权限

## 特别感谢
<a target="_blank" href="https://github.com/fearotor/PHPMailer">PHPMailer</a>

邮箱验证码的发送是基于此库的支持

<a target="_blank" href="https://github.com/top-think/thinkphp-extend/blob/master/extend/org/Verify.php">ThinkPHP图形验证码扩展库 </a>

图形验证码的生成基于此类的支持