<x-dashboard.layout title="Recepten" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Recipes/resources/assets/css/recepten.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Recipes/resources/assets/js/recepten.js'])
    </x-slot:scripts>

    @php
        // ---- icon closure: ports the RIc SVGs from recepten-core.jsx ----
        $RIc = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $inner = match ($name) {
                'Pot' => '<path d="M4 9h16v5a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6z"/><path d="M2.5 9h19M7 9 6 5.5M17 9l1-3.5"/>',
                'Bowl' => '<path d="M3 10.5h18a8 8 0 0 1-8 8h-2a8 8 0 0 1-8-8z"/><path d="M9 6.5c0-1.5 1.2-2 1.2-3M13 6.5c0-1.5 1.2-2 1.2-3"/>',
                'Pan' => '<circle cx="10" cy="13" r="6.5"/><path d="M16.2 11 22 9"/>',
                'Wok' => '<path d="M3 11h18a9 9 0 0 1-9 8 9 9 0 0 1-9-8z"/><path d="M21 11l1.5-1.5M3 11 1.5 9.5"/>',
                'Leaf' => '<path d="M4 20C4 11 11 4 20 4c0 9-7 16-16 16z"/><path d="M4 20c4.5-5 8-7.5 12-9"/>',
                'Fish' => '<path d="M3 12c4-5 11-5 15 0-4 5-11 5-15 0z"/><path d="M18 12c1.5-1.5 3-1.5 3-1.5s0 3-3 3M8.5 11h.01"/>',
                'Clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'Euro' => '<path d="M16.5 6.5A6 6 0 1 0 16.5 17.5"/><path d="M4 10.5h8M4 13.5h7"/>',
                'Store' => '<path d="M4 9.5 5 4.5h14l1 5"/><path d="M4 9.5a2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0"/><path d="M5 11.5V20h14v-8.5"/>',
                'Sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/>',
                'Refresh' => '<path d="M20 11a8 8 0 1 0-.6 4"/><path d="M20 4v5h-5"/>',
                'Check' => '<path d="M5 12.5 10 17l9-10"/>',
                'CheckSm' => '<path d="M4 12l5 5L20 6"/>',
                'X' => '<path d="M6 6l12 12M18 6 6 18"/>',
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'ArrowR' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
                'ArrowL' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
                'Alert' => '<path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/>',
                'Tag' => '<path d="M3.5 12.5 11 5h7.5V12.5L11 20z"/><circle cx="15" cy="8.5" r="1.4" fill="currentColor" stroke="none"/>',
                'Cart' => '<circle cx="9.5" cy="20" r="1.4" fill="currentColor" stroke="none"/><circle cx="17.5" cy="20" r="1.4" fill="currentColor" stroke="none"/><path d="M2.5 4h2.2l2.1 11.2a1.5 1.5 0 0 0 1.5 1.3h8.4a1.5 1.5 0 0 0 1.5-1.2L20.5 8H6"/>',
                'List' => '<path d="M8 6.5h12M8 12h12M8 17.5h12"/><circle cx="4" cy="6.5" r="1.1" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.1" fill="currentColor" stroke="none"/><circle cx="4" cy="17.5" r="1.1" fill="currentColor" stroke="none"/>',
                'Calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
                'Users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6M17.5 19a5.5 5.5 0 0 0-2.5-4.6"/>',
                'Flame' => '<path d="M12 3s5 4.5 5 9a5 5 0 0 1-10 0c0-1.8.8-3 .8-3 .5 1.2 1.7 1.6 1.7 1.6C9 8 12 3 12 3z"/>',
                'Info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
                default => '<path d="M3 10.5h18a8 8 0 0 1-8 8h-2a8 8 0 0 1-8-8z"/><path d="M9 6.5c0-1.5 1.2-2 1.2-3M13 6.5c0-1.5 1.2-2 1.2-3"/>',
            };

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        // ---- derive a dish icon from the recipe title ----
        $dishIcon = static function (string $title): string {
            $t = mb_strtolower($title);

            return match (true) {
                str_contains($t, 'wok') || str_contains($t, 'roerbak') || str_contains($t, 'teriyaki') => 'Wok',
                str_contains($t, 'zalm') || str_contains($t, 'vis') || str_contains($t, 'tonijn') => 'Fish',
                str_contains($t, 'curry') || str_contains($t, 'bowl') || str_contains($t, 'soep') => 'Bowl',
                str_contains($t, 'wrap') || str_contains($t, 'taco') || str_contains($t, 'gehakt') || str_contains($t, 'chili') => 'Flame',
                str_contains($t, 'salade') || str_contains($t, 'veggie') || str_contains($t, 'vega') => 'Leaf',
                str_contains($t, 'pasta') || str_contains($t, 'pesto') || str_contains($t, 'risotto') || str_contains($t, 'stoof') => 'Pot',
                default => 'Bowl',
            };
        };

        $euro = static fn ($n): string => '€'.number_format((float) $n, 2, ',', '.');

        // ---- store metadata ----
        $storeMeta = [
            'ah' => ['short' => 'AH', 'label' => 'Albert Heijn'],
            'lidl' => ['short' => 'Lidl', 'label' => 'Lidl'],
        ];
        $storeShort = static fn (?string $key) => $storeMeta[mb_strtolower((string) $key)]['short'] ?? mb_strtoupper((string) $key);
        $storeLabel = static fn (?string $key) => $storeMeta[mb_strtolower((string) $key)]['label'] ?? mb_strtoupper((string) $key);

        // ---- normalize state ----
        $recipes = collect($recipes);
        $offers = collect($offers);
        $storesFetched = collect($stores_fetched)->map(fn ($s) => mb_strtolower((string) $s));
        $storesFailed = collect($stores_failed)->map(fn ($s) => mb_strtolower((string) $s));

        $recipeCount = $recipes->count();
        $dealCount = $offers->count();
        $hasRecipes = $recipeCount > 0;
        $hasOffers = $dealCount > 0;
        $isFallback = (bool) $is_fallback;
        $hasFailed = $storesFailed->isNotEmpty();

        $avgMinutes = $hasRecipes
            ? (int) round($recipes->avg(fn ($r) => (int) ($r['time_minutes'] ?? 0)))
            : 0;

        // The stores we actually fetched, for the source strip (union of fetched + failed).
        $allStores = $storesFetched->merge($storesFailed)->unique();
        if ($allStores->isEmpty()) {
            $allStores = collect(['ah', 'lidl']);
        }
        $allStores = $allStores->sort()->values();

        $generatedLabel = $generated_at
            ? \Carbon\CarbonImmutable::parse($generated_at)->setTimezone(config('app.timezone', 'UTC'))->locale('nl')->isoFormat('dd D MMM · HH:mm')
            : null;

        // ---- offers grouped per store, with "used in menu" flag ----
        $usedProductNames = $recipes
            ->flatMap(fn ($r) => collect($r['ingredients'] ?? [])->pluck('name'))
            ->filter()
            ->map(fn ($n) => mb_strtolower(trim((string) $n)))
            ->unique();

        $isUsed = static function (string $product) use ($usedProductNames): bool {
            $p = mb_strtolower(trim($product));
            foreach ($usedProductNames as $name) {
                if ($name !== '' && (str_contains($p, $name) || str_contains($name, $p))) {
                    return true;
                }
            }

            return false;
        };

        $offersByStore = $offers->groupBy(fn ($o) => mb_strtolower((string) $o['store']));

        // Which overview/deals tab to show first.
        $initialTab = (! $hasRecipes && $hasOffers) || $isFallback ? 'aanbiedingen' : 'recepten';
    @endphp

    <div class="rc"
         data-recipes
         data-initial-tab="{{ $initialTab }}"
         data-generate-url="{{ route('recipes.generate') }}"
         data-week-key="{{ $week_key }}">
        <div class="rc-wrap">
            {{-- ============ HEADER ============ --}}
            <div class="rc-head">
                <div class="rc-head-l">
                    <span class="rc-eyebrow">{!! $RIc('Sparkle', 14, 1.7, 'ic') !!} Weekmenu · {{ $week_key }}</span>
                    <h1 class="rc-title disp">Recepten</h1>
                    <div class="rc-sub">
                        @if($isFallback)
                            <span class="warn">AI niet beschikbaar</span><span class="dot">·</span>ruwe aanbiedingen van <b>{{ $storesFetched->map($storeShort)->join(' + ') ?: 'AH + Lidl' }}</b>
                        @elseif($hasFailed && $hasRecipes)
                            <b class="tnum">{{ $recipeCount }}</b> recepten<span class="dot">·</span>alleen <b>{{ $storesFetched->map($storeLabel)->join(' + ') }}</b><span class="dot">·</span><span class="warn">{{ $storesFailed->map($storeLabel)->join(', ') }} mislukt</span>
                        @elseif($hasRecipes)
                            <b class="tnum">{{ $recipeCount }}</b> recepten<span class="dot">·</span><b>{{ $storesFetched->map($storeShort)->join(' + ') ?: 'AH + Lidl' }}</b> opgehaald<span class="dot">·</span>gem. <b class="tnum">{{ $avgMinutes }} min</b>
                        @else
                            Het weekmenu wordt elke vrijdagavond samengesteld
                        @endif
                    </div>
                </div>
                <div class="rc-head-r">
                    <button class="rc-btn rc-btn-primary" data-rc-generate>
                        {!! $RIc('Refresh', 15, 1.7, 'ic') !!} Opnieuw genereren
                    </button>
                </div>
            </div>

            {{-- ============ TABS ============ --}}
            <div class="rc-tabs">
                <button class="rc-tab {{ $initialTab === 'recepten' ? 'on' : '' }}" data-rc-tab="recepten">
                    {!! $RIc('Pot', 15, 1.7, 'ic') !!} Recepten
                    <span class="rc-tab-count tnum">{{ $recipeCount }}</span>
                </button>
                <button class="rc-tab {{ $initialTab === 'aanbiedingen' ? 'on' : '' }}" data-rc-tab="aanbiedingen">
                    {!! $RIc('Tag', 15, 1.7, 'ic') !!} Aanbiedingen
                    <span class="rc-tab-count tnum">{{ $dealCount }}</span>
                </button>
            </div>

            {{-- ============ GENERATING (JS busy-state) ============ --}}
            <div class="rc-gen" data-rc-gen hidden>
                {!! $RIc('Sparkle', 18, 1.7, 'ic') !!}
                <div>
                    <div class="rc-gen-tx">Recepten genereren…</div>
                    <div class="rc-gen-sub">Aanbiedingen van Albert Heijn &amp; Lidl ophalen en combineren tot een weekmenu.</div>
                </div>
            </div>

            {{-- ============ RECEPTEN TAB ============ --}}
            <div data-rc-panel="recepten" {{ $initialTab === 'recepten' ? '' : 'hidden' }}>
                {{-- OVERVIEW --}}
                <div data-rc-overview>
                    @if($isFallback)
                        <div class="rc-banner info">
                            <span class="rc-banner-ico">{!! $RIc('Sparkle', 18) !!}</span>
                            <div class="rc-banner-b">
                                <div class="rc-banner-title">AI-receptgeneratie niet beschikbaar</div>
                                <div class="rc-banner-sub">
                                    Het taalmodel kon niet worden bereikt, dus er zijn <b>geen recepten</b> gegenereerd.
                                    Onder het tabblad <b>Aanbiedingen</b> staan de ruwe aanbiedingen van beide winkels zodat je zelf kunt kiezen.
                                </div>
                            </div>
                            <button class="rc-banner-act" data-rc-generate>
                                {!! $RIc('Refresh', 13, 1.7, 'ic') !!} Opnieuw
                            </button>
                        </div>

                        @include('recipes::partials.source', ['stores' => $allStores, 'storesFailed' => $storesFailed, 'generatedLabel' => $generatedLabel, 'RIc' => $RIc, 'storeMeta' => $storeMeta, 'storeShort' => $storeShort, 'storeLabel' => $storeLabel])

                        @include('recipes::partials.deals', ['offersByStore' => $offersByStore, 'allStores' => $allStores, 'storesFailed' => $storesFailed, 'RIc' => $RIc, 'storeMeta' => $storeMeta, 'storeShort' => $storeShort, 'storeLabel' => $storeLabel, 'euro' => $euro, 'isUsed' => $isUsed])
                    @elseif($hasRecipes)
                        @if($hasFailed)
                            <div class="rc-banner warn">
                                <span class="rc-banner-ico">{!! $RIc('Alert', 18) !!}</span>
                                <div class="rc-banner-b">
                                    <div class="rc-banner-title">Eén winkel niet opgehaald — {{ $storesFailed->map($storeLabel)->join(', ') }}</div>
                                    <div class="rc-banner-sub">
                                        De aanbiedingen van <b>{{ $storesFailed->map($storeLabel)->join(', ') }}</b> konden niet worden opgehaald.
                                        Het weekmenu is samengesteld met alleen de aanbiedingen van <b>{{ $storesFetched->map($storeLabel)->join(' + ') }}</b> — daardoor staan er mogelijk minder recepten klaar.
                                    </div>
                                </div>
                                <button class="rc-banner-act" data-rc-generate>
                                    {!! $RIc('Refresh', 13, 1.7, 'ic') !!} Opnieuw
                                </button>
                            </div>
                        @endif

                        @include('recipes::partials.source', ['stores' => $allStores, 'storesFailed' => $storesFailed, 'generatedLabel' => $generatedLabel, 'RIc' => $RIc, 'storeMeta' => $storeMeta, 'storeShort' => $storeShort, 'storeLabel' => $storeLabel])

                        <div class="rc-grid">
                            @foreach($recipes as $recipe)
                                @php
                                    $ingredients = collect($recipe['ingredients'] ?? []);
                                    $offerIngredients = $ingredients->filter(fn ($i) => (bool) ($i['on_offer'] ?? false));
                                    $icon = $dishIcon($recipe['title']);
                                @endphp
                                <button class="rc-card" data-rc-recipe="{{ $recipe['id'] }}">
                                    <div class="rc-thumb">
                                        <span class="rc-thumb-ico">{!! $RIc($icon, 40, 1.5) !!}</span>
                                        <div class="rc-thumb-badges">
                                            <span class="rc-time-badge">{!! $RIc('Clock', 13, 1.7, 'ic') !!} {{ (int) ($recipe['time_minutes'] ?? 0) }} min</span>
                                            <span class="rc-cost-badge tnum">{{ $euro($recipe['estimated_cost'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                    <div class="rc-card-body">
                                        <div class="rc-card-name-wrap">
                                            <div class="rc-card-name">{{ $recipe['title'] }}</div>
                                            @if(($recipe['description'] ?? '') !== '')
                                                <div class="rc-card-desc">{{ $recipe['description'] }}</div>
                                            @endif
                                        </div>

                                        @if($offerIngredients->isNotEmpty())
                                            <div class="rc-offer-lab">In de aanbieding <span class="rule"></span></div>
                                            <div class="rc-offers">
                                                @foreach($offerIngredients as $ing)
                                                    <span class="rc-offer">
                                                        {{ $ing['name'] }}
                                                        @if(! empty($ing['store']))
                                                            <span class="store {{ mb_strtolower($ing['store']) }}">{{ $storeShort($ing['store']) }}</span>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="rc-card-foot">
                                            <span class="rc-card-ingr"><b class="tnum">{{ $ingredients->count() }}</b> ingrediënten · {{ (int) ($recipe['servings'] ?? 0) }} pers.</span>
                                            <span class="rc-card-go">Bekijk recept {!! $RIc('ArrowR', 15) !!}</span>
                                        </div>
                                    </div>

                                    {{-- full recipe payload for client-side detail rendering --}}
                                    <script type="application/json" data-rc-recipe-data>{!! json_encode([
                                        'id' => $recipe['id'],
                                        'title' => $recipe['title'],
                                        'description' => $recipe['description'] ?? '',
                                        'servings' => (int) ($recipe['servings'] ?? 0),
                                        'time_minutes' => (int) ($recipe['time_minutes'] ?? 0),
                                        'estimated_cost' => $recipe['estimated_cost'] ?? null,
                                        'icon' => $icon,
                                        'ingredients' => $recipe['ingredients'] ?? [],
                                        'steps' => $recipe['steps'] ?? [],
                                        'shopping_list' => $recipe['shopping_list'] ?? [],
                                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                                </button>
                            @endforeach
                        </div>
                    @else
                        {{-- LEEG --}}
                        <div class="rc-state">
                            <span class="rc-state-ico">{!! $RIc('Calendar', 26) !!}</span>
                            <div class="rc-state-title">Nog geen weekmenu</div>
                            <div class="rc-state-sub">
                                Het nieuwe weekmenu wordt elke <b>vrijdagavond</b> automatisch samengesteld op basis van de
                                aanbiedingen bij Albert Heijn en Lidl. Je kunt het ook nu al handmatig genereren.
                            </div>
                            <div class="rc-state-actions">
                                <button class="rc-btn rc-btn-primary" data-rc-generate>
                                    {!! $RIc('Sparkle', 15) !!} Nu genereren
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- DETAIL (filled + toggled by JS) --}}
                <div data-rc-detail hidden></div>
            </div>

            {{-- ============ AANBIEDINGEN TAB ============ --}}
            <div data-rc-panel="aanbiedingen" {{ $initialTab === 'aanbiedingen' ? '' : 'hidden' }}>
                @if($hasOffers || $allStores->isNotEmpty())
                    @include('recipes::partials.source', ['stores' => $allStores, 'storesFailed' => $storesFailed, 'generatedLabel' => $generatedLabel, 'RIc' => $RIc, 'storeMeta' => $storeMeta, 'storeShort' => $storeShort, 'storeLabel' => $storeLabel])

                    @include('recipes::partials.deals', ['offersByStore' => $offersByStore, 'allStores' => $allStores, 'storesFailed' => $storesFailed, 'RIc' => $RIc, 'storeMeta' => $storeMeta, 'storeShort' => $storeShort, 'storeLabel' => $storeLabel, 'euro' => $euro, 'isUsed' => $isUsed])
                @else
                    <div class="rc-state">
                        <span class="rc-state-ico">{!! $RIc('Tag', 26) !!}</span>
                        <div class="rc-state-title">Nog geen aanbiedingen</div>
                        <div class="rc-state-sub">
                            Er zijn deze week nog geen aanbiedingen opgehaald. Genereer het weekmenu om de
                            nieuwste aanbiedingen van Albert Heijn en Lidl binnen te halen.
                        </div>
                        <div class="rc-state-actions">
                            <button class="rc-btn rc-btn-primary" data-rc-generate>
                                {!! $RIc('Refresh', 15, 1.7, 'ic') !!} Nu genereren
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dashboard.layout>
