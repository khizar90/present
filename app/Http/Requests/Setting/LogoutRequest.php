<?php

namespace App\Http\Requests\Setting;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LogoutRequest extends FormRequest
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
    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'device_id' => 'required',
        ];
    }
    // public function messages()
    // {
    //     return [
    //         'email.required' => 'Please enter the Email Address',
    //         'email.email' => 'Please enter a valid Email Address',
    //         'email.exists' => 'The Email Adress is not registered ',
    //         'password.required' => 'Please enter the Password',
    //         'password.min' => 'Please enter atleast 6 characters in Password',
    //     ];
    // }
    public function failedValidation(Validator $validator)
    {
        $errorMessage = implode(', ', $validator->errors()->all());

        throw new HttpResponseException(response()->json([
            'status'   => false,
            'action' => $errorMessage
        ]));
    }
}
