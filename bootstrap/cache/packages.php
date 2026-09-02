<?php return array (
  'barryvdh/laravel-dompdf' => 
  array (
    'aliases' => 
    array (
      'PDF' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
      'Pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
    ),
    'providers' => 
    array (
      0 => 'Barryvdh\\DomPDF\\ServiceProvider',
    ),
  ),
  'laravel/horizon' => 
  array (
    'aliases' => 
    array (
      'Horizon' => 'Laravel\\Horizon\\Horizon',
    ),
    'providers' => 
    array (
      0 => 'Laravel\\Horizon\\HorizonServiceProvider',
    ),
  ),
  'laravel/pail' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Pail\\PailServiceProvider',
    ),
  ),
  'laravel/pao' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    ),
  ),
  'laravel/sentinel' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Sentinel\\SentinelServiceProvider',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
  'nunomaduro/termwind' => 
  array (
    'providers' => 
    array (
      0 => 'Termwind\\Laravel\\TermwindServiceProvider',
    ),
  ),
  'sentry/sentry-laravel' => 
  array (
    'aliases' => 
    array (
      'Sentry' => 'Sentry\\Laravel\\Facade',
    ),
    'providers' => 
    array (
      0 => 'Sentry\\Laravel\\ServiceProvider',
      1 => 'Sentry\\Laravel\\Tracing\\ServiceProvider',
    ),
  ),
  'spatie/laravel-backup' => 
  array (
    'providers' => 
    array (
      0 => 'Spatie\\Backup\\BackupServiceProvider',
    ),
  ),
  'spatie/laravel-signal-aware-command' => 
  array (
    'aliases' => 
    array (
      'Signal' => 'Spatie\\SignalAwareCommand\\Facades\\Signal',
    ),
    'providers' => 
    array (
      0 => 'Spatie\\SignalAwareCommand\\SignalAwareCommandServiceProvider',
    ),
  ),
);