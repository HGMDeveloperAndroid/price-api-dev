<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product.name' => 'string|min:2|nullable',
            'product.barcode' => 'string|min:1|nullable',
            'product.quantity' => 'numeric|nullable|min:1',
            'product.id_unit' => 'exists:units,id|nullable',
            'product.type' => 'string|nullable',
            'product.id_group' => 'exists:groups,id|nullable',
            'product.id_line' => 'exists:lines,id|nullable',
            'product.id_brand' => 'exists:brands,id|nullable',
//            'product.created_at' => 'date_format:Y-m-d|nullable',
        ];
    }
}
