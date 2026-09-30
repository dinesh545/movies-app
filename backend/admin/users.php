<?php
// backend/admin/users.php - User Approval & Management with Custom Action Confirmation Modals

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/header.php';

$msg = '';
$error = '';

// Handle Approve / Revoke / Delete Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE users SET is_admin_approved = 1 WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $msg = "User ID #{$userId} has been approved successfully!";
            } elseif ($action === 'revoke') {
                $stmt = $pdo->prepare("UPDATE users SET is_admin_approved = 0, session_token = NULL WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $msg = "User ID #{$userId} access has been revoked!";
            } elseif ($action === 'verify_email') {
                $stmt = $pdo->prepare("UPDATE users SET is_email_verified = 1, email_verification_otp = NULL WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $msg = "Email address for User ID #{$userId} marked as verified by Admin!";
            } elseif ($action === 'unverify_email') {
                $stmt = $pdo->prepare("UPDATE users SET is_email_verified = 0 WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $msg = "Email verification status for User ID #{$userId} set to unverified!";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $msg = "User ID #{$userId} deleted permanently!";
            }
        } catch (Exception $e) {
            $error = "Action failed: " . $e->getMessage();
        }
    }
}

// Fetch all users sorted by registration time
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>

<style>
    /* CUSTOM CONFIRMATION MODAL */
    .confirm-modal-backdrop {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px;
    }
    .confirm-modal-card {
        background-color: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: var(--radius-lg); padding: 28px; width: 100%; max-width: 440px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6); text-align: center;
        animation: modalPop 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .confirm-icon-box {
        width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 16px auto;
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
    }
    .confirm-icon-approve { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
    .confirm-icon-revoke { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .confirm-icon-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

    .confirm-title { font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
    .confirm-desc { font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 24px; }
    .confirm-desc strong { color: #fff; }

    .confirm-actions { display: flex; gap: 12px; justify-content: center; }
    .confirm-actions .btn { flex: 1; min-height: 42px; font-weight: 700; }

    @keyframes modalPop {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header-flex">
        <h2><i class="fa-solid fa-users-gear" style="color: var(--primary);"></i> User Verification & Admin Approval</h2>
        <span style="color: var(--text-muted); font-size: 0.88rem;">Total Registered: <?= count($users) ?> Users</span>
    </div>

    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 20px;">
        Manage user registrations. Users must complete Email Verification AND be Approved by an Admin before logging in.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>10-Digit Mobile</th>
                    <th>Email Address</th>
                    <th>Email Status</th>
                    <th>Admin Approval</th>
                    <th>Registered Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            <i class="fa-solid fa-user-slash" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                            No users registered yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong>#<?= (int)$u['id'] ?></strong></td>
                            <td><strong style="color: #fff;"><?= htmlspecialchars($u['name']) ?></strong></td>
                            <td><i class="fa-solid fa-mobile-screen-button" style="color: var(--primary); margin-right: 4px;"></i> <?= htmlspecialchars($u['mobile']) ?></td>
                            <td><i class="fa-solid fa-envelope" style="color: var(--text-muted); margin-right: 4px;"></i> <?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['is_email_verified'] == 1): ?>
                                    <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);">
                                        <i class="fa-solid fa-circle-check"></i> Verified
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);">
                                        <i class="fa-solid fa-clock"></i> Unverified OTP
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_admin_approved'] == 1): ?>
                                    <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">
                                        <i class="fa-solid fa-user-check"></i> Approved
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
                                        <i class="fa-solid fa-user-clock"></i> Pending Approval
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.82rem;"><?= htmlspecialchars($u['created_at']) ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    <?php if ($u['is_email_verified'] != 1): ?>
                                        <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #0284c7, #0369a1);"
                                                onclick="openConfirmModal('verify_email', <?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-envelope-circle-check"></i> Verify Email
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($u['is_admin_approved'] != 1): ?>
                                        <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #16a34a, #15803d);"
                                                onclick="openConfirmModal('approve', <?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-secondary" style="color: #fbbf24; border-color: rgba(245, 158, 11, 0.3);"
                                                onclick="openConfirmModal('revoke', <?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-ban"></i> Revoke
                                        </button>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-sm btn-danger"
                                            onclick="openConfirmModal('delete', <?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- HIDDEN FORM FOR MODAL ACTIONS -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="modalUserId" value="0">
    <input type="hidden" name="action" id="modalAction" value="">
</form>

<!-- ACTION CONFIRMATION MODAL -->
<div class="confirm-modal-backdrop" id="confirmModal">
    <div class="confirm-modal-card">
        <div class="confirm-icon-box" id="modalIconBox">
            <i class="fa-solid fa-user-check" id="modalIcon"></i>
        </div>
        <div class="confirm-title" id="modalTitle">Approve User Access</div>
        <div class="confirm-desc" id="modalDesc">
            Are you sure you want to approve <strong id="modalUserName">User</strong> to allow login to the streaming platform?
        </div>
        <div class="confirm-actions">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn" id="modalConfirmBtn" onclick="submitActionForm()">Confirm & Save</button>
        </div>
    </div>
</div>

<script>
    function openConfirmModal(action, userId, userName) {
        document.getElementById('modalUserId').value = userId;
        document.getElementById('modalAction').value = action;
        document.getElementById('modalUserName').innerText = userName;

        const iconBox = document.getElementById('modalIconBox');
        const icon = document.getElementById('modalIcon');
        const title = document.getElementById('modalTitle');
        const desc = document.getElementById('modalDesc');
        const confirmBtn = document.getElementById('modalConfirmBtn');

        iconBox.className = 'confirm-icon-box ';

        if (action === 'verify_email') {
            iconBox.className += 'confirm-icon-approve';
            icon.className = 'fa-solid fa-envelope-circle-check';
            title.innerText = 'Manually Verify Email';
            desc.innerHTML = `Manually mark email address as verified for user <strong>${userName}</strong> without requiring OTP?`;
            confirmBtn.className = 'btn';
            confirmBtn.style.background = 'linear-gradient(135deg, #0284c7, #0369a1)';
            confirmBtn.innerHTML = '<i class="fa-solid fa-envelope-circle-check"></i> Yes, Mark Email Verified';
        } else if (action === 'approve') {
            iconBox.className += 'confirm-icon-approve';
            icon.className = 'fa-solid fa-user-check';
            title.innerText = 'Approve User Login';
            desc.innerHTML = `Approve user <strong>${userName}</strong> to allow login to watch movies & series?`;
            confirmBtn.className = 'btn';
            confirmBtn.style.background = 'linear-gradient(135deg, #16a34a, #15803d)';
            confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Yes, Approve User';
        } else if (action === 'revoke') {
            iconBox.className += 'confirm-icon-revoke';
            icon.className = 'fa-solid fa-ban';
            title.innerText = 'Revoke User Approval';
            desc.innerHTML = `Revoke approval for <strong>${userName}</strong>? This will immediately log them out of all devices.`;
            confirmBtn.className = 'btn';
            confirmBtn.style.background = 'linear-gradient(135deg, #d97706, #b45309)';
            confirmBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Yes, Revoke Access';
        } else if (action === 'delete') {
            iconBox.className += 'confirm-icon-delete';
            icon.className = 'fa-solid fa-user-slash';
            title.innerText = 'Delete User Account';
            desc.innerHTML = `Permanently delete user <strong>${userName}</strong>? This action cannot be undone.`;
            confirmBtn.className = 'btn btn-danger';
            confirmBtn.style.background = '';
            confirmBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Yes, Delete Permanently';
        }

        document.getElementById('confirmModal').style.display = 'flex';
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').style.display = 'none';
    }

    function submitActionForm() {
        document.getElementById('actionForm').submit();
    }

    // Close modal if user clicks outside box
    document.getElementById('confirmModal').addEventListener('click', (e) => {
        if (e.target.id === 'confirmModal') {
            closeConfirmModal();
        }
    });
</script>

</div>
</main>
</div>
</body>
</html>
