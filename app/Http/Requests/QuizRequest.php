<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizRequest extends FormRequest
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
            'description' => 'nullable|string',
            // 'passing_score' => 'required|integer|min:0|max:100',
            // 'time_limit' => 'nullable|integer|min:1',
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
            'title.required' => 'Quiz title is required',
            'title.max' => 'Quiz title cannot exceed 255 characters',
            'passing_score.required' => 'Passing score is required',
            'passing_score.integer' => 'Passing score must be a number',
            'passing_score.min' => 'Passing score cannot be negative',
            'passing_score.max' => 'Passing score cannot exceed 100',
            'time_limit.integer' => 'Time limit must be a number',
            'time_limit.min' => 'Time limit must be at least 1 minute',
        ];
    }
}