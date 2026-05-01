<aside class="hidden w-80 shrink-0 border-r border-stone-200/80 bg-[linear-gradient(180deg,#201516_0%,#2f1e20_100%)] text-white lg:flex lg:h-screen lg:flex-col lg:overflow-y-auto">
    <div class="border-b border-white/10 px-6 py-6">
        <div class="flex items-center gap-4">
            <div class="rounded-2xl bg-white px-3 py-3 shadow-sm">
                <img src="{{ asset('img/icon/logo/logoBlackRed.png') }}" alt="TemuLKPP" class="h-10">
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-red-200">Portal Internal</p>
                <h1 class="mt-1 text-xl font-black tracking-[0.03em]">Agent Workspace</h1>
            </div>
        </div>
    </div>

    <div class="flex flex-1 flex-col px-5 py-6">
        <div class="space-y-8">
            <div class="rounded-[28px] border border-white/10 bg-white/6 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-red-200">Akun Aktif</p>
                <p class="mt-3 text-lg font-bold">{{ auth()->user()->name }}</p>
                <p class="mt-1 text-sm text-white/70">{{ auth()->user()->email }}</p>
            </div>

            @php
                $taskSummary = $agentTaskSummary ?? ['todayTotal' => 0, 'needsAction' => 0, 'inProgress' => 0];
            @endphp

            <div class="rounded-[28px] border border-red-300/20 bg-white/8 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-red-200">Notifikasi Tugas</p>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/8 px-4 py-3">
                        <span class="font-semibold text-white/75">Hari ini</span>
                        <span class="text-lg font-black text-white">{{ number_format($taskSummary['todayTotal']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/8 px-4 py-3">
                        <span class="font-semibold text-white/75">Perlu aksi</span>
                        <span class="text-lg font-black text-amber-200">{{ number_format($taskSummary['needsAction']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/8 px-4 py-3">
                        <span class="font-semibold text-white/75">Sedang berjalan</span>
                        <span class="text-lg font-black text-emerald-200">{{ number_format($taskSummary['inProgress']) }}</span>
                    </div>
                </div>
            </div>

            @php
                $roleMenus = \App\Models\RoleMenu::where('role', 'agent')->with('menu')->get()->pluck('menu');
            @endphp

            <nav class="space-y-2">
                @foreach ($roleMenus as $menu)
                    <a
                        href="{{ route($menu->route) }}"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition-all duration-300 {{ request()->routeIs($menu->route . '*') ? 'bg-white text-red-700 shadow-[0_14px_30px_rgba(255,255,255,0.16)]' : 'bg-white/5 text-white/82 hover:bg-white/12' }}"
                    >
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-current/15">
                            {!! $menu->icon !!}
                        </span>
                        {{ $menu->name }}
                        @if($menu->route === 'agent.jadwal' && ($taskSummary['needsAction'] ?? 0) > 0)
                            <span class="ml-auto inline-flex items-center rounded-full bg-red-600 px-2.5 py-1 text-[11px] font-black text-white shadow-sm">
                                {{ number_format($taskSummary['needsAction']) }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-auto border-t border-white/10 pt-6">
            @csrf
            <button type="submit" class="flex w-full items-center justify-center rounded-2xl border border-white/14 bg-white/8 px-4 py-3 text-sm font-bold text-white transition-all duration-300 hover:bg-white/14">
                Logout
            </button>
        </form>
    </div>
</aside>
