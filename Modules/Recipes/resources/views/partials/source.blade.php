{{--
    Source strip — which stores were fetched.
    Expects: $stores (Collection of lowercase keys), $storesFailed (Collection),
             $generatedLabel (?string), $RIc, $storeShort, $storeLabel closures.
--}}
<div class="rc-source">
    <span class="rc-source-lab">{!! $RIc('Store', 14, 1.7, 'ic') !!} Aanbiedingen opgehaald</span>
    <div class="rc-source-stores">
        @foreach($stores as $store)
            @php $ok = ! $storesFailed->contains($store); @endphp
            <span class="rc-store-stat {{ $store }} {{ $ok ? 'ok' : 'err' }}">
                <span class="mono">{{ $storeShort($store) }}</span>
                {{ $storeLabel($store) }}
                <span class="st">
                    @if($ok)
                        {!! $RIc('CheckSm', 13, 2.2) !!} opgehaald
                    @else
                        {!! $RIc('X', 13, 2.2) !!} mislukt
                    @endif
                </span>
            </span>
        @endforeach
    </div>
    @if($generatedLabel)
        <span class="rc-source-time">Gegenereerd <b>{{ $generatedLabel }}</b></span>
    @endif
</div>
