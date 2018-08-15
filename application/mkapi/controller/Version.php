<?php
namespace app\mkapi\controller;
use think\Controller;
use app\mkapi\model\App_versions;
use think\Request;

class Version extends Controller{

    /**
     * 获取最新版本信息
     */
    public function getLatestVersion(Request $request){
        $os = $request->post('os');
        if($os <> 'a' && $os <> 'i')
            return ['status'=>1,'msg'=>'os参数不正确！'];
        $versionDbInstances = App_versions::all(['os'=>$os]);

        //遍历得最高版本号
        $latestVersionDbInstance = $versionDbInstances[0];
        foreach($versionDbInstances as $versionDbInstance){
            if(version_compare($latestVersionDbInstance->version,$versionDbInstance->version) == '-1'){
                $latestVersionDbInstance = $versionDbInstance;
            }
        }

        $osFullName = 'Android';
        if($os == 'i')
            $osFullName = 'iOS';

        $result = [
            'status' => 0,
            'msg' => '',
            'data' => [
                'os' => $osFullName,
                'version' => $latestVersionDbInstance->version,
                'force' => $latestVersionDbInstance->force,
                'url' => $latestVersionDbInstance->url,
                'md5' => $latestVersionDbInstance->md5,
                'content' => $latestVersionDbInstance->content
            ]
        ];
        return $result;
        

    }
}