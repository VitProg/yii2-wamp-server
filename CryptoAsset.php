<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 14.09.2015
 * Time: 17:23
 */

namespace vitprog\wamp;


use yii\web\AssetBundle;

class CryptoAsset extends AssetBundle {

    public $sourcePath = '@bower/cryptojslib';

    public $js = [
        'rollups/hmac-sha256.js',
        'components/enc-base64-min.js',
        'components/md5-min.js',
    ];
}