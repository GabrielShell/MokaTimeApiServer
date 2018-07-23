<?php
/**
 * php实现AES/CBC/PKCS5Padding加密解密（又叫：对称加密）
 * @date: 2016年12月26日 上午10:24:16
 * @author: ZhaoYang
 */
class MagicCrypt {
    private $iv;//密钥偏移量IV，可自定义

    private $encryptKey;//AESkey，可自定义
    
    public function __construct($iv, $encryptKey){
        $this->iv = $iv;
        $this->encryptKey = $encryptKey;
    }
    //加密
    public function encrypt($encryptStr) {
        $localIV = $this->iv;
        $encryptKey = $this->encryptKey;

        //Open module
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);

        mcrypt_generic_init($module, $encryptKey, $localIV);

        //Padding
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = $block - (strlen($encryptStr) % $block); //Compute how many characters need to pad
        $encryptStr .= str_repeat(chr($pad), $pad); // After pad, the str length must be equal to block or its integer multiples

        //encrypt
        $encrypted = mcrypt_generic($module, $encryptStr);

        //Close
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);

        return base64_encode($encrypted);
    }

    //解密
    public function decrypt($encryptStr) {
        $localIV = $this->iv;
        $encryptKey = $this->encryptKey;

        //Open module
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);

        mcrypt_generic_init($module, $encryptKey, $localIV);

        $encryptedData = base64_decode($encryptStr);
        $encryptedData = mdecrypt_generic($module, $encryptedData);
        $encryptedData = mb_substr($encryptedData, 0, strripos($encryptedData, '}') + 1);
        return $encryptedData;
    }
}