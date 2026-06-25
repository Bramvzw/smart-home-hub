{{--
    Aanbiedingen (raw deals) view — one column per store.
    Expects: $offersByStore (Collection grouped by lowercase store), $allStores,
             $storesFailed, $RIc, $storeShort, $storeLabel, $euro, $isUsed closures.
--}}
<div class="rc-deals">
    @foreach($allStores as $store)
        @php
            $items = collect($offersByStore->get($store, []));
            $failed = $storesFailed->contains($store);
        @endphp
        @if($failed)
            <div class="rc-deal-col failed {{ $store }}">
                <div class="rc-deal-head">
                    <span class="rc-deal-mono">{{ $storeShort($store) }}</span>
                    <div>
                        <div class="rc-deal-name">{{ $storeLabel($store) }}</div>
                        <div class="rc-deal-meta">Niet opgehaald</div>
                    </div>
                </div>
                <div class="rc-deal-fail">
                    <span class="rc-deal-fail-ico">{!! $RIc('Alert', 30) !!}</span>
                    <div class="rc-deal-fail-tx">
                        De aanbiedingen van <b>{{ $storeLabel($store) }}</b> konden niet worden opgehaald.
                        Het weekmenu is samengesteld met alleen de andere winkel.
                    </div>
                </div>
            </div>
        @else
            @php $usedCount = $items->filter(fn ($o) => $isUsed((string) $o['product_name']))->count(); @endphp
            <div class="rc-deal-col {{ $store }}">
                <div class="rc-deal-head">
                    <span class="rc-deal-mono">{{ $storeShort($store) }}</span>
                    <div>
                        <div class="rc-deal-name">{{ $storeLabel($store) }}</div>
                        <div class="rc-deal-meta">{{ $items->count() }} aanbiedingen</div>
                    </div>
                    <span class="rc-deal-count tnum">{{ $usedCount }}/{{ $items->count() }} gebruikt</span>
                </div>
                <div class="rc-deal-list">
                    @forelse($items as $offer)
                        @php $used = $isUsed((string) $offer['product_name']); @endphp
                        <div class="rc-deal-row {{ $used ? 'used' : '' }}">
                            <div class="rc-deal-b">
                                <div class="rc-deal-item">
                                    {{ $offer['product_name'] }}
                                    @if($used)
                                        <span class="rc-deal-used">{!! $RIc('Pot', 11) !!} in menu</span>
                                    @endif
                                </div>
                                <div class="rc-deal-note">
                                    {{ $offer['discount_label'] ?: $offer['category'] ?: 'Aanbieding' }}
                                    @if($offer['unit'])· {{ $offer['unit'] }}@endif
                                </div>
                            </div>
                            <div class="rc-deal-price">
                                @if($offer['offer_price'] !== null)
                                    <div class="rc-deal-now tnum">{{ $euro($offer['offer_price']) }}</div>
                                @elseif($offer['normal_price'] !== null)
                                    <div class="rc-deal-now tnum">{{ $euro($offer['normal_price']) }}</div>
                                @endif
                                @if($offer['offer_price'] !== null && $offer['normal_price'] !== null && $offer['normal_price'] > $offer['offer_price'])
                                    <div class="rc-deal-was tnum">{{ $euro($offer['normal_price']) }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rc-deal-row err-col">
                            <div class="rc-deal-b">
                                <div class="rc-deal-note">Geen aanbiedingen opgehaald voor deze winkel.</div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    @endforeach
</div>
