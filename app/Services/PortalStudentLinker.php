<?php

namespace App\Services;

use App\Models\Student;

/**
 * Links EnrollEase student rows to DEORIS portal identity (SSO session).
 */
class PortalStudentLinker
{
    public static function resolveOrCreateFromSession(): ?Student
    {
        $portalId = session('sso_id');
        $email = session('sso_email');
        $name = session('sso_name', 'Student');

        if (! $portalId && ! $email) {
            return null;
        }

        if ($portalId) {
            $existing = Student::query()->where('deoris_user_id', $portalId)->first();
            if ($existing) {
                return $existing;
            }
        }

        if ($email) {
            $byEmail = Student::query()->where('email', $email)->first();
            if ($byEmail) {
                if ($portalId && ! $byEmail->deoris_user_id) {
                    $byEmail->update(['deoris_user_id' => $portalId]);
                }

                return $byEmail;
            }
        }

        $parts = explode(' ', trim($name), 2);

        return Student::query()->create([
            'deoris_user_id' => $portalId,
            'student_name' => $name,
            'first_name' => $parts[0] ?? $name,
            'last_name' => $parts[1] ?? '',
            'email' => $email ?? '',
        ]);
    }
}
