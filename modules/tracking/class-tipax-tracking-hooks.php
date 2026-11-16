<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Tracking_Hooks
{
    private const CRON_HOOK = 'tipax_daily_tracking_sync';
    private const ACCOUNT_ENDPOINT = 'tipax-tracking';
    private const PUBLIC_AJAX_ACTION = 'tipax_public_track_shipment';
    private const ADMIN_BATCH_AJAX_ACTION = 'tipax_batch_update_order_statuses';

    private static $assetsEnqueued = false;

    public function __construct()
    {
        add_action('init', [$this, 'register_account_endpoint']);
        add_action('init', [$this, 'maybe_refresh_endpoint_rules'], 20);
        add_action('init', [$this, 'ensure_daily_tracking_schedule']);
        add_filter('query_vars', [$this, 'register_query_var']);
        add_filter('woocommerce_get_query_vars', [$this, 'register_wc_query_var'], 999);

        add_action(self::CRON_HOOK, [$this, 'run_daily_tracking_sync']);

        add_shortcode('tipax_tracking', [$this, 'render_tracking_shortcode']);
        add_filter('widget_text', [$this, 'filter_embedded_tracking_shortcode'], 20);
        add_filter('widget_text_content', [$this, 'filter_embedded_tracking_shortcode'], 20);
        add_filter('elementor/frontend/the_content', [$this, 'filter_embedded_tracking_shortcode'], 20);
        add_filter('elementor/widget/render_content', [$this, 'filter_elementor_widget_content'], 20, 2);

        add_filter('woocommerce_account_menu_items', [$this, 'add_account_menu_item'], 999);
        add_action('woocommerce_account_' . self::ACCOUNT_ENDPOINT . '_endpoint', [$this, 'render_account_endpoint']);
        add_action('woocommerce_account_dashboard', [$this, 'render_account_dashboard_shortcut'], 40);

        add_action('wp_ajax_' . self::PUBLIC_AJAX_ACTION, [$this, 'handle_public_tracking_ajax']);
        add_action('wp_ajax_nopriv_' . self::PUBLIC_AJAX_ACTION, [$this, 'handle_public_tracking_ajax']);

        add_action('wp_ajax_' . self::ADMIN_BATCH_AJAX_ACTION, [$this, 'handle_admin_batch_tracking_ajax']);
    }

    public function register_account_endpoint()
    {
        add_rewrite_endpoint(self::ACCOUNT_ENDPOINT, EP_ROOT | EP_PAGES);
    }

    public function register_query_var($vars)
    {
        if (!is_array($vars)) {
            $vars = [];
        }

        if (!in_array(self::ACCOUNT_ENDPOINT, $vars, true)) {
            $vars[] = self::ACCOUNT_ENDPOINT;
        }

        return $vars;
    }

    public function register_wc_query_var($vars)
    {
        if (!is_array($vars)) {
            $vars = [];
        }

        if (empty($vars[self::ACCOUNT_ENDPOINT])) {
            $vars[self::ACCOUNT_ENDPOINT] = self::ACCOUNT_ENDPOINT;
        }

        return $vars;
    }

    public function maybe_refresh_endpoint_rules()
    {
        $storedVersion = get_option('tipax_tracking_endpoint_version');
        if ($storedVersion === TSM_VERSION) {
            return;
        }

        $this->register_account_endpoint();
        flush_rewrite_rules(false);
        update_option('tipax_tracking_endpoint_version', TSM_VERSION, false);
    }

    public function ensure_daily_tracking_schedule()
    {
        if (wp_next_scheduled(self::CRON_HOOK)) {
            return;
        }

        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
    }

    private function enqueue_frontend_assets()
    {
        if (self::$assetsEnqueued) {
            return;
        }

        $stylePath = TSM_DIR . 'assets/css/tipax-tracking.css';
        $scriptPath = TSM_DIR . 'assets/frontend/js/tipax-tracking.js';
        $styleVersion = TSM_VERSION;
        $scriptVersion = TSM_VERSION;

        if (file_exists($stylePath)) {
            $styleVersion .= '.' . filemtime($stylePath);
        }

        if (file_exists($scriptPath)) {
            $scriptVersion .= '.' . filemtime($scriptPath);
        }

        wp_enqueue_style(
            'tipax-tracking-frontend',
            TSM_ASSETS_URL . 'css/tipax-tracking.css',
            [],
            $styleVersion
        );

        wp_enqueue_script(
            'tipax-tracking-frontend',
            TSM_ASSETS_URL . 'frontend/js/tipax-tracking.js',
            ['jquery'],
            $scriptVersion,
            true
        );

        wp_localize_script('tipax-tracking-frontend', 'tipaxTrackingConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tipax_public_tracking'),
            'action' => self::PUBLIC_AJAX_ACTION,
            'loadingText' => 'در حال دریافت وضعیت مرسوله...',
            'buttonText' => 'رهگیری مرسوله',
            'rateLimitText' => 'تعداد درخواست‌های رهگیری زیاد شده است. چند دقیقه دیگر دوباره تلاش کنید.',
            'unexpectedErrorText' => 'دریافت وضعیت مرسوله با خطا مواجه شد.',
        ]);

        self::$assetsEnqueued = true;
    }

    private function render_tracking_shell($args = [])
    {
        $defaults = [
            'title' => 'رهگیری مرسولات تیپاکس',
            'subtitle' => 'کد رهگیری یا کد قرارداد تیپاکس را وارد کنید تا آخرین وضعیت مرسوله، مسیر حرکت و زمان آخرین بروزرسانی را ببینید.',
            'input_label' => 'کد رهگیری یا کد قرارداد',
            'input_placeholder' => 'کد رهگیری یا شماره قرارداد تیپاکس را وارد کنید',
            'button_label' => 'رهگیری مرسوله',
            'context' => 'shortcode',
            'prefill' => '',
        ];
        $args = wp_parse_args($args, $defaults);
        $inputId = 'tipax_tracking_code_' . wp_generate_uuid4();

        $this->enqueue_frontend_assets();

        ob_start();
        ?>
        <div class="tipax-tracking-widget" data-tipax-tracking-widget data-context="<?php echo esc_attr($args['context']); ?>">
            <section class="tipax-tracking-shell">
                <div class="tipax-tracking-hero">
                    <div class="tipax-tracking-hero-copy">
                        <span class="tipax-tracking-eyebrow">Tipax Tracking</span>
                        <h2><?php echo esc_html($args['title']); ?></h2>
                        <p><?php echo esc_html($args['subtitle']); ?></p>
                    </div>
                    <div class="tipax-tracking-hero-badge">
                        <span class="tipax-tracking-badge-dot"></span>
                        <span>رهگیری آنلاین</span>
                    </div>
                </div>

                <form class="tipax-tracking-form" data-tipax-tracking-form novalidate>
                    <label class="tipax-tracking-field" for="<?php echo esc_attr($inputId); ?>">
                        <span><?php echo esc_html($args['input_label']); ?></span>
                        <input
                            type="text"
                            id="<?php echo esc_attr($inputId); ?>"
                            name="tracking_code"
                            value="<?php echo esc_attr($args['prefill']); ?>"
                            placeholder="<?php echo esc_attr($args['input_placeholder']); ?>"
                            inputmode="text"
                            autocomplete="off"
                            required
                        />
                    </label>
                    <button type="submit" class="tipax-tracking-submit" data-tipax-submit>
                        <?php echo esc_html($args['button_label']); ?>
                    </button>
                </form>

                <div class="tipax-tracking-hints">
                    <span>نتیجه به‌صورت زنده از تیپاکس خوانده می‌شود.</span>
                    <span>کد کاملاً عددی به‌عنوان کد قرارداد شناسایی می‌شود.</span>
                </div>

                <div class="tipax-tracking-result" data-tipax-tracking-result>
                    <div class="tipax-tracking-empty-state">
                        <div class="tipax-tracking-empty-icon"></div>
                        <strong>مرسوله آماده رهگیری است</strong>
                        <p>بعد از وارد کردن کد رهگیری یا قرارداد، وضعیت جاری مرسوله و رویدادهای آخر نمایش داده می‌شود.</p>
                    </div>
                </div>
            </section>
        </div>
        <?php

        return ob_get_clean();
    }

    private function render_metric($label, $value, $emphasis = false)
    {
        if ($value === '') {
            return '';
        }

        $className = $emphasis ? ' tipax-tracking-metric-value--accent' : '';

        ob_start();
        ?>
        <div class="tipax-tracking-metric">
            <span class="tipax-tracking-metric-label"><?php echo esc_html($label); ?></span>
            <strong class="tipax-tracking-metric-value<?php echo esc_attr($className); ?>"><?php echo esc_html($value); ?></strong>
        </div>
        <?php

        return ob_get_clean();
    }

    private function render_tracking_result_card(array $viewData)
    {
        $status = isset($viewData['status']) ? (string) $viewData['status'] : 'نامشخص';
        $trackingCode = isset($viewData['tracking_code']) ? (string) $viewData['tracking_code'] : '';
        $contract = isset($viewData['contract']) ? (string) $viewData['contract'] : '';
        $checkedAt = isset($viewData['checked_at_display']) ? (string) $viewData['checked_at_display'] : '';
        $lastEventAt = isset($viewData['last_event_at_display']) ? (string) $viewData['last_event_at_display'] : '';
        $timeline = isset($viewData['timeline']) && is_array($viewData['timeline']) ? $viewData['timeline'] : [];

        ob_start();
        ?>
        <article class="tipax-tracking-card is-success">
            <div class="tipax-tracking-card-head">
                <div>
                    <span class="tipax-tracking-status-label">وضعیت فعلی</span>
                    <h3><?php echo esc_html($status); ?></h3>
                </div>
                <div class="tipax-tracking-status-pill"><?php echo esc_html($trackingCode); ?></div>
            </div>

            <div class="tipax-tracking-metrics">
                <?php echo $this->render_metric('کد رهگیری', $trackingCode, true); ?>
                <?php echo $this->render_metric('کد قرارداد', $contract); ?>
                <?php echo $this->render_metric('آخرین بررسی', $checkedAt); ?>
                <?php echo $this->render_metric('آخرین رویداد', $lastEventAt); ?>
                <?php echo $this->render_metric('فرستنده', isset($viewData['sender']) ? (string) $viewData['sender'] : ''); ?>
                <?php echo $this->render_metric('گیرنده', isset($viewData['receiver']) ? (string) $viewData['receiver'] : ''); ?>
                <?php echo $this->render_metric('مبدا', isset($viewData['origin']) ? (string) $viewData['origin'] : ''); ?>
                <?php echo $this->render_metric('مقصد', isset($viewData['destination']) ? (string) $viewData['destination'] : ''); ?>
            </div>

            <div class="tipax-tracking-timeline-wrap">
                <div class="tipax-tracking-timeline-head">
                    <h4>مسیر و بروزرسانی‌های اخیر</h4>
                    <span><?php echo esc_html(count($timeline)); ?> رویداد</span>
                </div>

                <?php if (!empty($timeline)) : ?>
                    <ol class="tipax-tracking-timeline">
                        <?php foreach ($timeline as $index => $event) : ?>
                            <?php
                            $title = isset($event['title']) ? trim((string) $event['title']) : '';
                            $description = isset($event['description']) ? trim((string) $event['description']) : '';
                            $location = isset($event['location']) ? trim((string) $event['location']) : '';
                            $date = isset($event['date']) ? trim((string) $event['date']) : '';
                            if ($title === '') {
                                continue;
                            }
                            ?>
                            <li class="tipax-tracking-timeline-item<?php echo $index === 0 ? ' is-current' : ''; ?>">
                                <div class="tipax-tracking-timeline-marker"></div>
                                <div class="tipax-tracking-timeline-body">
                                    <div class="tipax-tracking-timeline-row">
                                        <strong><?php echo esc_html($title); ?></strong>
                                        <?php if ($date !== '') : ?>
                                            <span><?php echo esc_html($date); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($location !== '') : ?>
                                        <div class="tipax-tracking-timeline-meta"><?php echo esc_html($location); ?></div>
                                    <?php endif; ?>
                                    <?php if ($description !== '') : ?>
                                        <p><?php echo esc_html($description); ?></p>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else : ?>
                    <div class="tipax-tracking-inline-empty">
                        <p>جزئیات رویدادها از پاسخ تیپاکس قابل استخراج نبود، اما وضعیت فعلی مرسوله با موفقیت دریافت شد.</p>
                    </div>
                <?php endif; ?>
            </div>
        </article>
        <?php

        return ob_get_clean();
    }

    private function render_tracking_results(array $views, $lookupType = 'tracking', $contractCode = '')
    {
        $views = array_values(array_filter($views, 'is_array'));
        if (empty($views)) {
            return $this->render_tracking_error_card('پاسخ رهگیری نامعتبر است', [
                'اطلاعات قابل نمایشی از پاسخ تیپاکس دریافت نشد.',
            ]);
        }

        ob_start();
        ?>
        <div class="tipax-tracking-results">
            <?php if ($lookupType === 'contract') : ?>
                <div class="tipax-tracking-contract-summary">
                    <div>
                        <span>نتیجه رهگیری قرارداد</span>
                        <strong><?php echo esc_html($contractCode); ?></strong>
                    </div>
                    <div class="tipax-tracking-contract-count">
                        <?php echo esc_html(number_format_i18n(count($views))); ?> مرسوله
                    </div>
                </div>
            <?php endif; ?>

            <div class="tipax-tracking-result-list">
                <?php foreach ($views as $viewData) : ?>
                    <?php echo $this->render_tracking_result_card($viewData); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    private function render_tracking_error_card($title, array $messages = [])
    {
        if (empty($messages)) {
            $messages = ['دریافت وضعیت مرسوله با خطا مواجه شد.'];
        }

        ob_start();
        ?>
        <article class="tipax-tracking-card is-error">
            <div class="tipax-tracking-card-head">
                <div>
                    <span class="tipax-tracking-status-label">خطا در رهگیری</span>
                    <h3><?php echo esc_html($title); ?></h3>
                </div>
                <div class="tipax-tracking-status-pill is-error">ناموفق</div>
            </div>
            <ul class="tipax-tracking-error-list">
                <?php foreach ($messages as $message) : ?>
                    <?php if (!is_scalar($message) || trim((string) $message) === '') { continue; } ?>
                    <li><?php echo esc_html((string) $message); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <?php

        return ob_get_clean();
    }

    private function is_rate_limited()
    {
        $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : 'guest';
        $key = 'tipax_tracking_rl_' . md5($remoteAddress);
        $count = (int) get_transient($key);

        if ($count >= 30) {
            return true;
        }

        set_transient($key, $count + 1, 10 * MINUTE_IN_SECONDS);
        return false;
    }

    private function maybe_render_embedded_shortcode($content)
    {
        if (!is_string($content) || $content === '' || strpos($content, '[tipax_tracking') === false) {
            return $content;
        }

        return shortcode_unautop(do_shortcode($content));
    }

    public function filter_embedded_tracking_shortcode($content)
    {
        return $this->maybe_render_embedded_shortcode($content);
    }

    public function filter_elementor_widget_content($content, $widget = null)
    {
        return $this->maybe_render_embedded_shortcode($content);
    }

    public function render_tracking_shortcode($atts)
    {
        $atts = shortcode_atts([
            'title' => 'رهگیری مرسولات تیپاکس',
            'subtitle' => 'کد رهگیری یا کد قرارداد را وارد کنید تا مشتری بتواند وضعیت مرسوله را به‌صورت کامل و زیبا ببیند.',
            'button' => 'رهگیری مرسوله',
        ], $atts, 'tipax_tracking');

        return $this->render_tracking_shell([
            'title' => $atts['title'],
            'subtitle' => $atts['subtitle'],
            'button_label' => $atts['button'],
            'context' => 'shortcode',
        ]);
    }

    public function add_account_menu_item($items)
    {
        if (!is_array($items)) {
            return $items;
        }

        $newItems = [];
        $inserted = false;

        foreach ($items as $key => $label) {
            $newItems[$key] = $label;

            if ($key === 'orders') {
                $newItems[self::ACCOUNT_ENDPOINT] = 'رهگیری مرسولات در تیپاکس';
                $inserted = true;
            }
        }

        if (!$inserted) {
            $newItems[self::ACCOUNT_ENDPOINT] = 'رهگیری مرسولات در تیپاکس';
        }

        return $newItems;
    }

    public function render_account_endpoint()
    {
        echo $this->render_tracking_shell([
            'title' => 'رهگیری مرسولات در تیپاکس',
            'subtitle' => 'برای مشاهده وضعیت مرسوله، کد رهگیری یا کد قرارداد تیپاکس را وارد کنید.',
            'button_label' => 'نمایش وضعیت مرسوله',
            'context' => 'account',
        ]);
    }

    private function get_account_endpoint_url()
    {
        if (function_exists('wc_get_account_endpoint_url')) {
            return wc_get_account_endpoint_url(self::ACCOUNT_ENDPOINT);
        }

        if (function_exists('wc_get_page_permalink')) {
            return trailingslashit(wc_get_page_permalink('myaccount')) . self::ACCOUNT_ENDPOINT . '/';
        }

        return '';
    }

    public function render_account_dashboard_shortcut()
    {
        $endpointUrl = $this->get_account_endpoint_url();
        if ($endpointUrl === '') {
            return;
        }

        echo '<section class="tipax-account-shortcut" style="margin:24px 0;padding:20px 22px;border-radius:20px;background:linear-gradient(135deg,#083b2b 0%,#0f5b42 100%);color:#fff;">';
        echo '<div style="display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap;">';
        echo '<div>';
        echo '<strong style="display:block;font-size:18px;margin-bottom:6px;">رهگیری مرسولات در تیپاکس</strong>';
        echo '<p style="margin:0;opacity:.92;">سفارشات تیپاکس خود را از اینجا رهگیری کنید.</p>';
        echo '</div>';
        echo '<a href="' . esc_url($endpointUrl) . '" style="display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:999px;background:#fff;color:#0b4b36;text-decoration:none;font-weight:700;white-space:nowrap;">ورود به بخش رهگیری</a>';
        echo '</div>';
        echo '</section>';
    }

    public function run_daily_tracking_sync()
    {
        $trackingService = new Tipax_Tracking_Service();
        $batchSize = (int) apply_filters('tipax_tracking_batch_size', 20);
        $maxBatches = (int) apply_filters('tipax_tracking_max_batches_per_run', 3);
        $report = $trackingService->run_scheduled_updates($batchSize, $maxBatches);

        update_option('tipax_tracking_last_sync_report', [
            'ran_at' => current_time('mysql'),
            'batch_size' => $batchSize,
            'max_batches' => $maxBatches,
            'processed' => isset($report['processed']) ? (int) $report['processed'] : 0,
            'updated' => isset($report['updated']) ? (int) $report['updated'] : 0,
            'skipped' => isset($report['skipped']) ? (int) $report['skipped'] : 0,
            'failed' => isset($report['failed']) ? (int) $report['failed'] : 0,
            'batches' => isset($report['batches']) ? (int) $report['batches'] : 0,
        ], false);
    }

    public function handle_public_tracking_ajax()
    {
        check_ajax_referer('tipax_public_tracking', 'nonce');

        if ($this->is_rate_limited()) {
            wp_send_json_error([
                'html' => $this->render_tracking_error_card('محدودیت موقت رهگیری', [
                    'تعداد درخواست‌های رهگیری از این IP زیاد شده است.',
                    'حدود ۱۰ دقیقه دیگر دوباره تلاش کنید.',
                ]),
                'message' => 'Rate limited.',
            ], 429);
        }

        $lookupInput = isset($_POST['tracking_code'])
            ? sanitize_text_field(wp_unslash($_POST['tracking_code']))
            : '';
        if (trim($lookupInput) === '') {
            wp_send_json_error([
                'html' => $this->render_tracking_error_card('کد واردشده نامعتبر است', [
                    'ابتدا کد رهگیری یا کد قرارداد تیپاکس را وارد کنید.',
                ]),
                'message' => 'Invalid tracking lookup input.',
            ]);
        }

        $trackingService = new Tipax_Tracking_Service();
        $result = $trackingService->lookup_tracking_input($lookupInput);

        if (empty($result['success'])) {
            wp_send_json_error([
                'html' => $this->render_tracking_error_card('رهگیری مرسوله انجام نشد', [
                    isset($result['message']) ? (string) $result['message'] : 'پاسخ معتبری از تیپاکس دریافت نشد.',
                ]),
                'message' => isset($result['message']) ? (string) $result['message'] : 'Tracking failed.',
            ]);
        }

        $views = isset($result['views']) && is_array($result['views'])
            ? $result['views']
            : [isset($result['view']) && is_array($result['view']) ? $result['view'] : []];
        $lookupType = isset($result['lookup_type']) ? (string) $result['lookup_type'] : 'tracking';

        wp_send_json_success([
            'html' => $this->render_tracking_results($views, $lookupType, isset($result['contract']) ? (string) $result['contract'] : ''),
            'status' => $result['status'],
            'contract' => $result['contract'],
            'lookup_type' => $lookupType,
        ]);
    }

    public function handle_admin_batch_tracking_ajax()
    {
        check_ajax_referer('tipax_batch_update_order_statuses');

        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'شما دسترسی لازم برای اجرای این عملیات را ندارید.',
            ], 403);
        }

        $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'page';
        $trackingService = new Tipax_Tracking_Service();

        if ($mode === 'due') {
            $batchSize = isset($_POST['limit']) ? max(1, absint($_POST['limit'])) : (int) apply_filters('tipax_tracking_batch_size', 20);
            $result = $trackingService->run_scheduled_updates($batchSize, 1);

            wp_send_json_success([
                'message' => sprintf(
                    'بروزرسانی خودکار اجرا شد. %1$d سفارش بررسی شد، %2$d مورد بروزرسانی شد، %3$d سفارش به‌دلیل پایان‌یافتن رهگیری نیازی به بروزرسانی نداشت و %4$d خطا داشت.',
                    isset($result['processed']) ? (int) $result['processed'] : 0,
                    isset($result['updated']) ? (int) $result['updated'] : 0,
                    isset($result['skipped']) ? (int) $result['skipped'] : 0,
                    isset($result['failed']) ? (int) $result['failed'] : 0
                ),
                'summary' => $result,
            ]);
        }

        $postedOrderIds = isset($_POST['order_ids']) ? (array) $_POST['order_ids'] : [];
        $orderIds = array_values(array_unique(array_filter(array_map('absint', $postedOrderIds))));

        if (empty($orderIds)) {
            wp_send_json_error([
                'message' => 'در این صفحه سفارشی با کد رهگیری برای بروزرسانی دسته‌ای پیدا نشد.',
            ]);
        }

        $result = $trackingService->update_orders_statuses($orderIds, true);
        wp_send_json_success([
            'message' => sprintf(
                'بروزرسانی دسته‌ای انجام شد. %1$d سفارش بررسی شد، %2$d مورد موفق بود، %3$d سفارش به‌دلیل پایان‌یافتن رهگیری نیازی به بروزرسانی نداشت و %4$d مورد ناموفق بود.',
                isset($result['processed']) ? (int) $result['processed'] : 0,
                isset($result['updated']) ? (int) $result['updated'] : 0,
                isset($result['skipped']) ? (int) $result['skipped'] : 0,
                isset($result['failed']) ? (int) $result['failed'] : 0
            ),
            'summary' => $result,
        ]);
    }
}

new Tipax_Tracking_Hooks();
