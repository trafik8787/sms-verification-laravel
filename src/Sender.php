<?php

namespace Phonedotcom\SmsVerification;

use Phonedotcom\SmsVerification\Exceptions\ConfigException;
use Phonedotcom\SmsVerification\Exceptions\SenderException;
use infobip\api\client\SendSingleTextualSms;
use infobip\api\configuration\BasicAuthConfiguration;
use infobip\api\model\sms\mt\send\textual\SMSTextualRequest;

/**
 * Class Sender
 * @package Phonedotcom\SmsVerification
 */
class Sender implements SenderInterface
{

    /**
     * Expected HTTP status for successful SMS sending request
     */
    const EXPECTED_HTTP_STATUS = 201;

    /**
     * Singleton instance
     * @var Sender
     */
    private static $instance;

    /**
     * API root URL
     * @var string
     */
    private $url;


    private $smsUser;

    private $smsPass;

    /**
     * User's Phone.com number which will be used for SMS sending
     * @var string
     */
    private $phoneNumber;

    /**
     * Singleton
     * @return Sender
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Sender constructor
     * @throws ConfigException
     */
    private function __construct()
    {

        $this->phoneNumber = Config::get('phone-com-phone-number');
        if (empty($this->phoneNumber)) {
            throw new ConfigException('Phone.com Phone Number is not specified in config/sms-verification.php');
        }

        $this->smsUser = Config::get('sms_user');
        $this->smsPass = Config::get('sms_pass');

        // $this->extensionId = config('sms-verification.phone-com-extension-id'); // Phone.com doesn't support it yet
    }

    /**
     * Send SMS via Phone.com AP
     * @param string $to
     * @param string $text
     * @return bool
     * @throws SenderException
     */
    public function send($to, $text)
    {

        try {
            $client = new SendSingleTextualSms(new BasicAuthConfiguration($this->smsUser, $this->smsPass));
            $requestBody = new SMSTextualRequest();
            $requestBody->setFrom($this->phoneNumber);
            $requestBody->setTo([$to]);
            $requestBody->setText($text);
            $response = $client->execute($requestBody);
            //print_r($response->getBulkId());
            //die(print_r($response->getBulkId()));
        } catch (\Exception $e){
            throw new SenderException('SMS sending was failed', null, 0, $e);
        }
        if (empty($response)){
            throw new SenderException('SMS was not sent', $res);
        }
        return true;
    }


}
