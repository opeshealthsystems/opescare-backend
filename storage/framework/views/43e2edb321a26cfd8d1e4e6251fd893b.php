<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'OpesCare | Secure Gateway'); ?></title>
    <meta name="description" content="Access your OpesCare secure clinical profile, Health ID registry, or organizational workspace.">
    <meta name="theme-color" content="#0F4C81">

    <!-- Favicon -->
    <?php echo $__env->make('partials.favicons', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link rel="manifest" href="<?php echo e(asset('site.webmanifest')); ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Stylesheets (cache-busted by file mtime so updated CSS/icons are never
         served stale from the browser cache) -->
    <link rel="stylesheet" href="<?php echo e(asset('css/auth.css')); ?>?v=<?php echo e(@filemtime(public_path('css/auth.css')) ?: '1'); ?>">

    <!-- Lucide Icons -->
    <script src="<?php echo e(asset('js/lucide.min.js')); ?>?v=<?php echo e(@filemtime(public_path('js/lucide.min.js')) ?: '1'); ?>"></script>
    <style>
        .brand-shield-icon{width:2.25rem;height:2.25rem;color:#38BDF8}
        .auth-sep{color:var(--auth-border,#e2e8f0)}
        .auth-layout-footer{margin-top:3rem;font-size:.8rem;text-align:center;color:var(--auth-text-muted,#94a3b8)}
        .auth-footer-link{color:var(--auth-primary,#0F4C81);font-weight:700;text-decoration:none}
    </style>
</head>
<body class="auth-body">

    <div class="auth-split-container">
        
        <!-- Left Sidebar: Medical Trust Points & Branding -->
        <aside class="auth-sidebar">
            <div class="sidebar-header">
                <a href="/" class="sidebar-logo">
                    <i data-lucide="shield-check" class="brand-shield-icon"></i>
                    <span>OpesCare</span>
                </a>
                <div class="sidebar-tagline"><?php echo e(__('onboarding.brand.tagline')); ?></div>
            </div>
            
            <div class="sidebar-content">
                <h2 class="sidebar-headline"><?php echo e(__('onboarding.login.welcome_back')); ?></h2>
                
                <div class="trust-bullets">
                    <div class="trust-bullet">
                        <div class="bullet-icon">
                            <i data-lucide="badge-check"></i>
                        </div>
                        <div class="bullet-text">
                            <strong><?php echo e(__('onboarding.brand.bullet_1_title')); ?></strong>
                            <p><?php echo e(__('onboarding.brand.bullet_1_desc')); ?></p>
                        </div>
                    </div>
                    
                    <div class="trust-bullet">
                        <div class="bullet-icon">
                            <i data-lucide="shield-plus"></i>
                        </div>
                        <div class="bullet-text">
                            <strong><?php echo e(__('onboarding.brand.bullet_2_title')); ?></strong>
                            <p><?php echo e(__('onboarding.brand.bullet_2_desc')); ?></p>
                        </div>
                    </div>
                    
                    <div class="trust-bullet">
                        <div class="bullet-icon">
                            <i data-lucide="code-2"></i>
                        </div>
                        <div class="bullet-text">
                            <strong><?php echo e(__('onboarding.brand.bullet_3_title')); ?></strong>
                            <p><?php echo e(__('onboarding.brand.bullet_3_desc')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-footer">
                <div class="sidebar-safety">
                    <i data-lucide="shield-alert"></i>
                    <div>
                        <strong><?php echo e(__('onboarding.brand.safety_disclaimer')); ?></strong>
                        <p><?php echo e(__('onboarding.brand.shield_note')); ?></p>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Right Content Plane: Active View Form -->
        <main class="auth-main">
            <div class="auth-header-nav">
                <!-- Dynamic Language Switcher -->
                <div class="auth-lang-selector">
                    <a href="<?php echo e(route('lang.switch', 'en')); ?>" class="<?php echo e(app()->getLocale() == 'en' ? 'active' : ''); ?>">English</a>
                    <span class="auth-sep">|</span>
                    <a href="<?php echo e(route('lang.switch', 'fr')); ?>" class="<?php echo e(app()->getLocale() == 'fr' ? 'active' : ''); ?>">Français</a>
                </div>
            </div>
            
            <div class="auth-card-wrapper">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
            
            <!-- Support Footer Fallback -->
            <div class="auth-footer-links auth-layout-footer">
                <p><?php echo e(__('onboarding.brand.need_help')); ?> 
                    <a href="<?php echo e(route('public.contact')); ?>" class="auth-footer-link">
                        <?php echo e(__('onboarding.brand.contact_support')); ?>

                    </a>
                </p>
            </div>
        </main>
        
    </div>

    <?php echo $__env->yieldContent('scripts'); ?>

    <!-- Auth-page behaviour (icon rendering, password toggle, demo handlers).
         External file — the strict CSP (script-src 'self', no 'unsafe-inline')
         blocks inline scripts, so this must be loaded from 'self'. -->
    <script src="<?php echo e(asset('js/auth.js')); ?>?v=<?php echo e(@filemtime(public_path('js/auth.js')) ?: '1'); ?>"></script>
</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\layouts\auth.blade.php ENDPATH**/ ?>