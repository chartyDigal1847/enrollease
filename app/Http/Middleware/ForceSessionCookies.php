<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceSessionCookies
{
    public function handle(Request $request, Closure $next): Response
    {
        $envPath = base_path('.env');
        $appKey  = $this->readEnvValue($envPath, 'APP_KEY');
        if ($appKey) {
            config(['app.key' => $appKey]);
        }
        $sessionDomain = $this->readEnvValue($envPath, 'SESSION_DOMAIN');

        config([
            'session.cookie'    => $this->readEnvValue($envPath, 'SESSION_COOKIE') ?: 'enrollease_session',
            'session.domain'    => $this->normalizeNullableEnvValue($sessionDomain),
            'session.secure'    => true,
            'session.same_site' => 'none',
            'session.http_only' => true,
        ]);
        return $next($request);
    }

    private function readEnvValue(string $envFile, string $key): ?string
    {
        if (! is_readable($envFile)) return null;
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#') continue;
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            if (trim(substr($line, 0, $eq)) !== $key) continue;
            $val = trim(substr($line, $eq + 1));
            if (strlen($val) >= 2 && $val[0] === '"'  && $val[-1] === '"')  $val = substr($val, 1, -1);
            if (strlen($val) >= 2 && $val[0] === "'"  && $val[-1] === "'")  $val = substr($val, 1, -1);
            return $val;
        }
        return null;
    }

    private function normalizeNullableEnvValue(?string $value): ?string
    {
        if ($value === null) return null;

        $value = trim($value);
        if ($value === '' || strtolower($value) === 'null') return null;

        return $value;
    }
}
