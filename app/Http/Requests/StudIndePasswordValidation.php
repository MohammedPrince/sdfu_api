<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudIndePasswordValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' =>'required|string',
            'new_password' => 'required|string',
            'new_password_confirm' => 'required|string',
        ];
    }
    public function messages()
    {
        return [
            'new_password.required' => 'Please Enter Your Password',
            'new_password_confirm.required' => 'Please Enter Your Password Confirmation',
        ];
    }
}
