<?php

namespace Phonedotcom\SmsVerification;

use Illuminate\Support\Facades\Log;
use Phonedotcom\SmsVerification\Exceptions\SmsVerificationException;
use Phonedotcom\SmsVerification\Exceptions\ValidationException;

/**
 * Class SmsVerification
 * @package Phonedotcom\SmsVerification
 */
class SmsVerification
{

    /**
     * Send code
     * @param $phoneNumber
     * @return array
     */
    public static function sendCode($phoneNumber)
    {

        $exceptionClass = null;
        $expiresAt = null;
        $response = [];
        try {
            static::validatePhoneNumber($phoneNumber);
            $now = time();
            $code = CodeProcessor::getInstance()->generateCode($phoneNumber);
            $translationCode = Config::get('message-translation-code');
            $text = $translationCode
                ? trans($translationCode, ['code' => $code])
                : 'SMS verification code: ' . $code;
            $senderClassName = Config::get('sender-class', Sender::class);

            $sender = $senderClassName::getInstance();

            if (!($sender instanceof SenderInterface)){
                throw new \Exception('Sender class ' . $senderClassName . ' doesn\'t implement SenderInterface');
            }

            $success = $sender->send($phoneNumber, $text);
            $description = $success ? 'OK' : 'Error';
            if ($success){
                $response['expires_at'] = $now + CodeProcessor::getInstance()->getLifetime();
                $response['code'] = 200;
            }

        } catch (\Exception $e) {

            $description = $e->getMessage();
            if (!($e instanceof ValidationException)) {
                Log::error('SMS Verification code sending was failed: ' . $description);
            }
            $success = false;
           // $response['error'] = ($e instanceof SmsVerificationException) ? $e->getErrorCode() : 999;
            $response['code'] = 401;
        }

        $response['status'] = $success;
        $response['message'] = $description;

        return $response;
    }

    /**
     * Check code
     * @param $code
     * @param $phoneNumber
     * @return array
     */
    public static function checkCode($code, $phoneNumber)
    {
        $exceptionClass = null;
        $response = [];
        try {
            if (!is_numeric($code)){
                throw new ValidationException('Incorrect code was provided');
            }
            static::validatePhoneNumber($phoneNumber);
            $success = CodeProcessor::getInstance()->validateCode($code, $phoneNumber);
            $description = $success ? 'OK' : 'Wrong code';
            $response['code'] = 200;
            
        } catch (\Exception $e) {

            $description = $e->getMessage();
            if (!($e instanceof ValidationException)) {
                Log::error('SMS Verification check was failed: ' . $description);
            }
            $success = false;
            $response['error'] = ($e instanceof SmsVerificationException) ? $e->getErrorCode() : 999;
            $response['code'] = 401;
        }
        $response['status'] = $success;
        $response['message'] = $description;
        
        return $response;
    }

    /**
     * Validate phone number
     * @param string $phoneNumber
     * @throws ValidationException
     */
    protected static function validatePhoneNumber($phoneNumber){
        $patterns = [
            "\+?1[2-9][0-9]{2}[2-9][0-9]{2}[0-9]{4}", // US
            "\+?[2-9]\d{9,}", // International
        ];
        if (!@preg_match("/^(" . implode('|', $patterns) . ")\$/", $phoneNumber)) {
            throw new ValidationException('Incorrect phone number was provided');
        }
    }

}
