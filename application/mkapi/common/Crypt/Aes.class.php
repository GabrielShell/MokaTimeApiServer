<?php
/**
 * AES加密算法，加密模式"AES/ECB/PKCS5Padding"
 * @date: 2017年2月9日 上午10:20:27
 * @author: ZhaoYang
 */
class AES{
    private $key;
    
    public function __construct($key){
        $this->key = $key;
    }

    public function encrypt($encrypt) {
    	$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    	$encrypt = AES::pkcs5_pad($encrypt, $size);
    	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
    	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    	mcrypt_generic_init($td, $this->key, $iv);
    	$data = mcrypt_generic($td, $encrypt);
    	mcrypt_generic_deinit($td);
    	mcrypt_module_close($td);
    	$data = base64_encode($data);
    	return $data;
	}
 
	private static function pkcs5_pad ($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}
 
	public function decrypt($decrypt) {
		$decrypted= mcrypt_decrypt(
    		MCRYPT_RIJNDAEL_128,
    		$this->key,
    		base64_decode($decrypt),
    		MCRYPT_MODE_ECB
        );
 
		$dec_s = strlen($decrypted);
		$padding = ord($decrypted[$dec_s-1]);
		$decrypted = substr($decrypted, 0, -$padding);
		return $decrypted;
	}

} 