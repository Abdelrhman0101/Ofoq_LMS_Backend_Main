<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $options = $this->options;
        if (is_string($options)) {
            $decodedOptions = json_decode($options, true);
            // Check if decoding was successful and the result is an array
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOptions)) {
                $options = $decodedOptions;
            } else {
                // If it's not a valid JSON string, return an empty array or handle as an error
                $options = [];
            }
        } elseif (!is_array($options)) {
            // If it's not a string or an array, default to an empty array
            $options = [];
        }

        $correctAnswer = $this->correct_answer;
        $correctIndex = null;

        if (is_numeric($correctAnswer)) {
            // If it's already a numeric index, use it directly
            $correctIndex = (int) $correctAnswer;
        } elseif (is_string($correctAnswer)) {
            // If it's a string, it could be a numeric string or the answer text
            if (ctype_digit($correctAnswer)) {
                $correctIndex = (int) $correctAnswer;
            } else {
                // If it's text, find its index in the options array
                $index = array_search($correctAnswer, $options);
                if ($index !== false) {
                    $correctIndex = $index;
                }
            }
        } elseif (is_array($correctAnswer) && !empty($correctAnswer)) {
            // If it's an array, take the first element (assuming single-answer questions for now)
            $firstValue = $correctAnswer[0];
            if (ctype_digit((string)$firstValue)) {
                $correctIndex = (int) $firstValue;
            } else {
                $index = array_search($firstValue, $options);
                if ($index !== false) {
                    $correctIndex = $index;
                }
            }
        }

        return [
            'id' => $this->id,
            'question' => $this->question,
            'options' => $options,
            'correct_answer' => $correctIndex,
            'explanation' => $this->explanation,
        ];
    }
}
