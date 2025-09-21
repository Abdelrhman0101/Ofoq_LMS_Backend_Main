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
            'question'=>'required|string',
            // 'question_text' => 'required|string',
            // 'question_type' => 'required|in:multiple_choice,true_false,short_answer',
            'options' => 'required_if:question_type,multiple_choice|array',
            'correct_answer' => 'required|string',
            // 'points' => 'required|integer|min:1',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $rules);
            // Keep required_if rule for options
            $rules['options'] = 'required_if:question_type,multiple_choice|array';
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
            'options.required_if' => 'Options are required for multiple choice questions',
            'options.array' => 'Options must be an array',
            'correct_answer.required' => 'Correct answer is required',
            'points.required' => 'Points are required',
            'points.integer' => 'Points must be a number',
            'points.min' => 'Points must be at least 1',
        ];
    }
}