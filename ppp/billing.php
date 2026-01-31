<?php
/*
 *  PPP Billing / Tagihan WiFi
 *  Send WhatsApp reminders to customers
 */

// Check if we're included from index.php (API already available)
if (!isset($API)) {
    // Direct access - do full setup
    session_start();
    error_reporting(0);
    if (!isset($_SESSION["mikpay"])) {
        header("Location:../admin.php?id=login");
        exit;
    }
    $session = $_GET['session'];
    include(dirname(__FILE__) . '/../include/config.php');
    include(dirname(__FILE__) . '/../include/readcfg.php');
    include(dirname(__FILE__) . '/../include/lang.php');
    include(dirname(__FILE__) . '/../lang/'.$langid.'.php');
    include_once(dirname(__FILE__) . '/../lib/routeros_api.class.php');
    include_once(dirname(__FILE__) . '/../lib/formatbytesbites.php');
    $API = new RouterosAPI();
    $API->debug = false;
    $API->connect($iphost, $userhost, decrypt($passwdhost));
}

// Include billing data functions
include_once(dirname(__FILE__) . '/billing-data.php');

// Include business config
include_once(dirname(__FILE__) . '/../include/business_config.php');

// Include Fonnte config (optional)
$fonnteEnabled = false;
$fonnteFile = dirname(__FILE__) . '/../include/fonnte_config.php';
if (file_exists($fonnteFile)) {
    include_once($fonnteFile);
    $fonnteEnabled = function_exists('isFonnteEnabled') ? isFonnteEnabled() : false;
}

    // Get PPP secrets
    $getpppsecrets = $API->comm("/ppp/secret/print");
    if (!is_array($getpppsecrets)) $getpppsecrets = array();
    $TotalReg = count($getpppsecrets);
    
    // Get PPP active connections to check online status
    $getpppactive = $API->comm("/ppp/active/print");
    if (!is_array($getpppactive)) $getpppactive = array();
    $onlineUsers = array();
    foreach ($getpppactive as $active) {
        if (isset($active['name'])) {
            $onlineUsers[$active['name']] = $active;
        }
    }
    
    // Get all customer billing settings
    $billingSettings = function_exists('getCustomerBillingSettings') ? getCustomerBillingSettings() : array();
    
    // Build customer list with billing status
    $today = new DateTime();
    $customers = array();
    
    foreach ($getpppsecrets as $secret) {
        if (!isset($secret['name'])) continue;
        
        $customerName = $secret['name'];
        
        // Get customer billing settings
        $custBilling = isset($billingSettings[$customerName]) ? $billingSettings[$customerName] : null;
        $dueDay = isset($custBilling['due_day']) ? $custBilling['due_day'] : 0;
        $phone = isset($custBilling['phone']) ? $custBilling['phone'] : '';
        $displayName = isset($custBilling['display_name']) ? $custBilling['display_name'] : '';
        $monthlyFee = isset($custBilling['monthly_fee']) ? $custBilling['monthly_fee'] : 0;
        $notes = isset($custBilling['notes']) ? $custBilling['notes'] : '';
        
        // Calculate billing status
        $billingStatus = array(
            'status' => 'not_set',
            'status_label' => 'Belum Diatur',
            'status_class' => 'not-set',
            'days_to_due' => null,
            'current_period' => date('Y-m'),
            'due_date' => null,
            'message' => ''
        );
        if (function_exists('getCustomerBillingStatus')) {
            $billingStatus = getCustomerBillingStatus($customerName, $dueDay > 0 ? $dueDay : null);
        }
        
        $customer = array(
            'id' => isset($secret['.id']) ? $secret['.id'] : '',
            'name' => $customerName,
            'profile' => isset($secret['profile']) ? $secret['profile'] : '',
            'comment' => isset($secret['comment']) ? $secret['comment'] : '',
            'disabled' => isset($secret['disabled']) ? $secret['disabled'] : 'false',
            'phone' => $phone,
            'display_name' => $displayName,
            'monthly_fee' => $monthlyFee,
            'notes' => $notes,
            'due_day' => $dueDay,
            'due_date' => isset($billingStatus['due_date']) ? $billingStatus['due_date'] : '',
            'days_left' => isset($billingStatus['days_to_due']) ? $billingStatus['days_to_due'] : null,
            'status' => isset($billingStatus['status']) ? $billingStatus['status'] : 'not_set',
            'status_label' => isset($billingStatus['status_label']) ? $billingStatus['status_label'] : 'Belum Diatur',
            'status_class' => isset($billingStatus['status_class']) ? $billingStatus['status_class'] : 'not-set',
            'status_message' => isset($billingStatus['message']) ? $billingStatus['message'] : '',
            'current_period' => isset($billingStatus['current_period']) ? $billingStatus['current_period'] : date('Y-m'),
            'online' => isset($onlineUsers[$customerName]),
            'needs_reminder' => false
        );
        
        if (isset($billingStatus['status'])) {
            $customer['needs_reminder'] = ($billingStatus['status'] === 'due_today' || 
                ($billingStatus['status'] === 'due_soon' && isset($billingStatus['days_to_due']) && $billingStatus['days_to_due'] === 3));
        }
        
        $customers[] = $customer;
    }
    
    // Sort by status priority
    $statusPriority = array(
        'overdue' => 1,
        'due_today' => 2,
        'due_soon' => 3,
        'waiting' => 4,
        'paid' => 5,
        'not_set' => 6
    );
    
    usort($customers, function($a, $b) use ($statusPriority) {
        $priorityA = isset($statusPriority[$a['status']]) ? $statusPriority[$a['status']] : 99;
        $priorityB = isset($statusPriority[$b['status']]) ? $statusPriority[$b['status']] : 99;
        if ($priorityA !== $priorityB) {
            return $priorityA - $priorityB;
        }
        if ($a['days_left'] === null && $b['days_left'] === null) return 0;
        if ($a['days_left'] === null) return 1;
        if ($b['days_left'] === null) return -1;
        return $a['days_left'] - $b['days_left'];
    });
    
    // Count statistics
    $countOverdue = 0;
    $countDueToday = 0;
    $countDueSoon = 0;
    $countPaid = 0;
    $countNotSet = 0;
    foreach ($customers as $c) {
        if ($c['status'] == 'overdue') $countOverdue++;
        elseif ($c['status'] == 'due_today') $countDueToday++;
        elseif ($c['status'] == 'due_soon') $countDueSoon++;
        elseif ($c['status'] == 'paid') $countPaid++;
        elseif ($c['status'] == 'not_set') $countNotSet++;
    }
    
    // Get current view/tab
    $view = isset($_GET['view']) ? $_GET['view'] : 'customers';
    $reportYear = intval(isset($_GET['year']) ? $_GET['year'] : date('Y'));
    $reportMonth = intval(isset($_GET['month']) ? $_GET['month'] : date('m'));
    
    // Get payment statistics
    $allPayments = function_exists('getBillingPayments') ? getBillingPayments() : array();
    $currentPeriod = date('Y-m');
    $thisMonthPayments = array();
    $totalThisMonth = 0;
    $totalAllTime = 0;
    foreach ($allPayments as $p) {
        if (isset($p['period']) && $p['period'] === $currentPeriod) {
            $thisMonthPayments[] = $p;
            $totalThisMonth += isset($p['amount']) ? $p['amount'] : 0;
        }
        $totalAllTime += isset($p['amount']) ? $p['amount'] : 0;
    }
    
    // Handle save bank account settings
    if (isset($_POST['save_bank_account']) && function_exists('saveBusinessSettings')) {
        $businessSettings = getBusinessSettings();
        $businessSettings['bank_account'] = trim($_POST['bank_account'] ?? '');
        $businessSettings['bank_name'] = trim($_POST['bank_name'] ?? '');
        $businessSettings['bank_account_name'] = trim($_POST['bank_account_name'] ?? '');
        saveBusinessSettings($businessSettings);
        header("Location: ?ppp=billing&session=$session&view=" . ($_GET['view'] ?? 'customers') . "&bank_saved=1");
        exit;
    }
    
    // Handle add payment form
    if (isset($_POST['add_payment']) && function_exists('addBillingPayment')) {
        $paymentDate = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        // Convert date to datetime format
        $paymentDateTime = $paymentDate . ' ' . date('H:i:s');
        
        $payment = addBillingPayment(
            $_POST['customer_name'],
            isset($_POST['phone']) ? $_POST['phone'] : '',
            floatval($_POST['amount']),
            $_POST['period'],
            isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Transfer',
            isset($_POST['notes']) ? $_POST['notes'] : '',
            isset($_POST['display_name']) ? $_POST['display_name'] : '',
            floatval(isset($_POST['bill_amount']) ? $_POST['bill_amount'] : 0),
            isset($_POST['payment_status']) ? $_POST['payment_status'] : 'paid',
            floatval(isset($_POST['previous_debt']) ? $_POST['previous_debt'] : 0),
            $paymentDateTime
        );
        header("Location: ?ppp=billing&session=$session&view=invoices&success=1");
        exit;
    }
    
    // Get business settings for bank account
    $businessSettings = getBusinessSettings();
    $bankAccount = isset($businessSettings['bank_account']) ? $businessSettings['bank_account'] : '';
    $bankName = isset($businessSettings['bank_name']) ? $businessSettings['bank_name'] : '';
    $bankAccountName = isset($businessSettings['bank_account_name']) ? $businessSettings['bank_account_name'] : '';
?>
<style>
/* Tabs */
.billing-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 25px;
    background: #FFFFFF;
    padding: 8px;
    border-radius: 16px;
    box-shadow: 0 0 30px rgba(0,0,0,0.05);
}

.billing-tab {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background: transparent;
    color: #64748b;
    font-weight: 600;
    cursor: pointer;
    border-radius: 12px;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.billing-tab:hover {
    background: #f1f5f9;
    color: #4D44B5;
}

.billing-tab.active {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFFFFF;
}

/* Bank Settings Card */
.bank-settings-card {
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 0 30px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    overflow: hidden;
}

.bank-settings-header {
    padding: 18px 25px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.bank-settings-header:hover {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.bank-settings-card:not(.expanded) .bank-settings-header {
    border-left: 4px solid #f59e0b;
}

.bank-settings-card.expanded .bank-settings-header {
    border-left: 4px solid #4D44B5;
}

.bank-settings-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    color: #1e293b;
    font-size: 15px;
    flex: 1;
}

.bank-settings-title > div {
    display: flex;
    flex-direction: column;
}

.bank-settings-title > div {
    display: flex;
    flex-direction: column;
}

.bank-settings-title i {
    color: #4D44B5;
    font-size: 18px;
}

#bankSettingsIcon {
    color: #64748b;
    transition: transform 0.3s ease;
}

.bank-settings-card.expanded #bankSettingsIcon {
    transform: rotate(180deg);
}

.bank-settings-body {
    padding: 25px;
}

.bank-settings-body .form-group {
    margin-bottom: 0;
}

.bank-settings-body .form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 13px;
}

.bank-settings-body .form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.bank-settings-body .form-group input:focus {
    outline: none;
    border-color: #4D44B5;
    box-shadow: 0 0 0 3px rgba(77, 68, 181, 0.1);
}

/* Stats Cards */
.billing-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 0 30px rgba(0,0,0,0.05);
}

.stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 15px;
}

.stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.stat-card .stat-label {
    color: #64748b;
    font-size: 13px;
    margin-top: 5px;
}

.stat-card.overdue .stat-icon { background: #fee2e2; color: #dc2626; }
.stat-card.due-today .stat-icon { background: #fef3c7; color: #f59e0b; }
.stat-card.due-soon .stat-icon { background: #dbeafe; color: #3b82f6; }
.stat-card.paid .stat-icon { background: #dcfce7; color: #16a34a; }
.stat-card.not-set .stat-icon { background: #f1f5f9; color: #64748b; }

/* Customer Table */
.billing-table-container {
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 0 30px rgba(0,0,0,0.05);
    overflow: hidden;
}

.billing-table-header {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFFFFF;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.billing-table-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.billing-table {
    width: 100%;
    border-collapse: collapse;
}

.billing-table th {
    background: #f8fafc;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    font-size: 13px;
    border-bottom: 2px solid #e2e8f0;
}

.billing-table td {
    padding: 15px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.billing-table tr:hover {
    background: #fafafa;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.overdue {
    background: #fee2e2;
    color: #dc2626;
}

.status-badge.due-today {
    background: #fef3c7;
    color: #f59e0b;
    animation: pulse 2s infinite;
}

.status-badge.due-soon {
    background: #dbeafe;
    color: #3b82f6;
}

.status-badge.waiting {
    background: #e0e7ff;
    color: #6366f1;
}

.status-badge.paid {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.not-set {
    background: #f1f5f9;
    color: #64748b;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Online indicator */
.online-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

.online-indicator.online {
    background: #22c55e;
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);
}

.online-indicator.offline {
    background: #cbd5e1;
}

/* Action buttons */
.btn-action {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    margin-right: 5px;
}

.btn-edit {
    background: #e0e7ff;
    color: #4D44B5;
}

.btn-edit:hover {
    background: #4D44B5;
    color: #FFF;
}

.btn-send-wa {
    background: #dcfce7;
    color: #16a34a;
}

.btn-send-wa:hover {
    background: #22c55e;
    color: #FFF;
}

.btn-send-wa:disabled {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}

.btn-excel {
    background: #f0f9ff;
    color: #0ea5e9;
}

.btn-excel:hover {
    background: #0ea5e9;
    color: #FFF;
}

.btn-import {
    background: #fef3c7;
    color: #f59e0b;
}

.btn-import:hover {
    background: #f59e0b;
    color: #FFF;
}

/* Customer name */
.customer-name {
    font-weight: 600;
    color: #1e293b;
}

/* Days left indicator */
.days-left {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
}

.days-left.negative {
    background: #fee2e2;
    color: #dc2626;
}

.days-left.warning {
    background: #fef3c7;
    color: #f59e0b;
}

.days-left.info {
    background: #dbeafe;
    color: #3b82f6;
}

/* No phone indicator */
.no-phone {
    color: #94a3b8;
    font-style: italic;
    font-size: 12px;
}

/* Filter buttons */
.filter-row {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.btn-filter {
    padding: 10px 20px;
    border: 2px solid #e2e8f0;
    background: #FFFFFF;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-filter:hover, .btn-filter.active {
    border-color: #4D44B5;
    color: #4D44B5;
}

.btn-filter .count {
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
}

/* Modal styles */
.wa-template-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.wa-template-modal.show {
    display: flex;
}

.wa-template-content {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 25px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.wa-template-content h4 {
    margin: 0 0 20px;
    font-size: 18px;
    color: #1e293b;
}

.wa-template-content textarea {
    width: 100%;
    min-height: 200px;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 15px;
    box-sizing: border-box;
}

.wa-template-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-cancel {
    padding: 12px 24px;
    border: 2px solid #e2e8f0;
    background: #FFFFFF;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
}

.btn-confirm {
    padding: 12px 24px;
    border: none;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: #FFFFFF;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Report Buttons - Modern Design */
.btn-report {
    padding: 16px 20px;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    min-width: 200px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-report::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.btn-report:hover::before {
    left: 100%;
}

.btn-report:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.btn-report:active {
    transform: translateY(-1px);
}

.btn-report-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.btn-report:hover .btn-report-icon {
    transform: scale(1.1) rotate(5deg);
}

.btn-report-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}

.btn-report-label {
    font-size: 15px;
    font-weight: 700;
    color: #FFFFFF;
    line-height: 1.2;
}

.btn-report-desc {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 400;
}

.btn-report-arrow {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.btn-report:hover .btn-report-arrow {
    transform: translateX(5px);
    color: #FFFFFF;
}

/* Monthly Report Button */
.btn-report-monthly {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.btn-report-monthly .btn-report-icon {
    background: rgba(255, 255, 255, 0.25);
    color: #FFFFFF;
}

.btn-report-monthly:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

/* Yearly Report Button */
.btn-report-yearly {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.btn-report-yearly .btn-report-icon {
    background: rgba(255, 255, 255, 0.25);
    color: #FFFFFF;
}

.btn-report-yearly:hover {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
}

/* Report Controls Wrapper */
.report-controls-wrapper {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.report-selectors {
    display: flex;
    gap: 10px;
    align-items: center;
}

.report-select {
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    background: #FFFFFF;
    color: #1e293b;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 140px;
}

.report-select:hover {
    border-color: #4D44B5;
    box-shadow: 0 2px 8px rgba(77, 68, 181, 0.1);
}

.report-select:focus {
    outline: none;
    border-color: #4D44B5;
    box-shadow: 0 0 0 3px rgba(77, 68, 181, 0.1);
}

.report-buttons {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

/* Responsive */
@media screen and (max-width: 768px) {
    .report-controls-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    
    .report-selectors {
        width: 100%;
        justify-content: space-between;
    }
    
    .report-select {
        flex: 1;
        min-width: 0;
    }
    
    .report-buttons {
        width: 100%;
        flex-direction: column;
    }
    
    .btn-report {
        width: 100%;
        min-width: auto;
        padding: 14px 16px;
        gap: 12px;
    }
    
    .btn-report-icon {
        width: 42px;
        height: 42px;
        font-size: 18px;
    }
    
    .btn-report-label {
        font-size: 14px;
    }
    
    .btn-report-desc {
        font-size: 10px;
    }
}

/* Payment Modal */
.payment-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.payment-modal.show {
    display: flex;
}

.payment-modal-content {
    background: #FFFFFF;
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.payment-modal-header {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFFFFF;
    padding: 20px 25px;
    border-radius: 16px 16px 0 0;
}

.payment-modal-header h4 {
    margin: 0;
    font-size: 18px;
}

.payment-modal-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #4D44B5;
    outline: none;
}

.payment-modal-footer {
    padding: 15px 25px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-primary {
    padding: 12px 24px;
    border: none;
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFFFFF;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
}

.btn-secondary {
    padding: 12px 24px;
    border: 2px solid #e2e8f0;
    background: #FFFFFF;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
}

/* Report Styles */
.report-header {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    border-radius: 16px;
    padding: 25px;
    color: #FFFFFF;
    margin-bottom: 25px;
}

.report-title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 10px;
}

.report-period {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.report-period select {
    padding: 10px 15px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    background: rgba(255,255,255,0.2);
    color: #FFFFFF;
}

.report-period select option {
    color: #1e293b;
}

.report-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.report-summary-card {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 0 30px rgba(0,0,0,0.05);
}

.report-summary-card .value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.report-summary-card .label {
    color: #64748b;
    font-size: 13px;
    margin-top: 5px;
}

/* Invoice styles */
.invoice-card {
    background: #FFFFFF;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.invoice-card-header {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFFFFF;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.invoice-card.partial .invoice-card-header {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.invoice-card-body {
    padding: 20px;
}

.invoice-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.invoice-row:last-child {
    border-bottom: none;
}

.invoice-label {
    color: #64748b;
}

.invoice-value {
    font-weight: 600;
    color: #1e293b;
}

.invoice-actions {
    display: flex;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #f1f5f9;
}

/* Success alert */
.success-alert {
    background: #dcfce7;
    border: 2px solid #22c55e;
    color: #16a34a;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Empty state */
.empty-invoices {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-invoices i {
    font-size: 60px;
    margin-bottom: 15px;
    color: #cbd5e1;
}

/* Invoice grid */
.invoice-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

/* Month header for invoices */
.invoice-month-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #FFFFFF;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.invoice-month-header h4 {
    margin: 0;
    font-size: 16px;
}

.invoice-month-stat {
    font-size: 13px;
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 768px) {
    .billing-tabs {
        flex-direction: column;
    }
    
    .billing-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .billing-table-container {
        overflow-x: auto;
    }
    
    .billing-table {
        min-width: 800px;
    }
    
    .filter-row {
        overflow-x: auto;
        padding-bottom: 10px;
    }
    
    .bank-settings-body form > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
    
    .invoice-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php if (empty($bankAccount)): ?>
<div style="margin-bottom: 15px; padding: 15px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 10px; display: flex; align-items: center; gap: 12px;">
    <i class="fa fa-exclamation-triangle" style="font-size: 24px; color: #f59e0b;"></i>
    <div style="flex: 1;">
        <strong style="color: #92400e;">Rekening Bank Belum Diatur</strong>
        <div style="font-size: 13px; color: #78350f; margin-top: 3px;">
            Silakan isi nomor rekening bank di bawah agar bisa muncul di pesan WhatsApp untuk pembayaran transfer.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bank Account Settings -->
<div class="bank-settings-card <?= empty($bankAccount) ? 'expanded' : '' ?>" id="bankSettingsCard">
    <div class="bank-settings-header" onclick="toggleBankSettings()">
        <div class="bank-settings-title">
            <i class="fa fa-university"></i>
            <div>
                <span>Pengaturan Rekening Bank</span>
                <?php if (!empty($bankAccount)): ?>
                <div style="font-size: 12px; font-weight: 400; color: #64748b; margin-top: 3px;">
                    <?php
                    $bankInfo = [];
                    if ($bankName) $bankInfo[] = $bankName;
                    $bankInfo[] = $bankAccount;
                    if ($bankAccountName) $bankInfo[] = 'a/n ' . $bankAccountName;
                    echo htmlspecialchars(implode(' - ', $bankInfo));
                    ?>
                </div>
                <?php else: ?>
                <div style="font-size: 12px; font-weight: 400; color: #f59e0b; margin-top: 3px;">
                    <i class="fa fa-exclamation-circle"></i> Belum diatur - Klik untuk mengisi
                </div>
                <?php endif; ?>
            </div>
        </div>
        <i class="fa fa-chevron-down" id="bankSettingsIcon"></i>
    </div>
    <div class="bank-settings-body" id="bankSettingsBody" style="display: <?= empty($bankAccount) ? 'block' : 'none' ?>;">
        <?php if (isset($_GET['bank_saved'])): ?>
        <div class="success-alert" style="margin-bottom: 15px;">
            <i class="fa fa-check-circle"></i>
            Data rekening bank berhasil disimpan!
        </div>
        <?php endif; ?>
        <form method="POST" action="?ppp=billing&session=<?= $session ?>&view=<?= $view ?>">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label>Nama Bank</label>
                    <input type="text" name="bank_name" value="<?= htmlspecialchars($bankName) ?>" placeholder="Contoh: Bank BCA, Bank Mandiri, dll">
                </div>
                <div class="form-group">
                    <label>Nomor Rekening</label>
                    <input type="text" name="bank_account" value="<?= htmlspecialchars($bankAccount) ?>" placeholder="Contoh: 1234567890">
                </div>
                <div class="form-group">
                    <label>Atas Nama (a/n)</label>
                    <input type="text" name="bank_account_name" value="<?= htmlspecialchars($bankAccountName) ?>" placeholder="Contoh: Muhammad Andi">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-secondary" onclick="toggleBankSettings()">Tutup</button>
                <button type="submit" name="save_bank_account" class="btn-primary">
                    <i class="fa fa-save"></i> Simpan
                </button>
            </div>
        </form>
        <?php if (!empty($bankAccount)): ?>
        <div style="margin-top: 15px; padding: 12px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
            <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">Rekening saat ini:</div>
            <div style="font-weight: 600; color: #1e293b;">
                <?php
                $bankInfoDisplay = [];
                if ($bankName) $bankInfoDisplay[] = $bankName;
                $bankInfoDisplay[] = $bankAccount;
                if ($bankAccountName) $bankInfoDisplay[] = 'a/n ' . $bankAccountName;
                echo htmlspecialchars(implode(' - ', $bankInfoDisplay));
                ?>
            </div>
            <div style="font-size: 11px; color: #64748b; margin-top: 5px;">
                <i class="fa fa-info-circle"></i> Nomor rekening ini akan muncul di pesan WhatsApp saat menggunakan placeholder {rekening}
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs -->
<div class="billing-tabs">
    <a href="?ppp=billing&session=<?= $session ?>&view=customers" class="billing-tab <?= $view == 'customers' ? 'active' : '' ?>">
        <i class="fa fa-users"></i> Pelanggan
    </a>
    <a href="?ppp=billing&session=<?= $session ?>&view=report" class="billing-tab <?= $view == 'report' ? 'active' : '' ?>">
        <i class="fa fa-bar-chart"></i> Laporan
    </a>
    <a href="?ppp=billing&session=<?= $session ?>&view=invoices" class="billing-tab <?= $view == 'invoices' ? 'active' : '' ?>">
        <i class="fa fa-file-text"></i> Invoice
    </a>
</div>

<?php if ($view == 'customers'): ?>
<!-- ========== TAB: PELANGGAN ========== -->

<!-- Stats Cards -->
<div class="billing-stats">
    <div class="stat-card overdue">
        <div class="stat-icon"><i class="fa fa-exclamation-circle"></i></div>
        <div class="stat-value"><?= $countOverdue ?></div>
        <div class="stat-label">Terlambat</div>
    </div>
    <div class="stat-card due-today">
        <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
        <div class="stat-value"><?= $countDueToday ?></div>
        <div class="stat-label">Jatuh Tempo Hari Ini</div>
    </div>
    <div class="stat-card due-soon">
        <div class="stat-icon"><i class="fa fa-bell"></i></div>
        <div class="stat-value"><?= $countDueSoon ?></div>
        <div class="stat-label">Segera Jatuh Tempo</div>
    </div>
    <div class="stat-card paid">
        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
        <div class="stat-value"><?= $countPaid ?></div>
        <div class="stat-label">Sudah Lunas</div>
    </div>
    <div class="stat-card not-set">
        <div class="stat-icon"><i class="fa fa-question-circle"></i></div>
        <div class="stat-value"><?= $countNotSet ?></div>
        <div class="stat-label">Belum Diatur</div>
    </div>
</div>

<!-- Filter -->
<div class="filter-row">
    <button class="btn-filter active" data-filter="all">
        Semua <span class="count"><?= count($customers) ?></span>
    </button>
    <button class="btn-filter" data-filter="overdue">
        Terlambat <span class="count"><?= $countOverdue ?></span>
    </button>
    <button class="btn-filter" data-filter="due_today">
        Hari Ini <span class="count"><?= $countDueToday ?></span>
    </button>
    <button class="btn-filter" data-filter="due_soon">
        Segera <span class="count"><?= $countDueSoon ?></span>
    </button>
    <button class="btn-filter" data-filter="paid">
        Lunas <span class="count"><?= $countPaid ?></span>
    </button>
</div>

<!-- Customer Table -->
<div class="billing-table-container">
    <div class="billing-table-header">
        <h3><i class="fa fa-users"></i> Daftar Pelanggan WiFi</h3>
        <div style="display:flex; gap:8px;">
            <a href="./ppp/export-excel-template.php?session=<?= $session ?>" 
               class="btn-action btn-excel" 
               title="Download Template CSV untuk diisi di Excel"
               download>
                <i class="fa fa-file-excel-o"></i> Template CSV
            </a>
            <button class="btn-action btn-send-wa" onclick="showBulkWAModal()" title="Kirim WA Massal">
                <i class="fa fa-whatsapp"></i>
            </button>
        </div>
    </div>
    <table class="billing-table" id="customerTable">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Pelanggan</th>
                <th>No. HP</th>
                <th>Jatuh Tempo</th>
                <th>Periode</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
            <tr data-status="<?= $customer['status'] ?>">
                <td>
                    <input type="checkbox" class="customer-check" 
                           data-name="<?= htmlspecialchars($customer['display_name'] ? $customer['display_name'] : $customer['name']) ?>" 
                           data-phone="<?= htmlspecialchars($customer['phone']) ?>"
                           data-due="<?= $customer['due_date'] ?>"
                           data-days="<?= $customer['days_left'] ?>"
                           data-fee="<?= $customer['monthly_fee'] ?>">
                </td>
                <td>
                    <span class="online-indicator <?= $customer['online'] ? 'online' : 'offline' ?>"></span>
                    <span class="customer-name"><?= htmlspecialchars($customer['name']) ?></span>
                    <?php if (!empty($customer['display_name'])): ?>
                        <br><small style="color:#64748b;"><?= htmlspecialchars($customer['display_name']) ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($customer['phone'])): ?>
                        <?= htmlspecialchars($customer['phone']) ?>
                    <?php else: ?>
                        <span class="no-phone">Belum diatur</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($customer['due_day'] > 0): ?>
                        <strong>Tgl <?= $customer['due_day'] ?></strong>
                        <?php if (!empty($customer['due_date'])): ?>
                            <br><small style="color:#64748b;"><?= date('d M Y', strtotime($customer['due_date'])) ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="no-phone">Belum diatur</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($customer['current_period'])): ?>
                        <?= date('M Y', strtotime($customer['current_period'] . '-01')) ?>
                        <?php if ($customer['days_left'] !== null): ?>
                            <br>
                            <?php if ($customer['days_left'] < 0): ?>
                                <small class="days-left negative">Lewat <?= abs($customer['days_left']) ?> hari</small>
                            <?php elseif ($customer['days_left'] == 0): ?>
                                <small class="days-left negative">Hari ini!</small>
                            <?php else: ?>
                                <small class="days-left <?= $customer['days_left'] <= 3 ? 'warning' : 'info' ?>"><?= $customer['days_left'] ?> hari lagi</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="no-phone">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="status-badge <?= $customer['status_class'] ?>">
                        <?= $customer['status_label'] ?>
                    </span>
                    <?php if ($customer['needs_reminder'] && !empty($customer['phone'])): ?>
                        <i class="fa fa-bell" style="color:#f97316; margin-left:5px;" title="Perlu kirim reminder"></i>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex; gap:5px;">
                        <button class="btn-action btn-edit" onclick="editCustomer('<?= htmlspecialchars($customer['name']) ?>', <?= $customer['due_day'] ?>, '<?= htmlspecialchars($customer['display_name']) ?>', '<?= htmlspecialchars($customer['phone']) ?>', <?= $customer['monthly_fee'] ?>, '<?= htmlspecialchars($customer['notes']) ?>')">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <?php if (!empty($customer['phone'])): ?>
                            <a href="javascript:void(0)" 
                               class="btn-action btn-send-wa"
                               onclick="sendWhatsApp('<?= htmlspecialchars($customer['phone']) ?>', '<?= htmlspecialchars($customer['display_name'] ? $customer['display_name'] : $customer['name']) ?>', '<?= htmlspecialchars($customer['due_date']) ?>', <?= $customer['days_left'] !== null ? $customer['days_left'] : 'null' ?>, '<?= $customer['status'] ?>', <?= $customer['monthly_fee'] ?>)">
                                <i class="fa fa-whatsapp"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn-action btn-send-wa" disabled><i class="fa fa-whatsapp"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- WhatsApp Template Modal -->
<div class="wa-template-modal" id="waModal">
    <div class="wa-template-content">
        <h4><i class="fa fa-whatsapp" style="color:#25D366"></i> Kirim Pesan WhatsApp</h4>
        <p style="color:#64748b; font-size:13px; margin-bottom:15px;">Edit template pesan sebelum mengirim:</p>
        <textarea id="waTemplate">Halo {nama},

Ini adalah pengingat bahwa tagihan WiFi Anda akan segera jatuh tempo pada tanggal {tanggal}.

Mohon segera lakukan pembayaran.
<?php if (!empty($bankAccount)): ?>
Pembayaran via Transfer:
{rekening}
<?php endif; ?>

Terima kasih,
MIKPAY WiFi</textarea>
        <div style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 8px; font-size: 11px; color: #64748b;">
            <strong>Placeholder yang tersedia:</strong><br>
            {nama} - Nama pelanggan<br>
            {tanggal} - Tanggal jatuh tempo<br>
            {hari} - Hari tersisa<br>
            {nominal} - Nominal tagihan<br>
            <?php if (!empty($bankAccount)): ?>
            {rekening} - Nomor rekening bank<br>
            <?php endif; ?>
        </div>
        <div class="wa-template-buttons" style="flex-wrap: wrap;">
            <button class="btn-cancel" onclick="closeWAModal()">Batal</button>
            <?php if ($fonnteEnabled): ?>
            <button class="btn-confirm" id="btnFonnte" onclick="sendViaFonnte(this)" style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);">
                <i class="fa fa-paper-plane"></i> Kirim via Fonnte
            </button>
            <?php endif; ?>
            <button class="btn-confirm" onclick="confirmSendWA()" style="<?= $fonnteEnabled ? 'background: #64748b;' : '' ?>">
                <i class="fa fa-external-link"></i> Buka WhatsApp
            </button>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="wa-template-modal" id="editModal">
    <div class="wa-template-content" style="max-width: 450px;">
        <h4><i class="fa fa-pencil" style="color:#4D44B5"></i> Edit Data Tagihan</h4>
        <form id="editForm" method="POST" action="./ppp/save-billing.php">
            <input type="hidden" name="action" value="save_customer_billing">
            <input type="hidden" name="session" value="<?= $session ?>">
            <input type="hidden" id="editUsername" name="username">
            
            <div class="form-group">
                <label>Username PPPoE</label>
                <input type="text" id="editUsernameDisplay" disabled style="background:#f1f5f9;">
            </div>
            
            <div class="form-group">
                <label>Nama Pelanggan (Invoice)</label>
                <input type="text" id="editDisplayName" name="display_name" placeholder="Nama untuk tampil di invoice">
            </div>
            
            <div class="form-group">
                <label>No. HP / WhatsApp</label>
                <input type="text" id="editPhone" name="phone" placeholder="08xxx">
            </div>
            
            <div class="form-group">
                <label>Tanggal Jatuh Tempo</label>
                <select id="editDueDay" name="due_day">
                    <option value="0">-- Pilih Tanggal --</option>
                    <?php for ($i = 1; $i <= 31; $i++): ?>
                    <option value="<?= $i ?>">Tanggal <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tarif Bulanan (Rp)</label>
                <input type="number" id="editMonthlyFee" name="monthly_fee" placeholder="150000" min="0">
            </div>
            
            <div class="form-group">
                <label>Catatan</label>
                <textarea id="editNotes" name="notes" rows="2" placeholder="Catatan tambahan..."></textarea>
            </div>
            
            <div class="wa-template-buttons">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-confirm" style="background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);">
                    <i class="fa fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($view == 'report'): ?>
<!-- ========== TAB: LAPORAN ========== -->

<?php
// Calculate total piutang (outstanding)
$allPayments = function_exists('getBillingPayments') ? getBillingPayments() : array();
$totalPiutang = 0;
$piutangCount = 0;
foreach ($allPayments as $p) {
    if (isset($p['remaining']) && $p['remaining'] > 0) {
        $totalPiutang += $p['remaining'];
        $piutangCount++;
    }
}

// Get monthly report data
$monthlyData = array();
foreach ($allPayments as $p) {
    $period = isset($p['period']) ? $p['period'] : '';
    if (!isset($monthlyData[$period])) {
        $monthlyData[$period] = array('total_amount' => 0, 'total_transactions' => 0);
    }
    $monthlyData[$period]['total_amount'] += isset($p['amount']) ? $p['amount'] : 0;
    $monthlyData[$period]['total_transactions']++;
}
krsort($monthlyData);
?>

<div class="report-summary">
    <div class="report-summary-card" style="background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color:#FFF;">
        <div class="value">Rp <?= number_format($totalThisMonth, 0, ',', '.') ?></div>
        <div class="label" style="color:rgba(255,255,255,0.8);">Pendapatan Bulan Ini</div>
    </div>
    <div class="report-summary-card" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color:#FFF;">
        <div class="value">Rp <?= number_format($totalAllTime, 0, ',', '.') ?></div>
        <div class="label" style="color:rgba(255,255,255,0.8);">Total Semua Waktu</div>
    </div>
    <?php if ($totalPiutang > 0): ?>
    <div class="report-summary-card" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color:#FFF;">
        <div class="value">Rp <?= number_format($totalPiutang, 0, ',', '.') ?></div>
        <div class="label" style="color:rgba(255,255,255,0.8);">Total Piutang (<?= $piutangCount ?> invoice)</div>
    </div>
    <?php endif; ?>
</div>

<div class="billing-table-container">
    <div class="billing-table-header">
        <h3><i class="fa fa-bar-chart"></i> Laporan Bulanan</h3>
        <div class="report-controls-wrapper">
            <div class="report-selectors">
                <select id="monthSelect" class="report-select">
                    <?php 
                    $currentMonth = intval(date('m'));
                    $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    for ($m = 1; $m <= 12; $m++): 
                    ?>
                    <option value="<?= $m ?>" <?= $m == $currentMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                    <?php endfor; ?>
                </select>
                <select id="yearSelect" class="report-select">
                    <?php 
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 5; $y--): 
                    ?>
                    <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="report-buttons">
                <button class="btn-report btn-report-monthly" onclick="printMonthlyReport()" title="Cetak Laporan Bulanan">
                    <div class="btn-report-icon">
                        <i class="fa fa-calendar-alt"></i>
                    </div>
                    <div class="btn-report-content">
                        <span class="btn-report-label">Laporan Bulanan</span>
                        <span class="btn-report-desc">Cetak per bulan</span>
                    </div>
                    <i class="fa fa-chevron-right btn-report-arrow"></i>
                </button>
                <button class="btn-report btn-report-yearly" onclick="printReport()" title="Cetak Laporan Tahunan">
                    <div class="btn-report-icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="btn-report-content">
                        <span class="btn-report-label">Laporan Tahunan</span>
                        <span class="btn-report-desc">Cetak per tahun</span>
                    </div>
                    <i class="fa fa-chevron-right btn-report-arrow"></i>
                </button>
            </div>
        </div>
    </div>
    <table class="billing-table">
        <thead>
            <tr>
                <th>Periode</th>
                <th>Jumlah Transaksi</th>
                <th>Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthlyData as $period => $data): ?>
            <tr>
                <td><strong><?= date('F Y', strtotime($period . '-01')) ?></strong></td>
                <td>
                    <?php if ($data['total_transactions'] > 0): ?>
                    <span class="amount">Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></span>
                    <?php else: ?>
                    <span class="no-data">Belum ada data</span>
                    <?php endif; ?>
                </td>
                <td><?= $data['total_transactions'] ?> transaksi</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($view == 'invoices'): ?>
<!-- ========== TAB: INVOICE ========== -->

<?php
$filterPeriod = isset($_GET['filter_period']) ? $_GET['filter_period'] : '';
$invoices = function_exists('getBillingPayments') ? getBillingPayments() : array();
if (!empty($filterPeriod)) {
    $invoices = array_filter($invoices, function($p) use ($filterPeriod) {
        return isset($p['period']) && $p['period'] === $filterPeriod;
    });
}

// Group by period
$invoicesByPeriod = array();
foreach ($invoices as $inv) {
    $period = isset($inv['period']) ? $inv['period'] : 'Unknown';
    if (!isset($invoicesByPeriod[$period])) {
        $invoicesByPeriod[$period] = array();
    }
    $invoicesByPeriod[$period][] = $inv;
}
krsort($invoicesByPeriod);

// Calculate period totals
$periodTotals = array();
$periodPiutang = array();
foreach ($invoicesByPeriod as $period => $invList) {
    $periodTotals[$period] = 0;
    $periodPiutang[$period] = 0;
    foreach ($invList as $inv) {
        $periodTotals[$period] += isset($inv['amount']) ? $inv['amount'] : 0;
        $periodPiutang[$period] += isset($inv['remaining']) ? $inv['remaining'] : 0;
    }
}

$successMsg = isset($_GET['success']);
?>

<?php if ($successMsg): ?>
<div class="success-alert">
    <i class="fa fa-check-circle"></i>
    Pembayaran berhasil dicatat!
</div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
    <div style="display:flex; gap:10px; align-items:center;">
        <select id="filterPeriod" onchange="filterByPeriod()" style="padding:10px 15px; border:2px solid #e2e8f0; border-radius:10px;">
            <option value="">Semua Periode</option>
            <?php 
            $allPaymentsForPeriods = function_exists('getBillingPayments') ? getBillingPayments() : array();
            $periods = array();
            foreach ($allPaymentsForPeriods as $p) {
                if (isset($p['period']) && !in_array($p['period'], $periods)) {
                    $periods[] = $p['period'];
                }
            }
            rsort($periods);
            foreach ($periods as $period):
            ?>
            <option value="<?= $period ?>" <?= $filterPeriod == $period ? 'selected' : '' ?>><?= date('F Y', strtotime($period . '-01')) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn-confirm" onclick="showPaymentModal()" style="background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);">
        <i class="fa fa-plus"></i> Tambah Pembayaran
    </button>
</div>

<?php if (empty($invoices)): ?>
<div class="empty-invoices">
    <i class="fa fa-file-text-o"></i>
    <h3>Belum Ada Invoice</h3>
    <p>Klik "Tambah Pembayaran" untuk mencatat pembayaran baru.</p>
</div>
<?php else: ?>

<?php foreach ($invoicesByPeriod as $period => $periodInvoices): ?>
<div style="margin-bottom: 30px;">
    <div class="invoice-month-header">
        <h4><?= date('F Y', strtotime($period . '-01')) ?></h4>
        <div class="month-stats">
            <span class="invoice-month-stat">
                <?= count($periodInvoices) ?> transaksi | 
                Diterima: Rp <?= number_format(isset($periodTotals[$period]) ? $periodTotals[$period] : 0, 0, ',', '.') ?>
            </span>
            <?php if ((isset($periodPiutang[$period]) ? $periodPiutang[$period] : 0) > 0): ?>
            <div class="invoice-month-stat">
                | Piutang: <span style="color:#fbbf24;">Rp <?= number_format($periodPiutang[$period], 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="invoice-grid">
        <?php foreach ($periodInvoices as $inv): 
            $isPartial = (isset($inv['status']) ? $inv['status'] : 'paid') === 'partial';
            $remaining = isset($inv['remaining']) ? $inv['remaining'] : 0;
            $totalBill = isset($inv['total_bill']) ? $inv['total_bill'] : $inv['amount'];
        ?>
        <div class="invoice-card <?= $isPartial ? 'partial' : '' ?>">
            <div class="invoice-card-header">
                <span class="invoice-id"><?= $inv['id'] ?></span>
                <?php if ($isPartial): ?>
                <span class="invoice-status" style="background: rgba(255,255,255,0.2); color:#FFF;">Sebagian</span>
                <?php else: ?>
                <span class="invoice-status">Lunas</span>
                <?php endif; ?>
            </div>
            <div class="invoice-card-body">
                <div class="invoice-row">
                    <span class="invoice-label">Pelanggan</span>
                    <span class="invoice-value"><?= htmlspecialchars(isset($inv['display_name']) && $inv['display_name'] ? $inv['display_name'] : $inv['customer_name']) ?></span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Username</span>
                    <span class="invoice-value"><?= htmlspecialchars($inv['customer_name']) ?></span>
                </div>
                <?php if ($totalBill > 0): ?>
                <div class="invoice-row">
                    <span class="invoice-label">Total Tagihan</span>
                    <span class="invoice-value">Rp <?= number_format($totalBill, 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>
                <div class="invoice-row">
                    <span class="invoice-label">Dibayar</span>
                    <span class="invoice-value" style="color:#16a34a; font-size:18px;">
                    Rp <?= number_format($inv['amount'], 0, ',', '.') ?>
                    <?php if ($isPartial && $remaining > 0): ?>
                    <div style="font-size:13px; color:#dc2626; margin-top:5px;">
                        Sisa: Rp <?= number_format($remaining, 0, ',', '.') ?>
                    </div>
                    <?php endif; ?>
                </span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Tanggal</span>
                    <span class="invoice-value"><?= isset($inv['payment_date']) && !empty($inv['payment_date']) ? date('d M Y H:i', strtotime($inv['payment_date'])) : date('d M Y H:i') ?></span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Metode</span>
                    <span class="invoice-value"><?= $inv['payment_method'] ?></span>
                </div>
            </div>
            <div class="invoice-actions">
                <button class="btn-secondary" onclick="printInvoice('<?= $inv['id'] ?>')">
                    <i class="fa fa-print"></i> Cetak
                </button>
                <button class="btn-secondary" onclick="deleteInvoice('<?= $inv['id'] ?>', '<?= htmlspecialchars($inv['customer_name']) ?>')" style="color:#dc2626; border-color:#fee2e2;">
                    <i class="fa fa-trash"></i> Hapus
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Add Payment Modal -->
<div class="payment-modal" id="paymentModal">
    <div class="payment-modal-content">
        <div class="payment-modal-header">
            <h4><i class="fa fa-plus-circle"></i> Tambah Pembayaran</h4>
        </div>
        <form method="POST" action="?ppp=billing&session=<?= $session ?>&view=invoices">
            <div class="payment-modal-body">
                <div class="form-group">
                    <label>Pelanggan (Username PPPoE)</label>
                    <select name="customer_name" id="customerSelect" required onchange="updateCustomerInfo()">
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php foreach ($customers as $c): 
                            $outstanding = 0;
                            if (function_exists('getCustomerOutstanding')) {
                                $outstanding = getCustomerOutstanding($c['name']);
                            }
                        ?>
                        <option value="<?= htmlspecialchars($c['name']) ?>"
                                data-phone="<?= htmlspecialchars($c['phone']) ?>"
                                data-display="<?= htmlspecialchars($c['display_name']) ?>"
                                data-fee="<?= $c['monthly_fee'] ?>"
                                data-outstanding="<?= $outstanding ?>">
                            <?= htmlspecialchars($c['name']) ?> 
                            <?= $c['display_name'] ? '(' . htmlspecialchars($c['display_name']) . ')' : '' ?>
                            <?= $outstanding > 0 ? ' - Piutang: Rp ' . number_format($outstanding, 0, ',', '.') : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Nama Pelanggan (untuk Invoice)</label>
                    <input type="text" name="display_name" id="displayName" placeholder="Nama yang tampil di invoice">
                </div>
                
                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="phone" id="paymentPhone" placeholder="08xxx">
                </div>
                
                <div class="form-group">
                    <label>Periode</label>
                    <input type="month" name="period" value="<?= date('Y-m') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Pembayaran</label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Tagihan Bulan Ini (Rp)</label>
                    <input type="number" name="bill_amount" id="billAmount" placeholder="150000" min="0">
                </div>
                
                <div class="form-group">
                    <label>Piutang Sebelumnya (Rp)</label>
                    <input type="number" name="previous_debt" id="previousDebt" value="0" min="0" readonly style="background:#f1f5f9;">
                </div>
                
                <div class="form-group">
                    <label>Jumlah Dibayar (Rp)</label>
                    <input type="number" name="amount" id="paymentAmount" placeholder="150000" required min="0" onchange="updatePaymentInfo()">
                </div>
                
                <div class="form-group">
                    <label>Status Pembayaran</label>
                    <select name="payment_status" id="paymentStatus">
                        <option value="paid">Lunas</option>
                        <option value="partial">Sebagian (ada sisa)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="payment_method">
                        <option value="Cash">Cash</option>
                        <option value="Transfer">Transfer Bank</option>
                        <option value="QRIS">QRIS</option>
                        <option value="E-Wallet">E-Wallet</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>
            <div class="payment-modal-footer">
                <button type="button" class="btn-secondary" onclick="closePaymentModal()">Batal</button>
                <button type="submit" name="add_payment" class="btn-primary">
                    <i class="fa fa-save"></i> Simpan Pembayaran
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<script>
// Fonnte configuration
var fonnteEnabled = <?= $fonnteEnabled ? 'true' : 'false' ?>;

// Current customer data for single send
var currentCustomer = null;
var bulkMode = false;

// Filter buttons
document.querySelectorAll('.btn-filter').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-filter').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        
        var filter = this.getAttribute('data-filter');
        document.querySelectorAll('#customerTable tbody tr').forEach(function(row) {
            if (filter === 'all' || row.getAttribute('data-status') === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.customer-check').forEach(function(cb) {
        cb.checked = this.checked;
    }.bind(this));
});

// Format Rupiah
function formatRupiah(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Toggle bank settings
function toggleBankSettings() {
    var card = document.getElementById('bankSettingsCard');
    var body = document.getElementById('bankSettingsBody');
    var icon = document.getElementById('bankSettingsIcon');
    
    if (body.style.display === 'none') {
        body.style.display = 'block';
        card.classList.add('expanded');
    } else {
        body.style.display = 'none';
        card.classList.remove('expanded');
    }
}

// Get bank account info from PHP
var bankAccount = '<?= addslashes($bankAccount) ?>';
var bankName = '<?= addslashes($bankName) ?>';
var bankAccountName = '<?= addslashes($bankAccountName) ?>';
var bankInfo = '';
if (bankAccount) {
    var parts = [];
    if (bankName) parts.push(bankName);
    parts.push(bankAccount);
    if (bankAccountName) parts.push('a/n ' + bankAccountName);
    bankInfo = parts.join(' - ');
}

// Send single WhatsApp
function sendWhatsApp(phone, name, dueDate, daysLeft, status, monthlyFee) {
    currentCustomer = {
        phone: phone,
        name: name,
        dueDate: dueDate,
        daysLeft: daysLeft,
        status: status,
        monthlyFee: monthlyFee || 0
    };
    bulkMode = false;
    
    var nominal = formatRupiah(monthlyFee);
    var template = 'Halo {nama},\n\nTagihan WiFi Anda untuk periode ini: *Rp {nominal}*\nJatuh tempo: {tanggal}\n\n';
    if (bankInfo) {
        template += 'Pembayaran via Transfer:\n{rekening}\n\n';
    }
    template += 'Terima kasih,\nMIKPAY WiFi';
    template = template.replace(/{nominal}/g, nominal);
    if (bankInfo) {
        template = template.replace(/{rekening}/g, bankInfo);
    }
    
    document.getElementById('waTemplate').value = template;
    document.getElementById('waModal').classList.add('show');
}

// Show bulk WA modal
function showBulkWAModal() {
    var selected = document.querySelectorAll('.customer-check:checked');
    if (selected.length === 0) {
        alert('Pilih minimal satu pelanggan!');
        return;
    }
    bulkMode = true;
    
    // Set default template for bulk
    var template = 'Halo {nama},\n\nTagihan WiFi Anda untuk periode ini: *Rp {nominal}*\nJatuh tempo: {tanggal}\n\n';
    if (bankInfo) {
        template += 'Pembayaran via Transfer:\n{rekening}\n\n';
    }
    template += 'Terima kasih,\nMIKPAY WiFi';
    
    document.getElementById('waTemplate').value = template;
    document.getElementById('waModal').classList.add('show');
}

// Close WA modal
function closeWAModal() {
    document.getElementById('waModal').classList.remove('show');
}

// Show import modal
function showImportModal() {
    document.getElementById('importModal').classList.add('show');
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importForm').reset();
}

// Close import modal
function closeImportModal() {
    document.getElementById('importModal').classList.remove('show');
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importProgress').style.display = 'none';
    document.getElementById('importForm').reset();
}

// Send via Fonnte API
function sendViaFonnte(btn) {
    var template = document.getElementById('waTemplate').value;
    var originalText = btn.innerHTML;
    
    if (bulkMode) {
        var selected = document.querySelectorAll('.customer-check:checked');
        var recipients = [];
        
        selected.forEach(function(cb) {
            var phone = cb.getAttribute('data-phone');
            var name = cb.getAttribute('data-name');
            var due = cb.getAttribute('data-due');
            var days = cb.getAttribute('data-days');
            var fee = cb.getAttribute('data-fee') || 0;
            var nominal = formatRupiah(fee);
            
            var message = template
                .replace(/{nama}/g, name)
                .replace(/{tanggal}/g, due || '-')
                .replace(/{hari}/g, days !== 'null' ? days : '-')
                .replace(/{nominal}/g, nominal);
            
            if (bankInfo) {
                message = message.replace(/{rekening}/g, bankInfo);
            }
            
            recipients.push({ phone: phone, message: message });
        });
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengirim...';
        
        fetch('./ppp/send-whatsapp.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=send_bulk&recipients=' + encodeURIComponent(JSON.stringify(recipients))
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.success) {
                alert(data.message);
                closeWAModal();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(function(error) {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('Error: ' + error.message);
        });
    } else {
        if (currentCustomer) {
            var nominal = formatRupiah(currentCustomer.monthlyFee);
            var message = template
                .replace(/{nama}/g, currentCustomer.name)
                .replace(/{tanggal}/g, currentCustomer.dueDate || '-')
                .replace(/{hari}/g, currentCustomer.daysLeft !== null ? currentCustomer.daysLeft : '-')
                .replace(/{nominal}/g, nominal);
            
            if (bankInfo) {
                message = message.replace(/{rekening}/g, bankInfo);
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengirim...';
            
            fetch('./ppp/send-whatsapp.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=send_single&phone=' + encodeURIComponent(currentCustomer.phone) + '&message=' + encodeURIComponent(message)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (data.success) {
                    alert('Pesan berhasil dikirim ke ' + currentCustomer.name);
                    closeWAModal();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(function(error) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                alert('Error: ' + error.message);
            });
        }
    }
}

// Confirm and send WhatsApp (open wa.me)
function confirmSendWA() {
    var template = document.getElementById('waTemplate').value;
    
    if (bulkMode) {
        var selected = document.querySelectorAll('.customer-check:checked');
        selected.forEach(function(cb) {
            var phone = cb.getAttribute('data-phone');
            var name = cb.getAttribute('data-name');
            var due = cb.getAttribute('data-due');
            var days = cb.getAttribute('data-days');
            var fee = cb.getAttribute('data-fee') || 0;
            var nominal = formatRupiah(fee);
            
            var message = template
                .replace(/{nama}/g, name)
                .replace(/{tanggal}/g, due || '-')
                .replace(/{hari}/g, days !== 'null' ? days : '-')
                .replace(/{nominal}/g, nominal);
            
            if (bankInfo) {
                message = message.replace(/{rekening}/g, bankInfo);
            }
            
            var formattedPhone = phone.replace(/^0/, '62');
            window.open('https://wa.me/' + formattedPhone + '?text=' + encodeURIComponent(message), '_blank');
        });
    } else {
        if (currentCustomer) {
            var nominal = formatRupiah(currentCustomer.monthlyFee);
            var message = template
                .replace(/{nama}/g, currentCustomer.name)
                .replace(/{tanggal}/g, currentCustomer.dueDate || '-')
                .replace(/{hari}/g, currentCustomer.daysLeft !== null ? currentCustomer.daysLeft : '-')
                .replace(/{nominal}/g, nominal);
            
            if (bankInfo) {
                message = message.replace(/{rekening}/g, bankInfo);
            }
            
            var phone = currentCustomer.phone.replace(/^0/, '62');
            window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(message), '_blank');
        }
    }
    
    closeWAModal();
}

// Edit customer
function editCustomer(username, dueDay, displayName, phone, monthlyFee, notes) {
    document.getElementById('editUsername').value = username;
    document.getElementById('editUsernameDisplay').value = username;
    document.getElementById('editDueDay').value = dueDay;
    document.getElementById('editDisplayName').value = displayName;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editMonthlyFee').value = monthlyFee;
    document.getElementById('editNotes').value = notes;
    document.getElementById('editModal').classList.add('show');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// Submit edit form via AJAX
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    
    fetch('./ppp/save-billing.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Data berhasil disimpan!');
            location.reload();
        } else {
            alert('Gagal: ' + data.message);
        }
    })
    .catch(function(error) {
        alert('Error: ' + error.message);
    });
});

// Payment modal
function showPaymentModal() {
    document.getElementById('paymentModal').classList.add('show');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('show');
}

// Update customer info when selected
function updateCustomerInfo() {
    var select = document.getElementById('customerSelect');
    var option = select.options[select.selectedIndex];
    
    document.getElementById('paymentPhone').value = option.getAttribute('data-phone') || '';
    document.getElementById('displayName').value = option.getAttribute('data-display') || '';
    document.getElementById('billAmount').value = option.getAttribute('data-fee') || '';
    document.getElementById('previousDebt').value = option.getAttribute('data-outstanding') || '0';
    
    updatePaymentInfo();
}

// Update payment info
function updatePaymentInfo() {
    var billAmount = parseFloat(document.getElementById('billAmount').value) || 0;
    var previousDebt = parseFloat(document.getElementById('previousDebt').value) || 0;
    var paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
    var totalBill = billAmount + previousDebt;
    
    if (paymentAmount >= totalBill && totalBill > 0) {
        document.getElementById('paymentStatus').value = 'paid';
    } else if (paymentAmount > 0) {
        document.getElementById('paymentStatus').value = 'partial';
    }
}

// Filter by period
function filterByPeriod() {
    var period = document.getElementById('filterPeriod').value;
    window.location.href = '?ppp=billing&session=<?= $session ?>&view=invoices' + (period ? '&filter_period=' + period : '');
}

// Close modal on outside click
document.getElementById('waModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeWAModal();
    }
});

// Import modal click outside to close
document.getElementById('importModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImportModal();
    }
});

// Handle import form submit
document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    var fileInput = document.getElementById('excelFile');
    
    if (!fileInput.files || !fileInput.files[0]) {
        alert('Silakan pilih file terlebih dahulu');
        return;
    }
    
    // Show progress
    document.getElementById('importProgress').style.display = 'block';
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importSubmitBtn').disabled = true;
    
    // Upload file
    fetch('./ppp/import-excel.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        // Check if response is OK
        if (!response.ok) {
            throw new Error('HTTP Error: ' + response.status + ' ' + response.statusText);
        }
        
        // Get response text first
        return response.text().then(function(text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                // If not JSON, return error
                throw new Error('Invalid JSON response: ' + text.substring(0, 200));
            }
        });
    })
    .then(function(data) {
        document.getElementById('importProgress').style.display = 'none';
        document.getElementById('importSubmitBtn').disabled = false;
        
        var resultDiv = document.getElementById('importResult');
        resultDiv.style.display = 'block';
        
        if (data.success) {
            resultDiv.style.background = 'linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%)';
            resultDiv.style.border = '1px solid #22c55e';
            resultDiv.style.color = '#166534';
            
            var html = '<div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">';
            html += '<i class="fa fa-check-circle" style="font-size:24px;"></i>';
            html += '<strong>' + data.message + '</strong>';
            html += '</div>';
            
            if (data.stats) {
                html += '<div style="font-size:13px; margin-top:10px;">';
                html += '<div>✓ Berhasil: <strong>' + data.stats.success + '</strong> data</div>';
                if (data.stats.skipped > 0) {
                    html += '<div>⊘ Dilewati: <strong>' + data.stats.skipped + '</strong> baris</div>';
                }
                if (data.stats.errors > 0) {
                    html += '<div style="color:#dc2626;">✗ Error: <strong>' + data.stats.errors + '</strong></div>';
                }
                html += '</div>';
            }
            
            if (data.errors && data.errors.length > 0) {
                html += '<div style="margin-top:15px; padding:10px; background:#fee2e2; border-radius:6px; font-size:12px; max-height:150px; overflow-y:auto;">';
                html += '<strong style="color:#dc2626;">Detail Error:</strong><ul style="margin:5px 0 0 20px; color:#991b1b;">';
                data.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul></div>';
            }
            
            resultDiv.innerHTML = html;
            
            // Reload page after 2 seconds if success
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        } else {
            resultDiv.style.background = 'linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%)';
            resultDiv.style.border = '1px solid #ef4444';
            resultDiv.style.color = '#dc2626';
            resultDiv.innerHTML = '<div style="display:flex; align-items:center; gap:10px;">' +
                '<i class="fa fa-exclamation-circle" style="font-size:24px;"></i>' +
                '<strong>' + data.message + '</strong>' +
                '</div>';
        }
    })
    .catch(function(error) {
        document.getElementById('importProgress').style.display = 'none';
        document.getElementById('importSubmitBtn').disabled = false;
        
        var resultDiv = document.getElementById('importResult');
        resultDiv.style.display = 'block';
        resultDiv.style.background = 'linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%)';
        resultDiv.style.border = '1px solid #ef4444';
        resultDiv.style.color = '#dc2626';
        resultDiv.innerHTML = '<div style="display:flex; align-items:center; gap:10px;">' +
            '<i class="fa fa-exclamation-circle" style="font-size:24px;"></i>' +
            '<strong>Error: ' + error.message + '</strong>' +
            '</div>';
    });
});

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

// Print monthly report
function printMonthlyReport() {
    var month = document.getElementById('monthSelect').value;
    var year = document.getElementById('yearSelect').value;
    window.open('./ppp/print-monthly-report.php?session=<?= $session ?>&month=' + month + '&year=' + year, '_blank', 'width=800,height=600');
}

// Print yearly report
function printReport() {
    var year = document.getElementById('yearSelect').value;
    window.open('./ppp/print-report.php?session=<?= $session ?>&year=' + year, '_blank', 'width=800,height=600');
}

// Print invoice
function printInvoice(invoiceId) {
    window.open('./ppp/print-invoice.php?id=' + invoiceId + '&session=<?= $session ?>', '_blank', 'width=400,height=700');
}

// Delete invoice
function deleteInvoice(invoiceId, customerName) {
    if (!confirm('Apakah Anda yakin ingin menghapus invoice ' + invoiceId + ' untuk pelanggan ' + customerName + '?\n\nTindakan ini tidak dapat dibatalkan.')) {
        return;
    }
    
    fetch('./ppp/delete-invoice.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'invoice_id=' + encodeURIComponent(invoiceId) + '&session=<?= $session ?>'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Invoice berhasil dihapus!');
            location.reload();
        } else {
            alert('Gagal menghapus: ' + data.message);
        }
    })
    .catch(function(error) {
        alert('Error: ' + error.message);
    });
}
</script>
