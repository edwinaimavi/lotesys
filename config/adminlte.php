<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */

    'title' => 'CiCoSys',
    'title_prefix' => '',
    'title_postfix' => ' | Sistema Inmobiliario',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    */

    'logo' => '<b>CiCo</b>Sys',
    'logo_img' => 'vendor/adminlte/dist/img/logoblanco.png',
    'logo_img_class' => 'brand-image elevation-0',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'CiCoSys Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    */

    'auth_logo' => [
        'enabled' => true,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/logoblanco.png',
            'alt' => 'CiCoSys Auth Logo',
            'class' => '',
            'width' => 180,
            'height' => 70,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    */

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'vendor/adminlte/dist/img/logoblanco.png',
            'alt' => 'CiCoSys Preloader',
            'effect' => 'animation__pulse',
            'width' => 130,
            'height' => 70,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    */

    'usermenu_enabled' => true,
    'usermenu_header' => true,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => true,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    */

    'classes_auth_card' => 'card-outline card-primary shadow-lg',
    'classes_auth_header' => 'text-center',
    'classes_auth_body' => 'px-4 py-4',
    'classes_auth_footer' => 'text-center',
    'classes_auth_icon' => 'text-primary',
    'classes_auth_btn' => 'btn-primary btn-flat',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    */

    'classes_body' => 'layout-fixed text-sm',
    'classes_brand' => 'bg-gradient-navy border-bottom-0',
    'classes_brand_text' => 'font-weight-bold text-white',
    'classes_content_wrapper' => 'bg-light',
    'classes_content_header' => 'pb-0',
    'classes_content' => 'pt-2',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => 'nav-child-indent nav-compact',
    'classes_topnav' => 'navbar-white navbar-light border-bottom-0 shadow-sm',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container-fluid',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => true,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar
    |--------------------------------------------------------------------------
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-sliders-h',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    'use_route_url' => false,
    'dashboard_url' => 'home',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    */

    'menu' => [

        /*
        |--------------------------------------------------------------------------
        | Navbar superior
        |--------------------------------------------------------------------------
        */

        [
            'type' => 'navbar-search',
            'text' => 'Buscar',
            'topnav_right' => false,
        ],

        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Buscador lateral
        |--------------------------------------------------------------------------
        */

        [
            'type' => 'sidebar-menu-search',
            'text' => 'Buscar en menú',
        ],

        /*
        |--------------------------------------------------------------------------
        | Inicio
        |--------------------------------------------------------------------------
        */

        [
            'text' => 'Panel Principal',
            'url' => 'home',
            'icon' => 'fas fa-tachometer-alt',
            'classes' => 'mt-2',
        ],

        ['header' => 'ADMINISTRACIÓN'],

        [
            'text' => 'Usuarios y Accesos',
            'icon' => 'fas fa-user-shield',
            'submenu' => [
                [
                    'text' => 'Roles',
                    'url' => 'admin/roles',
                    'icon' => 'fas fa-user-lock',
                    'icon_color' => 'info',
                    'can' => 'admin.roles.index',
                ],
                [
                    'text' => 'Usuarios',
                    'url' => 'admin/users',
                    'icon' => 'fas fa-users-cog',
                    'icon_color' => 'warning',
                    'can' => 'admin.users.index',
                ],
            ],
        ],

        [
            'text' => 'Configuración',
            'icon' => 'fas fa-cogs',
            'submenu' => [
                [
                    'text' => 'Empresas',
                    'url' => 'admin/companies',
                    'icon' => 'fas fa-building',
                    'icon_color' => 'primary',
                    'can' => 'admin.roles.index',
                ],
                [
                    'text' => 'Bancos',
                    'url' => 'admin/banks',
                    'icon' => 'fas fa-university',
                    'icon_color' => 'warning',
                    'can' => 'admin.users.index',
                ],
                [
                    'text' => 'Configuración de Mora',
                    'url' => 'admin/lateFeeSettings',
                    'icon' => 'fas fa-percentage',
                    'icon_color' => 'success',
                    'can' => 'admin.users.index',
                ],
                [
                    'text' => 'Feriados',
                    'url' => 'admin/holidays',
                    'icon' => 'fas fa-calendar-alt',
                    'icon_color' => 'danger',
                    'can' => 'admin.users.index',
                ],
            ],
        ],

        ['header' => 'GESTIÓN INMOBILIARIA'],

        [
            'text' => 'Proyectos',
            'url' => 'admin/projects',
            'icon' => 'fas fa-map-marked-alt',
            'label_color' => 'primary',
        ],

        [
            'text' => 'Manzanas',
            'url' => 'admin/blocks',
            'icon' => 'fas fa-th-large',
            'label_color' => 'info',
        ],

        [
            'text' => 'Lotes',
            'url' => 'admin/lots',
            'icon' => 'fas fa-map',
            'label_color' => 'success',
        ],

        [
            'text' => 'Clientes',
            'url' => 'admin/customers',
            'icon' => 'fas fa-address-book',
            'label_color' => 'success',
        ],

        ['header' => 'OPERACIONES'],

        [
            'text' => 'Ventas',
            'icon' => 'fas fa-file-invoice-dollar',
            'submenu' => [
                [
                    'text' => 'Registro de Ventas',
                    'url' => 'admin/sales',
                    'icon' => 'fas fa-file-contract',
                    'icon_color' => 'primary',
                    'can' => 'admin.roles.index',
                ],
                [
                    'text' => 'Pagos',
                    'url' => 'admin/payments',
                    'icon' => 'fas fa-money-bill-wave',
                    'icon_color' => 'success',
                    'can' => 'admin.users.index',
                ],
                [
                    'text' => 'Amortizaciones',
                    'url' => 'admin/amortizations',
                    'icon' => 'fas fa-hand-holding-usd',
                    'icon_color' => 'info',
                    'can' => 'admin.users.index',
                ],
                [
                    'text' => 'Rescisión',
                    'url' => 'admin/rescissions',
                    'icon' => 'fas fa-ban',
                    'icon_color' => 'danger',
                    'can' => 'admin.users.index',
                ],
            ],
        ],

        [
            'text' => 'Comprobantes',
            'url' => 'admin/invoices',
            'icon' => 'fas fa-receipt',
            'label_color' => 'warning',
        ],

        ['header' => 'ANÁLISIS'],

        [
            'text' => 'Reportes',
            'url' => 'admin/reports',
            'icon' => 'fas fa-chart-pie',
            'label_color' => 'success',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    */

    'plugins' => [

        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],

        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],

        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],

        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],

        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    */

    'livewire' => false,
];
