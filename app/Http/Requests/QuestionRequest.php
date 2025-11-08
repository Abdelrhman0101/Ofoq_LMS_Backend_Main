<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
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
            'question' => 'required|string',
            'options' => 'required|array|min:3|max:4',
            'correct_answer' => 'required',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $rules);
            // Keep options rule consistent
            $rules['options'] = 'sometimes|required|array|min:3|max:4';
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
            'question_text.required' => 'Question text is required',
            'question_type.required' => 'Question type is required',
            'question_type.in' => 'Question type must be multiple_choice, true_false, or short_answer',
            'options.required' => 'Options are required',
            'options.array' => 'Options must be an array',
            'options.min' => 'Options must contain at least 3 items',
            'options.max' => 'Options must not exceed 4 items',
            'correct_answer.required' => 'Correct answer is required',
            'points.required' => 'Points are required',
            'points.integer' => 'Points must be a number',
            'points.min' => 'Points must be at least 1',
        ];
    }
}