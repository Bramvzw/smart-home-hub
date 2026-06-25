@use(\Modules\Deals\Support\SafeUrl)
<x-dashboard.layout title="Dealtracker" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Deals/resources/assets/css/dealtracker.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Deals/resources/assets/js/dealtracker.js'])
    </x-slot:scripts>

    @php
        // ---- icon closure: ports the DtIc SVGs from dt-core.jsx ----
        $dtIc = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $fill = in_array($name, ['BookmarkF', 'Dots'], true);
            $inner = match ($name) {
                'Tag' => '<path d="M3 12.5V5.5A2.5 2.5 0 0 1 5.5 3h7a2 2 0 0 1 1.4.6l6.5 6.5a2 2 0 0 1 0 2.8l-7 7a2 2 0 0 1-2.8 0l-6.5-6.5A2 2 0 0 1 3 12.5z"/><circle cx="8" cy="8" r="1.4"/>',
                'Bell' => '<path d="M18 9a6 6 0 0 0-12 0c0 5-2.5 6.5-2.5 6.5h17S18 14 18 9z"/><path d="M10.3 19a2 2 0 0 0 3.4 0"/>',
                'TrendDown' => '<path d="M3 7l7 7 4-4 7 7"/><path d="M21 12v5h-5"/>',
                'TrendUp' => '<path d="M3 17l7-7 4 4 7-7"/><path d="M21 12V7h-5"/>',
                'ArrowDown' => '<path d="M12 5v14M6 13l6 6 6-6"/>',
                'Search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/>',
                'Check' => '<path d="M5 12.5 10 17l9-10"/>',
                'CheckSm' => '<path d="M4 12l5 5L20 6"/>',
                'CheckCircle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l2.6 2.6L16 9"/>',
                'X' => '<path d="M6 6l12 12M18 6 6 18"/>',
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'ArrowR' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
                'Alert' => '<path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/>',
                'Plug' => '<path d="M12 22v-5"/><path d="M9 8V3M15 8V3"/><path d="M6 8h12v3a6 6 0 0 1-12 0z"/>',
                'Clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'Refresh' => '<path d="M20 11a8 8 0 1 0-.6 4"/><path d="M20 4v5h-5"/>',
                'Box' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
                'Trash' => '<path d="M4 7h16M9 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 5v2M6 7l1 13a1.5 1.5 0 0 0 1.5 1.4h7A1.5 1.5 0 0 0 17 20L18 7"/>',
                'Pause' => '<path d="M9 5v14M15 5v14"/>',
                'Undo' => '<path d="M9 7 4 12l5 5"/><path d="M4 12h11a5 5 0 0 1 0 10h-3"/>',
                'Cog' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.5v2.6M12 18.9v2.6M21.5 12h-2.6M5.1 12H2.5M18.7 5.3l-1.8 1.8M7.1 16.9l-1.8 1.8M18.7 18.7l-1.8-1.8M7.1 7.1 5.3 5.3"/>',
                'Dots' => '<circle cx="5" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.4" fill="currentColor" stroke="none"/>',
                'Info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
                'Shield' => '<path d="M12 3 5 6v5c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="M9.2 12l2 2 3.6-3.8"/>',
                default => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
            };

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="'.($fill ? 'currentColor' : 'none').'" stroke="'.($fill ? 'none' : 'currentColor').'" stroke-width="'.($fill ? 0 : $stroke).'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        $euro = static fn ($n): string => '€'.number_format((float) $n, 2, ',', '.');

        // ---- retailer metadata ----
        $storeMeta = [
            'bol' => ['label' => 'bol.com', 'cls' => 'bol'],
            'amazon' => ['label' => 'Amazon', 'cls' => 'amazon'],
            'tweakers' => ['label' => 'Tweakers', 'cls' => 'tweakers'],
        ];
        $storeLabel = static fn (?string $key) => $storeMeta[mb_strtolower((string) $key)]['label'] ?? ucfirst((string) $key);
        $storeCls = static fn (?string $key) => $storeMeta[mb_strtolower((string) $key)]['cls'] ?? 'bol';

        // ---- normalize state ----
        $products = collect($products);

        // Split each product's listings into confirmed (tracked) vs candidates (review).
        $withConfirmed = $products->filter(fn ($p) => collect($p['listings'] ?? [])->contains(fn ($l) => (bool) ($l['confirmed'] ?? false)));
        $inReview = $products->filter(fn ($p) => collect($p['listings'] ?? [])->isNotEmpty()
            && collect($p['listings'] ?? [])->every(fn ($l) => ! (bool) ($l['confirmed'] ?? false)));

        $trackedCount = $withConfirmed->count();
        $reviewCount = $inReview->count();
        $hasProducts = $products->isNotEmpty();

        // Count price drops across tracked listings (current below lowest-ever recorded).
        $dropCount = $withConfirmed
            ->flatMap(fn ($p) => $p['listings'] ?? [])
            ->filter(fn ($l) => (bool) ($l['confirmed'] ?? false))
            ->filter(function ($l) {
                $cur = $l['current_price'] ?? null;
                $low = $l['lowest_price'] ?? null;
                return $cur !== null && $low !== null && $cur <= $low;
            })
            ->count();

        // Most-recent check timestamp across all confirmed listings.
        $lastChecked = $products
            ->flatMap(fn ($p) => $p['listings'] ?? [])
            ->pluck('last_checked_at')
            ->filter()
            ->map(fn ($t) => \Carbon\CarbonImmutable::parse($t))
            ->sort()
            ->last();
        $lastCheckedHuman = $lastChecked
            ? $lastChecked->locale('nl')->diffForHumans(\Carbon\CarbonImmutable::now())
            : null;
        $lastCheckedFull = $lastChecked
            ? $lastChecked->setTimezone(config('app.timezone', 'UTC'))->locale('nl')->isoFormat('dd D MMM · HH:mm')
            : null;

        // Helper: best (lowest) confirmed listing for a product.
        $bestListing = static function (array $product) {
            return collect($product['listings'] ?? [])
                ->filter(fn ($l) => (bool) ($l['confirmed'] ?? false) && ($l['current_price'] ?? null) !== null)
                ->sortBy('current_price')
                ->first();
        };
    @endphp

    <div class="dt"
         data-deals
         data-store-url="{{ route('deals.products.store') }}"
         data-check-url="{{ route('deals.check') }}"
         data-confirm-tpl="{{ url('deals/listings/__ID__/confirm') }}"
         data-destroy-tpl="{{ url('deals/listings/__ID__') }}"
         data-history-tpl="{{ url('deals/products/__ID__/history') }}">
        <div class="dt-wrap">
            {{-- ============ HEADER ============ --}}
            <div class="dt-head">
                <div class="dt-head-l">
                    <span class="dt-eyebrow">{!! $dtIc('Tag', 14, 1.7, 'ic') !!} Prijsbewaking</span>
                    <h1 class="dt-title disp">Dealtracker</h1>
                    <div class="dt-sub">
                        @if($hasProducts)
                            <b class="tnum">{{ $trackedCount }}</b> producten gevolgd
                            <span class="dot">·</span>
                            @if($dropCount > 0)
                                <span class="ok">{{ $dropCount }} {{ $dropCount === 1 ? 'prijsdaling' : 'prijsdalingen' }}</span>
                            @else
                                geen prijsdalingen
                            @endif
                            @if($reviewCount > 0)
                                <span class="dot">·</span><span class="acc">{{ $reviewCount }} te beoordelen</span>
                            @endif
                        @else
                            Nog geen producten gevolgd <span class="dot">·</span> volgt prijzen bij <b>bol.com</b>, <b>Amazon</b> &amp; <b>Tweakers</b>
                        @endif
                    </div>
                </div>
                <div class="dt-head-r">
                    @if($lastCheckedHuman)
                        <span class="dt-checked">{!! $dtIc('Clock', 14, 1.7, 'ic') !!} Gecheckt <b>{{ $lastCheckedHuman }}</b></span>
                    @endif
                    <button class="dt-btn dt-btn-ghost" data-deals-check>
                        {!! $dtIc('Refresh', 15, 1.7, 'ic') !!} Nu checken
                    </button>
                    <button class="dt-btn dt-btn-primary" data-deals-add-open>
                        {!! $dtIc('Plus', 15, 1.7) !!} Product toevoegen
                    </button>
                </div>
            </div>

            {{-- ============ CONTEXT STRIP ============ --}}
            @if($hasProducts)
                <div class="dt-strip">
                    {!! $dtIc('Bell', 15, 1.7, 'ic') !!}
                    <span>Je krijgt een <b>melding</b> zodra een gevolgde prijs zakt.</span>
                    @if($lastCheckedFull)
                        <span class="dt-strip-time">Laatst gecheckt <b>{{ $lastCheckedFull }}</b></span>
                    @endif
                </div>
            @endif

            {{-- ============ ADD / SEARCH (toggled by JS) ============ --}}
            <div class="dt-add" data-deals-add hidden>
                <div class="dt-guard" style="background: var(--surface); border-color: var(--line);">
                    {!! $dtIc('Info', 17, 1.7, 'ic') !!}
                    <div class="dt-guard-tx">
                        Typ de naam van een product. De hub zoekt het bij <b>bol.com</b>, <b>Amazon</b> en <b>Tweakers</b>
                        en laat je per winkel de juiste match bevestigen voordat het volgen begint.
                    </div>
                </div>
                <form class="dt-search" data-deals-search-form>
                    {!! $dtIc('Search', 18, 1.7, 'ic') !!}
                    <input type="text" data-deals-search-input
                           placeholder="bv. Bambu Lab AMS, Sony WH-1000XM5, Philips Hue starterkit…" autocomplete="off">
                    <button type="submit" class="dt-btn dt-btn-primary" data-deals-search-submit disabled>
                        {!! $dtIc('Search', 15, 1.7) !!} Zoeken
                    </button>
                </form>
                <div style="margin-top: 16px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="dt-foot-link" data-deals-add-cancel>Annuleren</button>
                </div>
            </div>

            {{-- ============ ADD: SEARCH LOADING (toggled by JS) ============ --}}
            <div class="dt-add" data-deals-add-loading hidden>
                <div class="dt-load-banner">
                    {!! $dtIc('Refresh', 18, 1.7, 'ic') !!}
                    <div>
                        <div class="dt-load-tx">Zoeken naar “<span data-deals-search-term></span>”…</div>
                        <div class="dt-load-sub">bol.com, Amazon en Tweakers doorzoeken op overeenkomende producten.</div>
                    </div>
                </div>
                <div class="dt-review-grid">
                    @foreach(['bol', 'amazon', 'tweakers'] as $store)
                        <div class="dt-storecol">
                            <div class="dt-storecol-head {{ $store }}">
                                <span class="led" style="opacity: 0.4;"></span>
                                <span class="dt-sk" style="height: 13px; width: 80px;"></span>
                            </div>
                            <div class="dt-storecol-body">
                                @for($i = 0; $i < 2; $i++)
                                    <div class="dt-cand" style="background: var(--card);">
                                        <div class="dt-cand-top">
                                            <span class="dt-sk" style="width: 48px; height: 48px; border-radius: 8px;"></span>
                                            <div style="flex: 1; display: flex; flex-direction: column; gap: 8px; padding-top: 2px;">
                                                <span class="dt-sk" style="height: 12px; width: 90%;"></span>
                                                <span class="dt-sk" style="height: 12px; width: 60%;"></span>
                                                <span class="dt-sk" style="height: 14px; width: 64px; margin-top: 2px;"></span>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 8px; margin-top: 12px;">
                                            <span class="dt-sk" style="height: 34px; flex: 1; border-radius: 8px;"></span>
                                            <span class="dt-sk" style="height: 34px; flex: 1; border-radius: 8px;"></span>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ============ WATCHLIST + REVIEW (default view) ============ --}}
            <div data-deals-main>
                @if(! $hasProducts)
                    {{-- LEEG --}}
                    <div class="dt-state">
                        <span class="dt-state-ico">{!! $dtIc('Tag', 26, 1.7) !!}</span>
                        <div class="dt-state-title">Je volgt nog geen producten</div>
                        <div class="dt-state-sub">
                            Voeg een product toe en de hub controleert de prijs bij bol.com, Amazon en Tweakers.
                            Je krijgt een melding zodra de prijs zakt.
                        </div>
                        <div class="dt-state-actions">
                            <button class="dt-btn dt-btn-primary" data-deals-add-open>
                                {!! $dtIc('Plus', 15, 1.7) !!} Product toevoegen
                            </button>
                        </div>
                    </div>
                @else
                    {{-- ===== REVIEW: producten met alleen onbevestigde kandidaten ===== --}}
                    @foreach($inReview as $product)
                        @php $listings = collect($product['listings'] ?? []); @endphp
                        <div class="dt-add" style="margin-bottom: 22px;"
                             data-deals-review="{{ $product['id'] }}">
                            <div class="dt-review-head">
                                <div class="dt-review-q">
                                    <span>Resultaten voor</span>
                                    <span class="term">{!! $dtIc('Search', 13, 1.7, 'ic') !!} {{ $product['name'] }}</span>
                                </div>
                            </div>

                            <div class="dt-guard">
                                {!! $dtIc('Shield', 17, 1.7, 'ic') !!}
                                <div class="dt-guard-tx">
                                    Bevestig per winkel de <b>juiste</b> match en verwijder verkeerde resultaten — zoals een andere
                                    generatie of los accessoire. <b>Alleen bevestigde producten worden gevolgd</b>, zodat je geen
                                    verkeerde prijs binnenhaalt.
                                </div>
                            </div>

                            <div class="dt-review-grid">
                                @foreach(['bol', 'amazon', 'tweakers'] as $store)
                                    @php $cands = $listings->filter(fn ($l) => mb_strtolower((string) $l['retailer']) === $store)->values(); @endphp
                                    <div class="dt-storecol">
                                        <div class="dt-storecol-head {{ $store }}">
                                            <span class="led"></span>
                                            <span class="dt-storecol-name">{{ $storeLabel($store) }}</span>
                                            <span class="dt-storecol-count">{{ $cands->count() }} {{ $cands->count() === 1 ? 'kandidaat' : 'kandidaten' }}</span>
                                        </div>
                                        <div class="dt-storecol-body">
                                            @if($cands->isEmpty())
                                                <div class="dt-cand-none">
                                                    {!! $dtIc('X', 20, 1.7, 'ic') !!}
                                                    <div>Geen match — hier wordt niets gevolgd.</div>
                                                </div>
                                            @else
                                                @foreach($cands as $listing)
                                                    <div class="dt-cand" data-deals-cand="{{ $listing['id'] }}">
                                                        <div class="dt-cand-top">
                                                            <div class="dt-cand-thumb">{!! $dtIc('Box', 20, 1.5, 'ic') !!}</div>
                                                            <div class="dt-cand-main">
                                                                <div class="dt-cand-name">{{ $listing['title'] }}</div>
                                                                <div class="dt-cand-row">
                                                                    @if(($listing['current_price'] ?? null) !== null)
                                                                        <span class="dt-cand-price tnum">{{ $euro($listing['current_price']) }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="dt-cand-actions">
                                                            <button class="dt-cact confirm" data-deals-confirm>{!! $dtIc('Check', 14, 1.7) !!} Bevestigen</button>
                                                            <button class="dt-cact remove" data-deals-remove>{!! $dtIc('X', 14, 1.7) !!} Verwijderen</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- ===== WATCHLIST: producten met bevestigde listings ===== --}}
                    @if($withConfirmed->isNotEmpty())
                        <div class="dt-list">
                            @foreach($withConfirmed as $product)
                                @php
                                    $confirmed = collect($product['listings'] ?? [])->filter(fn ($l) => (bool) ($l['confirmed'] ?? false))->values();
                                    $best = $bestListing($product);
                                    $checkedAt = collect($confirmed)->pluck('last_checked_at')->filter()
                                        ->map(fn ($t) => \Carbon\CarbonImmutable::parse($t))->sort()->last();
                                    $checkedHuman = $checkedAt ? $checkedAt->locale('nl')->diffForHumans(\Carbon\CarbonImmutable::now()) : null;
                                @endphp
                                <div class="dt-card" data-deals-product="{{ $product['id'] }}">
                                    <div class="dt-card-head">
                                        @php $imageUrl = SafeUrl::http($product['image_url'] ?? null); @endphp
                                        <div class="dt-thumb">
                                            @if($imageUrl)
                                                <img src="{{ $imageUrl }}" alt="{{ $product['name'] }}"
                                                     style="position:relative;z-index:1;width:100%;height:100%;object-fit:cover;">
                                            @else
                                                {!! $dtIc('Box', 26, 1.5, 'ic') !!}
                                                <span class="dt-thumb-cap">foto</span>
                                            @endif
                                        </div>
                                        <div class="dt-head-main">
                                            @if(! empty($product['category']))
                                                <div class="dt-prod-cat">{{ $product['category'] }}</div>
                                            @endif
                                            <div class="dt-prod-name">{{ $product['name'] }}</div>
                                            <div class="dt-prod-meta">
                                                <span>{{ $confirmed->count() }} {{ $confirmed->count() === 1 ? 'winkel' : 'winkels' }} gevolgd</span>
                                            </div>
                                        </div>
                                        @if($best)
                                            <div class="dt-best">
                                                <span class="dt-best-lab">{!! $dtIc('Tag', 11, 1.7, 'ic') !!} Beste prijs</span>
                                                <div class="dt-best-price tnum">{{ $euro($best['current_price']) }}</div>
                                                <div class="dt-best-store">bij <b>{{ $storeLabel($best['retailer']) }}</b></div>
                                            </div>
                                        @endif
                                        <button class="dt-card-menu" aria-label="Opties">{!! $dtIc('Dots', 18, 1.7) !!}</button>
                                    </div>

                                    <div class="dt-listings">
                                        @foreach($confirmed as $listing)
                                            @php
                                                $cur = $listing['current_price'] ?? null;
                                                $low = $listing['lowest_price'] ?? null;
                                                $isBest = $best && $best['id'] === $listing['id'];
                                                $isAth = $cur !== null && $low !== null && $cur <= $low;
                                            @endphp
                                            <div class="dt-listing" data-deals-listing="{{ $listing['id'] }}">
                                                <div class="dt-store-col">
                                                    <span class="dt-store {{ $storeCls($listing['retailer']) }}">
                                                        <span class="led"></span>
                                                        <span class="dt-store-name">{{ $storeLabel($listing['retailer']) }}</span>
                                                    </span>
                                                    <div class="dt-store-low {{ $isAth ? 'ath' : '' }}">
                                                        @if($isAth)
                                                            {!! $dtIc('TrendDown', 13, 1.7, 'ic') !!} laagste ooit
                                                        @elseif($low !== null)
                                                            laagste <b class="tnum">{{ $euro($low) }}</b>
                                                        @else
                                                            nog geen geschiedenis
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="dt-mid">
                                                    <svg class="dt-spark" width="88" height="30" viewBox="0 0 88 30"
                                                         data-deals-spark aria-hidden="true"></svg>
                                                    @if($isAth)
                                                        <span class="dt-drop">{!! $dtIc('ArrowDown', 13, 1.7, 'ic') !!} op laagste prijs</span>
                                                    @else
                                                        <span class="dt-flat">stabiel</span>
                                                    @endif
                                                </div>
                                                <div class="dt-price-col">
                                                    @if($cur !== null)
                                                        <div class="dt-price tnum {{ $isBest ? 'best' : '' }}">{{ $euro($cur) }}</div>
                                                        @if($isBest)
                                                            <div class="dt-price-tag">{!! $dtIc('Tag', 12, 1.7, 'ic') !!} BESTE PRIJS</div>
                                                        @else
                                                            <div class="dt-price-sub">huidige prijs</div>
                                                        @endif
                                                    @else
                                                        <div class="dt-price tnum">—</div>
                                                        <div class="dt-price-sub">nog geen prijs</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="dt-card-foot">
                                        {!! $dtIc('Clock', 14, 1.7, 'ic') !!}
                                        @if($checkedHuman)
                                            <span>Laatst gecheckt <b>{{ $checkedHuman }}</b></span>
                                        @else
                                            <span>Nog niet gecheckt</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-dashboard.layout>
