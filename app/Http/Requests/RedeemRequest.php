<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemRequest extends FormRequest
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
            'code' => 'required|string|max:255',
            'user' => 'required|array',
            'user.email' => 'required|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The redeemcode is required',
            'code.string' => 'The redeem code must be a string',
            'code.max' => 'The redeem code must be less than 255 characters',
            'user.required' => 'The user is required',
            'user.email.required' => 'The user email is required',
            'user.email.email' => 'The user email must be a valid email address',
            'user.email.max' => 'The user email must be less than 255 characters',
        ];
    }
}
