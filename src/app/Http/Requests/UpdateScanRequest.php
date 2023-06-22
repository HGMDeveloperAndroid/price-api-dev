<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScanRequest extends FormRequest
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
            'product.name' => 'string|min:2',
            'scan.barcode' => 'string|max:255',
            'scan.price' => '|numeric|nullable',
            'scan.special_price' => 'boolean|nullable',
            'product.id_brand' => 'exists:brands,id|nullable',
            'scan.id_chain' => 'exists:stores,id|nullable', //id_store
            'store.branch' => 'string|min:2|nullable',
            'product.quantity' => 'numeric|nullable',
            'product.id_unit' => 'exists:units,id|nullable',
            'product.type' => 'string|nullable',
            'product.id_group' => 'exists:groups,id|nullable',
            'product.id_line' => 'exists:lines,id|nullable',
        ];
    }
}
