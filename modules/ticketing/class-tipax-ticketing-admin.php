<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Ticketing_Admin')) {
    return;
}

class Tipax_Ticketing_Admin
{
    private static $instance = null;
    private $service;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->service = new Tipax_Ticketing_Service();
    }

    private function get_form_defaults()
    {
        return [
            'department_id' => 822,
            'priority_id' => 2,
            'subject' => '',
            'description' => '',
        ];
    }

    private function get_posted_id($key)
    {
        if (!isset($_POST[$key])) {
            return 0;
        }

        $value = wp_unslash($_POST[$key]);
        return is_scalar($value) ? absint($value) : 0;
    }

    private function get_posted_form_data()
    {
        return [
            'department_id' => $this->get_posted_id('department_id'),
            'priority_id' => $this->get_posted_id('priority_id'),
            'subject' => isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '',
        ];
    }

    private function build_redirect_url(array $args = [])
    {
        return add_query_arg($args, admin_url('admin.php?page=tipax-support'));
    }

    private function maybe_handle_form_submission()
    {
        if (!isset($_POST['tipax_submit_ticket'])) {
            return null;
        }

        check_admin_referer('tipax_create_ticket');
        $formData = $this->get_posted_form_data();

        if (!isset(Tipax_Ticketing_Service::get_departments()[$formData['department_id']])) {
            return [
                'success' => false,
                'message' => 'یک دپارتمان معتبر انتخاب کنید.',
                'form_data' => $formData,
            ];
        }

        if (!isset(Tipax_Ticketing_Service::get_priorities()[$formData['priority_id']])) {
            return [
                'success' => false,
                'message' => 'یک اولویت معتبر انتخاب کنید.',
                'form_data' => $formData,
            ];
        }

        if ($formData['subject'] === '' || $formData['description'] === '') {
            return [
                'success' => false,
                'message' => 'عنوان و توضیحات تیکت الزامی است.',
                'form_data' => $formData,
            ];
        }

        $result = $this->service->create_ticket(
            $formData['department_id'],
            $formData['priority_id'],
            $formData['subject'],
            $formData['description']
        );

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['message'],
                'form_data' => $formData,
            ];
        }

        $redirectArgs = [
            'ticket_created' => 1,
        ];

        if (!empty($result['ticket_id'])) {
            $redirectArgs['ticket_id'] = (int) $result['ticket_id'];
        }

        wp_safe_redirect($this->build_redirect_url($redirectArgs));
        exit;
    }

    private function render_notice($type, $message)
    {
        if ($message === '') {
            return;
        }

        echo '<div class="notice notice-' . esc_attr($type) . '"><p>' . esc_html($message) . '</p></div>';
    }

    private function render_ticket_meta_item($label, $value, $modifier = '')
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return;
        }

        $modifierClass = $modifier !== '' ? ' ' . $modifier : '';

        echo '<div class="tipax-support-meta-item' . esc_attr($modifierClass) . '">';
        echo '<span class="tipax-support-meta-label">' . esc_html($label) . '</span>';
        echo '<strong class="tipax-support-meta-value">' . esc_html((string) $value) . '</strong>';
        echo '</div>';
    }

    private function render_create_form(array $formData)
    {
        echo '<section class="tipax-support-card">';
        echo '<div class="tipax-support-card-head">';
        echo '<h3>ایجاد تیکت جدید</h3>';
        echo '<p>دپارتمان مرتبط و میزان اولویت درخواست را انتخاب کنید.</p>';
        echo '</div>';
        echo '<form method="post" action="" class="tipax-support-form">';
        wp_nonce_field('tipax_create_ticket');
        echo '<input type="hidden" name="tipax_submit_ticket" value="1">';

        echo '<div class="tipax-support-form-grid">';
        echo '<label class="tipax-support-field">';
        echo '<span>دپارتمان</span>';
        echo '<select name="department_id" required>';
        echo '<option value="">انتخاب دپارتمان</option>';
        foreach (Tipax_Ticketing_Service::get_departments() as $departmentId => $departmentTitle) {
            echo '<option value="' . esc_attr($departmentId) . '" ' . selected((int) $formData['department_id'], $departmentId, false) . '>';
            echo esc_html($departmentTitle);
            echo '</option>';
        }
        echo '</select>';
        echo '</label>';

        echo '<label class="tipax-support-field">';
        echo '<span>اولویت</span>';
        echo '<select name="priority_id" required>';
        echo '<option value="">انتخاب اولویت</option>';
        foreach (Tipax_Ticketing_Service::get_priorities() as $priorityId => $priorityTitle) {
            echo '<option value="' . esc_attr($priorityId) . '" ' . selected((int) $formData['priority_id'], $priorityId, false) . '>';
            echo esc_html($priorityTitle);
            echo '</option>';
        }
        echo '</select>';
        echo '</label>';

        echo '<label class="tipax-support-field is-full">';
        echo '<span>عنوان تیکت</span>';
        echo '<input type="text" name="subject" value="' . esc_attr($formData['subject']) . '" required />';
        echo '</label>';

        echo '<label class="tipax-support-field is-full">';
        echo '<span>توضیحات</span>';
        echo '<textarea name="description" rows="7" required>' . esc_textarea($formData['description']) . '</textarea>';
        echo '</label>';
        echo '</div>';

        echo '<div class="tipax-support-form-actions">';
        echo '<button type="submit" class="button button-primary">ثبت تیکت در تیپاکس</button>';
        echo '</div>';
        echo '</form>';
        echo '</section>';
    }

    private function render_ticket_detail_panel($selectedTicketResult)
    {
        echo '<section class="tipax-support-card">';
        echo '<div class="tipax-support-card-head">';
        echo '<h3>جزئیات تیکت</h3>';
        echo '<p>با انتخاب هر تیکت از جدول پایین، جزئیات آن در این بخش نمایش داده می‌شود.</p>';
        echo '</div>';

        if (!$selectedTicketResult) {
            echo '<div class="tipax-support-empty-detail"><p>هنوز تیکتی برای مشاهده انتخاب نشده است.</p></div>';
            echo '</section>';
            return;
        }

        if (!$selectedTicketResult['success']) {
            echo '<div class="tipax-support-empty-detail is-error"><p>' . esc_html($selectedTicketResult['message']) . '</p></div>';
            echo '</section>';
            return;
        }

        $ticket = $selectedTicketResult['ticket'];
        echo '<div class="tipax-support-ticket-detail">';
        echo '<div class="tipax-support-ticket-title-row">';
        echo '<h4>#' . esc_html((string) ($ticket['id'] ?? 0)) . ' - ' . esc_html((string) ($ticket['subject'] ?? '')) . '</h4>';
        echo '<span class="tipax-support-status-pill">' . esc_html((string) ($ticket['status_name'] ?? '')) . '</span>';
        echo '</div>';

        echo '<div class="tipax-support-meta-grid">';
        $this->render_ticket_meta_item('تاریخ میلادی', $ticket['created_on_display'] ?? '');
        $this->render_ticket_meta_item('تاریخ جلالی', $ticket['created_on_jalali'] ?? '');
        $this->render_ticket_meta_item('اولویت', $ticket['priority'] ?? '');
        $this->render_ticket_meta_item('دپارتمان', $ticket['department'] ?? '');
        $this->render_ticket_meta_item('ثبت‌کننده', $ticket['creator_name'] ?? '');
        $this->render_ticket_meta_item('مشتری', $ticket['customer_name'] ?? '');
        echo '</div>';

        echo '<div class="tipax-support-description-box">';
        echo '<h5>شرح تیکت</h5>';
        echo '<p>' . nl2br(esc_html((string) ($ticket['description'] ?? ''))) . '</p>';
        echo '</div>';
        echo '</div>';

        echo '</section>';
    }

    private function render_ticket_list(array $tickets, $totalCount, $selectedTicketId)
    {
        echo '<section class="tipax-support-card tipax-support-card--list">';
        echo '<div class="tipax-support-card-head">';
        echo '<h3>لیست تیکت‌ها</h3>';
        echo '<p>تعداد کل تیکت‌های برگشتی از تیپاکس: <strong>' . esc_html((string) $totalCount) . '</strong></p>';
        echo '</div>';

        if (empty($tickets)) {
            echo '<div class="tipax-result-area"><div class="tipax-empty-state">' . tipax_admin_icon('sos') . '<p>تیکتی برای نمایش دریافت نشد.</p></div></div>';
            echo '</section>';
            return;
        }

        echo '<table class="widefat striped tipax-data-table tipax-support-table">';
        echo '<thead><tr>';
        echo '<th>شناسه</th>';
        echo '<th>تاریخ</th>';
        echo '<th>موضوع</th>';
        echo '<th>ثبت‌کننده</th>';
        echo '<th>اولویت</th>';
        echo '<th>وضعیت</th>';
        echo '<th>اقدامات</th>';
        echo '</tr></thead><tbody>';

        foreach ($tickets as $ticket) {
            $detailUrl = $this->build_redirect_url([
                'ticket_id' => (int) ($ticket['id'] ?? 0),
            ]);

            $rowClass = ((int) ($ticket['id'] ?? 0) === (int) $selectedTicketId) ? ' class="is-selected"' : '';

            echo '<tr' . $rowClass . '>';
            echo '<td>#' . esc_html((string) ($ticket['id'] ?? 0)) . '</td>';
            echo '<td>' . esc_html((string) ($ticket['created_on_jalali'] ?: ($ticket['created_on_display'] ?? '-'))) . '</td>';
            echo '<td class="tipax-support-subject-cell">' . esc_html((string) ($ticket['subject'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($ticket['creator_name'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($ticket['priority'] ?? '')) . '</td>';
            echo '<td><span class="tipax-support-status-chip">' . esc_html((string) ($ticket['status_name'] ?? '')) . '</span></td>';
            echo '<td><a class="button button-secondary" href="' . esc_url($detailUrl) . '">جزئیات</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</section>';
    }

    public function render_page()
    {
        $submissionState = $this->maybe_handle_form_submission();
        $formData = $submissionState && !empty($submissionState['form_data'])
            ? $submissionState['form_data']
            : $this->get_form_defaults();

        $selectedTicketId = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
        $selectedTicketResult = null;

        tipax_admin_tabs('tipax-support');

        if (!tipax_is_connection_validated()) {
            $this->render_notice('error', 'برای دسترسی به این بخش ابتدا باید تنظیمات اتصال را کامل کرده و تست اتصال را با موفقیت انجام دهید.');
            return;
        }

        if ($submissionState && !$submissionState['success']) {
            $this->render_notice('error', $submissionState['message']);
        } elseif (isset($_GET['ticket_created']) && absint($_GET['ticket_created']) === 1) {
            $this->render_notice('success', 'تیکت با موفقیت در تیپاکس ثبت شد.');
        }

        $ticketsResult = $this->service->get_tickets();

        if ($selectedTicketId > 0) {
            $selectedTicketResult = $this->service->get_ticket_detail($selectedTicketId);
        }

        echo '<div class="tipax-action-panel tipax-support-panel">';
        echo '<div class="tipax-action-icon">' . tipax_admin_icon('sos') . '</div>';
        echo '<div class="tipax-action-content">';
        echo '<h2>پشتیبانی و تیکت‌های تیپاکس</h2>';
        echo '<p>در این بخش می‌توانید تیکت جدید ثبت کنید، فهرست تیکت‌های قبلی را ببینید و جزئیات هر تیکت را از API تیپاکس دریافت کنید.</p>';
        echo '</div>';
        echo '<div class="tipax-support-panel-actions">';
        echo '<a class="button button-primary" href="' . esc_url($this->build_redirect_url()) . '">بروزرسانی لیست تیکت‌ها</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="tipax-support-grid">';
        $this->render_create_form($formData);
        $this->render_ticket_detail_panel($selectedTicketResult);
        echo '</div>';

        if (!$ticketsResult['success']) {
            $this->render_notice('error', $ticketsResult['message']);
            return;
        }

        $this->render_ticket_list(
            $ticketsResult['tickets'] ?? [],
            $ticketsResult['total_count'] ?? 0,
            $selectedTicketId
        );
    }
}

function tipax_support_page()
{
    Tipax_Ticketing_Admin::instance()->render_page();
}
