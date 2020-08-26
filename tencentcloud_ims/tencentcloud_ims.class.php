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
if (!defined('IN_DISCUZ')){
    exit('Access Denied');
}
defined('TENCENT_DISCUZX_IMS_DIR')||define( 'TENCENT_DISCUZX_IMS_DIR', __DIR__.DIRECTORY_SEPARATOR);
defined('TENCENT_DISCUZX_IMS_PLUGIN_NAME')||define( 'TENCENT_DISCUZX_IMS_PLUGIN_NAME', 'tencentcloud_ims');
if (!is_file(TENCENT_DISCUZX_IMS_DIR.'vendor/autoload.php')) {
    exit(lang('plugin/tencentcloud_ims','require_sdk'));
}
require_once 'vendor/autoload.php';
use TencentDiscuzIMS\IMSActions;
class plugin_tencentcloud_ims
{
    public function common()
    {
        if ( $_GET['mod'] !== 'swfupload' || $_GET['action'] !== 'swfupload') {
            return;
        }
        $dzxIMS = new IMSActions();
        try {
            $dzxIMS->examineImage($_FILES['Filedata']);
        } catch (\Exception $exception) {
            //不可自定义消息，只能使用dz的错误码
            echo -9;
            exit();
        }

    }
}
