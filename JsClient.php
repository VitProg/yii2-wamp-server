<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 14.09.2015
 * Time: 17:32
 */

namespace vitprog\wamp;


use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class JsClient {

    public static function InitJs($jsSettings = []) {
        AutobahnAsset::register(\Yii::$app->getView());
        CryptoAsset::register(\Yii::$app->getView());
        Asset::register(\Yii::$app->getView());

        $jsSettings = ArrayHelper::merge([], $jsSettings);

        $jsSettings = Json::encode($jsSettings);

        \Yii::$app->getView()->registerJs(<<<JS
    wamp.init({$jsSettings});
JS
        );
    }

}