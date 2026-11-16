<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Financial_Admin')) {
    return;
}

class Tipax_Financial_Admin
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function format_amount_value($amount)
    {
        return number_format((float) $amount);
    }

    private function format_amount($amount)
    {
        return $this->format_amount_value($amount) . ' ریال';
    }

    private function render_amount_html($amount)
    {
        return '<span class="tipax-financial-amount-wrap"><bdi class="tipax-financial-amount-number" dir="ltr">' . esc_html($this->format_amount_value($amount)) . '</bdi><span class="tipax-financial-amount-currency">ریال</span></span>';
    }

    private function render_date_html($value)
    {
        $value = trim((string) $value);

        if (preg_match('/^(.+?)\s+([\p{N}]{1,2}:[\p{N}]{2})$/u', $value, $parts)) {
            return '<span class="tipax-financial-datetime"><bdi dir="ltr">' . esc_html($parts[1]) . '</bdi><bdi dir="ltr">' . esc_html($parts[2]) . '</bdi></span>';
        }

        return '<bdi dir="ltr">' . esc_html($value) . '</bdi>';
    }

    private function render_kpi($label, $value, $modifier = '', $allow_html = false)
    {
        $modifierClass = $modifier !== '' ? ' ' . $modifier : '';

        echo '<div class="tipax-financial-kpi' . esc_attr($modifierClass) . '">';
        echo '<span class="tipax-financial-kpi-label">' . esc_html($label) . '</span>';
        echo '<strong class="tipax-financial-kpi-value">' . ($allow_html ? $value : esc_html($value)) . '</strong>';
        echo '</div>';
    }

    private function get_wallet_balance()
    {
        $customerService = new Tipax_Customer_Service();
        $walletResponse = $customerService->get_wallet_remaining_asset();

        if (is_numeric($walletResponse)) {
            return (float) $walletResponse;
        }

        return null;
    }

    public function render_page()
    {
        tipax_admin_tabs('tipax-financial');

        if (!tipax_is_connection_validated()) {
            echo '<div class="notice notice-error"><p>برای دسترسی به این بخش ابتدا باید <a href="' . esc_url(admin_url('admin.php?page=tipax-api-config')) . '">تنظیمات اتصال</a> را کامل کرده و اتصال را <a href="' . esc_url(admin_url('admin.php?page=tipax-test-connection')) . '">آزمایش</a> کنید.</p></div>';
            return;
        }

        $financialService = new Tipax_Financial_Service();
        $documentsResult = $financialService->get_documents(100);
        $walletBalance = $this->get_wallet_balance();
        $refreshUrl = admin_url('admin.php?page=tipax-financial&refresh=1');

        echo '<div class="tipax-action-panel tipax-financial-panel">';
        echo '<div class="tipax-action-icon">' . tipax_admin_icon('chart-bar') . '</div>';
        echo '<div class="tipax-action-content">';
        echo '<h2>گزارش مالی و گردش حساب تیپاکس</h2>';
        echo '<p>حداکثر 100 تراکنش آخر از سرویس مالی تیپاکس نمایش داده می‌شود. این بخش برای بررسی بستانکار، بدهکار و شرح اسناد مالی مناسب است.</p>';
        echo '</div>';
        echo '<div class="tipax-financial-panel-actions">';
        echo '<a class="button button-primary" href="' . esc_url($refreshUrl) . '">بروزرسانی لیست تراکنش‌ها</a>';
        echo '</div>';
        echo '</div>';

        if (!$documentsResult['success']) {
            echo '<div class="notice notice-error"><p>' . esc_html($documentsResult['message']) . '</p></div>';
            return;
        }

        $summary = isset($documentsResult['summary']) && is_array($documentsResult['summary'])
            ? $documentsResult['summary']
            : [
                'count' => 0,
                'credit_total' => 0.0,
                'debt_total' => 0.0,
                'net_total' => 0.0,
            ];

        echo '<div class="tipax-financial-kpis">';
        $this->render_kpi('تعداد تراکنش‌ها', (string) (int) ($summary['count'] ?? 0));
        $this->render_kpi('مجموع بستانکار', $this->render_amount_html($summary['credit_total'] ?? 0), 'is-credit', true);
        $this->render_kpi('مجموع بدهکار', $this->render_amount_html($summary['debt_total'] ?? 0), 'is-debt', true);
        $this->render_kpi(
            'خالص گردش',
            $this->render_amount_html($summary['net_total'] ?? 0),
            (($summary['net_total'] ?? 0) >= 0 ? 'is-credit' : 'is-debt'),
            true
        );

        if ($walletBalance !== null) {
            $this->render_kpi('موجودی فعلی کیف پول', $this->render_amount_html($walletBalance), '', true);
        }
        echo '</div>';

        $documents = isset($documentsResult['documents']) && is_array($documentsResult['documents'])
            ? $documentsResult['documents']
            : [];

        if (empty($documents)) {
            echo '<div class="tipax-result-area"><div class="tipax-empty-state">' . tipax_admin_icon('media-text') . '<p>هیچ تراکنش مالی برای نمایش دریافت نشد.</p></div></div>';
            return;
        }

        echo '<div class="tipax-financial-note">نمایش حداکثر 100 تراکنش آخر.</div>';
        echo '<table class="widefat striped tipax-data-table tipax-financial-table">';
        echo '<thead><tr>';
        echo '<th>شناسه سند</th>';
        echo '<th>تاریخ ثبت</th>';
        echo '<th>تاریخ موثر</th>';
        echo '<th>بدهکار</th>';
        echo '<th>بستانکار</th>';
        echo '<th>شرح</th>';
        echo '</tr></thead><tbody>';

        foreach ($documents as $document) {
            $description = !empty($document['user_desc']) ? $document['user_desc'] : ($document['description'] ?? '');

            echo '<tr>';
            echo '<td>#' . esc_html((string) ($document['id'] ?? 0)) . '</td>';
            echo '<td>' . $this->render_date_html($document['insert_date_display'] ?? '-') . '</td>';
            echo '<td>' . $this->render_date_html($document['effective_date_display'] ?? '-') . '</td>';
            echo '<td class="tipax-financial-amount is-debt">' . $this->render_amount_html($document['debt'] ?? 0) . '</td>';
            echo '<td class="tipax-financial-amount is-credit">' . $this->render_amount_html($document['credit'] ?? 0) . '</td>';
            echo '<td class="tipax-financial-description">' . esc_html((string) $description) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

function tipax_financial_page()
{
    Tipax_Financial_Admin::instance()->render_page();
}
