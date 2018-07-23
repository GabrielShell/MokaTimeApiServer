<?php
/**
 * 用于：
 * author： Xiaoxiaowei
 * Date：  2017-05-08 14:36
 */
class DesUtil{

    public function ecbEncrypt($key = "", $encrypt) {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $decrypted = mcrypt_encrypt(MCRYPT_DES, $key, $encrypt, MCRYPT_MODE_ECB, $iv);
        $encode = base64_encode($decrypted);
        return $encode;
    }

    public function ecbDecrypt($key = "", $decrypt) {
        $decoded = base64_decode($decrypt);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $decrypted = mcrypt_decrypt(MCRYPT_DES, $key, $decoded, MCRYPT_MODE_ECB, $iv);
        return self::trimEnd($decrypted);
    }

    /*
     * 去掉填充的字符
     */

    private function trimEnd($text) {
        $len = strlen($text);
        $c = $text[$len - 1];

        if (ord($c) == 0) {
            return rtrim($text, $c);
        }

        if (ord($c) < $len) {
            for ($i = $len - ord($c); $i < $len; $i++) {
                if ($text[$i] != $c) {
                    return $text;
                }
            }
            return substr($text, 0, $len - ord($c));
        }
        return $text;
    }
}