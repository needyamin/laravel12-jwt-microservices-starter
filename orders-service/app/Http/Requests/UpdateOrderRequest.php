<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'product' => 'sometimes|required|string|max:255',
            'quantity' => 'sometimes|required|integer|min:1',
        ];
    }
}


