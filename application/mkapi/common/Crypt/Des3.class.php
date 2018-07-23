<?php
/**
 * 3DES加解密类
 * @Author: Xiaoxiaowei
 * @date 2017年2月23日16:14:55
 */
class DES3
{
    //加密秘钥，
    private $key;

    public function __construct($key){
        $this->key = $key;
    }

    /**
     * 对字符串进行3DES加密
     * @param string $strinfo 要加密的字符串
     * @return mixed 加密成功返回加密后的字符串，否则返回false
     */
    public function encrypt3DES($strinfo)
    {
        $size = mcrypt_get_block_size(MCRYPT_3DES,'ecb');
        $strinfo = $this->pkcs5_pad($strinfo, $size);
        $key = str_pad($this->key, 24, '0');
        $td = mcrypt_module_open(MCRYPT_3DES, '', 'ecb', '');
        $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $strinfo);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 对加密的字符串进行3DES解密
     * @param string $encrypted 要解密的字符串
     * @return mixed 加密成功返回加密后的字符串，否则返回false
     */
    function decrypt3DES($encrypted){
        $encrypted = base64_decode($encrypted);
        $key = str_pad($this->key,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES,'','ecb','');
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
        $ks = mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $y=$this->pkcs5_unpad($decrypted);
        return $y;
    }

    public function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    function pkcs5_unpad($text){
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad){
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }
}