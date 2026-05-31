<?php

namespace App\Services;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class EntryEaseApplicantDocuments
{
    private const TYPES = [
        'psa' => 'psa_birth_cert',
        'photo' => 'photo_2x2',
    ];

    public function stream(Enrollment $enrollment, string $type)
    {
        if (! array_key_exists($type, self::TYPES)) {
            abort(404, 'Document not found.');
        }

        $applicant = $this->findApplicantFor($enrollment);
        $documentColumn = self::TYPES[$type];
        $relativePath = $applicant?->{$documentColumn};

        if (! $relativePath) {
            abort(404, 'EntryEase document not found.');
        }

        $absolutePath = $this->resolveDocumentPath($relativePath);

        if (! $absolutePath) {
            abort(404, 'EntryEase document file not found on disk.');
        }

        $filename = basename($absolutePath);

        $response = response()->file($absolutePath, [
            'Content-Type' => mime_content_type($absolutePath) ?: $this->fallbackMimeType($filename),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);

        return $response;
    }

    private function findApplicantFor(Enrollment $enrollment): ?object
    {
        $enrollment->loadMissing('student');
        $portalId = $enrollment->student?->deoris_user_id;
        $email = $enrollment->email;

        if (! $portalId && ! $email) {
            return null;
        }

        return DB::connection('entryease')
            ->table('applicants')
            ->select(['id', 'deoris_user_id', 'portal_student_email', 'photo_2x2', 'psa_birth_cert'])
            ->where(function ($query) use ($portalId, $email) {
                if ($portalId) {
                    $query->orWhere('deoris_user_id', $portalId);
                }

                if ($email) {
                    $query->orWhere('portal_student_email', $email);
                }
            })
            ->latest('id')
            ->first();
    }

    private function resolveDocumentPath(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        foreach ($this->privateStorageRoots() as $root) {
            $root = rtrim($root, DIRECTORY_SEPARATOR . '/\\');
            $candidate = $root . DIRECTORY_SEPARATOR . $relativePath;
            $realRoot = realpath($root);
            $realCandidate = realpath($candidate);

            if ($realRoot && $realCandidate && str_starts_with($realCandidate, $realRoot) && is_file($realCandidate)) {
                return $realCandidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function privateStorageRoots(): array
    {
        $configured = env('ENTRYEASE_PRIVATE_STORAGE_PATH');

        return array_values(array_filter([
            $configured,
            '/var/deoris/entryease/private',
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'entryEase' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'private',
        ]));
    }

    private function fallbackMimeType(string $filename): string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}
