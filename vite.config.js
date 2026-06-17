import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/pages/amortization.js',
                'resources/js/pages/bank.js',
                'resources/js/pages/block.js',
                'resources/js/pages/company.js',
                'resources/js/pages/customer.js',
                'resources/js/pages/holiday.js',
                'resources/js/pages/lateFeeSetting.js',
                'resources/js/pages/lot.js',
                'resources/js/pages/payment-report.js',
                'resources/js/pages/payment.js',
                'resources/js/pages/project.js',
                'resources/js/pages/report.js',
                'resources/js/pages/rescission.js',
                'resources/js/pages/roles.js',
                'resources/js/pages/sale.js',
                'resources/js/pages/user.js',
            ],
            refresh: true,
        }),
    ],
});
