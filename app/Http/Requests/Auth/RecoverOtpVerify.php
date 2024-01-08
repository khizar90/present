<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RecoverOtpVerify extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|min:6'
        ];
    }
    public function messages()
    {
        return [
            'email.required' => 'Please enter the Email Address',
            'email.email' => 'Please enter a valid Email Address',
            'email.exists' => 'This Email Address is not registered ',
            'otp.min' => 'Please enter atleast 6 digit'
        ];
    }
    public function failedValidation(Validator $validator)
    {
        $errorMessage = implode(', ', $validator->errors()->all());

        throw new HttpResponseException(response()->json([
            'status'   => false,
            'action' => $errorMessage
        ]));
    }
}
