<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules =  [
            'title'              => 'required|string|max:255',
            'description'        => 'required|string',
            'duration'           => 'required|integer|min:0',
            'rating'             => 'nullable|numeric|min:0|max:5',
            'discount_price'     => 'nullable|numeric|min:0',
            'discount_ends_at'   => 'nullable|date|after:now',
            'price'              => 'required|numeric|min:0',
            'is_free'            => 'boolean',
            'instructor_id'      => 'nullable|integer|exists:users,id',
            'name_instructor'    => 'nullable|string|max:255',
            'bio_instructor'     => 'nullable|string|max:1000',
            'image_instructor'   => 'nullable|string|max:255',
            'title_instructor'   => 'nullable|string|max:255',
            'chapters_count'     => 'nullable|integer|min:0',
            'students_count'     => 'nullable|integer|min:0',
            'hours_count'        => 'nullable|integer|min:0',
            'reviews_count'      => 'nullable|integer|min:0',
            'image'              => 'nullable|string|max:255',
        ];


        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_starts_with($rule, 'required|')
                    ? 'sometimes|' . substr($rule, 9)
                    : 'sometimes|' . $rule;
            }, $rules);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required',
            'title.max' => 'Course title cannot exceed 255 characters',

            'description.required' => 'Course description is required',

            'duration.required' => 'Course duration is required',
            'duration.integer' => 'Course duration must be a number',
            'duration.min' => 'Course duration cannot be negative',

            'rating.numeric' => 'Rating must be a number',
            'rating.max' => 'Rating cannot be higher than 5',

            'discount_price.numeric' => 'Discount price must be a valid number',
            'discount_ends_at.date' => 'Discount end date must be a valid date',
            'discount_ends_at.after' => 'Discount end date must be in the future',

            'price.required' => 'Course price is required',
            'price.numeric' => 'Course price must be a number',
            'price.min' => 'Course price cannot be negative',

            'is_free.boolean' => 'Free status must be true or false',

            'instructor_id.exists' => 'Instructor ID must refer to an existing user',

            'chapters_count.integer' => 'Chapters count must be a number',
            'students_count.integer' => 'Students count must be a number',
            'hours_count.integer' => 'Hours count must be a number',
            'reviews_count.integer' => 'Reviews count must be a number',
        ];
    }
}
