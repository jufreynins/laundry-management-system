<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Location::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:20'],
            'timezone' => ['required', 'timezone'],
            'active' => ['boolean'],
        ];
    }
}
