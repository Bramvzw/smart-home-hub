<x-dashboard.layout title="Dashboard">
    <div class="h-full p-5">
        <section class="max-w-5xl">
            <div class="mb-5">
                <h2 class="text-[22px] font-bold text-[var(--hub-text)] leading-tight">Modules</h2>
                <p class="mt-1 text-sm text-[var(--hub-dim)]">Local control for music and tasks.</p>
            </div>

            @if($modules->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($modules as $module)
                        @php $nav = $module->getNavigation()[0] ?? null; @endphp
                        @if($nav)
                            <a href="{{ route($nav['route']) }}" class="hub-card group flex min-h-[118px] items-center gap-4 p-4">
                                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-[8px] bg-[var(--hub-accent-soft)] text-[var(--hub-accent)]">
                                    @if(isset($nav['icon']))
                                        <x-dynamic-component :component="'dashboard.icons.' . $nav['icon']" class="h-5 w-5 transition-colors group-hover:text-[var(--hub-accent-hover)]" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-[15px] font-bold text-[var(--hub-text)]">{{ $module->getModuleName() }}</h3>
                                    <p class="mt-1 text-sm text-[var(--hub-dim)]">
                                        @if($module->getModuleSlug() === 'spotify')
                                            Playback, queue and playlists.
                                        @elseif($module->getModuleSlug() === 'tasks')
                                            Boards, labels and todos.
                                        @else
                                            Open module.
                                        @endif
                                    </p>
                                </div>
                                <span class="text-[var(--hub-dim)] transition-colors group-hover:text-[var(--hub-text)]">→</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="hub-empty h-[360px]">
                    <div class="mb-4 grid h-12 w-12 place-items-center rounded-[10px] bg-[var(--hub-card)] text-[var(--hub-dim)] ring-1 ring-[var(--hub-line)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-[var(--hub-muted)]">No modules</h2>
                    <p class="mt-1 text-sm text-[var(--hub-dim)]">Enable modules to get started.</p>
                </div>
            @endif
        </section>
    </div>
</x-dashboard.layout>
