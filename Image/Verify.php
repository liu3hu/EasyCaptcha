<?php
namespace EasyCaptcha\Image;

class Verify {
    protected $config =	[
        'useImgBg'  =>  false,           // 使用背景图片 
        'fontSize'  =>  25,              // 验证码字体大小(px)
        'useCurve'  =>  true,            // 是否画混淆曲线
        'useNoise'  =>  true,            // 是否添加杂点	
        'imageH'    =>  0,               // 验证码图片高度
        'imageW'    =>  0,               // 验证码图片宽度
        'fontttf'   =>  '',              // 验证码字体，不设置随机获取
        'bg'        =>  [243, 251, 254],  // 背景颜色
    ];

    private $_image   = NULL;     // 验证码图片实例
    private $_color   = NULL;     // 验证码字体颜色

    /**
     * 架构方法 设置参数
     * @access public     
     * @param  array $config 配置参数
     */    
    public function __construct($config=array()){
        $this->config   =   array_merge($this->config, $config);
    }

    public function getCode($length, $type) {
        $imageH = $this->config['imageH'];
        $imageW = $this->config['imageW'];
        $fontSize = $this->config['fontSize'];
        $bg = $this->config['bg'];
        $fontttf = $this->config['fontttf'];

        // 图片宽(px)
        $imageW || $imageW = $length*$fontSize*1.5 + $length*$fontSize/2;
        // 图片高(px)
        $imageH || $imageH = $fontSize * 2.5;
        // 建立一幅 $this->imageW x $this->imageH 的图像
        $this->_image = imagecreate($imageW, $imageH);
        // 设置背景      
        imagecolorallocate($this->_image, $bg[0], $bg[1], $bg[2]);

        // 验证码字体随机颜色
        $this->_color = imagecolorallocate($this->_image, mt_rand(1,150), mt_rand(1,150), mt_rand(1,150));

        $use_zh = false;
        if(in_array('zh', $type)){
            $use_zh = true;
        }
        $code_set = $this->_getCodeSet($type);

        // 验证码使用随机字体
        $ttfPath = dirname(__FILE__) . '/Verify/' . ($use_zh ? 'zhttfs' : 'ttfs') . '/';

        if(empty($fontttf)){
            $dir = dir($ttfPath);
            $ttfs = [];
            while (false !== ($file = $dir->read())) {
                if($file[0] != '.' && substr($file, -4) == '.ttf') {
                    $ttfs[] = $file;
                }
            }
            $dir->close();
            $fontttf = $ttfs[array_rand($ttfs)];
        } 
        $fontttf = $ttfPath . $fontttf;
        
        if($this->config['useImgBg']) {
            $this->_background();
        }
        
        if ($this->config['useNoise']) {
            // 绘杂点
            $this->_writeNoise();
        } 
        if ($this->config['useCurve']) {
            // 绘干扰线
            $this->_writeCurve();
        }
        
        // 绘验证码
        $code = []; // 验证码
        $codeNX = 0; // 验证码第N个字符的左边距
        if($use_zh){ // 中文验证码
            for ($i = 0; $i<$length; $i++) {
                $code[$i] = iconv_substr($code_set,floor(mt_rand(0,mb_strlen($code_set,'utf-8')-1)),1,'utf-8');
                imagettftext($this->_image, $fontSize, mt_rand(-40, 40), $fontSize*($i+1)*1.5, $fontSize + mt_rand(10, 20), $this->_color, $fontttf, $code[$i]);
            }
        }else{
            for ($i = 0; $i<$length; $i++) {
                $code[$i] = $code_set[mt_rand(0, strlen($code_set)-1)];
                $codeNX  += mt_rand($fontSize*1.2, $fontSize*1.6);
                imagettftext($this->_image, $fontSize, mt_rand(-40, 40), $codeNX, $fontSize*1.6, $this->_color, $fontttf, $code[$i]);
            }
        }

        ob_start ();
        imagepng($this->_image);
        $image_data = ob_get_contents ();
        ob_end_clean ();
        $image_data_base64 = "data:image/png;base64,". base64_encode ($image_data);
        imagedestroy($this->_image);

        return ['code' => implode('',$code) , 'image_data_base64' => $image_data_base64];
    }

    //获取验证码字符集
    private function _getCodeSet($type)
    {
        $config = include dirname(__DIR__).'/EasyCaptcha/config.php';

        $code_set = '';
        if(in_array('number', $type) && in_array('alpha', $type)){
            $code_set = $config['code_set']['number_alpha'];
            unset($type['number'], $type['alpha']);
        }
        foreach($type as $t){
            $code_set = $code_set.$config['code_set'][$t];
        }
        return $code_set;
    }

    /** 
     * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数) 
     *      
     *      高中的数学公式咋都忘了涅，写出来
     *		正弦型函数解析式：y=Asin(ωx+φ)+b
     *      各常数值对函数图像的影响：
     *        A：决定峰值（即纵向拉伸压缩的倍数）
     *        b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
     *        φ：决定波形与X轴位置关系或横向移动距离（左加右减）
     *        ω：决定周期（最小正周期T=2π/∣ω∣）
     *
     */
    private function _writeCurve() {
        $px = $py = 0;

        $imageH = $this->config['imageH'];
        $imageW = $this->config['imageW'];
        $fontSize = $this->config['fontSize'];

        // 曲线前部分
        $A = mt_rand(1, $imageH/2);                  // 振幅
        $b = mt_rand(-$imageH/4, $imageH/4);   // Y轴方向偏移量
        $f = mt_rand(-$imageH/4, $imageH/4);   // X轴方向偏移量
        $T = mt_rand($imageH, $imageW*2);  // 周期
        $w = (2* M_PI)/$T;
                        
        $px1 = 0;  // 曲线横坐标起始位置
        $px2 = mt_rand($imageW/2, $imageW * 0.8);  // 曲线横坐标结束位置

        for ($px=$px1; $px<=$px2; $px = $px + 1) {
            if ($w!=0) {
                $py = $A * sin($w*$px + $f)+ $b + $imageH/2;  // y = Asin(ωx+φ) + b
                $i = (int) ($fontSize/5);
                while ($i > 0) {	
                    imagesetpixel($this->_image, $px + $i , $py + $i, $this->_color);  // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多				
                    $i--;
                }
            }
        }
        
        // 曲线后部分
        $A = mt_rand(1, $imageH/2);                  // 振幅
        $f = mt_rand(-$imageH/4, $imageH/4);   // X轴方向偏移量
        $T = mt_rand($imageH, $imageW*2);  // 周期
        $w = (2* M_PI)/$T;		
        $b = $py - $A * sin($w*$px + $f) - $imageH/2;
        $px1 = $px2;
        $px2 = $imageW;

        for ($px=$px1; $px<=$px2; $px=$px+ 1) {
            if ($w!=0) {
                $py = $A * sin($w*$px + $f)+ $b + $imageH/2;  // y = Asin(ωx+φ) + b
                $i = (int) ($fontSize/5);
                while ($i > 0) {			
                    imagesetpixel($this->_image, $px + $i, $py + $i, $this->_color);	
                    $i--;
                }
            }
        }
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    private function _writeNoise() {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for($i = 0; $i < 10; $i++){
            //杂点颜色
            $noiseColor = imagecolorallocate($this->_image, mt_rand(150,225), mt_rand(150,225), mt_rand(150,225));
            for($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($this->_image, 5, mt_rand(-10, $this->config['imageW']),  mt_rand(-10, $this->config['imageH']), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }

    /**
     * 绘制背景图片
     * 注：如果验证码输出图片比较大，将占用比较多的系统资源
     */
    private function _background() {
        $path = dirname(__FILE__).'/Verify/bgs/';
        $dir = dir($path);

        $bgs = array();		
        while (false !== ($file = $dir->read())) {
            if($file[0] != '.' && substr($file, -4) == '.jpg') {
                $bgs[] = $path . $file;
            }
        }
        $dir->close();

        $gb = $bgs[array_rand($bgs)];

        list($width, $height) = @getimagesize($gb);
        // Resample
        $bgImage = @imagecreatefromjpeg($gb);
        @imagecopyresampled($this->_image, $bgImage, 0, 0, 0, 0, $this->config['imageW'], $this->config['imageH'], $width, $height);
        @imagedestroy($bgImage);
    }
}
