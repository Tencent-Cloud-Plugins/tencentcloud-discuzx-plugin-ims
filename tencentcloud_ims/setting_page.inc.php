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
if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}
defined('TENCENT_DISCUZX_IMS_DIR')||define( 'TENCENT_DISCUZX_IMS_DIR', __DIR__.DIRECTORY_SEPARATOR);
if (!is_file(TENCENT_DISCUZX_IMS_DIR.'vendor/autoload.php')) {
    exit(lang('plugin/tencentcloud_ims','require_sdk'));
}
require_once 'vendor/autoload.php';
use TencentDiscuzIMS\IMSActions;
use TencentDiscuzIMS\IMSOptions;

try {
    //不是ajax请求直接返回html页面
    if( $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $options = IMSActions::getIMSOptionsObject();
        $secretId = $options->getSecretID();
        $secretKey = $options->getSecretKey();
        $customKey = $options->getCustomKey();
        include template('tencentcloud_ims:setting_page');
        exit;
    }
    $dzxIMS = new IMSActions();
    $customKey = intval($dzxIMS->filterPostParam('customKey',IMSOptions::GLOBAL_KEY));
    $secretId = $dzxIMS->filterPostParam('secretId');
    $secretKey = $dzxIMS->filterPostParam('secretKey');
    if ($customKey!==IMSOptions::GLOBAL_KEY && (empty($secretId) || empty($secretKey))) {
        cpmsg('secretId或secretKey为空', '', 'error');
    }
    $options = IMSActions::getIMSOptionsObject();
    $options->setCustomKey($customKey);
    $options->setSecretID($secretId);
    $options->setSecretKey($secretKey);

    C::t('common_setting')->update_batch(array("tencentcloud_ims" => $options->toArray()));
    updatecache('setting');
    IMSActions::uploadDzxStatisticsData('save_config');

    $url = 'action=plugins&operation=config&do='.$pluginid.'&identifier=tencentcloud_ims&pmod=setting_page';
    cpmsg('plugins_edit_succeed', $url, 'succeed');
}catch (\Exception $exception) {
    cpmsg($exception->getMessage(), '', 'error');
}
