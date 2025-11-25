<?php

namespace App\Services;

use App\Models\CategoryOfCourse;
use App\Models\User;
use App\Models\UserCourse;
use App\Models\DiplomaCertificate;
use App\Models\UserCategoryEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DiplomaEligibilityService
{
    /**
     * Calculate diploma completion percentage for a student
     * Formula: (passed courses in diploma / total diploma courses) * 100
     */
    public function calculateCompletionPercentage(User $user, CategoryOfCourse $diploma): float
    {
        $totalCourses = $diploma->courses()->count();
        if ($totalCourses === 0) {
            return 0.0;
        }

        $completedCourses = UserCourse::where('user_id', $user->id)
            ->whereHas('course', function (Builder $query) use ($diploma) {
                $query->where('category_id', $diploma->id);
            })
            ->where('status', 'completed')
            ->where('final_exam_score', '>=', 60)
            ->count();

        // Use 2-decimal precision to preserve exact 100% when fully completed
        return round(($completedCourses / $totalCourses) * 100, 2);
    }
    /**
     * Get all eligible students for a specific diploma
     * 
     * @param CategoryOfCourse $diploma
     * @return Collection
     */
    public function getEligibleStudents(CategoryOfCourse $diploma): Collection
    {
        return User::whereHas('categoryEnrollments', function (Builder $query) use ($diploma) {
                // Check if student is enrolled in this diploma
                $query->where('category_id', $diploma->id)
                      ->where('status', 'active'); // Assuming 'active' means enrolled
            })
            ->whereHas('enrollments', function (Builder $query) use ($diploma) {
                // Filter only courses that belong to this diploma
                $query->whereHas('course', function (Builder $courseQuery) use ($diploma) {
                    $courseQuery->where('category_id', $diploma->id);
                })
                // Only count completed courses with passing grade
                ->where('status', 'completed')
                ->where('final_exam_score', '>=', 60); // Assuming 60 is passing grade
            })
            ->withCount(['enrollments as completed_diploma_courses' => function (Builder $query) use ($diploma) {
                $query->whereHas('course', function (Builder $courseQuery) use ($diploma) {
                    $courseQuery->where('category_id', $diploma->id);
                })
                ->where('status', 'completed')
                ->where('final_exam_score', '>=', 60);
            }])
            ->whereDoesntHave('diplomaCertificates', function (Builder $query) use ($diploma) {
                // Exclude students who already have a certificate for this diploma
                $query->where('diploma_id', $diploma->id)
                      ->where('status', '!=', 'revoked'); // Don't count revoked certificates
            })
            ->having('completed_diploma_courses', '=', $diploma->courses()->count())
            ->with(['enrollments' => function ($query) use ($diploma) {
                $query->whereHas('course', function (Builder $courseQuery) use ($diploma) {
                    $courseQuery->where('category_id', $diploma->id);
                });
            }])
            ->get();
    }

    /**
     * Check if a specific student is eligible for a diploma certificate
     * 
     * @param User $user
     * @param CategoryOfCourse $diploma
     * @return bool
     */
    public function isStudentEligible(User $user, CategoryOfCourse $diploma): bool
    {
        // Check if student is enrolled in the diploma
        $enrollment = UserCategoryEnrollment::where('user_id', $user->id)
            ->where('category_id', $diploma->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return false;
        }

        // Check if student already has a certificate (not revoked)
        $existingCertificate = DiplomaCertificate::where('user_id', $user->id)
            ->where('diploma_id', $diploma->id)
            ->where('status', '!=', 'revoked')
            ->first();

        if ($existingCertificate) {
            return false;
        }

        // Get total number of courses in this diploma
        $totalCourses = $diploma->courses()->count();
        
        if ($totalCourses === 0) {
            return false;
        }

        // Get number of completed courses with passing grade
        $completedCourses = UserCourse::where('user_id', $user->id)
            ->whereHas('course', function (Builder $query) use ($diploma) {
                $query->where('category_id', $diploma->id);
            })
            ->where('status', 'completed')
            ->where('final_exam_score', '>=', 60)
            ->count();

        // Student is eligible if they completed all courses
        return $completedCourses === $totalCourses;
    }

    /**
     * Get detailed eligibility information for a student
     * 
     * @param User $user
     * @param CategoryOfCourse $diploma
     * @return array
     */
    public function getStudentEligibilityDetails(User $user, CategoryOfCourse $diploma): array
    {
        $enrollment = UserCategoryEnrollment::where('user_id', $user->id)
            ->where('category_id', $diploma->id)
            ->first();

        $totalCourses = $diploma->courses()->count();
        
        $completedCourses = UserCourse::where('user_id', $user->id)
            ->whereHas('course', function (Builder $query) use ($diploma) {
                $query->where('category_id', $diploma->id);
            })
            ->where('status', 'completed')
            ->where('final_exam_score', '>=', 60)
            ->count();

        $existingCertificate = DiplomaCertificate::where('user_id', $user->id)
            ->where('diploma_id', $diploma->id)
            ->where('status', '!=', 'revoked')
            ->first();

        $courseDetails = UserCourse::where('user_id', $user->id)
            ->whereHas('course', function (Builder $query) use ($diploma) {
                $query->where('category_id', $diploma->id);
            })
            ->with('course')
            ->get()
            ->map(function ($userCourse) {
                return [
                    'course_id' => $userCourse->course->id,
                    'course_title' => $userCourse->course->title,
                    'status' => $userCourse->status,
                    'final_exam_score' => $userCourse->final_exam_score,
                    'completed_at' => $userCourse->completed_at,
                ];
            });

        return [
            'is_enrolled' => $enrollment && $enrollment->status === 'active',
            'total_courses' => $totalCourses,
            'completed_courses' => $completedCourses,
            'completion_percentage' => $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 2) : 0,
            'has_certificate' => $existingCertificate !== null,
            'certificate_status' => $existingCertificate ? $existingCertificate->status : null,
            'is_eligible' => $this->isStudentEligible($user, $diploma),
            'courses' => $courseDetails,
        ];
    }

    /**
     * Get all diplomas that a student is eligible for
     * 
     * @param User $user
     * @return Collection
     */
    public function getEligibleDiplomasForStudent(User $user): Collection
    {
        return CategoryOfCourse::whereHas('enrollments', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('status', 'active');
            })
            ->whereHas('courses')
            ->whereDoesntHave('certificates', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('status', '!=', 'revoked');
            })
            ->withCount('courses as total_courses')
            ->get()
            ->filter(function ($diploma) use ($user) {
                // Get number of completed courses with passing grade
                $completedCourses = UserCourse::where('user_id', $user->id)
                    ->whereHas('course', function (Builder $query) use ($diploma) {
                        $query->where('category_id', $diploma->id);
                    })
                    ->where('status', 'completed')
                    ->where('final_exam_score', '>=', 60)
                    ->count();
                
                return $diploma->total_courses > 0 && $completedCourses === $diploma->total_courses;
            });
    }
}