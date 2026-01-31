<?php
/*
 * MIKPAY User Management
 * Halaman untuk superadmin/admin manage user
 */

// Check if user is admin
$isAdmin = false;
$isSuperAdmin = false;
if (isset($_SESSION["user_id"])) {
    try {
        if (file_exists('./include/database.php')) {
            include_once('./include/database.php');
            if (function_exists('isAdmin') && function_exists('isSuperAdmin')) {
                $isAdmin = isAdmin($_SESSION["user_id"]);
                $isSuperAdmin = isSuperAdmin($_SESSION["user_id"]);
            }
        }
    } catch (Exception $e) {
        // Continue
    }
}

if (!$isAdmin) {
    echo "<script>window.location='./admin.php?id=sessions'</script>";
    exit;
}

// Handle actions
$message = '';
$messageType = '';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $email = trim($_POST['email']);
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $role = isset($_POST['role']) ? $_POST['role'] : 'user';
        
        if (empty($username) || empty($password)) {
            $message = 'Username dan password harus diisi';
            $messageType = 'error';
        } else {
            $result = createUser($username, $password, $email, $fullName, $phone, $role);
            if ($result['success']) {
                $message = 'User berhasil dibuat';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    } elseif ($action == 'update') {
        $userId = intval($_POST['user_id']);
        $updateData = array();
        
        if (!empty($_POST['email'])) $updateData['email'] = $_POST['email'];
        if (!empty($_POST['full_name'])) $updateData['full_name'] = $_POST['full_name'];
        if (!empty($_POST['phone'])) $updateData['phone'] = $_POST['phone'];
        if (!empty($_POST['role']) && $isSuperAdmin) $updateData['role'] = $_POST['role'];
        if (!empty($_POST['status'])) $updateData['status'] = $_POST['status'];
        if (!empty($_POST['password'])) $updateData['password'] = $_POST['password'];
        if (!empty($_POST['subscription_package'])) $updateData['subscription_package'] = $_POST['subscription_package'];
        if (!empty($_POST['subscription_start'])) $updateData['subscription_start'] = $_POST['subscription_start'];
        if (!empty($_POST['subscription_end'])) $updateData['subscription_end'] = $_POST['subscription_end'];
        
        if (updateUser($userId, $updateData)) {
            $message = 'User berhasil diupdate';
            $messageType = 'success';
        } else {
            $message = 'Gagal update user';
            $messageType = 'error';
        }
    } elseif ($action == 'delete') {
        $userId = intval($_POST['user_id']);
        if (deleteUser($userId)) {
            $message = 'User berhasil dihapus';
            $messageType = 'success';
        } else {
            $message = 'Gagal hapus user';
            $messageType = 'error';
        }
    }
}

// Get all users
$users = getAllUsers();
?>

<style>
.user-management-container {
    padding: 20px;
}

.user-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.user-header h2 {
    color: #fff;
    margin: 0;
}

.btn-add-user {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-add-user:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(77, 68, 181, 0.4);
}

.message {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.message.success {
    background: rgba(34, 197, 94, 0.2);
    border: 1px solid rgba(34, 197, 94, 0.4);
    color: #4ade80;
}

.message.error {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.4);
    color: #f87171;
}

.users-table {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.users-table table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th {
    background: rgba(255, 255, 255, 0.1);
    padding: 15px;
    text-align: left;
    color: #fff;
    font-weight: 600;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.users-table td {
    padding: 15px;
    color: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.users-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge.active {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
}

.badge.inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.badge.superadmin {
    background: rgba(249, 115, 22, 0.2);
    color: #fb923c;
}

.badge.admin {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.badge.user {
    background: rgba(156, 163, 175, 0.2);
    color: #d1d5db;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    margin-right: 5px;
    transition: all 0.3s;
}

.btn-edit {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.btn-edit:hover {
    background: rgba(59, 130, 246, 0.3);
}

.btn-delete {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.btn-delete:hover {
    background: rgba(239, 68, 68, 0.3);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: rgba(30, 30, 30, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 30px;
    width: 90%;
    max-width: 500px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h3 {
    color: #fff;
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #fff;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #4D44B5;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-submit {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.btn-cancel {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}
</style>

<div class="user-management-container">
    <div class="user-header">
        <h2><i class="fa fa-users"></i> User Management</h2>
        <button class="btn-add-user" onclick="showCreateModal()">
            <i class="fa fa-plus"></i> Tambah User
        </button>
    </div>

    <?php if ($message): ?>
    <div class="message <?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="users-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Subscription</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px;">
                        <p style="color: rgba(255, 255, 255, 0.5);">Belum ada user</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($user['full_name'] ?: '-') ?></td>
                    <td>
                        <span class="badge <?= $user['role'] ?>">
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $user['status'] ?>">
                            <?= htmlspecialchars($user['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['subscription_end']): ?>
                            <?= date('d/m/Y', strtotime($user['subscription_end'])) ?>
                        <?php elseif ($user['trial_ends']): ?>
                            Trial: <?= date('d/m/Y', strtotime($user['trial_ends'])) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                    <td>
                        <button class="btn-action btn-edit" onclick="showEditModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <?php if ($user['role'] != 'superadmin' || $isSuperAdmin): ?>
                        <button class="btn-action btn-delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Tambah User Baru</h3>
            <button class="close-modal" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="full_name">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone">
            </div>
            <?php if ($isSuperAdmin): ?>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Super Admin</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('createModal')">Batal</button>
                <button type="submit" class="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User</h3>
            <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="edit_username" readonly style="background: rgba(255, 255, 255, 0.1);">
            </div>
            <div class="form-group">
                <label>Password (kosongkan jika tidak diubah)</label>
                <input type="password" name="password">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email">
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="full_name" id="edit_full_name">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone">
            </div>
            <?php if ($isSuperAdmin): ?>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="edit_role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Super Admin</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subscription Package</label>
                <input type="text" name="subscription_package" id="edit_subscription_package">
            </div>
            <div class="form-group">
                <label>Subscription Start</label>
                <input type="date" name="subscription_start" id="edit_subscription_start">
            </div>
            <div class="form-group">
                <label>Subscription End</label>
                <input type="date" name="subscription_end" id="edit_subscription_end">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" class="btn-submit">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById('createModal').classList.add('active');
}

function showEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_phone').value = user.phone || '';
    if (document.getElementById('edit_role')) {
        document.getElementById('edit_role').value = user.role;
    }
    document.getElementById('edit_status').value = user.status;
    document.getElementById('edit_subscription_package').value = user.subscription_package || '';
    document.getElementById('edit_subscription_start').value = user.subscription_start || '';
    document.getElementById('edit_subscription_end').value = user.subscription_end || '';
    document.getElementById('editModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function deleteUser(userId, username) {
    if (confirm('Apakah Anda yakin ingin menghapus user "' + username + '"?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                         '<input type="hidden" name="user_id" value="' + userId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>
