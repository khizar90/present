<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyReqeust extends FormRequest
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
            'email' => 'required|unique:users,email|email',
            'username' => 'required|unique:users,username',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Please enter the Email Address',
            'username.required' => 'Please enter the Username',
            'email.unique' => 'Email Address is already registered',
            'username.unique' => 'Username is already registered',
            'email.email' => 'Please enter a valid Email Address',
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
