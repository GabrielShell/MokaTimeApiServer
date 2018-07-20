<?php
/**
*文件上传工具
*/

namespace app\mkapi\controller;
use think\Controller;
use think\Request;
class Upload extends Controller{
	private $error_msg = 'success';   //错误信息
	private $prefix;     //文件名前缀
	private $upload_path;
	
	private $type;
	private $MIME;
	private $fileExt;
	private $fileSize;
	
	public function __construct($type = null,$MIME = null,$fileSize = null,$fileExt = null){

		
		$this->upload_path = APP_PATH.'mkapi/public/upload/';
	

		if($type == null){
			$this->type = 'image';
		}else{
			$this->type = $type;
		}

		if($MIME == null){
			$this->MIME = 'image/png,image/x-png,image/jpeg,image/pjpeg,image/gif';
		}else{
			$this->MIME = $MIME;
		}

		if($fileExt == null){
			$this->fileExt = 'png,jpg,jpeg,gif'; 
		}else{
			$this->fileExt = $fileExt;
		}

		if($fileSize == null){
			$this->fileSize = '1024'; 
		}else{
			$this->fileSize = $fileSize;
		}

		$this->prefix = 'ca_';
	}

	/**
	 *检查文件类型是否合法
	 *@param array $file 表单上传的文件信息
	 *@param string $type 文件类型('image')
	 *@param string $fileExt 支持的文件后缀，用逗号隔开('png,jpg,jpeg')
	 *@param string $MIME 文件MIME()
	 *@param string $fileSize 文件大小
	*/
	public function check($file){
		
        if (empty($file)) {
 
            return '请上传文件';
        }

        //判断文件类型是否合法
        $infoType = $this->validate(['file' => $file],['file' => 'require|'.$this->type]);
        if(!$infoType){
        	
        	return '文件类型不合法';
        }

        //判断文件后缀是否支持
        $infoFileExt = $this->validate(['file' => $file],['file' => 'require|fileExt:'.$this->fileExt]);
        if(!$infoFileExt){
        	
        	return '文件后缀名不支持';
        }

        //判断MIME是否支持
        $infoMIME = $this->validate(['file' => $file],['file' => 'require|fileMime:'.$this->MIME]);
        if(!$infoMIME){
        	
        	return 'MIME不支持';
        }

        //判断文件是否超过最大限制
        $infoFileSize = $this->validate(['file' => $file],['file' => 'require|fileSize:'.$this->fileSize]);
        if(!$infoFileSize){
        	
        	return '文件过大';
        }

        return 'success';
        
	}

	/**
	 *上传单个文件
	 *@param array $file 表单上传的文件信息
	 *@param string $path 文件保存路径
	 *@param string $prefix 文件前缀
	*/
	public function uploadOne($file,$path = null,$dirName = null,$prefix = null){
		//文件保存目录
		is_null($dirName) ? $date = date('y-m-d') : $date = $dirName;
		if($path == null){
			$realPath = $this->upload_path.$date;
		}else{
			$realPath = $path.$date;
		}
		//判断目录是否创建
		if(!is_dir($realPath)){
			mkdir($realPath);
		}

		//
		$fileName = UniqID($prefix,true);
		$info = $file->rule('uniqid')->move($realPath);
		if(!$info){
			$result = array('msg'=>'error','data'=>$info->getError());
			return $result;
		}else{
			$result = array('msg'=>'success','data'=>$date.'/'.$info->getSaveName());
			return $result;
		}

	}

	/**
	 *返回错误信息
	*/
	public function getError(){
		return $this->error_msg;
	}

}