<x-dashboard.layout title="3D printer inventory" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Printer/resources/assets/css/printer.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Printer/resources/assets/js/printer.js'])
    </x-slot:scripts>

    @php
        // ---- icon closure: ports the PIc SVGs from the design ----
        $PIc = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $fillDots = '<circle cx="12" cy="5" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.4" fill="currentColor" stroke="none"/>';

            $inner = match ($name) {
                'Spool' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3"/><path d="M12 3.5v3M12 17.5v3M3.5 12h3M17.5 12h3"/>',
                'Cube' => '<path d="M12 2.6 20.5 7v10L12 21.4 3.5 17V7z"/><path d="M3.5 7 12 11.4 20.5 7M12 11.4V21.4"/>',
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'Minus' => '<path d="M5 12h14"/>',
                'Pencil' => '<path d="M15.5 5.5 18.5 8.5M4 20l1-4L16 5a2 2 0 0 1 3 3L8 19z"/>',
                'Trash' => '<path d="M4 6.5h16M9 6.5V4.5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M6.5 6.5 7.5 20a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1l1-13.5"/><path d="M10 10.5v6M14 10.5v6"/>',
                'Dots' => $fillDots,
                'Check' => '<path d="M5 12.5 10 17l9-10"/>',
                'X' => '<path d="M6 6l12 12M18 6 6 18"/>',
                'Drop' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
                'Nozzle' => '<path d="M8 3.5h8l-1 7H9z"/><path d="M9 10.5 7.5 16h9L15 10.5"/><path d="M9.5 16 12 21l2.5-5"/>',
                'Layers' => '<path d="M12 3 21 8l-9 5-9-5z"/><path d="M3 13l9 5 9-5M3 16.5l9 5 9-5"/>',
                'Tube' => '<rect x="9" y="3.5" width="6" height="4" rx="1"/><path d="M9.5 7.5 9 20a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1l-.5-12.5"/><path d="M9.3 13.5h5.4"/>',
                'Wipe' => '<rect x="4" y="4" width="16" height="16" rx="2.5"/><path d="M8 9h8M8 13h8M8 17h5"/>',
                'Adjust' => '<path d="M3 12h7M14 12h7"/><circle cx="12" cy="12" r="2.4"/><path d="M5 6h4M15 6h4M5 18h4M15 18h4"/><circle cx="7" cy="6" r="2"/>',
                'Box' => '<rect x="3.5" y="6" width="17" height="13" rx="2"/><path d="M3.5 10h17M8 6V4.5h8V6"/>',
                'Alert' => '<path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/>',
                default => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3"/>',
            };

            $isDots = $name === 'Dots';
            $fill = $isDots ? 'none' : 'none';

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="'.$fill.'" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        // ---- derive a part icon from category + name ----
        $partIcon = static function (string $category, string $name): string {
            $n = mb_strtolower($name);

            return match (true) {
                str_contains($n, 'nozzle') => 'Nozzle',
                str_contains($n, 'plaat') || str_contains($n, 'pei') => 'Layers',
                str_contains($n, 'isopropan') || str_contains($n, 'reinig') || str_contains($n, 'ipa') => 'Drop',
                str_contains($n, 'doek') => 'Wipe',
                str_contains($n, 'vet') || str_contains($n, 'lijm') || str_contains($n, 'tube') || str_contains($n, 'ptfe') => 'Tube',
                default => $category === 'consumable' ? 'Drop' : 'Box',
            };
        };

        $fmt = static fn ($n): string => number_format((float) $n, 0, ',', '.');

        // ---- defensive: only emit a stored hex into a CSS background if it is a strict 6-digit hex ----
        $safeHex = static fn (?string $hex): string => is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : '#333';

        // ---- options for the modal forms ----
        $materials = ['PLA', 'PETG', 'TPU', 'ABS', 'ASA', 'Nylon', 'Silk PLA', 'Other'];
        $units = ['pcs', 'ml', 'm', 'tube', 'roll', 'set'];
        $hexPresets = ['#15171e', '#f4f3ee', '#e0a44e', '#e0575c', '#4eb0e6', '#54b896', '#8b6cd6', '#c9613f'];

        // ---- filament stats ----
        $spoolCount = $filament->count();
        $totalRemaining = $filament->sum('remaining_g');
        $lowSpools = $filament->filter(fn ($s) => $s->is_low)->count();
        $totalKg = number_format($totalRemaining / 1000, 1, ',', '.');

        // ---- parts stats ----
        $reserveParts = $parts->where('category', 'spare')->values();
        $verbruikParts = $parts->where('category', 'consumable')->values();
        $partsCount = $parts->count();
        $lowParts = $parts->filter(fn ($p) => $p->is_low)->count();
    @endphp

    <div class="pr"
         data-printer
         data-low-pct="{{ (int) app(\App\Services\Settings\SettingsStore::class)->get('printer.low_filament_pct', config('printer.low_filament_pct', 20)) }}"
         data-filament-store-url="{{ route('printer.filament.store') }}"
         data-parts-store-url="{{ route('printer.parts.store') }}">
        <div class="pr-wrap">
            {{-- ============ HEADER ============ --}}
            <div class="pr-head">
                <div class="pr-head-l">
                    <span class="pr-eyebrow">{!! $PIc('Spool', 14, 1.7, 'ic') !!} Inventory · 3D printer inventory</span>
                    <h1 class="pr-title disp" data-pr-title>Filament</h1>

                    <div class="pr-sub" data-pr-sub-filament>
                        <b class="tnum">{{ $spoolCount }}</b> spools<span class="dot">·</span>
                        <b class="tnum">{{ $totalKg }}</b> kg in stock
                        @if($lowSpools > 0)
                            <span class="dot">·</span><span class="warn"><b>{{ $lowSpools }}</b> running low</span>
                        @endif
                    </div>
                    <div class="pr-sub" data-pr-sub-onderdelen hidden>
                        <b class="tnum">{{ $partsCount }}</b> parts<span class="dot">·</span>
                        <b class="tnum">{{ $reserveParts->count() }}</b> spare, <b class="tnum">{{ $verbruikParts->count() }}</b> consumable
                        @if($lowParts > 0)
                            <span class="dot">·</span><span class="warn"><b>{{ $lowParts }}</b> low</span>
                        @endif
                    </div>
                </div>
                <div class="pr-head-r">
                    <button class="pr-btn pr-btn-primary" data-pr-add data-pr-add-filament>
                        {!! $PIc('Plus', 15, 2.2) !!} <span data-pr-add-label>New spool</span>
                    </button>
                </div>
            </div>

            {{-- ============ TABS ============ --}}
            <div class="pr-tabs">
                <button class="pr-tab on" data-pr-tab="filament">
                    {!! $PIc('Spool', 15, 1.7, 'ic') !!} Filament
                    <span class="pr-tab-count tnum">{{ $spoolCount }}</span>
                </button>
                <button class="pr-tab" data-pr-tab="onderdelen">
                    {!! $PIc('Cube', 15, 1.7, 'ic') !!} Parts
                    <span class="pr-tab-count tnum">{{ $partsCount }}</span>
                </button>
            </div>

            {{-- ============ FILAMENT PANEL ============ --}}
            <div data-pr-panel="filament">
                @if($spoolCount > 0)
                    <div class="pr-grid">
                        @foreach($filament as $spool)
                            @php
                                $total = (int) $spool->total_weight_g;
                                $remaining = (int) $spool->remaining_g;
                                $pct = (int) $spool->remaining_pct;
                                $low = $spool->is_low;
                                $barWidth = max($pct, $remaining > 0 ? 2 : 0);
                            @endphp
                            <div class="pr-fcard {{ $low ? 'low' : '' }}"
                                 data-pr-spool="{{ $spool->id }}"
                                 data-pr-total="{{ $total }}"
                                 data-pr-remaining="{{ $remaining }}"
                                 data-pr-material="{{ $spool->material }}"
                                 data-pr-color="{{ $spool->color_name }}"
                                 data-pr-hex="{{ $spool->color_hex }}"
                                 data-pr-brand="{{ $spool->brand }}"
                                 data-pr-price="{{ $spool->purchase_price }}"
                                 data-pr-store="{{ $spool->purchase_store }}"
                                 data-pr-bought="{{ $spool->purchased_at?->toDateString() }}"
                                 data-pr-update-url="{{ route('printer.filament.update', $spool) }}"
                                 data-pr-delete-url="{{ route('printer.filament.destroy', $spool) }}"
                                 data-pr-adjust-url="{{ route('printer.filament.adjust', $spool) }}">
                                <div class="pr-fhead">
                                    <span class="pr-swatch" style="background: {{ $safeHex($spool->color_hex) }}"></span>
                                    <div class="pr-fnames">
                                        <div class="pr-fname">{{ $spool->material }} · {{ $spool->color_name }}</div>
                                        <div class="pr-fmeta">
                                            @if($spool->brand)
                                                <span class="pr-brand">{{ $spool->brand }}</span>
                                            @endif
                                            @if($spool->color_hex)
                                                <span class="pr-hex mono">{{ $spool->color_hex }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="pr-menuwrap" data-pr-menuwrap>
                                        <button class="pr-kebab" data-pr-kebab aria-label="Actions">{!! $PIc('Dots', 18) !!}</button>
                                        <div class="pr-menu" data-pr-menu hidden>
                                            <button data-pr-edit-spool>{!! $PIc('Pencil', 15, 1.7, 'ic') !!} Edit spool</button>
                                            <button data-pr-adjust-open>{!! $PIc('Adjust', 15, 1.7, 'ic') !!} Use / refill</button>
                                            <hr />
                                            <button class="danger" data-pr-delete-spool>{!! $PIc('Trash', 15, 1.7, 'ic') !!} Delete</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="pr-stockrow">
                                    <div class="pr-bar"><div class="pr-bar-fill" data-pr-bar style="width: {{ $barWidth }}%"></div></div>
                                    <div class="pr-bar-line">
                                        <span class="pr-stock tnum"><b data-pr-remaining-text>{{ $fmt($remaining) }}</b> / <span data-pr-total-text>{{ $fmt($total) }}</span> g</span>
                                        <span data-pr-stockmeta>
                                            @if($low)
                                                <span class="pr-lowpill">{!! $PIc('Alert', 12) !!} Running low · {{ $pct }}%</span>
                                            @else
                                                <span class="pr-pct tnum">{{ $pct }}%</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="pr-ffoot" data-pr-ffoot>
                                    <button class="pr-adjbtn" data-pr-adjust-open>
                                        {!! $PIc('Adjust', 15, 1.7, 'ic') !!} Use / refill
                                    </button>
                                </div>

                                {{-- inline grams adjuster (hidden until opened) --}}
                                <div class="pr-adj" data-pr-adj hidden>
                                    <div class="pr-adj-top">
                                        <span class="pr-adj-lab">Remaining weight</span>
                                        <span class="pr-adj-num tnum"><span data-pr-adj-remaining>{{ $fmt($remaining) }}</span><span class="u">/ <span data-pr-adj-total>{{ $fmt($total) }}</span> g</span></span>
                                    </div>
                                    <div class="pr-adj-grid">
                                        <div class="pr-chips">
                                            <button class="pr-chip minus" data-pr-delta="-100">−100</button>
                                            <button class="pr-chip minus" data-pr-delta="-50">−50</button>
                                            <button class="pr-chip minus" data-pr-delta="-10">−10</button>
                                        </div>
                                        <div class="pr-chips">
                                            <button class="pr-chip plus" data-pr-delta="10">+10</button>
                                            <button class="pr-chip plus" data-pr-delta="50">+50</button>
                                            <button class="pr-chip plus" data-pr-delta="100">+100</button>
                                        </div>
                                        <span class="pr-adj-sep"></span>
                                        <div class="pr-adj-actions">
                                            <button class="pr-mini" data-pr-fill>Fill up</button>
                                            <button class="pr-mini done" data-pr-adjust-close>{!! $PIc('Check', 14, 2.4) !!} Done</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="pr-state">
                        <span class="pr-state-ico">{!! $PIc('Spool', 28) !!}</span>
                        <div class="pr-state-title">No filament yet</div>
                        <div class="pr-state-sub">
                            Keep track of your spools — material, color and remaining weight. The hub warns you as soon as a spool
                            drops below the threshold, so you can reorder in time.
                        </div>
                        <div class="pr-state-actions">
                            <button class="pr-btn pr-btn-primary" data-pr-add data-pr-add-filament>{!! $PIc('Plus', 15, 2.2) !!} Add spool</button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ============ ONDERDELEN PANEL ============ --}}
            <div data-pr-panel="onderdelen" hidden>
                @if($partsCount > 0)
                    @php
                        $groups = [
                            ['key' => 'reserve', 'label' => 'Spare parts', 'items' => $reserveParts],
                            ['key' => 'verbruik', 'label' => 'Consumables', 'items' => $verbruikParts],
                        ];
                    @endphp
                    @foreach($groups as $group)
                        @if($group['items']->count() > 0)
                            <div class="pr-group">
                                <div class="pr-group-head">
                                    <span class="pr-group-label">{{ $group['label'] }}</span>
                                    <span class="pr-group-count tnum">{{ $group['items']->count() }}</span>
                                    <span class="pr-group-rule"></span>
                                </div>
                                <div class="pr-plist">
                                    @foreach($group['items'] as $part)
                                        @php
                                            $count = $part->quantity;
                                            $countDisplay = floor($count) === (float) $count ? (int) $count : $count;
                                            $step = $part->unit === 'ml' ? 50 : 1;
                                            $low = $part->is_low;
                                        @endphp
                                        <div class="pr-prow {{ $low ? 'low' : '' }}"
                                             data-pr-part="{{ $part->id }}"
                                             data-pr-count="{{ $countDisplay }}"
                                             data-pr-step="{{ $step }}"
                                             data-pr-name="{{ $part->name }}"
                                             data-pr-note="{{ $part->notes }}"
                                             data-pr-group="{{ $part->category === 'spare' ? 'reserve' : 'verbruik' }}"
                                             data-pr-unit="{{ $part->unit }}"
                                             data-pr-min="{{ $part->low_threshold }}"
                                             data-pr-update-url="{{ route('printer.parts.update', $part) }}"
                                             data-pr-delete-url="{{ route('printer.parts.destroy', $part) }}"
                                             data-pr-adjust-url="{{ route('printer.parts.adjust', $part) }}">
                                            <span class="pr-pico">{!! $PIc($partIcon($part->category, $part->name), 20) !!}</span>
                                            <div class="pr-pbody">
                                                <div class="pr-pname">{{ $part->name }}</div>
                                                @if($part->notes)
                                                    <div class="pr-pnote">{{ $part->notes }}</div>
                                                @endif
                                            </div>
                                            <div class="pr-pright">
                                                <span data-pr-lowtag>
                                                    @if($low)
                                                        <span class="pr-lowtag">{!! $PIc('Alert', 11) !!} Low</span>
                                                    @endif
                                                </span>
                                                <div class="pr-stepper">
                                                    <button class="pr-step" data-pr-step-minus aria-label="Less" @disabled($countDisplay <= 0)>{!! $PIc('Minus', 15, 2.2) !!}</button>
                                                    <span class="pr-count tnum"><span data-pr-count-text>{{ $fmt($countDisplay) }}</span><span class="u">{{ $part->unit }}</span></span>
                                                    <button class="pr-step" data-pr-step-plus aria-label="More">{!! $PIc('Plus', 15, 2.2) !!}</button>
                                                </div>
                                                <button class="pr-pedit" data-pr-edit-part aria-label="Edit">{!! $PIc('Pencil', 16) !!}</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="pr-state">
                        <span class="pr-state-ico">{!! $PIc('Cube', 28) !!}</span>
                        <div class="pr-state-title">No parts yet</div>
                        <div class="pr-state-sub">
                            Track your spare parts and consumables — nozzles, build plates, isopropyl alcohol.
                            See at a glance what's running low.
                        </div>
                        <div class="pr-state-actions">
                            <button class="pr-btn pr-btn-primary" data-pr-add data-pr-add-part>{!! $PIc('Plus', 15, 2.2) !!} Add part</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ FILAMENT MODAL ============ --}}
        <div class="pr-modal-backdrop" data-pr-modal="filament" hidden>
            <div class="pr-modal" role="dialog" aria-label="Spool">
                <form data-pr-filament-form>
                    <div class="pr-modal-head">
                        <span class="pr-mh-ico">{!! $PIc('Spool', 20) !!}</span>
                        <div class="pr-mh-l">
                            <div class="pr-modal-title" data-pr-modal-title>New spool</div>
                            <div class="pr-modal-desc" data-pr-modal-desc>Add a filament spool to your inventory</div>
                        </div>
                        <button type="button" class="pr-modal-close" data-pr-modal-close aria-label="Close">{!! $PIc('X', 17) !!}</button>
                    </div>
                    <div class="pr-modal-body">
                        <div class="pr-preview">
                            <span class="pr-swatch" data-pr-prev-swatch style="background: #15171e"></span>
                            <div class="pr-preview-b">
                                <div class="pr-preview-name" data-pr-prev-name>PLA</div>
                                <div class="pr-preview-sub" data-pr-prev-sub>Brand — · 1.000 / 1.000 g · 100%</div>
                                <div class="pr-bar"><div class="pr-bar-fill" data-pr-prev-bar style="width: 100%"></div></div>
                            </div>
                        </div>

                        <div class="pr-field">
                            <label class="pr-label">Material</label>
                            <div class="pr-seg" data-pr-seg="material">
                                @foreach($materials as $m)
                                    <button type="button" class="pr-seg-btn {{ $loop->first ? 'on' : '' }}" data-pr-seg-val="{{ $m }}">{{ $m }}</button>
                                @endforeach
                            </div>
                            <input type="hidden" name="material" value="PLA" data-pr-field="material" />
                        </div>

                        <div class="pr-row2">
                            <div class="pr-field">
                                <label class="pr-label">Color name</label>
                                <input class="pr-input" name="color_name" data-pr-field="color" placeholder="e.g. Galaxy Black" />
                            </div>
                            <div class="pr-field">
                                <label class="pr-label">Brand</label>
                                <input class="pr-input" name="brand" data-pr-field="brand" placeholder="e.g. Polymaker" />
                            </div>
                        </div>

                        <div class="pr-field">
                            <label class="pr-label">Color (hex)</label>
                            <div class="pr-hexrow">
                                <span class="pr-hex-preview" data-pr-hex-preview style="background: #15171e"></span>
                                <input class="pr-input pr-hex-input mono" name="color_hex" data-pr-field="hex" value="#15171e" placeholder="#15171e" />
                            </div>
                            <div class="pr-swatches" data-pr-swatches>
                                @foreach($hexPresets as $h)
                                    <button type="button" class="pr-swatch-opt {{ $loop->first ? 'on' : '' }}" data-pr-hex-val="{{ $h }}" style="background: {{ $h }}" aria-label="{{ $h }}"></button>
                                @endforeach
                            </div>
                        </div>

                        <div class="pr-row2">
                            <div class="pr-field">
                                <label class="pr-label">Total weight</label>
                                <div class="pr-input-wrap">
                                    <input class="pr-input suffix tnum" type="number" name="total_weight_g" data-pr-field="total" value="1000" />
                                    <span class="pr-input-suffix">g</span>
                                </div>
                            </div>
                            <div class="pr-field">
                                <label class="pr-label">Remaining weight</label>
                                <div class="pr-input-wrap">
                                    <input class="pr-input suffix tnum" type="number" name="remaining_g" data-pr-field="remaining" value="1000" />
                                    <span class="pr-input-suffix">g</span>
                                </div>
                            </div>
                        </div>

                        <div class="pr-divider"><span>Optional</span><span class="rule"></span></div>

                        <div class="pr-row3">
                            <div class="pr-field">
                                <label class="pr-label">Purchase price</label>
                                <div class="pr-input-prefix-wrap">
                                    <span class="pr-input-prefix">€</span>
                                    <input class="pr-input prefix tnum" type="number" step="0.01" name="purchase_price" data-pr-field="price" placeholder="0.00" />
                                </div>
                            </div>
                            <div class="pr-field">
                                <label class="pr-label">Store</label>
                                <input class="pr-input" name="purchase_store" data-pr-field="store" placeholder="e.g. 123-3D" />
                            </div>
                            <div class="pr-field">
                                <label class="pr-label">Date</label>
                                <input class="pr-input" type="date" name="purchased_at" data-pr-field="bought" />
                            </div>
                        </div>
                    </div>
                    <div class="pr-modal-foot">
                        <button type="button" class="pr-del" data-pr-modal-delete hidden>{!! $PIc('Trash', 15) !!} Delete</button>
                        <span class="grow"></span>
                        <button type="button" class="pr-btn pr-btn-ghost" data-pr-modal-close>Cancel</button>
                        <button type="submit" class="pr-btn pr-btn-primary">{!! $PIc('Check', 15, 2.2) !!} <span data-pr-submit-label>Add spool</span></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============ PART MODAL ============ --}}
        <div class="pr-modal-backdrop" data-pr-modal="part" hidden>
            <div class="pr-modal" role="dialog" aria-label="Part">
                <form data-pr-part-form>
                    <div class="pr-modal-head">
                        <span class="pr-mh-ico">{!! $PIc('Cube', 20) !!}</span>
                        <div class="pr-mh-l">
                            <div class="pr-modal-title" data-pr-modal-title>New part</div>
                            <div class="pr-modal-desc" data-pr-modal-desc>Add a spare part or consumable</div>
                        </div>
                        <button type="button" class="pr-modal-close" data-pr-modal-close aria-label="Close">{!! $PIc('X', 17) !!}</button>
                    </div>
                    <div class="pr-modal-body">
                        <div class="pr-field">
                            <label class="pr-label">Name</label>
                            <input class="pr-input" name="name" data-pr-field="name" placeholder="e.g. 0.4 mm nozzle" />
                        </div>

                        <div class="pr-field">
                            <label class="pr-label">Description <span class="opt">optional</span></label>
                            <input class="pr-input" name="notes" data-pr-field="note" placeholder="e.g. Brass · standard" />
                        </div>

                        <div class="pr-field">
                            <label class="pr-label">Group</label>
                            <div class="pr-seg" data-pr-seg="group">
                                <button type="button" class="pr-seg-btn on" data-pr-seg-val="reserve">Spare part</button>
                                <button type="button" class="pr-seg-btn" data-pr-seg-val="verbruik">Consumable</button>
                            </div>
                            <input type="hidden" name="group" value="reserve" data-pr-field="group" />
                        </div>

                        <div class="pr-row2">
                            <div class="pr-field">
                                <label class="pr-label">Quantity</label>
                                <input class="pr-input tnum" type="number" name="quantity" data-pr-field="count" value="1" />
                            </div>
                            <div class="pr-field">
                                <label class="pr-label">Unit</label>
                                <select class="pr-select" name="unit" data-pr-field="unit">
                                    @foreach($units as $u)
                                        <option value="{{ $u }}">{{ $u }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="pr-field">
                            <label class="pr-label">Warn below <span class="opt">threshold for "low"</span></label>
                            <div class="pr-input-wrap">
                                <input class="pr-input suffix tnum" type="number" name="low_threshold" data-pr-field="min" value="1" />
                                <span class="pr-input-suffix" data-pr-min-suffix>pcs</span>
                            </div>
                        </div>
                    </div>
                    <div class="pr-modal-foot">
                        <button type="button" class="pr-del" data-pr-modal-delete hidden>{!! $PIc('Trash', 15) !!} Delete</button>
                        <span class="grow"></span>
                        <button type="button" class="pr-btn pr-btn-ghost" data-pr-modal-close>Cancel</button>
                        <button type="submit" class="pr-btn pr-btn-primary">{!! $PIc('Check', 15, 2.2) !!} <span data-pr-submit-label>Add part</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard.layout>
