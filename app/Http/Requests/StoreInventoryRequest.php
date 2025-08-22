<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class StoreInventoryRequest extends FormRequest
{


    public function authorize()
    {
        // Return true if no auth guard or you handle auth elsewhere
        return true;
    }

    public function rules()
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'quantity'     => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
}

