<?php
/** One icon set for the whole app. 24px grid, 1.8 stroke. Use icon('name') in PHP or <svg class="ic"><use href="#i-name"/></svg> in JS. */

function icon_paths(): array
{
    return [
        'home'       => '<path d="M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10"/>',
        'plus'       => '<path d="M12 5v14M5 12h14"/>',
        'list'       => '<path d="M9 4h6v3H9zM7 5H5v16h14V5h-2M9 12h6M9 16h4"/>',
        'users'      => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.3 2.9-6 6.5-6s6.5 2.7 6.5 6M16.5 4.8a3.2 3.2 0 0 1 0 6M18 14.2c2.2.6 3.5 2.4 3.5 5.3"/>',
        'user'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'pin'        => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'chart'      => '<path d="M5 20V11M12 20V4M19 20v-6M3 20h18"/>',
        'activity'   => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'help'       => '<circle cx="12" cy="12" r="9.5"/><path d="M9.6 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1.1 1-1.1 1.7M12 17h.01"/>',
        'settings'   => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
        'bell'       => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2 7.5 2 7.5H4S6 15 6 9zM10 20a2 2 0 0 0 4 0"/>',
        'logout'     => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
        'menu'       => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'chevL'      => '<path d="M15 6l-6 6 6 6"/>',
        'chevR'      => '<path d="M9 6l6 6-6 6"/>',
        'chevD'      => '<path d="M6 9l6 6 6-6"/>',
        'search'     => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'calendar'   => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'alert'      => '<path d="M12 3l10 18H2L12 3zM12 10v5M12 18h.01"/>',
        'check'      => '<path d="M5 12l5 5 9-10"/>',
        'checkCircle'=> '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'eye'        => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eyeOff'     => '<path d="M3 3l18 18M10.6 6.1A9.6 9.6 0 0 1 12 5c6 0 10 7 10 7a17 17 0 0 1-3.2 3.9M6.5 7.5C3.9 9.3 2 12 2 12s4 7 10 7c1.4 0 2.8-.4 4-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'image'      => '<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-4-9 8"/>',
        'star'       => '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.2 6.4 20.2l1.1-6.2L3 9.6l6.2-.9z"/>',
        'shield'     => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
        'lock'       => '<rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'mail'       => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 8l9 6 9-6"/>',
        'edit'       => '<path d="M4 20h4L19 9l-4-4L4 16v4zM13 7l4 4"/>',
        'trash'      => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'refresh'    => '<path d="M20 11a8 8 0 0 0-14-4M4 4v4h4M4 13a8 8 0 0 0 14 4M20 20v-4h-4"/>',
        'filter'     => '<path d="M3 5h18l-7 8v6l-4-2v-4L3 5z"/>',
        'x'          => '<path d="M6 6l12 12M18 6L6 18"/>',
        'download'   => '<path d="M12 4v11M7 11l5 5 5-5M5 20h14"/>',
        'briefcase'  => '<rect x="3" y="7" width="18" height="13" rx="3"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
        'inbox'      => '<path d="M3 13l3-8h12l3 8v6H3v-6zM3 13h5l1 3h6l1-3h5"/>',
        'send'       => '<path d="M21 3L10 14M21 3l-7 18-4-7-7-4 18-7z"/>',
        'grid'       => '<rect x="3" y="3" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"/>',
        'file'       => '<path d="M7 3h8l4 4v14H7zM15 3v4h4M10 12h6M10 16h6"/>',
        'trend'      => '<path d="M3 17l6-6 4 4 8-8M15 7h6v6"/>',
        'building'   => '<path d="M4 21V5l8-2v18M20 21V10l-8-3M8 8h1M8 12h1M8 16h1M16 13h1M16 17h1M2 21h20"/>',
        'play'       => '<path d="M8 5l11 7-11 7z"/>',
        'zap'        => '<path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z"/>',
        'message'    => '<path d="M4 5h16v11H9l-5 4V5z"/>',
        'link'       => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'key'        => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'camera'     => '<path d="M4 8h3l2-3h6l2 3h3v12H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'sun'        => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'       => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"/>',
        'monitor'    => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'printer'    => '<path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7z"/>',
        'sparkle'    => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>',
        'upload'     => '<path d="M12 15V4M7 9l5-5 5 5M5 15v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>',
        'route'      => '<circle cx="6" cy="19" r="2.5"/><circle cx="18" cy="5" r="2.5"/><path d="M8.5 19H16a3.5 3.5 0 0 0 0-7H8a3.5 3.5 0 0 1 0-7h7.5"/>',
        'reply'      => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
        'unlink'     => '<path d="M9 17H7A5 5 0 0 1 7 7h2M15 7h2a5 5 0 0 1 4 8M8 12h3M2 2l20 20"/>',
    ];
}

function icon(string $name, string $class = ''): string
{
    return '<svg class="ic ' . e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"/></svg>';
}

/** Hidden sprite emitted once per panel page; both PHP and JS reference these symbols. */
function icon_sprite(): void
{
    echo '<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>';
    foreach (icon_paths() as $name => $body) {
        echo '<symbol id="i-' . $name . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $body . '</symbol>';
    }
    echo '</defs></svg>';
}
