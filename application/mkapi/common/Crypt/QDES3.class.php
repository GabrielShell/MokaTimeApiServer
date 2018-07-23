<?php
/**
 * 用于：乾汇通3DES加密使用
 * author： Xiaoxiaowei
 * Date：  2017-04-20 19:53
 */
class QDES3{
    var $key = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnYHSR5ry4MtQeN8wbIGIH9TpYeHfdkZ/G2xOXLERzwzv+8HN7YK9RYlmHooL6Efr2BCUkymPPxq/68LwP2c5v9C0yUynA1SFmAR4bgjF2ZIxenEVO7FJr2YDSy43SzQ8nhtYlOpdc2ZMOrxVykzITAO72zmcVcjF9W4TUXJYmzSs5/KGBi3xdPQM/csuN/N5WpmxZn1t7PTKIxg7O+qnH97t9Ri7lF4ZktMSisG6FZwR786AEh5Go+CNUp1E/fbrA/sD2rDdrXhonai6vp63RFH4oUQfXq7ztHQTie3ao7nle4tdnVe0NL5uVSE9v8h1OARotfTL9sMBIP32MZO6UwIDAQAB";
    var $iv = "23456789";

    function encrypt($input){
        $size = mcrypt_get_block_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
        $input = $this->pkcs5_pad($input, $size);
        $key = str_pad($this->key,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        if( $this->iv == '' )
        {
            $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        else
        {
            $iv = $this->iv;
        }
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }
    function decrypt($encrypted){
        $encrypted = base64_decode($encrypted);
        $key = str_pad($this->key,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_CBC,'');
        if( $this->iv == '' )
        {
            $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        else
        {
            $iv = $this->iv;
        }
        $ks = mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $y=$this->pkcs5_unpad($decrypted);
        return $y;
    }
    function pkcs5_pad ($text, $blocksize) {
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
    function PaddingPKCS7($data) {
        $block_size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char),$padding_char);
        return $data;
    }
}