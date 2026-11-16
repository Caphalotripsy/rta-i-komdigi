@props(['name', 'class' => 'size-5'])
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
            @break
        @case('tambah')
            <rect x="3" y="3" width="18" height="18" rx="3"/><path d="M12 8v8M8 12h8"/>
            @break
        @case('daftar')
            <path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>
            @break
        @case('keluar')
            <path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16"/>
            @break
        @case('peringatan')
            <path d="M12 3l10 18H2L12 3z"/><path d="M12 10v5M12 18h.01"/>
            @break
        @case('centang')
            <circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>
            @break
        @case('jam')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
        @case('dompet')
            <rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="16" cy="14.5" r="1"/>
            @break
        @case('berkas')
            <path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/>
            @break
        @case('info')
            <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
            @break
        @case('panah')
            <path d="M5 12h14M13 6l6 6-6 6"/>
            @break
        @case('arsip')
            <path d="M3 7h18v13H3z"/><path d="M3 7l2-4h14l2 4M10 12h4"/>
            @break
        @case('balik')
            <path d="M9 14L4 9l5-5"/><path d="M4 9h10a6 6 0 010 12h-3"/>
            @break
        @case('mata')
            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
            @break
    @endswitch
</svg>
