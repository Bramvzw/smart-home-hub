<?php

namespace App\Data;

/**
 * One editable field in a module's settings pane.
 *
 * `value` is the effective current value (stored value overlaid on the config
 * default); `default` is the underlying config/env default shown as a hint.
 * `rules` are the Laravel validation rules applied when the field is saved.
 * `options` is used by the `select` type as `value => label` pairs.
 */
final readonly class SettingField
{
    public const TYPE_NUMBER = 'number';

    public const TYPE_STRING = 'string';

    public const TYPE_SELECT = 'select';

    public const TYPE_BOOLEAN = 'boolean';

    /**
     * @param  array<int|string, string>|string  $rules
     * @param  array<int|string, string>  $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public array|string $rules,
        public mixed $value,
        public mixed $default = null,
        public array $options = [],
        public ?string $help = null,
    ) {}
}
