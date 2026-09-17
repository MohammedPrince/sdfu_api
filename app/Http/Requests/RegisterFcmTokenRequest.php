<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class RegisterFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'max:4096',
            ],

            'device_type' => [
                'required',
                'string',
                'max:20',
            ],

            'device_name' => [
                'required',
                'string',
                'max:255',
            ],

            'app_version' => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }
}