<?php

/**
 * 拉卡拉AES/ECB/PKCS5Padding
 * Class AesCbc
 */
class AesCbc {
    private $_string;

    public function __construct($_string){
        $this->_key = substr($_string, 0, 16);
        $this->_iv = substr($_string, 16,32);
    }

    /**
     * AES/CBC/PKCS5Padding 加密
     * @param $input
     * @return string
     */
    function encryptString($input) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $input = $this->pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($td, $this->_key, $this->_iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    //解密
    function decryptString($sStr) {
        $decrypted= @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->_key, base64_decode($sStr), MCRYPT_MODE_CBC, $this->_iv);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    /**
     * MD5withRSA签名
     * @param string $data
     * @param string $filePath
     * @return bool|string
     * @date 2017年6月20日09:09:55
     * @author xxw
     */
    public function sign($data='', $filePath){
        if (empty($data)) {
            return False;
        }
        $privateContent = file_get_contents($filePath);
        if (empty($privateContent)) {
            echo "Private Key error!";
            return False;
        }
        $privateKey = openssl_get_privatekey($privateContent);
        if (empty($privateKey)) {
            echo "private key resource identifier False!";
            return False;
        }
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_MD5);
        openssl_free_key($privateKey);
        return base64_encode($signature);
    }

    /**
     * MD5withRSA验证签名
     * @param string $data
     * @param string $filePath
     * @param string $signature
     * @return bool|string
     * @date 2017年6月20日09:09:55
     * @author xxw
     */
    public function checkSign($data='', $signature, $filePath){
        if (empty($data)){
            return false;
        }
        $publicContent = file_get_contents($filePath);
        if (empty($publicContent)){
            echo "Public Key error!";
            return false;
        }
        $publicKey = openssl_get_publickey($publicContent);
        if (empty($publicContent)){
            echo "public key resource identifier False!";
            return false;
        }
        $sign = base64_decode($signature);
        $result = (bool)openssl_verify($data, $sign, $publicKey, OPENSSL_ALGO_MD5);
        openssl_free_key($publicKey);
        return $result;
    }
}