@php
    // Genre vocabulary mirrors the design's taste profile. Active genres + favorite
    // titles are hydrated client-side from the taste endpoint and persisted on edit.
    $genreVocab = ['Sci-fi', 'Drama', 'Thriller', 'Animation', 'Horror', 'Arthouse', 'Documentary', 'Comedy'];
@endphp

<aside class="ent-taste"
       data-ent-taste
       data-ent-taste-show-url="{{ route('entertainment.taste.show') }}"
       data-ent-taste-save-url="{{ route('entertainment.taste.update') }}">
    <div class="ent-taste-head">
        {!! $eIc('Star', 18, 1.7, 'ic') !!}
        <span class="ent-taste-title">Taste profile</span>
    </div>
    <p class="ent-taste-note">
        This is what the hub bases your film recommendations on. Set your favorite genres and add films you liked.
    </p>

    <div class="ent-taste-lab">Favorite genres <span class="rule"></span></div>
    <div class="ent-genres" data-ent-genres>
        @foreach($genreVocab as $genre)
            <button class="ent-genre" data-ent-genre="{{ $genre }}">{{ $genre }}</button>
        @endforeach
    </div>

    <div class="ent-taste-lab" style="margin-top:20px;">Films you liked <span class="rule"></span></div>
    <div class="ent-fav-list" data-ent-fav-list></div>
    <button class="ent-fav-add" data-ent-fav-add>{!! $eIc('Plus', 15, 1.7) !!} Add film</button>
</aside>
