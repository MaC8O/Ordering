<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'delivery_dorm' => ['required', 'string', 'min:2', 'max:120'],
            'delivery_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['qr', 'cash'])],
            'payment_screenshot' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('payment_method') === 'qr'),
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:' . config('ordering.screenshot_max_kb'),
            ],
            'cart' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Enter a valid phone number (digits only, 7–15 characters, optional leading +).',
            'delivery_date.after_or_equal' => 'The delivery date cannot be in the past.',
            'payment_screenshot.required' => 'Please upload a screenshot of your transfer.',
            'payment_screenshot.max' => 'The screenshot must be 5 MB or smaller.',
            'payment_screenshot.image' => 'The upload must be an image file.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'full name',
            'customer_phone' => 'phone number',
            'delivery_dorm' => 'dorm / building / address',
            'delivery_date' => 'delivery date',
            'payment_screenshot' => 'payment screenshot',
        ];
    }
}
