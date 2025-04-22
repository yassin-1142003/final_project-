<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApartmentRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'area' => 'required|numeric|min:0',
            'status' => 'sometimes|string|in:available,rented,sold',
            'type' => 'required|string|in:apartment,house,villa,studio',
            'is_featured' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048'
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $rules);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The apartment title is required',
            'description.required' => 'A description of the apartment is required',
            'price.required' => 'The price is required',
            'price.numeric' => 'The price must be a number',
            'price.min' => 'The price cannot be negative',
            'location.required' => 'The location is required',
            'bedrooms.required' => 'The number of bedrooms is required',
            'bedrooms.integer' => 'The number of bedrooms must be a whole number',
            'bathrooms.required' => 'The number of bathrooms is required',
            'bathrooms.integer' => 'The number of bathrooms must be a whole number',
            'area.required' => 'The apartment area is required',
            'area.numeric' => 'The area must be a number',
            'area.min' => 'The area cannot be negative',
            'type.required' => 'The apartment type is required',
            'type.in' => 'Invalid apartment type selected',
            'status.in' => 'Invalid status selected',
            'images.*.image' => 'The file must be an image',
            'images.*.mimes' => 'Only JPEG, PNG and JPG images are allowed',
            'images.*.max' => 'Image size should not exceed 2MB'
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
} 