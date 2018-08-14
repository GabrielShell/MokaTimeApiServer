<?php
namespace app\mkapi\common\UMeng\android;
use app\mkapi\common\UMeng\AndroidNotification;
class AndroidUnicast extends AndroidNotification {
	function __construct() {
		parent::__construct();
		$this->data["type"] = "unicast";
		$this->data["device_tokens"] = NULL;
	}

}