
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php if (! empty(trim($__env->yieldContent('title')))): ?><title><?php echo $__env->yieldContent('title'); ?> — OpesCare Finder</title><?php else: ?><title><?php echo e(__('finder_shell.meta.default_title')); ?></title><?php endif; ?>
    <meta name="description" content="<?php echo $__env->yieldContent('description', __('finder_shell.meta.default_description')); ?>">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    
    <?php echo $__env->make('partials.favicons', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link rel="manifest" href="<?php echo e(asset('site.webmanifest')); ?>">

    <link rel="stylesheet" href="<?php echo e(asset('assets/finder.css')); ?>?v=<?php echo e(@filemtime(public_path('assets/finder.css')) ?: '1'); ?>">

    
    <?php echo $__env->make('partials.finder.schema.site', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->yieldPushContent('head'); ?>
    <?php echo $__env->yieldPushContent('schema'); ?>
</head>
<body>

    <a class="f-skip" href="#f-main"><?php echo e(__('finder_shell.a11y.skip_to_content')); ?></a>

    
    <input type="checkbox" id="f-nav-toggle" class="f-nav-toggle" aria-hidden="true" tabindex="-1">

    <header class="f-header">
        <div class="f-container f-header__row">
            <a href="<?php echo e(route('public.landing')); ?>" class="f-brand" aria-label="<?php echo e(__('finder_shell.a11y.home')); ?>">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 22, 'class' => 'f-brand__mark'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <span>OpesCare&nbsp;<span class="f-brand__finder">Finder</span></span>
            </a>

            <nav class="f-nav" aria-label="<?php echo e(__('finder_shell.a11y.main_navigation')); ?>">
                <a href="<?php echo e(route('public.network.medicine-finder')); ?>"><?php echo e(__('finder_shell.nav.find_medicine')); ?></a>
                <a href="<?php echo e(route('public.network.blood-finder')); ?>"><?php echo e(__('finder_shell.nav.find_blood')); ?></a>
                
                <a href="<?php echo e(route('public.care-map')); ?>"><?php echo e(__('finder_shell.nav.book_appointment')); ?></a>
                <a href="<?php echo e(route('public.care-map')); ?>"><?php echo e(__('finder_shell.nav.directory')); ?></a>
                <a href="<?php echo e(route('public.solutions.pharmacies')); ?>"><?php echo e(__('finder_shell.nav.for_pharmacies')); ?></a>
            </nav>

            <div class="f-header__actions">
                <div class="f-lang" aria-label="<?php echo e(__('finder_shell.a11y.language')); ?>">
                    <a href="<?php echo e(route('lang.switch', 'en')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => app()->getLocale() === 'en']); ?>" lang="en" hreflang="en">EN</a>
                    <a href="<?php echo e(route('lang.switch', 'fr')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => app()->getLocale() === 'fr']); ?>" lang="fr" hreflang="fr">FR</a>
                </div>

                <a href="<?php echo e(route('login')); ?>" class="f-btn f-btn--ghost f-nav__login"><?php echo e(__('finder_shell.nav.login')); ?></a>

                <label for="f-nav-toggle" class="f-burger" role="button" tabindex="0"
                       aria-controls="f-nav-panel" aria-expanded="false"
                       aria-label="<?php echo e(__('finder_shell.a11y.open_menu')); ?>">
                    <span class="f-burger__open"><?php echo $__env->make('partials.finder.icon', ['name' => 'menu', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-burger__close"><?php echo $__env->make('partials.finder.icon', ['name' => 'x', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </label>
            </div>
        </div>
    </header>

    
    <nav class="f-nav-panel" id="f-nav-panel" aria-label="<?php echo e(__('finder_shell.a11y.main_navigation')); ?>">
        <a href="<?php echo e(route('public.network.medicine-finder')); ?>">
            <span class="f-nav-panel__label"><?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e(__('finder_shell.nav.find_medicine')); ?></span>
            <span class="f-nav-panel__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
        </a>
        <a href="<?php echo e(route('public.network.blood-finder')); ?>">
            <span class="f-nav-panel__label"><?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e(__('finder_shell.nav.find_blood')); ?></span>
            <span class="f-nav-panel__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
        </a>
        <a href="<?php echo e(route('public.care-map')); ?>">
            <span class="f-nav-panel__label"><?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e(__('finder_shell.nav.book_appointment')); ?></span>
            <span class="f-nav-panel__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
        </a>
        <a href="<?php echo e(route('public.care-map')); ?>">
            <span class="f-nav-panel__label"><?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e(__('finder_shell.nav.directory')); ?></span>
            <span class="f-nav-panel__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
        </a>
        <a href="<?php echo e(route('public.solutions.pharmacies')); ?>">
            <span class="f-nav-panel__label"><?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e(__('finder_shell.nav.for_pharmacies')); ?></span>
            <span class="f-nav-panel__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
        </a>
        <div class="f-nav-panel__cta">
            <a href="<?php echo e(route('login')); ?>" class="f-btn f-btn--primary f-btn--block">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'log-in', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e(__('finder_shell.nav.login')); ?>

            </a>
        </div>
    </nav>

    <main id="f-main" class="f-main">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer class="f-footer" aria-label="<?php echo e(__('finder_shell.a11y.footer_navigation')); ?>">
        <div class="f-container">
            <div class="f-footer__grid">
                <div>
                    <a href="<?php echo e(route('public.landing')); ?>" class="f-brand" aria-label="<?php echo e(__('finder_shell.a11y.home')); ?>">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 20, 'class' => 'f-brand__mark'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <span>OpesCare&nbsp;<span class="f-brand__finder">Finder</span></span>
                    </a>
                    <p class="f-footer__tagline"><?php echo e(__('finder_shell.brand.tagline')); ?></p>
                </div>

                <nav aria-label="<?php echo e(__('finder_shell.footer.col_services')); ?>">
                    <h2 class="f-footer__title"><?php echo e(__('finder_shell.footer.col_services')); ?></h2>
                    <ul>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.network.medicine-finder')); ?>"><?php echo e(__('finder_shell.nav.find_medicine')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.network.blood-finder')); ?>"><?php echo e(__('finder_shell.nav.find_blood')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.care-map')); ?>"><?php echo e(__('finder_shell.nav.book_appointment')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.care-map')); ?>"><?php echo e(__('finder_shell.nav.directory')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.care-map.emergency')); ?>"><?php echo e(__('finder_shell.footer.emergency')); ?></a></li>
                    </ul>
                </nav>

                <nav aria-label="<?php echo e(__('finder_shell.footer.col_facilities')); ?>">
                    <h2 class="f-footer__title"><?php echo e(__('finder_shell.footer.col_facilities')); ?></h2>
                    <ul>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.solutions.pharmacies')); ?>"><?php echo e(__('finder_shell.nav.for_pharmacies')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.solutions.hospitals')); ?>"><?php echo e(__('finder_shell.footer.for_hospitals')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('login')); ?>"><?php echo e(__('finder_shell.nav.login')); ?></a></li>
                    </ul>
                </nav>

                <nav aria-label="<?php echo e(__('finder_shell.footer.col_legal')); ?>">
                    <h2 class="f-footer__title"><?php echo e(__('finder_shell.footer.col_legal')); ?></h2>
                    <ul>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.terms')); ?>"><?php echo e(__('finder_shell.footer.terms')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.privacy')); ?>"><?php echo e(__('finder_shell.footer.privacy')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.help')); ?>"><?php echo e(__('finder_shell.footer.help')); ?></a></li>
                        <li><a class="f-footer__link" href="<?php echo e(route('public.contact')); ?>"><?php echo e(__('finder_shell.footer.contact')); ?></a></li>
                    </ul>
                </nav>
            </div>

            <div class="f-footer__meta">
                <p><?php echo e(__('finder_shell.footer.attribution')); ?></p>
                <p><?php echo e(__('finder_shell.footer.disclaimer')); ?></p>
                <div class="f-footer__meta-row">
                    <p><?php echo e(__('finder_shell.footer.copyright', ['year' => date('Y')])); ?></p>
                    <p>
                        <a class="f-footer__link" href="<?php echo e(route('lang.switch', 'en')); ?>" lang="en" hreflang="en">English</a>
                        &nbsp;·&nbsp;
                        <a class="f-footer__link" href="<?php echo e(route('lang.switch', 'fr')); ?>" lang="fr" hreflang="fr">Français</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    
    <script>
    (function () {
        var t = document.getElementById('f-nav-toggle');
        var b = document.querySelector('.f-burger');
        if (!t || !b) return;
        var openLabel = <?php echo json_encode(__('finder_shell.a11y.open_menu'), 15, 512) ?>;
        var closeLabel = <?php echo json_encode(__('finder_shell.a11y.close_menu'), 15, 512) ?>;
        function sync() {
            b.setAttribute('aria-expanded', t.checked ? 'true' : 'false');
            b.setAttribute('aria-label', t.checked ? closeLabel : openLabel);
        }
        t.addEventListener('change', sync);
        b.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); t.checked = !t.checked; sync(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && t.checked) { t.checked = false; sync(); }
        });
    })();
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/layouts/finder.blade.php ENDPATH**/ ?>