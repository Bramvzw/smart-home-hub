<div class="flex-grow flex items-center justify-center relative z-10 p-6">
    <div class="hub-card max-w-sm mx-auto p-8 text-center">
        <div class="w-16 h-16 rounded-[10px] bg-[rgba(52,184,160,0.14)] flex items-center justify-center mx-auto mb-5 ring-1 ring-[rgba(52,184,160,0.3)]">
            <svg class="w-9 h-9 text-[#95e2d3]" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
            </svg>
        </div>
        <h2 class="text-[19px] font-bold mb-2 text-[var(--hub-text)]">Connect to Spotify</h2>
        <p class="text-[var(--hub-dim)] mb-7 leading-relaxed text-sm">Link your account to control music playback from your smart home dashboard.</p>
        <a href="{{ $authUrl }}"
           class="inline-flex items-center bg-[#34b8a0] hover:bg-[#42c9b0] active:bg-[#2ca791] text-[#08110f] font-bold px-5 rounded-[7px] transition-all duration-150 active:scale-[0.99] min-h-[38px] text-sm">
            <svg class="w-5 h-5 mr-2.5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
            </svg>
            Connect with Spotify
        </a>
    </div>
</div>
