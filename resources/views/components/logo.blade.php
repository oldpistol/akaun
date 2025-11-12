@props(['variant' => 'default', 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'w-8 h-8',
        'md' => 'w-12 h-12',
        'lg' => 'w-16 h-16',
        'xl' => 'w-24 h-24',
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if($variant === 'icon')
    {{-- Compact Icon Version --}}
    <svg {{ $attributes->merge(['class' => $sizeClass]) }} viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="100" height="100" rx="20" fill="url(#gradient)"/>
        <defs>
            <linearGradient id="gradient" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#1d4ed8;stop-opacity:1" />
            </linearGradient>
        </defs>
        <text x="50" y="65" font-family="Arial, sans-serif" font-size="48" font-weight="bold" fill="white" text-anchor="middle">IA</text>
    </svg>
@elseif($variant === 'minimal')
    {{-- Minimal Version --}}
    <svg {{ $attributes->merge(['class' => $sizeClass]) }} viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" stroke="currentColor" stroke-width="4" fill="none"/>
        <text x="50" y="65" font-family="Arial, sans-serif" font-size="40" font-weight="bold" fill="currentColor" text-anchor="middle">IA</text>
    </svg>
@elseif($variant === 'gradient')
    {{-- Gradient Background Version --}}
    <svg {{ $attributes->merge(['class' => $sizeClass]) }} viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="bg-gradient" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                <stop offset="50%" style="stop-color:#8b5cf6;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#d946ef;stop-opacity:1" />
            </linearGradient>
            <linearGradient id="text-gradient" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                <stop offset="0%" style="stop-color:#fbbf24;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#f59e0b;stop-opacity:1" />
            </linearGradient>
        </defs>
        <rect width="100" height="100" rx="24" fill="url(#bg-gradient)"/>
        <text x="50" y="70" font-family="Arial, sans-serif" font-size="52" font-weight="bold" fill="url(#text-gradient)" text-anchor="middle">IA</text>
    </svg>
@else
    {{-- Default Professional Version --}}
    <svg {{ $attributes->merge(['class' => $sizeClass]) }} viewBox="0 0 200 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="logo-gradient" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#1e40af;stop-opacity:1" />
            </linearGradient>
        </defs>
        
        {{-- Background shape --}}
        <rect x="5" y="5" width="70" height="70" rx="16" fill="url(#logo-gradient)"/>
        
        {{-- IA Letters --}}
        <text x="40" y="58" font-family="system-ui, -apple-system, sans-serif" font-size="44" font-weight="700" fill="white" text-anchor="middle">IA</text>
        
        {{-- Company name (optional) --}}
        <text x="90" y="45" font-family="system-ui, -apple-system, sans-serif" font-size="24" font-weight="600" fill="currentColor">Invoice</text>
        <text x="90" y="65" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="400" fill="currentColor" opacity="0.7">Assistant</text>
    </svg>
@endif
