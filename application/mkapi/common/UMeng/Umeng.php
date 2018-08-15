<?php
namespace app\mkapi\common\UMeng;
use app\mkapi\common\UMeng\AndroidNotification;
use app\mkapi\common\UMeng\IOSNotification;
use app\mkapi\common\UMeng\UmengNotification;
use app\mkapi\common\UMeng\android\AndroidUnicast;
use app\mkapi\common\UMeng\ios\IOSUnicast;

class Umeng{
	protected $appkey           = "5b641ce9f43e480ec500005a"; 
	protected $appMasterSecret     = "t1fkd6deroob5ogp02br1ontn4gq7lv7";
	protected $timestamp        = NULL;
	protected $validation_token = NULL;

	function __construct(){
		$this->timestamp = strval(time());
	}

	public function sendAndroidUnicast($param){
		try {
			$unicast = new AndroidUnicast();
			$unicast->setAppMasterSecret($this->appMasterSecret);
			$unicast->setPredefinedKeyValue("appkey",           $this->appkey);
			$unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set your device tokens here
			foreach($param as $key => $value){
				$unicast->setPredefinedKeyValue($key,$value);
			}
			// $unicast->setPredefinedKeyValue("device_tokens",    "AtqxL9I_jgZJs5951idxtWKDhSTulJmFyrlvO8oAsLwE"); 
			// $unicast->setPredefinedKeyValue("ticker",           "Android unicast ticker");
			// $unicast->setPredefinedKeyValue("title",            "Android unicast title");
			// $unicast->setPredefinedKeyValue("text",             "Android unicast text");
			$unicast->setPredefinedKeyValue("after_open",       "go_app");
			// Set 'production_mode' to 'false' if it's a test device. 
			// For how to register a test device, please see the developer doc.
			$unicast->setPredefinedKeyValue("production_mode", "true");
			// Set extra fields
			$unicast->setExtraField("test", "helloworld");
			print("Sending unicast notification, please wait...\r\n");
			$unicast->send();
			print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}
}