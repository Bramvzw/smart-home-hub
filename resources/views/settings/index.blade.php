<x-dashboard.layout title="Settings">
    <div class="h-full p-4 md:p-5">
        <section class="mx-auto w-full max-w-3xl">
            <div class="mb-5">
                <h2 class="text-[22px] font-bold text-[var(--hub-text)] leading-tight">Settings</h2>
                <p class="mt-1 text-sm text-[var(--hub-dim)]">Configuration per module, without editing the .env file.</p>
            </div>

            @if(session('settings_status'))
                <div class="hub-card mb-5 flex items-center gap-3 p-4 text-sm text-[var(--hub-text)]">
                    <x-dashboard.icons.check-circle class="h-5 w-5 shrink-0 text-[var(--hub-accent)]" />
                    <span>{{ session('settings_status') }}</span>
                </div>
            @endif

            @forelse($panes as $pane)
                <div class="hub-card mb-4 p-5">
                    <h3 class="text-[15px] font-bold text-[var(--hub-text)]">{{ $pane['name'] }}</h3>

                    <form method="POST" action="{{ route('settings.update', $pane['slug']) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')

                        @foreach($pane['fields'] as $field)
                            @php $current = old($field->key, $field->value); @endphp
                            <div>
                                @if($field->type === \App\Data\SettingField::TYPE_BOOLEAN)
                                    <label class="flex items-center gap-3">
                                        <input type="hidden" name="{{ $field->key }}" value="0" />
                                        <input type="checkbox" name="{{ $field->key }}" value="1"
                                               @checked((bool) $current)
                                               class="h-4 w-4 rounded border-[var(--hub-line)]" />
                                        <span class="text-sm font-semibold text-[var(--hub-text)]">{{ $field->label }}</span>
                                    </label>
                                @else
                                    <label for="{{ $pane['slug'] }}-{{ $field->key }}" class="mb-1.5 block text-sm font-semibold text-[var(--hub-text)]">
                                        {{ $field->label }}
                                    </label>

                                    @if($field->type === \App\Data\SettingField::TYPE_SELECT)
                                        <select id="{{ $pane['slug'] }}-{{ $field->key }}" name="{{ $field->key }}"
                                                class="hub-input h-9 w-full px-3 text-sm">
                                            @foreach($field->options as $optValue => $optLabel)
                                                <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input id="{{ $pane['slug'] }}-{{ $field->key }}"
                                               type="{{ $field->type === \App\Data\SettingField::TYPE_NUMBER ? 'number' : 'text' }}"
                                               name="{{ $field->key }}"
                                               value="{{ $current }}"
                                               class="hub-input h-9 w-full px-3 text-sm" />
                                    @endif
                                @endif

                                @if($field->help)
                                    <p class="mt-1 text-xs text-[var(--hub-dim)]">{{ $field->help }}</p>
                                @endif

                                @error($field->key)
                                    <p class="mt-1 text-xs text-[#e0575c]">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach

                        <div class="pt-1">
                            <button type="submit" class="hub-action">Save</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="hub-empty h-[240px]">
                    <h2 class="text-sm font-bold text-[var(--hub-muted)]">No configurable modules</h2>
                    <p class="mt-1 text-sm text-[var(--hub-dim)]">Modules appear here once they offer settings.</p>
                </div>
            @endforelse
        </section>
    </div>
</x-dashboard.layout>
