<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/70 bg-white/85 shadow-sm shadow-sky-100/70 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ auth()->check() ? route('dashboard') : route('inicio') }}" class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-700 via-sky-600 to-emerald-500 text-white shadow-lg shadow-sky-200/80">
                    <x-application-logo class="block h-7 w-auto fill-current" />
                </span>
                <div class="hidden sm:block">
                    <p class="font-display text-sm text-slate-900">Lavandería Registro</p>
                    <p class="text-[11px] uppercase tracking-[0.25em] text-sky-700">Control de usuarios y quincenas</p>
                </div>
            </a>

            @auth
                <div class="hidden items-center gap-2 sm:flex">
                    @if (auth()->user()->tieneRol('admin', 'programador', 'usuario'))
                        <a href="{{ route('produccion.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ request()->routeIs('produccion.*') ? 'bg-sky-100 text-sky-900 ring-1 ring-sky-200 shadow-sm shadow-sky-100/60' : 'text-slate-600 hover:bg-sky-50 hover:text-sky-800' }}">
                            Producción
                        </a>
                    @endif
                    @if (auth()->user()->tieneRol('recolector'))
                        <a href="{{ route('recolector.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ request()->routeIs('recolector.*') ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-200 shadow-sm shadow-amber-100/70' : 'text-slate-600 hover:bg-amber-50 hover:text-amber-800' }}">
                            Recolector
                        </a>
                    @endif
                    @if (auth()->user()->tieneRol('admin', 'programador'))
                        <a href="{{ route('admin.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ request()->routeIs('admin.*') || request()->routeIs('prendas.*') || request()->routeIs('clientes.*') || request()->routeIs('recolector-prendas.*') ? 'bg-slate-900 text-white shadow-lg shadow-slate-200/70' : 'text-slate-600 hover:bg-sky-50 hover:text-sky-800' }}">
                            @php($alertasIncongruencia = auth()->user()->unreadNotifications()->where('type', 'App\\Notifications\\IncongruenciaRecolectorDetectada')->count())
                            Panel Admin
                            @if ($alertasIncongruencia > 0)
                                <span class="ml-2 rounded-full bg-rose-500 px-2 py-0.5 text-xs font-bold text-white">{{ $alertasIncongruencia }}</span>
                            @endif
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ request()->routeIs('profile.*') ? 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200 shadow-sm shadow-emerald-100/70' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-800' }}">
                        Perfil
                    </a>
                </div>
            @endauth
        </div>

        @auth
            <div class="hidden items-center gap-4 sm:flex">
                <div class="text-right">
                    <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs uppercase tracking-[0.24em] text-sky-700">{{ auth()->user()->obtenerRol() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-full border border-rose-200/80 bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow-sm shadow-rose-100/60 hover:bg-rose-50">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        @else
            <div class="hidden items-center gap-3 sm:flex">
                <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-sky-50 hover:text-sky-800">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="brand-button-primary rounded-full px-4 py-2">Registrarse</a>
            </div>
        @endauth

        <button @click="open = ! open" class="inline-flex items-center justify-center rounded-xl p-2 text-slate-500 hover:bg-sky-50 hover:text-sky-700 sm:hidden">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-sky-100 bg-white/95 backdrop-blur sm:hidden">
        <div class="space-y-1 px-4 py-4">
            @auth
                @if (auth()->user()->tieneRol('admin', 'programador', 'usuario'))
                    <a href="{{ route('produccion.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('produccion.*') ? 'bg-sky-100 text-sky-900 ring-1 ring-sky-200' : 'text-slate-700 hover:bg-sky-50 hover:text-sky-800' }}">Producción</a>
                @endif
                @if (auth()->user()->tieneRol('recolector'))
                    <a href="{{ route('recolector.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('recolector.*') ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-200' : 'text-slate-700 hover:bg-amber-50 hover:text-amber-800' }}">Recolector</a>
                @endif
                @if (auth()->user()->tieneRol('admin', 'programador'))
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.*') || request()->routeIs('prendas.*') || request()->routeIs('clientes.*') || request()->routeIs('recolector-prendas.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-sky-50 hover:text-sky-800' }}">
                        @php($alertasIncongruenciaMobile = auth()->user()->unreadNotifications()->where('type', 'App\\Notifications\\IncongruenciaRecolectorDetectada')->count())
                        Panel Admin
                        @if ($alertasIncongruenciaMobile > 0)
                            <span class="ml-2 rounded-full bg-rose-500 px-2 py-0.5 text-xs font-bold text-white">{{ $alertasIncongruenciaMobile }}</span>
                        @endif
                    </a>
                @endif
                <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('profile.*') ? 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' }}">Perfil</a>
                <form method="POST" action="{{ route('logout') }}" class="pt-2">
                    @csrf
                    <button class="block w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-left text-sm font-medium text-rose-700 shadow-sm shadow-rose-100/60">Cerrar sesión</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-sky-50 hover:text-sky-800">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="block rounded-xl bg-gradient-to-r from-sky-700 via-sky-600 to-emerald-500 px-3 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-200/80">Registrarse</a>
            @endauth
        </div>
    </div>
</nav>
