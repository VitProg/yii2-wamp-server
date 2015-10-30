<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 10.09.15
 * Time: 15:11
 */

namespace vitprog\wamp;


use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\AssetBundle;

class Asset extends AssetBundle {

    public $sourcePath = '@vitprog/wamp/assets';

    public $js = [
        'wamp.js'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'vitprog\wamp\AutobahnAsset',
        'vitprog\wamp\CryptoAsset',
    ];

    public $jsSettings = [];

    public function publish($am) {
        parent::publish($am);

        $jsSettings = ArrayHelper::merge([], $this->jsSettings);

        $jsSettings = Json::encode($jsSettings);

        \Yii::$app->getView()->registerJs(<<<JS
    wamp.init({$jsSettings});
JS
        );
    }


}