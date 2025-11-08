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
            // Lesson
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'video_url' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
            'attachments'=>'nullable|string',
            'resources'=>'nullable|array',
            'is_visible'=>'required|boolean',
            'chapter_id' => 'sometimes|required|exists:chapters,id',
            
            // Quiz
            'quiz' => 'nullable|array',
            'quiz.title' => 'required_with:quiz|string|max:255',
            'quiz.description' => 'nullable|string',
            'quiz.passing_score' => 'nullable|integer|min:0|max:100',
            'quiz.time_limit' => 'nullable|integer|min:1',
            'quiz.delete' => 'sometimes|boolean',
            
            // Questions
            'quiz.questions' => 'nullable|array',
            'quiz.questions.*.id' => 'sometimes|exists:questions,id',
            'quiz.questions.*.question' => 'required|string',
            // 'quiz.questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'quiz.questions.*.options' => 'required|array|min:3|max:4',
            'quiz.questions.*.correct_answer' => 'required',
            'quiz.questions.*.explanation' => 'nullable|string',
            // 'quiz.questions.*.points' => 'sometimes|integer|min:1',
            // 'quiz.questions.*.delete' => 'sometimes|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $rules);
            $rules['quiz.title'] = 'sometimes|required_with:quiz|string|max:255';
            $rules['quiz.questions.*.question'] = 'sometimes|required|string';
            $rules['quiz.questions.*.type'] = 'sometimes|in:multiple_choice,true_false,short_answer';
            $rules['quiz.questions.*.correct_answer'] = 'sometimes|required';
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
            // Lesson
            'title.required' => 'Lesson title is required',
            'title.max' => 'Lesson title cannot exceed 255 characters',
            'content.required' => 'Lesson content is required',
            'order.required' => 'Lesson order is required',
            'order.integer' => 'Lesson order must be a number',
            'order.min' => 'Lesson order must be at least 1',
            'attachments.string'=>'Attachments must be a string',
            'resources.array'=>'Resources must be a json string',
            'video_url.required'=>'Video url is required',
            'is_visible.required'=>'Is visible is required',
            // 'video_url.max'=>'Video url cannot exceed 255 characters',
            // quiz
            'quiz.title.required_with' => 'Quiz title is required when adding a quiz',
            'quiz.title.max' => 'Quiz title cannot exceed 255 characters',
            'quiz.passing_score.integer' => 'Passing score must be a number',
            'quiz.passing_score.min' => 'Passing score cannot be negative',
            'quiz.passing_score.max' => 'Passing score cannot exceed 100',
            'quiz.time_limit.integer' => 'Time limit must be a number',
            'quiz.time_limit.min' => 'Time limit must be at least 1 minute',

            // questions data
            'quiz.questions.*.question.required' => 'Each question must have text',
            'quiz.questions.*.correct_answer.required' => 'Each question must have a correct answer',
            'quiz.questions.*.options.min' => 'Each question must have at least 3 options',
            'quiz.questions.*.options.max' => 'Each question must have at most 4 options',
            'quiz.questions.*.options.array' => 'Options must be an array',
            'quiz.questions.*.explanation.string' => 'Explanation must be a string',
        ];
    }
}
