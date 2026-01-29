<?php
/*
 * PPP Billing Data Management
 * Mengelola data pembayaran pelanggan WiFi
 */

if (!defined('BILLING_DATA_FILE')) {
    define('BILLING_DATA_FILE', dirname(__DIR__) . '/include/billing_payments.json');
}
if (!defined('BILLING_CUSTOMERS_FILE')) {
    define('BILLING_CUSTOMERS_FILE', dirname(__DIR__) . '/include/billing_customers.json');
}

/**
 * =========================================
 * CUSTOMER BILLING SETTINGS
 * Menyimpan pengaturan jatuh tempo per pelanggan
 * =========================================
 */

/**
 * Get all customer billing settings
 */
function getCustomerBillingSettings() {
    if (!file_exists(BILLING_CUSTOMERS_FILE)) {
        return array();
    }
    $data = file_get_contents(BILLING_CUSTOMERS_FILE);
    return json_decode($data, true) ?: array();
}

/**
 * Save customer billing settings
 */
function saveCustomerBillingSettings($settings) {
    file_put_contents(BILLING_CUSTOMERS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
}

/**
 * Get or create customer billing setting
 * @param string $customerName - PPP username
 * @return array Customer billing data
 */
function getCustomerBilling($customerName) {
    $settings = getCustomerBillingSettings();
    if (isset($settings[$customerName])) {
        return $settings[$customerName];
    }
    return array(
        'customer_name' => $customerName,
        'due_day' => 0, // 0 = belum diatur
        'display_name' => '',
        'phone' => '',
        'monthly_fee' => 0, // Tarif bulanan
        'notes' => ''
    );
}

/**
 * Save customer billing setting
 * @param string $customerName - PPP username
 * @param int $dueDay - Tanggal jatuh tempo (1-31)
 * @param string $displayName - Nama untuk invoice
 * @param string $phone - Nomor telepon
 * @param float $monthlyFee - Tarif bulanan
 * @param string $notes - Catatan
 */
function saveCustomerBilling($customerName, $dueDay, $displayName = '', $phone = '', $monthlyFee = 0, $notes = '') {
    $settings = getCustomerBillingSettings();
    $settings[$customerName] = array(
        'customer_name' => $customerName,
        'due_day' => intval($dueDay),
        'display_name' => $displayName,
        'phone' => $phone,
        'monthly_fee' => floatval($monthlyFee),
        'notes' => $notes,
        'updated_at' => date('Y-m-d H:i:s')
    );
    saveCustomerBillingSettings($settings);
    return $settings[$customerName];
}

/**
 * Calculate billing status for a customer
 * @param string $customerName - PPP username
 * @param int|null $dueDay - Tanggal jatuh tempo (null = ambil dari settings)
 * @return array Status info: status, days_to_due, current_period, message
 */
function getCustomerBillingStatus($customerName, $dueDay = null) {
    $today = new DateTime();
    $currentYear = intval($today->format('Y'));
    $currentMonth = intval($today->format('m'));
    $currentDay = intval($today->format('d'));
    
    // Get due day from settings if not provided
    if ($dueDay === null) {
        $customerBilling = getCustomerBilling($customerName);
        $dueDay = isset($customerBilling['due_day']) ? $customerBilling['due_day'] : 0;
    }
    
    // If due day not set
    if ($dueDay <= 0 || $dueDay > 31) {
        return array(
            'status' => 'not_set',
            'status_label' => 'Belum Diatur',
            'status_class' => 'not-set',
            'days_to_due' => null,
            'current_period' => date('Y-m'),
            'due_date' => null,
            'message' => 'Tanggal jatuh tempo belum diatur'
        );
    }
    
    // Calculate due date for current month
    $daysInMonth = intval($today->format('t'));
    $actualDueDay = min($dueDay, $daysInMonth);
    $dueDateThisMonth = new DateTime($currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($actualDueDay, 2, '0', STR_PAD_LEFT));
    
    // Calculate days to due date
    $diff = $today->diff($dueDateThisMonth);
    $daysTodue = $diff->invert ? -$diff->days : $diff->days;
    
    // Determine current billing period
    // If past due date this month and not paid, period is still current month
    // If paid for current month, period moves to next month
    $currentPeriod = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
    
    // Check if customer has paid for current period
    $hasPaid = hasCustomerPaid($customerName, $currentPeriod);
    
    if ($hasPaid) {
        // Already paid for this month
        // Check next month's billing
        $nextMonth = $currentMonth + 1;
        $nextYear = $currentYear;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        $nextPeriod = $nextYear . '-' . str_pad($nextMonth, 2, '0', STR_PAD_LEFT);
        $daysInNextMonth = cal_days_in_month(CAL_GREGORIAN, $nextMonth, $nextYear);
        $nextDueDay = min($dueDay, $daysInNextMonth);
        $nextDueDate = new DateTime($nextYear . '-' . str_pad($nextMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($nextDueDay, 2, '0', STR_PAD_LEFT));
        
        $diffNext = $today->diff($nextDueDate);
        $daysToNextDue = $diffNext->invert ? -$diffNext->days : $diffNext->days;
        
        return array(
            'status' => 'paid',
            'status_label' => 'Lunas',
            'status_class' => 'paid',
            'days_to_due' => $daysToNextDue,
            'current_period' => $currentPeriod,
            'next_period' => $nextPeriod,
            'due_date' => $nextDueDate->format('Y-m-d'),
            'message' => 'Sudah lunas untuk ' . this_month_name($currentMonth) . '. Jatuh tempo berikutnya: ' . $nextDueDate->format('d M Y')
        );
    }
    
    // Not paid yet
    if ($daysTodue < 0) {
        // Overdue (past due date)
        return array(
            'status' => 'overdue',
            'status_label' => 'Terlambat',
            'status_class' => 'overdue',
            'days_to_due' => $daysTodue,
            'days_overdue' => abs($daysTodue),
            'current_period' => $currentPeriod,
            'due_date' => $dueDateThisMonth->format('Y-m-d'),
            'message' => 'Terlambat ' . abs($daysTodue) . ' hari'
        );
    } elseif ($daysTodue === 0) {
        // Due today (H)
        return array(
            'status' => 'due_today',
            'status_label' => 'Hari Ini',
            'status_class' => 'due-today',
            'days_to_due' => 0,
            'current_period' => $currentPeriod,
            'due_date' => $dueDateThisMonth->format('Y-m-d'),
            'message' => 'Jatuh tempo hari ini!',
            'send_reminder' => true
        );
    } elseif ($daysTodue <= 3) {
        // Due soon (H-3)
        return array(
            'status' => 'due_soon',
            'status_label' => $daysTodue . ' Hari Lagi',
            'status_class' => 'due-soon',
            'days_to_due' => $daysTodue,
            'current_period' => $currentPeriod,
            'due_date' => $dueDateThisMonth->format('Y-m-d'),
            'message' => 'Jatuh tempo ' . $daysTodue . ' hari lagi',
            'send_reminder' => ($daysTodue === 3) // H-3
        );
    } else {
        // Not yet due
        return array(
            'status' => 'waiting',
            'status_label' => $daysTodue . ' Hari',
            'status_class' => 'waiting',
            'days_to_due' => $daysTodue,
            'current_period' => $currentPeriod,
            'due_date' => $dueDateThisMonth->format('Y-m-d'),
            'message' => 'Jatuh tempo: ' . $dueDateThisMonth->format('d M Y')
        );
    }
}

/**
 * Helper: Get month name in Indonesian
 */
function this_month_name($month) {
    $names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return isset($names[$month]) ? $names[$month] : '';
}

/**
 * Get customers needing reminder (H-3 or H)
 * @return array List of customers needing WhatsApp reminder
 */
function getCustomersNeedingReminder() {
    $settings = getCustomerBillingSettings();
    $reminders = array();
    
    foreach ($settings as $customerName => $data) {
        if (empty($data['phone'])) continue;
        
        $status = getCustomerBillingStatus($customerName, $data['due_day']);
        
        // Send reminder on H-3 or H (due_today)
        if (($status['status'] === 'due_soon' && $status['days_to_due'] === 3) || 
            $status['status'] === 'due_today') {
            $reminders[] = array(
                'customer_name' => $customerName,
                'display_name' => $data['display_name'] ?: $customerName,
                'phone' => $data['phone'],
                'due_day' => $data['due_day'],
                'due_date' => $status['due_date'],
                'days_to_due' => $status['days_to_due'],
                'status' => $status['status'],
                'period' => $status['current_period']
            );
        }
    }
    
    return $reminders;
}

/**
 * Get overdue customers
 * @return array List of customers with overdue payments
 */
function getOverdueCustomers() {
    $settings = getCustomerBillingSettings();
    $overdue = array();
    
    foreach ($settings as $customerName => $data) {
        $status = getCustomerBillingStatus($customerName, $data['due_day']);
        
        if ($status['status'] === 'overdue') {
            $overdue[] = array(
                'customer_name' => $customerName,
                'display_name' => $data['display_name'] ?: $customerName,
                'phone' => $data['phone'],
                'due_day' => $data['due_day'],
                'due_date' => $status['due_date'],
                'days_overdue' => $status['days_overdue'],
                'period' => $status['current_period']
            );
        }
    }
    
    return $overdue;
}

/**
 * Get all billing payments
 */
function getBillingPayments() {
    if (!file_exists(BILLING_DATA_FILE)) {
        return array();
    }
    $data = file_get_contents(BILLING_DATA_FILE);
    return json_decode($data, true) ?: array();
}

/**
 * Save billing payments
 */
function saveBillingPayments($payments) {
    file_put_contents(BILLING_DATA_FILE, json_encode($payments, JSON_PRETTY_PRINT));
}

/**
 * Add payment record
 * @param string $customerName - PPP username
 * @param string $displayName - Nama pelanggan yang ditampilkan
 * @param string $phone - Nomor telepon
 * @param float $amount - Jumlah pembayaran
 * @param string $period - Periode (format: 2026-01)
 * @param string $paymentMethod - Metode pembayaran
 * @param string $notes - Catatan
 */
/**
 * Add payment record with partial payment support
 * @param string $customerName - PPP username
 * @param string $phone - Nomor telepon
 * @param float $amount - Jumlah yang dibayar
 * @param string $period - Periode (format: 2026-01)
 * @param string $paymentMethod - Metode pembayaran
 * @param string $notes - Catatan
 * @param string $displayName - Nama pelanggan untuk invoice
 * @param float $billAmount - Total tagihan (untuk hitung sisa)
 * @param string $paymentStatus - Status: 'paid' (lunas) / 'partial' (sebagian)
 * @param float $previousDebt - Piutang dari bulan sebelumnya yang ikut dibayar
 * @param string $paymentDate - Tanggal pembayaran (format: Y-m-d H:i:s), default: tanggal sekarang
 */
function addBillingPayment($customerName, $phone, $amount, $period, $paymentMethod = 'Transfer', $notes = '', $displayName = '', $billAmount = 0, $paymentStatus = 'paid', $previousDebt = 0, $paymentDate = null) {
    $payments = getBillingPayments();
    
    // Calculate remaining balance
    $totalBill = floatval($billAmount) + floatval($previousDebt);
    $remaining = $totalBill - floatval($amount);
    if ($remaining < 0) $remaining = 0;
    
    // Auto-determine status if not explicitly set
    if ($billAmount > 0 && $amount < $totalBill && $paymentStatus !== 'partial') {
        $paymentStatus = 'partial';
    }
    
    // Use provided payment date or default to current date/time
    if (empty($paymentDate)) {
        $paymentDate = date('Y-m-d H:i:s');
    }
    
    $payment = array(
        'id' => 'INV-' . date('Ymd') . '-' . rand(1000, 9999),
        'customer_name' => $customerName,
        'display_name' => !empty($displayName) ? $displayName : $customerName,
        'phone' => $phone,
        'amount' => floatval($amount),
        'bill_amount' => floatval($billAmount), // Total tagihan bulan ini
        'previous_debt' => floatval($previousDebt), // Piutang dari bulan sebelumnya
        'total_bill' => $totalBill, // Total yang harus dibayar
        'remaining' => $remaining, // Sisa yang belum dibayar
        'period' => $period,
        'payment_date' => $paymentDate,
        'payment_method' => $paymentMethod,
        'notes' => $notes,
        'status' => $paymentStatus // 'paid' atau 'partial'
    );
    
    $payments[] = $payment;
    saveBillingPayments($payments);
    
    return $payment;
}

/**
 * Get customer outstanding balance (piutang)
 * @param string $customerName - PPP username
 * @return float Total outstanding balance
 */
function getCustomerOutstanding($customerName) {
    $payments = getBillingPayments();
    $outstanding = 0;
    
    foreach ($payments as $payment) {
        if ($payment['customer_name'] === $customerName) {
            // Add remaining from partial payments
            if (isset($payment['remaining']) && $payment['remaining'] > 0) {
                $outstanding += $payment['remaining'];
            }
        }
    }
    
    return $outstanding;
}

/**
 * Get all customers with outstanding balance
 * @return array List of customers with outstanding balance
 */
function getCustomersWithOutstanding() {
    $payments = getBillingPayments();
    $customers = array();
    
    foreach ($payments as $payment) {
        if (isset($payment['remaining']) && $payment['remaining'] > 0) {
            $name = $payment['customer_name'];
            if (!isset($customers[$name])) {
                $customers[$name] = array(
                    'customer_name' => $name,
                    'display_name' => isset($payment['display_name']) && $payment['display_name'] ? $payment['display_name'] : $name,
                    'total_outstanding' => 0,
                    'payments' => array()
                );
            }
            $customers[$name]['total_outstanding'] += $payment['remaining'];
            $customers[$name]['payments'][] = array(
                'id' => $payment['id'],
                'period' => $payment['period'],
                'remaining' => $payment['remaining']
            );
        }
    }
    
    return $customers;
}

/**
 * Clear outstanding balance (when customer pays remaining)
 * @param string $invoiceId - Invoice ID to clear
 */
function clearOutstanding($invoiceId) {
    $payments = getBillingPayments();
    
    foreach ($payments as &$payment) {
        if ($payment['id'] === $invoiceId) {
            $payment['remaining'] = 0;
            $payment['status'] = 'paid';
            $payment['cleared_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    saveBillingPayments($payments);
}

/**
 * Get payments by customer
 */
function getPaymentsByCustomer($customerName) {
    $payments = getBillingPayments();
    return array_filter($payments, function($p) use ($customerName) {
        return $p['customer_name'] === $customerName;
    });
}

/**
 * Get payments by period
 */
function getPaymentsByPeriod($period) {
    $payments = getBillingPayments();
    return array_filter($payments, function($p) use ($period) {
        return $p['period'] === $period;
    });
}

/**
 * Get monthly report
 */
function getMonthlyReport($year, $month) {
    $payments = getBillingPayments();
    $period = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
    
    $monthlyPayments = array_filter($payments, function($p) use ($period) {
        return $p['period'] === $period;
    });
    
    $totalAmount = array_reduce($monthlyPayments, function($sum, $p) {
        return $sum + $p['amount'];
    }, 0);
    
    return array(
        'period' => $period,
        'total_transactions' => count($monthlyPayments),
        'total_amount' => $totalAmount,
        'payments' => array_values($monthlyPayments)
    );
}

/**
 * Check if customer has paid for period
 */
function hasCustomerPaid($customerName, $period) {
    $payments = getBillingPayments();
    foreach ($payments as $payment) {
        if ($payment['customer_name'] === $customerName && $payment['period'] === $period) {
            return true;
        }
    }
    return false;
}

/**
 * Get invoice by ID
 */
function getInvoiceById($invoiceId) {
    $payments = getBillingPayments();
    foreach ($payments as $payment) {
        if ($payment['id'] === $invoiceId) {
            return $payment;
        }
    }
    return null;
}

/**
 * Delete payment
 */
function deletePayment($invoiceId) {
    $payments = getBillingPayments();
    $payments = array_filter($payments, function($p) use ($invoiceId) {
        return $p['id'] !== $invoiceId;
    });
    saveBillingPayments(array_values($payments));
    return true;
}

/**
 * Get yearly summary
 */
function getYearlySummary($year) {
    $payments = getBillingPayments();
    $monthlySummary = array();
    
    for ($m = 1; $m <= 12; $m++) {
        $period = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $monthPayments = array_filter($payments, function($p) use ($period) {
            return $p['period'] === $period;
        });
        
        $monthlySummary[$m] = array(
            'month' => $m,
            'period' => $period,
            'total_transactions' => count($monthPayments),
            'total_amount' => array_reduce($monthPayments, function($sum, $p) {
                return $sum + $p['amount'];
            }, 0)
        );
    }
    
    return $monthlySummary;
}
