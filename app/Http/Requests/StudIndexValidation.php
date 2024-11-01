<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudIndexValidation extends FormRequest
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
            'stud_index' => 'required|string',
        ];
    }
    public function messages()
    {
        return [
            'stud_index.required' => 'Please Enter Your Index',
        ];
    }
}
