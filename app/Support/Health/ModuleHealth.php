<?php

namespace App\Support\Health;

use App\Enums\ModuleHealthStatus;

final readonly class ModuleHealth
{
    /**
     * @param  list<string>  $issues
     */
    public function __construct(
        public ModuleHealthStatus $status,
        public array $issues = [],
    ) {}

    public static function ok(): self
    {
        return new self(ModuleHealthStatus::Ok);
    }

    /**
     * The module cannot work yet — required config or a coupling is missing.
     *
     * @param  list<string>  $issues
     */
    public static function needsSetup(array $issues): self
    {
        return new self(ModuleHealthStatus::NeedsSetup, array_values($issues));
    }

    /**
     * The module works, but an optional feature is unavailable.
     *
     * @param  list<string>  $issues
     */
    public static function degraded(array $issues): self
    {
        return new self(ModuleHealthStatus::Degraded, array_values($issues));
    }

    /**
     * Build a result from required .env keys plus optional coupling issues.
     *
     * Each entry in $required is `ENV_KEY => currentValue`; any blank value is
     * reported as a missing-config issue. Extra issues (e.g. "not linked yet")
     * are appended. The result is OK only when nothing is missing.
     *
     * @param  array<string, mixed>  $required
     * @param  list<string>  $extraIssues
     */
    public static function require(array $required, array $extraIssues = []): self
    {
        $issues = [];

        foreach ($required as $envKey => $value) {
            if (self::isBlank($value)) {
                $issues[] = "Ontbrekende .env: {$envKey}";
            }
        }

        foreach ($extraIssues as $issue) {
            $issues[] = $issue;
        }

        return $issues === [] ? self::ok() : self::needsSetup($issues);
    }

    public function isOk(): bool
    {
        return $this->status === ModuleHealthStatus::Ok;
    }

    private static function isBlank(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === null || $value === [];
    }
}
