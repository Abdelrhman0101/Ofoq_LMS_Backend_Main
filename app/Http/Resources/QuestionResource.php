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
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOptions)) {
                $options = $decodedOptions;
            } else {
                $options = [];
            }
        } elseif (!is_array($options)) {
            $options = [];
        }
        // Sanitize options: remove empty strings/nulls, reindex
        $options = array_values(array_filter($options, function ($opt) {
            if (is_null($opt)) return false;
            if (is_string($opt)) return trim($opt) !== '';
            return true;
        }));

        $correctAnswer = $this->correct_answer;
        $correctIndex = null;

        if (is_numeric($correctAnswer)) {
            $idx = (int) $correctAnswer;
            $correctIndex = array_key_exists($idx, $options) ? $idx : null;
        } elseif (is_string($correctAnswer)) {
            if (ctype_digit($correctAnswer)) {
                $idx = (int) $correctAnswer;
                $correctIndex = array_key_exists($idx, $options) ? $idx : null;
            } else {
                $index = array_search($correctAnswer, $options, true);
                if ($index !== false) {
                    $correctIndex = $index;
                }
            }
        } elseif (is_array($correctAnswer) && !empty($correctAnswer)) {
            $firstValue = $correctAnswer[0];
            if (ctype_digit((string) $firstValue)) {
                $idx = (int) $firstValue;
                $correctIndex = array_key_exists($idx, $options) ? $idx : null;
            } else {
                $index = array_search($firstValue, $options, true);
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
