<?php
/*
 * Admin User Management Page
 * Menampilkan dan mengelola daftar user
 */

include_once('./include/subscription.php');

// Get all users
$users = getAllUsers();
$packages = getSubscriptionPackages();

// Filter users (exclude config.php sessions, only show JSON users)
$jsonUsers = array();
foreach ($users as $user) {
    // Only show users from JSON (not from config.php)
    if (isset($user['source']) && $user['source'] == 'config') {
        continue;
    }
    // Or if user has email/phone (indicating it's from JSON)
    if (isset($user['email']) || isset($user['phone']) || isset($user['password'])) {
        $jsonUsers[] = $user;
    }
}

// Get user stats
$totalUsers = count($jsonUsers);
$activeUsers = 0;
$inactiveUsers = 0;
foreach ($jsonUsers as $user) {
    if (isset($user['status']) && $user['status'] === 'active') {
        $activeUsers++;
    } else {
        $inactiveUsers++;
    }
}

// Get messages
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$messages = array(
    'deleted' => 'User berhasil dihapus!',
    'activated' => 'User berhasil diaktifkan!',
    'deactivated' => 'User berhasil dinonaktifkan.'
);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-users"></i> Kelola User</h3>
            </div>
            <div class="card-body">
                <?php if ($msg && isset($messages[$msg])): ?>
                <div class="alert alert-success" style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa fa-check-circle"></i> <?= $messages[$msg] ?>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-4">
                        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px;">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">Total User</h4>
                            <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;"><?= $totalUsers ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 12px;">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">User Aktif</h4>
                            <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;"><?= $activeUsers ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 20px; border-radius: 12px;">
                            <h4 style="margin: 0; font-size: 14px; opacity: 0.9;">User Nonaktif</h4>
                            <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;"><?= $inactiveUsers ?></h2>
                        </div>
                    </div>
                </div>
                
                <!-- Users Table -->
                <?php if (empty($jsonUsers)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                    <i class="fa fa-users" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p style="font-size: 18px; margin: 0;">Belum ada user terdaftar</p>
                    <p style="font-size: 14px; margin-top: 10px; opacity: 0.7;">User akan muncul di sini setelah ditambahkan dari Super Admin Panel</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" style="background: white; border-radius: 8px; overflow: hidden;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 12px;">ID User</th>
                                <th style="padding: 12px;">Nama</th>
                                <th style="padding: 12px;">Email</th>
                                <th style="padding: 12px;">No. WhatsApp</th>
                                <th style="padding: 12px;">Paket</th>
                                <th style="padding: 12px;">Status</th>
                                <th style="padding: 12px;">Trial End</th>
                                <th style="padding: 12px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jsonUsers as $user): ?>
                            <tr>
                                <td style="padding: 12px;">
                                    <strong><?= htmlspecialchars($user['id']) ?></strong>
                                </td>
                                <td style="padding: 12px;">
                                    <?= htmlspecialchars(isset($user['name']) ? $user['name'] : '-') ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?= htmlspecialchars(isset($user['email']) ? $user['email'] : '-') ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?= htmlspecialchars(isset($user['phone']) ? $user['phone'] : '-') ?>
                                </td>
                                <td style="padding: 12px;">
                                    <?php 
                                    $packageKey = isset($user['package']) ? $user['package'] : 'trial';
                                    $packageColor = isset($packages[$packageKey]['color']) ? $packages[$packageKey]['color'] : '#64748b';
                                    ?>
                                    <span style="background: <?= $packageColor ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                        <?= ucfirst($packageKey) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <?php 
                                    $status = isset($user['status']) ? $user['status'] : 'active';
                                    $statusColor = $status === 'active' ? '#10b981' : '#ef4444';
                                    ?>
                                    <span style="background: <?= $statusColor ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <?php if (isset($user['subscription_end'])): ?>
                                        <?= date('d M Y', strtotime($user['subscription_end'])) ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <button 
                                        class="btn btn-danger btn-sm" 
                                        onclick="if(confirm('Apakah Anda yakin ingin menghapus user <?= htmlspecialchars($user['id']) ?>? Tindakan ini tidak dapat dibatalkan.')){window.location='./admin.php?id=delete-user&user_id=<?= urlencode($user['id']) ?>'}"
                                        style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                                        <i class="fa fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
