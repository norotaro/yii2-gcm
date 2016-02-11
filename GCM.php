<?php
/**
 * @author Bryan Jayson Tan <bryantan16@gmail.com>
 * @author Nelson Otazo <nelson.otazo@gmail.com>
 */
namespace norotaro\gcm;

use Yii;
use yii\base\Component;
use yii\log\Logger;
use yii\base\InvalidConfigException;

class GCM extends Component
{
    public $retryTimes = 3;
    public $dryRun = false;
    public $enableLogging = false;
    public $errors = [];
    public $success = false;
    public $apiKey;
    private $_client = null;

    public function init()
    {
        if (!$this->apiKey) {
            throw new InvalidConfigException('Api key cannot be empty');
        }
        parent::init();
    }

    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new \PHP_GCM\Sender($this->apiKey);
        }
        return $this->_client;
    }

    /**
     * Send a push notification using GCM client
     *
     * Usage 1:
     * <code>
     * $this->send(
     *  'some-valid-token',
     *  'some-message',
     *  [
     *    'custom_data_key_1'=>'custom_data_value_1',
     *    'custom_data_key_2'=>'custom_data_value_2',
     *  ]
     * );
     * </code>
     * @param string $token
     * @param $text
     * @param array $payloadData
     * @param array $args
     * @return null|\PHP_GCM\Message
     */
    public function send($token, $text, $payloadData = [], $args = [])
    {
        // check if its dry run or not
        if ($this->dryRun === true) {
            $this->log($token, $text, $payloadData, $args);
            return null;
        }

        $message = new \PHP_GCM\Message();
        foreach ($args as $method => $value) {
            $value = is_array($value) ? $value : [$value];
            call_user_func_array([$message, $method], $value);
        }

        // set a custom payload data
        $payloadData['message'] = $text;
        foreach ($payloadData as $key => $value) {
            $message->addData($key, $value);
        }

        try {
            // send a message
            $result = $this->getClient()->send($message, $token, $this->retryTimes);
            $this->success = $result->getErrorCode() != null ? false : true;
            if (!$this->success) {
                $this->errors[] = $result->getErrorCode();
            }
            // HTTP code 200, but message sent with error
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
            // $deviceRegistrationId was null
        } catch (\PHP_GCM\InvalidRequestException $e) {
            if ($e->getMessage()) {
                $this->errors[] = $e->getMessage();
            } else {
                $this->errors[] = sprintf("Received error code %s from GCM Service", $e->getCode());
            }
            // server returned HTTP code other than 200 or 503
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            // message could not be sent
        }
        return $message;
    }

    /**
     * Send a push notification using GCM client
     *
     * Usage 1:
     * <code>
     * $this->sendMulti(
     *  'some-valid-token',
     *  'some-message',
     *  [
     *   'custom_data_key_1' => 'custom_data_value_1',
     *   'custom_data_key_2' => 'custom_data_value_2',
     *  ]
     * );
     * </code>
     *
     * Usage 2:
     * <code>
     * $this->sendMulti(
     *  ['valid-token-1','valid-token-2','valid-token-3'],
     *  'some-message',
     *  [
     *   'custom_data_key_1'=>'custom_data_value_1',
     *   'custom_data_key_2'=>'custom_data_value_2',
     *  ]
     * );
     * </code>
     * @param string|array $tokens
     * @param $text
     * @param array $payloadData
     * @param array $args
     * @return null|\PHP_GCM\Message
     */
    public function sendMulti($tokens, $text, $payloadData = [], $args = [])
    {
        $tokens = is_array($tokens) ? $tokens : [$tokens];
        // check if its dry run or not
        if ($this->dryRun === true) {
            $this->log($tokens, $text, $payloadData, $args);
            $this->success = true;
            return null;
        }

        $message = new \PHP_GCM\Message($payloadData, $args);

        try {
            // send a message
            $result = $this->getClient()->send($message, $tokens);
            $this->success = $result->getSuccess();;
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
            // $deviceRegistrationId was null
        } catch (\PHP_GCM\InvalidRequestException $e) {
            if ($e->getMessage()) {
                $this->errors[] = $e->getMessage();
            } else {
                $this->errors[] = sprintf("Received error code %s from GCM Service", $e->getCode());
            }
            // server returned HTTP code other than 200 or 503
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            // message could not be sent
        }

        return $message;
    }

    public function __call($method, $params)
    {
        $client = $this->getClient();
        if (method_exists($client, $method)) {
            return call_user_func_array([$client, $method], $params);
        }
        return parent::__call($method, $params);
    }

    public function log($tokens, $text, $payloadData = [], $args = [])
    {
        $payloadData = http_build_query($payloadData);
        $args = http_build_query($args);
        $tokens = is_array($tokens) ? implode(', ', $tokens) : $tokens;
        $msg = "Sending push notifications to " . $tokens . "\n" .
            "message: {$text}\n" .
            "payload data: " . str_replace('&', ', ', $payloadData) . "\n" .
            "arguments: " . str_replace('&', ', ', $args);
        Yii::getLogger()->log($msg, Logger::LEVEL_INFO, 'bryglen/apnsgcm');
    }
} 
