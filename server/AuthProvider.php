<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 09.09.2015
 * Time: 21:55
 */

namespace vitprog\wamp\server;

use Thruway\Authentication\AbstractAuthProviderClient;
use Thruway\Message\Message;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\web\IdentityInterface;

class AuthProvider extends AbstractAuthProviderClient {

    /**
     * Process HelloMessage
     *
     * @param array $args
     * @return array<string|array>
     */
    public function processHello(array $args)
    {
        try {
            $helloMsg = array_shift($args);
            $sessionInfo = array_shift($args);

            if (!is_array($helloMsg)) {
                return ["ERROR"];
            }

            if (!is_object($sessionInfo)) {
                return ["ERROR"];
            }

            $helloMsg = Message::createMessageFromArray($helloMsg);

            //        VarDumper::dump($helloMsg, 3);

            $authid = (int)preg_replace('~^user~', '', $helloMsg->getDetails()->authid);

            /** @var ActiveRecord $userClass */
            $userClass = \Yii::$app->user->identityClass;
            $user = $userClass::findOne($authid);
            if ($user == null) {
                return ['ERROR'];
            }

            //        VarDumper::dump($authid, 3);


            //        $serializedChallenge = json_encode(['authid' => $authid]);
            //        $challengeDetails = [
            //            "challenge"        => $serializedChallenge,
            //            "challenge_method" => $this->getMethodName()
            //        ];
            //        return ['CHALLENGE', (object)$challengeDetails];

            $nonce = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
            $authRole = "user";
            $authMethod = "wampcra";
            //        $authProvider = "userdb";
            $now = new \DateTime();
            $timeStamp = $now->format($now::ISO8601);
            if (!isset($sessionInfo->sessionId)) {
                return ["ERROR"];
            }
            $sessionId = $sessionInfo->sessionId;
            $challenge = [
                "authid" => $authid,
                "authrole" => $authRole,
                //            "authprovider" => $authProvider,
                "authmethod" => $authMethod,
                "nonce" => $nonce,
                "timestamp" => $timeStamp,
                "session" => $sessionId
            ];
            $serializedChallenge = json_encode($challenge);
            $challengeDetails = [
                "challenge" => $serializedChallenge,
                "challenge_method" => $this->getMethodName()
            ];
            return ["CHALLENGE", (object)$challengeDetails];
        } catch (Exception $ex) {
//            VarDumper::dump($ex, 3);
            \Yii::getLogger()->log($ex, Logger::LEVEL_ERROR, 'wamp-server');
        } catch (\Exception $ex) {
//            VarDumper::dump($ex, 3);
            \Yii::getLogger()->log($ex, Logger::LEVEL_ERROR, 'wamp-server');
        }
        return ["ERROR"];
    }

    /**
     * Process authenticate
     *
     * @param mixed $signature
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($signature, $extra = null)
    {
        try {
            $challenge = $this->getChallengeFromExtra($extra);

            if (!$challenge
                || !isset($challenge->authid)
            ) {
                return ["FAILURE"];
            }

            $authid = (int)$challenge->authid;

            /** @var ActiveRecord|IdentityInterface $userClass */
            $userClass = \Yii::$app->user->identityClass;
            /** @var ActiveRecord|IdentityInterface $user */
            $user = $userClass::findOne($authid);
            if ($user == null) {
                return ['ERROR'];
            }

            $token = md5($user->getAuthKey());
            $hmac = hash_hmac('sha256', json_encode($challenge), $token, true);
            $hmac64 = base64_encode($hmac);

//            VarDumper::dump([
//                'signature' => $signature,
//                'challenge' => $challenge,
//                'json_encode($challenge)' => json_encode($challenge),
//                'token' => $token,
//                '$hmac' => $hmac,
//                '$hmac64' => $hmac64,
//            ]);

            if ($hmac64 != $signature) {
                return ["FAILURE"];
            }

            $authDetails = [
                "authmethod"   => "wampcra",
                "authrole"     => "user",
                "authid"       => $challenge->authid,
//                "authprovider" => $challenge->authprovider
            ];

            return ["SUCCESS", $authDetails];
        } catch (Exception $ex) {
//            VarDumper::dump($ex, 3);
            \Yii::getLogger()->log($ex, Logger::LEVEL_ERROR, 'wamp-server');
        } catch (\Exception $ex) {
//            VarDumper::dump($ex, 3);
            \Yii::getLogger()->log($ex, Logger::LEVEL_ERROR, 'wamp-server');
        }

        return ['ERROR'];
    }


    /**
     * Gets the Challenge Message from the extra object
     * @param $extra
     * @return bool | \stdClass
     */
    private function getChallengeFromExtra($extra)
    {
        return (is_object($extra)
            && isset($extra->challenge_details)
            && is_object($extra->challenge_details)
            && isset($extra->challenge_details->challenge))
            ? json_decode($extra->challenge_details->challenge)
            : false;
    }

    /**
     * @return mixed
     */
    public function getMethodName() {
        return 'token';
    }
}