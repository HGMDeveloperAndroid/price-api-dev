<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationRequest extends FormRequest
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
            'author' => 'required',
            'barCode' => 'required|max:255',
            'price' => 'required',
            'mission_id' => 'integer|nullable',
//            'product_picture' => 'required|file|mimes:jpeg,jpg,png|max:5243|nullable',
//            'self_picture' => 'file|mimes:jpeg,jpg,png|max:5243|nullable',
//            'promo_picture' => 'file|mimes:jpeg,jpg,png|max:5243|nullable',
            'place' => 'min:8|nullable',
            'special_price' => 'boolean|nullable',
            'fill_in_place' => 'min:5|nullable',
            'lat' => 'numeric',
            'lng' => 'numeric',
            'picture_id' => 'integer|nullable',
            'comments' => 'string|nullable'
        ];
    }
}
