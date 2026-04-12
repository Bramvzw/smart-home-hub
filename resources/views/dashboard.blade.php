<x-dashboard.layout title="Dashboard">
    <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($modules as $module)
                @php $nav = $module->getNavigation()[0] ?? null; @endphp
                @if($nav)
                <a href="{{ route($nav['route']) }}"
                   class="aspect-square rounded-2xl p-6 flex flex-col items-center justify-center text-center bg-white/[0.04] border border-white/[0.08] hover:bg-white/[0.08] hover:border-violet-500/30 transition-all duration-200 group">
                    <div class="p-3.5 rounded-2xl bg-violet-500/15 group-hover:bg-violet-500/25 transition-colors mb-4">
                        @if(isset($nav['icon']))
                            <x-dynamic-component :component="'dashboard.icons.' . $nav['icon']" class="h-7 w-7 text-violet-300 group-hover:text-violet-200 transition-colors" />
                        @endif
                    </div>
                    <h3 class="font-semibold text-white text-sm">{{ $module->getModuleName() }}</h3>
                </a>
                @endif
            @endforeach
        </div>

        @if($modules->isEmpty())
            <div class="text-center py-20">
                <div class="w-16 h-16 rounded-2xl bg-white/[0.04] border border-white/[0.08] flex items-center justify-center mx-auto mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h2 class="text-base font-medium text-gray-300">No modules installed</h2>
                <p class="text-gray-500 text-sm mt-2">Add modules to get started.</p>
            </div>
        @endif
    </div>
</x-dashboard.layout>
