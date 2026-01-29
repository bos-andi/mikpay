<?php
/*
 *  Copyright (C) 2018 Muhammad Andi.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// hide all error
error_reporting(0);
if (!isset($_SESSION["mikpay"])) {
  header("Location:../admin.php?id=login");
} else {

// Get total users count
$totalUsers = $API->comm("/ip/hotspot/user/print", array("count-only" => ""));

// Get user profiles
$getprofile = $API->comm("/ip/hotspot/user/profile/print");
$TotalProfiles = count($getprofile);

// Gradient colors for cards
$gradients = array(
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
    'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
    'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
);
?>

<style>
/* Voucher Page Styles */
.voucher-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
    padding: 20px 25px;
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    border-radius: 16px;
    color: #FFF;
}
.voucher-page-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}
.voucher-page-header h2 i {
    font-size: 28px;
}
.voucher-header-stats {
    display: flex;
    gap: 30px;
}
.voucher-stat {
    text-align: center;
}
.voucher-stat-value {
    font-size: 28px;
    font-weight: 700;
}
.voucher-stat-label {
    font-size: 13px;
    opacity: 0.9;
}
.voucher-refresh-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #FFF;
    padding: 12px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}
.voucher-refresh-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

/* Profile Grid */
.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

/* Profile Card */
.profile-card {
    background: #FFF;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid #f1f5f9;
}
.profile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}
.profile-card-header {
    padding: 20px;
    color: #FFF;
    position: relative;
    overflow: hidden;
}
.profile-card-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: rgba(255,255,255,0.1);
    transform: rotate(45deg);
    pointer-events: none;
}
.profile-icon {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.25);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 15px;
}
.profile-name {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 5px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.profile-count {
    font-size: 14px;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 6px;
}
.profile-count i {
    font-size: 12px;
}

/* Profile Card Body */
.profile-card-body {
    padding: 15px 20px 20px;
    display: flex;
    gap: 10px;
}
.profile-btn {
    flex: 1;
    padding: 12px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}
.profile-btn-open {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFF;
}
.profile-btn-open:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(77, 68, 181, 0.4);
    color: #FFF;
    text-decoration: none;
}
.profile-btn-generate {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #FFF;
}
.profile-btn-generate:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    color: #FFF;
    text-decoration: none;
}

/* All Profile Card (Special) */
.profile-card.all-profiles .profile-card-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
}
.profile-card.all-profiles .profile-icon {
    background: rgba(255,255,255,0.15);
}

/* Responsive */
@media screen and (max-width: 768px) {
    .voucher-page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    .voucher-header-stats {
        justify-content: center;
    }
    .profile-grid {
        grid-template-columns: 1fr;
    }
    .profile-card-body {
        flex-direction: column;
    }
}
</style>

<!-- Page Header -->
<div class="voucher-page-header">
    <h2><i class="fa fa-ticket"></i> <?= $_vouchers ?></h2>
    <div class="voucher-header-stats">
        <div class="voucher-stat">
            <div class="voucher-stat-value"><?= $totalUsers ?></div>
            <div class="voucher-stat-label">Total Vouchers</div>
        </div>
        <div class="voucher-stat">
            <div class="voucher-stat-value"><?= $TotalProfiles ?></div>
            <div class="voucher-stat-label">Profiles</div>
        </div>
    </div>
    <button class="voucher-refresh-btn" onclick="location.reload();">
        <i class="fa fa-refresh"></i> Refresh
    </button>
</div>

<!-- Profile Grid -->
<div class="profile-grid">
    <!-- All Profiles Card -->
    <div class="profile-card all-profiles">
        <div class="profile-card-header">
            <div class="profile-icon">
                <i class="fa fa-th-large"></i>
            </div>
            <h3 class="profile-name">
                <i class="fa fa-folder-open"></i> All Profiles
            </h3>
            <div class="profile-count">
                <i class="fa fa-users"></i>
                <?= $totalUsers ?> <?= $totalUsers < 2 ? 'Voucher' : 'Vouchers' ?>
            </div>
        </div>
        <div class="profile-card-body">
            <a href="./?hotspot=users&profile=all&session=<?= $session; ?>" class="profile-btn profile-btn-open" title="View all vouchers">
                <i class="fa fa-external-link"></i> <?= $_open ?>
            </a>
            <a href="./?hotspot-user=generate&session=<?= $session; ?>" class="profile-btn profile-btn-generate" title="Generate new vouchers">
                <i class="fa fa-plus-circle"></i> <?= $_generate ?>
            </a>
        </div>
    </div>

    <!-- Individual Profile Cards -->
    <?php
    $colorIndex = 0;
    for ($i = 0; $i < $TotalProfiles; $i++) {
        $profiledetalis = $getprofile[$i];
        $pname = $profiledetalis['name'];
        $countuser = $API->comm("/ip/hotspot/user/print", array("count-only" => "", "?profile" => "$pname"));
        $gradient = $gradients[$colorIndex % count($gradients)];
        $colorIndex++;
    ?>
    <div class="profile-card">
        <div class="profile-card-header" style="background: <?= $gradient ?>;">
            <div class="profile-icon">
                <i class="fa fa-ticket"></i>
            </div>
            <h3 class="profile-name"><?= htmlspecialchars($pname) ?></h3>
            <div class="profile-count">
                <i class="fa fa-users"></i>
                <?= $countuser ?> <?= $countuser < 2 ? 'Voucher' : 'Vouchers' ?>
            </div>
        </div>
        <div class="profile-card-body">
            <a href="./?hotspot=users&profile=<?= urlencode($pname); ?>&session=<?= $session; ?>" class="profile-btn profile-btn-open" title="View vouchers for <?= htmlspecialchars($pname); ?>">
                <i class="fa fa-external-link"></i> <?= $_open ?>
            </a>
            <a href="./?hotspot-user=generate&genprof=<?= urlencode($pname); ?>&session=<?= $session; ?>" class="profile-btn profile-btn-generate" title="Generate vouchers for <?= htmlspecialchars($pname); ?>">
                <i class="fa fa-plus-circle"></i> <?= $_generate ?>
            </a>
        </div>
    </div>
    <?php 
    }
} ?>
</div>
