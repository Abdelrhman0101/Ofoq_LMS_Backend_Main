<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
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
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'video_url' => 'nullable|string|max:255',
            'order' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
        ];

        // For update requests, make fields optional
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
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Lesson title is required',
            'title.max' => 'Lesson title cannot exceed 255 characters',
            'content.required' => 'Lesson content is required',
            'order.required' => 'Lesson order is required',
            'order.integer' => 'Lesson order must be a number',
            'order.min' => 'Lesson order must be at least 1',
            'duration.required' => 'Lesson duration is required',
            'duration.integer' => 'Lesson duration must be a number',
            'duration.min' => 'Lesson duration must be at least 1 minute',
        ];
    }
}