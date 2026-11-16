<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_City_Admin')) {
    return;
}

class Tipax_City_Admin
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function render_page()
    {
        tipax_admin_tabs('tipax-cities');

        $service = new Tipax_City_Service();
        $search = isset($_GET['city_search']) ? sanitize_text_field(wp_unslash($_GET['city_search'])) : '';
        $state_title = isset($_GET['state_title']) ? sanitize_text_field(wp_unslash($_GET['state_title'])) : '';
        $status = isset($_GET['city_status']) ? sanitize_key(wp_unslash($_GET['city_status'])) : 'all';
        if (!in_array($status, ['all', 'enabled', 'disabled'], true)) {
            $status = 'all';
        }
        $page = isset($_GET['city_page']) ? max(1, absint($_GET['city_page'])) : 1;
        $summary = $service->get_summary();
        $states = $service->get_states();
        $cities = $service->get_cities([
            'search' => $search,
            'state_title' => $state_title,
            'status' => $status,
            'page' => $page,
            'per_page' => 50,
        ]);
        $last_sync = $service->get_last_sync();
        $last_sync_text = !empty($last_sync['timestamp'])
            ? wp_date('Y/m/d H:i', (int) $last_sync['timestamp'])
            : 'هنوز از API بروزرسانی نشده';

        echo '<div class="tipax-action-panel tipax-cities-panel">';
        echo '<div class="tipax-action-icon">' . tipax_admin_icon('building') . '</div>';
        echo '<div class="tipax-action-content">';
        echo '<h2>مدیریت شهرهای تیپاکس</h2>';
        echo '<p>این فهرست مستقیماً از جدول شهرهای مورد استفاده افزونه در Checkout خوانده می‌شود. شهر غیرفعال همچنان در فرم آدرس ووکامرس دیده می‌شود، اما روش حمل تیپاکس برای آن نمایش داده نخواهد شد.</p>';
        echo '<small class="tipax-cities-last-sync">آخرین بروزرسانی API: ' . esc_html($last_sync_text) . '</small>';
        echo '</div>';
        echo '<button type="button" class="button button-primary tipax-action-button" id="tipax_refresh_cities">بروزرسانی شهرها از تیپاکس</button>';
        echo '</div>';

        echo '<div class="tipax-cities-kpis">';
        $this->render_kpi('کل شهرها', $summary['total'], 'is-total');
        $this->render_kpi('استان‌ها', $summary['states'], 'is-states');
        $this->render_kpi('شهرهای فعال', $summary['enabled'], 'is-enabled');
        $this->render_kpi('شهرهای غیرفعال', $summary['disabled'], 'is-disabled');
        echo '</div>';

        echo '<div id="tipax_cities_feedback" class="tipax-cities-feedback" hidden aria-live="polite"></div>';

        if (!$service->table_exists()) {
            echo '<div class="notice notice-error"><p>جدول شهرهای تیپاکس در دیتابیس پیدا نشد. افزونه را یک‌بار غیرفعال و دوباره فعال کنید.</p></div>';
            return;
        }

        echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" class="tipax-cities-filters">';
        echo '<input type="hidden" name="page" value="tipax-cities">';
        echo '<label for="tipax_city_search">جستجو</label>';
        echo '<input type="search" id="tipax_city_search" name="city_search" value="' . esc_attr($search) . '" placeholder="نام شهر، استان یا شناسه شهر">';
        echo '<label for="tipax_city_state">استان</label>';
        echo '<select id="tipax_city_state" name="state_title">';
        echo '<option value="">همه استان‌ها</option>';
        foreach ($states as $state) {
            $option_label = (string) ($state['stateTitle'] ?? '');
            $option_count = absint($state['cityCount'] ?? 0);
            echo '<option value="' . esc_attr($option_label) . '" ' . selected(tipax_normalize_state_lookup_text($state_title), tipax_normalize_state_lookup_text($option_label), false) . '>'
                . esc_html($option_label . ' (' . $option_count . ')') . '</option>';
        }
        echo '</select>';
        echo '<label for="tipax_city_status">وضعیت</label>';
        echo '<select id="tipax_city_status" name="city_status">';
        echo '<option value="all" ' . selected($status, 'all', false) . '>همه شهرها</option>';
        echo '<option value="enabled" ' . selected($status, 'enabled', false) . '>فقط فعال</option>';
        echo '<option value="disabled" ' . selected($status, 'disabled', false) . '>فقط غیرفعال</option>';
        echo '</select>';
        echo '<button type="submit" class="button button-primary">اعمال فیلتر</button>';
        if ($search !== '' || $state_title !== '' || $status !== 'all') {
            echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=tipax-cities')) . '">حذف فیلترها</a>';
        }
        echo '</form>';

        echo '<div class="tipax-cities-list-head">';
        echo '<div class="tipax-cities-result-count">' . esc_html(number_format_i18n($cities['total'])) . ' شهر مطابق فیلتر فعلی</div>';
        if (!empty($cities['items'])) {
            echo '<div class="tipax-cities-group-actions">';
            echo '<button type="button" class="button button-small" data-tipax-expand-all>نمایش همه گروه‌ها</button>';
            echo '<button type="button" class="button button-small" data-tipax-collapse-all>جمع‌کردن همه گروه‌ها</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="tipax-cities-table-wrap">';
        echo '<table class="widefat striped tipax-data-table tipax-cities-table">';
        echo '<thead><tr><th>شناسه شهر</th><th>شهر</th><th>استان</th><th>شناسه استان</th><th>ارسال با تیپاکس</th></tr></thead><tbody>';

        if (empty($cities['items'])) {
            echo '<tr><td colspan="5"><div class="tipax-cities-empty">شهری مطابق فیلتر انتخاب‌شده پیدا نشد.</div></td></tr>';
        }

        $state_counts = [];
        foreach ($states as $state) {
            $state_counts[(string) ($state['stateTitle'] ?? '')] = absint($state['cityCount'] ?? 0);
        }

        $current_state = null;
        foreach ($cities['items'] as $city) {
            $enabled = !empty($city['enabled']);
            $city_state_title = (string) ($city['state_title'] ?? '');
            $city_state_title = $city_state_title !== '' ? $city_state_title : 'استان نامشخص';
            $state_group = substr(md5($city_state_title), 0, 12);

            if ($current_state !== $city_state_title) {
                $state_city_count = $state_counts[$city_state_title] ?? 0;
                echo '<tr class="tipax-city-state-group">';
                echo '<td colspan="5">';
                echo '<button type="button" class="tipax-state-group-toggle" data-state-group-toggle="' . esc_attr($state_group) . '" aria-expanded="true">';
                echo '<span class="tipax-state-group-title">استان: ' . esc_html($city_state_title) . '</span>';
                echo '<span class="tipax-state-group-meta">کل استان: ' . esc_html(number_format_i18n($state_city_count)) . ' شهر</span>';
                echo '<span class="tipax-state-group-action">جمع‌کردن</span>';
                echo '<span class="tipax-state-group-chevron" aria-hidden="true">⌃</span>';
                echo '</button>';
                echo '</td>';
                echo '</tr>';
                $current_state = $city_state_title;
            }

            $state_id_display = !empty($city['state_id']) ? number_format_i18n((int) $city['state_id']) : '—';
            echo '<tr data-tipax-city-row="' . esc_attr($city['city_id']) . '" data-state-group="' . esc_attr($state_group) . '" class="tipax-city-data-row ' . ($enabled ? 'is-enabled' : 'is-disabled') . '">';
            echo '<td><bdi dir="ltr">' . esc_html($city['city_id']) . '</bdi></td>';
            echo '<td><strong>' . esc_html($city['city_title']) . '</strong></td>';
            echo '<td>' . esc_html($city_state_title) . '</td>';
            echo '<td><bdi dir="ltr">' . esc_html($state_id_display) . '</bdi></td>';
            echo '<td><div class="tipax-city-toggle-cell">';
            echo '<label class="tipax-switch" aria-label="تغییر وضعیت ارسال تیپاکس برای ' . esc_attr($city['city_title']) . '">';
            echo '<input type="checkbox" class="tipax-city-toggle" data-city-id="' . esc_attr($city['city_id']) . '" ' . checked($enabled, true, false) . '>';
            echo '<span class="tipax-switch-track"></span>';
            echo '</label>';
            echo '<span class="tipax-city-status ' . ($enabled ? 'is-enabled' : 'is-disabled') . '">' . ($enabled ? 'فعال' : 'غیرفعال') . '</span>';
            echo '</div></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
        $this->render_pagination($cities, $search, $state_title, $status);
        $this->render_script();
    }

    public function ajax_refresh_cities()
    {
        $this->authorize_ajax();
        $result = (new Tipax_City_Service())->sync_from_api();

        if (empty($result['success'])) {
            wp_send_json_error($result);
        }

        wp_send_json_success($result);
    }

    public function ajax_toggle_city()
    {
        $this->authorize_ajax();
        $city_id = isset($_POST['city_id']) ? absint($_POST['city_id']) : 0;
        $enabled = isset($_POST['enabled']) && wp_unslash($_POST['enabled']) === '1';
        $result = (new Tipax_City_Service())->set_city_enabled($city_id, $enabled);

        if (empty($result['success'])) {
            wp_send_json_error($result);
        }

        wp_send_json_success($result);
    }

    private function authorize_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز است.']);
        }

        check_ajax_referer('tipax_cities_admin', 'nonce');
    }

    private function render_kpi($label, $value, $modifier)
    {
        echo '<div class="tipax-cities-kpi ' . esc_attr($modifier) . '">';
        echo '<span>' . esc_html($label) . '</span>';
        echo '<strong>' . esc_html(number_format_i18n((int) $value)) . '</strong>';
        echo '</div>';
    }

    private function render_pagination(array $cities, $search, $state_title, $status)
    {
        $total_pages = (int) ($cities['pages'] ?? 0);
        $current_page = (int) ($cities['page'] ?? 1);
        if ($total_pages <= 1) {
            return;
        }

        $base_args = [
            'page' => 'tipax-cities',
            'city_search' => $search,
            'state_title' => $state_title,
            'city_status' => $status,
        ];
        $visible_pages = array_unique(array_filter([
            1,
            2,
            $current_page - 2,
            $current_page - 1,
            $current_page,
            $current_page + 1,
            $current_page + 2,
            $total_pages - 1,
            $total_pages,
        ], function ($page) use ($total_pages) {
            return $page >= 1 && $page <= $total_pages;
        }));
        sort($visible_pages, SORT_NUMERIC);

        echo '<nav class="tipax-pagination tipax-cities-pagination" aria-label="صفحه‌بندی شهرها">';
        $previous_page = null;
        foreach ($visible_pages as $page_number) {
            if ($previous_page !== null && $page_number > $previous_page + 1) {
                echo '<span class="tipax-pagination-gap">…</span>';
            }
            $url = add_query_arg(array_merge($base_args, ['city_page' => $page_number]), admin_url('admin.php'));
            echo '<a class="button' . ($page_number === $current_page ? ' button-primary' : '') . '" href="' . esc_url($url) . '">' . esc_html($page_number) . '</a>';
            $previous_page = $page_number;
        }
        echo '</nav>';
    }

    private function render_script()
    {
        ?>
        <script>
        jQuery(function($) {
            const feedback = $('#tipax_cities_feedback');
            const nonce = '<?php echo esc_js(wp_create_nonce('tipax_cities_admin')); ?>';

            function showFeedback(type, message) {
                feedback.removeAttr('hidden').removeClass('is-success is-error').addClass(type === 'success' ? 'is-success' : 'is-error').text(message);
            }

            function setGroupExpanded(button, expanded) {
                const group = button.data('state-group-toggle');
                $('.tipax-city-data-row[data-state-group="' + group + '"]').toggleClass('is-group-hidden', !expanded);
                button.attr('aria-expanded', expanded ? 'true' : 'false');
                button.find('.tipax-state-group-action').text(expanded ? 'جمع‌کردن' : 'نمایش شهرها');
            }

            $('.tipax-state-group-toggle').on('click', function() {
                const button = $(this);
                setGroupExpanded(button, button.attr('aria-expanded') !== 'true');
            });

            $('[data-tipax-expand-all]').on('click', function() {
                $('.tipax-state-group-toggle').each(function() {
                    setGroupExpanded($(this), true);
                });
            });

            $('[data-tipax-collapse-all]').on('click', function() {
                $('.tipax-state-group-toggle').each(function() {
                    setGroupExpanded($(this), false);
                });
            });

            $('#tipax_refresh_cities').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).text('در حال دریافت شهرها...');
                showFeedback('success', 'در حال دریافت و بروزرسانی لیست شهرهای تیپاکس...');

                $.post(ajaxurl, {
                    action: 'tipax_refresh_cities',
                    nonce: nonce
                }).done(function(response) {
                    const data = response && response.data ? response.data : {};
                    const feedbackType = response && response.success && !data.partial ? 'success' : 'error';
                    showFeedback(feedbackType, data.message || 'بروزرسانی شهرها ناموفق بود.');
                    if (response && response.success) {
                        window.setTimeout(function() { window.location.reload(); }, 900);
                    }
                }).fail(function(xhr, textStatus) {
                    const responseData = xhr && xhr.responseJSON && xhr.responseJSON.data
                        ? xhr.responseJSON.data
                        : {};
                    let message = responseData.message || '';

                    if (!message && xhr && xhr.status === 403) {
                        message = 'اعتبار امنیتی درخواست منقضی شده است. صفحه را تازه‌سازی و دوباره تلاش کنید.';
                    } else if (!message && xhr && xhr.status >= 500) {
                        message = 'سرور سایت هنگام بروزرسانی شهرها با خطای داخلی متوقف شد (HTTP ' + xhr.status + ').';
                    } else if (!message && textStatus === 'parsererror') {
                        message = 'پاسخ بروزرسانی شهرها JSON معتبر نبود. نمایش خطاهای PHP یا خروجی اضافی سرور را بررسی کنید.';
                    } else if (!message) {
                        message = 'ارتباط با سرور سایت برای بروزرسانی شهرها ناموفق بود'
                            + (xhr && xhr.status ? ' (HTTP ' + xhr.status + ')' : '') + '.';
                    }

                    showFeedback('error', message);
                }).always(function() {
                    button.prop('disabled', false).text('بروزرسانی شهرها از تیپاکس');
                });
            });

            $('.tipax-city-toggle').on('change', function() {
                const toggle = $(this);
                const enabled = toggle.is(':checked');
                const row = toggle.closest('tr');
                const status = row.find('.tipax-city-status');
                toggle.prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'tipax_toggle_city',
                    nonce: nonce,
                    city_id: toggle.data('city-id'),
                    enabled: enabled ? '1' : '0'
                }).done(function(response) {
                    const data = response && response.data ? response.data : {};
                    if (!response || !response.success) {
                        toggle.prop('checked', !enabled);
                        showFeedback('error', data.message || 'ذخیره وضعیت شهر ناموفق بود.');
                        return;
                    }

                    row.toggleClass('is-enabled', enabled).toggleClass('is-disabled', !enabled);
                    status.toggleClass('is-enabled', enabled).toggleClass('is-disabled', !enabled).text(enabled ? 'فعال' : 'غیرفعال');
                    showFeedback('success', data.message || 'وضعیت شهر ذخیره شد.');
                }).fail(function(xhr, textStatus) {
                    toggle.prop('checked', !enabled);
                    const responseData = xhr && xhr.responseJSON && xhr.responseJSON.data
                        ? xhr.responseJSON.data
                        : {};
                    let message = responseData.message || '';

                    if (!message && xhr && xhr.status === 400 && $.trim(xhr.responseText || '') === '0') {
                        message = 'اکشن مدیریت شهرها در admin-ajax وردپرس ثبت نشده است.';
                    } else if (!message && xhr && xhr.status === 403) {
                        message = 'اعتبار امنیتی درخواست منقضی شده است. صفحه را تازه‌سازی و دوباره تلاش کنید.';
                    } else if (!message && xhr && xhr.status >= 500) {
                        message = 'سرور سایت هنگام ذخیره وضعیت شهر با خطای داخلی متوقف شد (HTTP ' + xhr.status + ').';
                    } else if (!message && textStatus === 'parsererror') {
                        message = 'پاسخ ذخیره وضعیت شهر JSON معتبر نبود.';
                    } else if (!message) {
                        message = 'ارتباط با سرور سایت برای ذخیره وضعیت شهر ناموفق بود'
                            + (xhr && xhr.status ? ' (HTTP ' + xhr.status + ')' : '') + '.';
                    }

                    showFeedback('error', message);
                }).always(function() {
                    toggle.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
}

function tipax_cities_page()
{
    Tipax_City_Admin::instance()->render_page();
}
