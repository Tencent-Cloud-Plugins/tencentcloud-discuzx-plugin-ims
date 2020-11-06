<?php
/*
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace TencentDiscuzIMS;
use C;
use DB;
use Exception;
use TencentCloud\Cms\V20190321\Models\ImageModerationResponse;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Cms\V20190321\CmsClient;
use TencentCloud\Cms\V20190321\Models\ImageModerationRequest;
defined('TENCENT_DISCUZX_IMS_PLUGIN_NAME')||define( 'TENCENT_DISCUZX_IMS_PLUGIN_NAME', 'tencentcloud_ims');
class IMSActions
{
    const PLUGIN_TYPE = 'ims';
    const EXAMINE_TYPE = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/gif',
        'image/bmp',
        'application/octet-stream',
    ];

    /**
     * post参数过滤
     * @param $key
     * @param string $default
     * @return string|void
     */
    public function filterPostParam($key, $default = '')
    {
        return isset($_POST[$key]) ? dhtmlspecialchars($_POST[$key]) : $default;
    }
    /**
     * get参数过滤
     * @param $key
     * @param string $default
     * @return string|void
     */
    public function filterGetParam($key, $default = '')
    {
        return isset($_GET[$key]) ? dhtmlspecialchars($_GET[$key]) : $default;
    }


    /**
     * 图片检测
     * @param array $filedata
     * @return bool
     * @throws Exception
     */
    public function examineImage($filedata)
    {
        //非图片类型直接返回
        if (empty($filedata['tmp_name']) || !in_array($filedata['type'],self::EXAMINE_TYPE)) {
            return true;
        }
        $IMSOptions = self::getIMSOptionsObject();
        //没有填写配置直接返回
        if (empty($IMSOptions->getSecretID()) || empty($IMSOptions->getSecretKey())) {
            return true;
        }
        //内容为空直接返回
        $imgContent = file_get_contents($filedata['tmp_name']);
        if (empty($imgContent)) {
            return true;
        }
        $response = $this->imageModeration($IMSOptions, $imgContent);
        //检测接口异常不影响用户发帖回帖
        if ( !($response instanceof ImageModerationResponse) ) {
            return true;
        }
        if ( $response->getData()->EvilFlag !== 0 || $response->getData()->EvilType !== 100 ) {
            throw new \Exception(lang('plugin/tencentcloud_ims','picture_invalid'));
        }
        return true;
    }

    /**
     * 腾讯云图片检测
     * @param $IMSOptions
     * @param string $imgContent
     * @param string $imgUrl
     * @return Exception|ImageModerationResponse|TencentCloudSDKException
     * @throws Exception
     */
    private function imageModeration($IMSOptions,$imgContent = '',$imgUrl = '')
    {
        try {
            if (empty($imgContent) && empty($imgUrl)) {
                throw new \Exception(lang('plugin/tencentcloud_ims','picture_invalid'));
            }
            $cred = new Credential($IMSOptions->getSecretID(), $IMSOptions->getSecretKey());
            $clientProfile = new ClientProfile();
            $client = new CmsClient($cred, "ap-shanghai", $clientProfile);
            $req = new ImageModerationRequest();
            if ($imgUrl) {
                $params['FileUrl'] = $imgUrl;
            } else {
                $params['FileContent'] = base64_encode($imgContent);
            }
            $req->fromJsonString(\GuzzleHttp\json_encode($params,JSON_UNESCAPED_UNICODE));
            $resp = $client->ImageModeration($req);
            return $resp;
        }
        catch(TencentCloudSDKException $e) {
            return $e;
        }
    }

    /**
     * 获取配置对象
     * @return IMSOptions
     * @throws Exception
     */
    public static function getIMSOptionsObject()
    {
        global $_G;
        $IMSOptions = new IMSOptions();
        $options = $_G['setting'][TENCENT_DISCUZX_IMS_PLUGIN_NAME];
        if (!empty($options)) {
            C::t('common_pluginvar')->delete_by_pluginid($GLOBALS['pluginid']);
            $options = unserialize($options);
            $IMSOptions->setCustomKey($options['customKey']);
            $IMSOptions->setSecretID($options['secretId']);
            $IMSOptions->setSecretKey($options['secretKey']);
        }
        return $IMSOptions;
    }

    public static function uploadDzxStatisticsData($action)
    {
        try {
            $file = DISCUZ_ROOT . './source/plugin/tencentcloud_center/lib/tencentcloud_helper.class.php';
            if (!is_file($file)) {
                return;
            }
            require_once $file;
            $data['action'] = $action;
            $data['plugin_type'] = self::PLUGIN_TYPE;
            $data['data']['site_url'] = \TencentCloudHelper::siteUrl();
            $data['data']['site_app'] = \TencentCloudHelper::getDiscuzSiteApp();
            $data['data']['site_id'] = \TencentCloudHelper::getDiscuzSiteID();
            $options = self::getIMSOptionsObject();
            $data['data']['uin'] = \TencentCloudHelper::getUserUinBySecret(
                $options->getSecretID(),
                $options->getSecretKey()
            );
            $data['data']['cust_sec_on'] = $options->getCustomKey() === $options::CUSTOM_KEY ? 1 : 2;

            \TencentCloudHelper::sendUserExperienceInfo($data);
        }catch (\Exception $exception) {
            return;
        }

    }
}
