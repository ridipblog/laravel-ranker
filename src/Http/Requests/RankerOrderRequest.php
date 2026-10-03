<?php

namespace Ranker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RankerOrderRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'model' => ['sometimes', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required'],
            'start_order' => ['sometimes', 'integer', 'min:0'],
            'primary_key' => ['sometimes', 'string'],
        ];
    }
}
