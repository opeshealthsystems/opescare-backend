<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'OpesCare Portal'); ?></title>
    <meta name="theme-color" content="#0F4C81">
    <?php echo $__env->make('partials.favicons', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link rel="manifest" href="<?php echo e(asset('site.webmanifest')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if(file_exists(public_path('assets/finder.css'))): ?>
        <link rel="stylesheet" href="<?php echo e(asset('assets/finder.css')); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/portal.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/helix-v2.css')); ?>">
    <style>
        .sidebar-spacer{margin-bottom:var(--p-space-3,.75rem)}
        .min-w-0{min-width:0}
    </style>
    <?php echo $__env->yieldContent('head'); ?>
</head>
<body class="portal-body">

<div class="portal-wrap">

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

    <?php
        $sidebarProfileKey  = auth()->user()?->role?->dashboard_profile_key ?? 'patient';
        $sidebarRoleLabel   = auth()->user()?->role?->description ?? (auth()->user()?->roleName() ?? 'Portal');
        $topbarFacilityName = auth()->user()?->primaryFacility?->name;
    ?>

    <!-- Sidebar -->
    <aside class="portal-sidebar" id="portal-sidebar" role="navigation" aria-label="Portal navigation">

        <a href="<?php echo e(url('/')); ?>" class="sidebar-brand" aria-label="OpesCare Home">
            <div class="sidebar-brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <div>
                <div class="sidebar-brand-name">OpesCare</div>
                <div class="sidebar-brand-sub">
                    <?php if (! empty(trim($__env->yieldContent('sidebar_user_role')))): ?><?php echo $__env->yieldContent('sidebar_user_role'); ?><?php else: ?><?php echo e($sidebarRoleLabel); ?><?php endif; ?>
                </div>
            </div>
        </a>

        <div class="sidebar-nav">
            <?php if (! empty(trim($__env->yieldContent('sidebar_nav')))): ?>
                <?php echo $__env->yieldContent('sidebar_role_badge'); ?>
                <div class="sidebar-spacer"></div>
                <?php echo $__env->yieldContent('sidebar_nav'); ?>
            <?php else: ?>
                <?php if ($__env->exists('partials.sidebars.' . $sidebarProfileKey)) echo $__env->make('partials.sidebars.' . $sidebarProfileKey, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar" aria-hidden="true">
                    <?php echo e(strtoupper(substr(auth()->user()->name ?? 'U', 0, 1))); ?>

                </div>
                <div class="min-w-0">
                    <div class="sidebar-user-name"><?php echo e(auth()->user()->name ?? 'User'); ?></div>
                    <div class="sidebar-user-role">
                        <?php if (! empty(trim($__env->yieldContent('sidebar_user_role')))): ?><?php echo $__env->yieldContent('sidebar_user_role'); ?><?php else: ?><?php echo e($sidebarRoleLabel); ?><?php endif; ?>
                    </div>
                </div>
            </div>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="sidebar-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <?php echo e(__('public.portal.sign_out', [], app()->getLocale()) ?: 'Sign Out'); ?>

                </button>
            </form>
        </div>
    </aside>

    <!-- Main Area -->
    <div class="portal-main" id="portal-main">

        <!-- Topbar -->
        <header class="portal-topbar" role="banner">
            <button class="topbar-menu-btn" id="sidebar-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="portal-sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo $__env->yieldContent('breadcrumb_home_url', url('/')); ?>"><?php echo $__env->yieldContent('breadcrumb_home', 'Portal'); ?></a>
                <?php if (! empty(trim($__env->yieldContent('breadcrumb_section')))): ?>
                    <span class="topbar-breadcrumb-sep" aria-hidden="true">/</span>
                    <span class="topbar-breadcrumb-current"><?php echo $__env->yieldContent('breadcrumb_section'); ?></span>
                <?php endif; ?>
            </nav>

            <?php if($topbarFacilityName): ?>
            <div class="topbar-facility" title="<?php echo e($topbarFacilityName); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/></svg>
                <span><?php echo e($topbarFacilityName); ?></span>
            </div>
            <?php endif; ?>

            <div class="topbar-actions">
                <?php
                    $locale = app()->getLocale();
                    $otherLocale = $locale === 'fr' ? 'en' : 'fr';
                    $otherLabel  = $locale === 'fr' ? 'EN' : 'FR';
                ?>
                <a href="<?php echo e(url('/lang/' . $otherLocale)); ?>" class="topbar-lang" aria-label="Switch language to <?php echo e($otherLabel); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span><?php echo e($otherLabel); ?></span>
                </a>

                <a href="<?php echo e(route('public.help')); ?>" class="topbar-icon-btn" aria-label="Help">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </a>

                <div class="topbar-user">
                    <button type="button" class="topbar-user-btn" id="topbar-user-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="topbar-user-menu" aria-label="<?php echo e(auth()->user()->name ?? 'User'); ?>">
                        <span class="topbar-avatar" aria-hidden="true"><?php echo e(strtoupper(substr(auth()->user()->name ?? 'U', 0, 1))); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="topbar-user-menu" id="topbar-user-menu" hidden>
                        <div class="topbar-user-meta">
                            <div class="topbar-user-name"><?php echo e(auth()->user()->name ?? 'User'); ?></div>
                            <div class="topbar-user-role">
                                <?php if (! empty(trim($__env->yieldContent('sidebar_user_role')))): ?><?php echo $__env->yieldContent('sidebar_user_role'); ?><?php else: ?><?php echo e($sidebarRoleLabel); ?><?php endif; ?>
                            </div>
                            <?php if($topbarFacilityName): ?>
                                <div class="topbar-user-facility"><?php echo e($topbarFacilityName); ?></div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="topbar-user-signout">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                <?php echo e(__('public.portal.sign_out', [], app()->getLocale()) ?: 'Sign Out'); ?>

                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <?php echo $__env->yieldContent('patient_banner'); ?>

        <!-- Flash Messages -->
        <?php if(session('success')): ?>
        <div class="flash-bar" role="alert">
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                <div><?php echo e(session('success')); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
        <div class="flash-bar" role="alert">
            <div class="alert alert-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div><?php echo e(session('error')); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <main class="portal-content" id="main-content" role="main">
            <?php echo $__env->yieldContent('content'); ?>
        </main>

    </div><!-- /.portal-main -->

</div><!-- /.portal-wrap -->

<!-- Lucide Icons -->
<script src="<?php echo e(asset('js/lucide.min.js')); ?>"></script>
<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>

<!-- Portal JS -->
<script>
(function() {
    var sidebar  = document.getElementById('portal-sidebar');
    var overlay  = document.getElementById('sidebar-overlay');
    var toggleBtn = document.getElementById('sidebar-toggle');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        toggleBtn.setAttribute('aria-expanded', 'false');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', function() {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Topbar user menu
    var userToggle = document.getElementById('topbar-user-toggle');
    var userMenu   = document.getElementById('topbar-user-menu');

    function closeUserMenu() {
        if (userMenu && !userMenu.hidden) {
            userMenu.hidden = true;
            userToggle.setAttribute('aria-expanded', 'false');
        }
    }

    if (userToggle && userMenu) {
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = userMenu.hidden;
            userMenu.hidden = !open;
            userToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function(e) {
            if (!userMenu.hidden && !userMenu.contains(e.target)) closeUserMenu();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (sidebar.classList.contains('open')) closeSidebar();
            closeUserMenu();
        }
    });

    // Mark the active sidebar link.
    //
    // This compared getAttribute('href') against location.pathname. route()
    // emits ABSOLUTE urls, so it was comparing 'https://host/portals/staff'
    // with '/portals/staff' — never equal, and the whole block was dead. Only
    // the handful of links carrying a Blade routeIs() guard ever highlighted.
    //
    // Now: compare paths to paths, and fall back to the longest matching
    // prefix so a detail page (/portals/staff/visits/123) still lights up its
    // section (/portals/staff/visits) instead of leaving the user with no
    // indication of where they are.
    var currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
    var best = null, bestLen = -1;

    document.querySelectorAll('.sidebar-link').forEach(function (link) {
        var href = link.getAttribute('href') || '';
        var path;
        try {
            path = new URL(href, window.location.origin).pathname;
        } catch (e) {
            return;
        }
        path = path.replace(/\/+$/, '') || '/';

        if (path === currentPath) {
            best = link; bestLen = Infinity;               // exact wins outright
        } else if (
            bestLen !== Infinity &&
            path !== '/' &&
            currentPath.indexOf(path + '/') === 0 &&    // real segment boundary
            path.length > bestLen
        ) {
            best = link; bestLen = path.length;             // deepest prefix wins
        }
    });

    // Blade routeIs() guards already mark some links server-side; clear those
    // so exactly one item is highlighted rather than two.
    if (best) {
        document.querySelectorAll('.sidebar-link.active').forEach(function (l) {
            if (l !== best) l.classList.remove('active');
        });
        best.classList.add('active');
    }
})();
</script>

<?php echo $__env->yieldContent('scripts'); ?>

</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\layouts\portal.blade.php ENDPATH**/ ?>