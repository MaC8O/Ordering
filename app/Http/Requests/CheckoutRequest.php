<?php

namespace App\Http\Requests;

use App\Models\Setting;
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
        $dorms = (array) (Setting::current()->dorms ?? []);

        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'delivery_dorm' => ['required', 'string', Rule::in($dorms)],
            'delivery_room' => ['required', 'string', 'max:30'],
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
            'delivery_dorm.in' => 'Please choose a dorm or building from the list.',
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
            'delivery_dorm' => 'dorm / building',
            'delivery_room' => 'room number',
            'payment_screenshot' => 'payment screenshot',
        ];
    }
}
