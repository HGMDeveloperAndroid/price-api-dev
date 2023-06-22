<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateScanRequest extends FormRequest
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
            'scan.barcode' => 'required|max:255',
            'scan.price' => 'required|numeric',
            'scan.special_price' => 'boolean|nullable',
            'scan.product_picture' => 'file|mimes:jpeg,jpg,png|max:5243|nullable',
            'scan.shelf_picture' => 'file|mimes:jpeg,jpg,png|max:5243|nullable',
            'scan.promo_picture' => 'file|mimes:jpeg,jpg,png|max:5243|nullable',
            'scan.comments' => 'string|nullable',
            'product.id' => 'integer|nullable',
            'product.name' => 'min:2',
            'product.brand' => 'integer',
            'product.price' => 'numeric|min:0',
            'product.quantity' => 'numeric|nullable',
            'product.unit' => 'integer|nullable',
            'product.group' => 'integer|nullable',
            'product.line' => 'integer|nullable',
            'product.type' => 'string|nullable',
            'product.picture_path' => 'file|mimes:jpeg,jpg,png|max:5243|nullable',
            'store.id' => 'integer|nullable',
            'store.name' => 'string|nullable',
            'store.address' => 'string|nullable',
//            'store.phone' => 'min:10|max:15',
            'store.lat' => 'numeric|between:-90,90|present',
            'store.lng' => 'numeric|between:-180,180|present',
        ];
    }
}
