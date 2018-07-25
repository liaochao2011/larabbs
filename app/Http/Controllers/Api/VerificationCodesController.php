<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use Overtrue\EasySms\EasySms;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        $phone = $request->phone;

        if (!app()->environment('production')) {
            $code = '1234';
        } else {

            $code = str_pad(random_int(0, 9999), 4, 0, STR_PAD_LEFT);

            try {
                $result = $easySms->send($phone, [
                    'content' => "【lara社区】您的验证码是{$code}。如非本人操作，请忽略本短信",
                ]);
            } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $exception) {
                $message = $exception->getException('yunpian')->getMessage();
                return $this->response->errorInternal($message ?? '短信发送异常');
            }
        }

        $key      = "verificationCode_" . str_random(15);
        $expireAt = now()->addMinutes(5);
        \Cache::put($key, ['phone' => $phone, 'code' => $code], $expireAt);
        return $this->response->array([
            'key'        => $key,
            'expired_at' => $expireAt->toDateTimeString()])->setStatusCode(201);
    }
}