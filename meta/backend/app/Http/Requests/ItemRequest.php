<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            $this->merge(['status' => 'available']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_tag' => [
                'required',
                'string',
                'max:255',
                Rule::unique('items', 'asset_tag')->ignore($this->route('item')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'sometimes', Rule::in(['available', 'assigned', 'under_repair', 'disposed'])],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
