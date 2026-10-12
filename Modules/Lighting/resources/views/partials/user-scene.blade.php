{{-- One user scene in the scene strip. Rendered blank inside a <template> for lighting.js. --}}
<div class="lighting-scene lighting-scene--user" data-user-scene data-scene-id="{{ $scene['id'] }}" style="--scene-dot: {{ $scene['accent'] }}">
    <button
        type="button"
        class="lighting-scene__apply"
        data-scene="{{ $scene['id'] }}"
        data-scene-label="{{ $scene['name'] }}"
        data-scene-accent="{{ $scene['accent'] }}"
        data-scene-lights="{{ json_encode($scene['lights']) }}"
        data-active="{{ $scene['active'] ? 'true' : 'false' }}"
        aria-pressed="{{ $scene['active'] ? 'true' : 'false' }}"
    >
        <span class="lighting-scene__dot" aria-hidden="true"></span>
        <span class="lighting-scene__copy">
            <span class="lighting-scene__name" data-scene-name>{{ $scene['name'] }}</span>
            <span class="lighting-scene__sub" data-scene-sub>{{ $scene['subtitle'] }}</span>
        </span>
        <span class="lighting-scene__active">Active</span>
    </button>
    <button type="button" class="lighting-scene__menu" data-scene-menu aria-label="Options for {{ $scene['name'] }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle></svg>
    </button>
</div>
