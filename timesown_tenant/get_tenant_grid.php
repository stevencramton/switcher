<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_tenant')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) === 0) {
    echo '<div class="alert alert-danger">User not found</div>';
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];

$tenants_query = "
    SELECT t.*,
           COUNT(DISTINCT ut.user_id) as user_count,
           COUNT(DISTINCT d.id) as department_count,
           COUNT(DISTINCT s.id) as shift_count
    FROM to_tenants t
    LEFT JOIN to_user_tenants ut ON t.id = ut.tenant_id AND ut.active = 1
    LEFT JOIN to_departments d ON t.id = d.tenant_id AND d.active = 1
    LEFT JOIN to_shifts s ON t.id = s.tenant_id AND s.shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY t.id
    ORDER BY t.display_order ASC, t.name ASC
";

$stmt = mysqli_prepare($dbc, $tenants_query);
mysqli_stmt_execute($stmt);
$tenants_result = mysqli_stmt_get_result($stmt);
$tenants = mysqli_fetch_all($tenants_result, MYSQLI_ASSOC);

foreach ($tenants as $tenant): ?>
<div class="card tenant-card border-0 shadow-sm" data-tenant-id="<?= $tenant['id'] ?>" data-status="<?= $tenant['active'] ? 'active' : 'inactive' ?>">
    <div class="card-header bg-white">
        <span class="badge tenant-status-badge <?= $tenant['active'] ? 'bg-mint' : 'bg-hot' ?>">
            <?= $tenant['active'] ? 'Active' : 'Inactive' ?>
        </span>
      	<div class="d-flex align-items-center">
            <?php if ($tenant['logo']): ?>
                <img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="Logo" class="tenant-logo me-3">
            <?php else: ?>
                <div class="tenant-logo me-3 d-flex align-items-center justify-content-center bg-light">
                    <i class="fas fa-building text-muted"></i>
                </div>
            <?php endif; ?>
            <div>
                <h5 class="card-title mb-1"><?= htmlspecialchars($tenant['name']) ?></h5>
                <small class="text-muted"><?= htmlspecialchars($tenant['slug']) ?></small>
            </div>
        </div>
    </div>
    <div class="card-body position-relative">
        <div class="row text-center mb-3">
            <div class="col-4">
                <div class="h5 text-primary mb-0"><?= $tenant['user_count'] ?></div>
                <small class="text-muted">Users</small>
            </div>
            <div class="col-4">
                <div class="h5 text-primary mb-0"><?= $tenant['department_count'] ?></div>
                <small class="text-muted">Departments</small>
            </div>
            <div class="col-4">
                <div class="h5 text-primary mb-0"><?= $tenant['shift_count'] ?></div>
                <small class="text-muted">Recent Shifts</small>
            </div>
        </div>
     	<p class="card-text text-muted small mb-2">
            Created <?= date('M j, Y', strtotime($tenant['created_at'])) ?>
            <?php if ($tenant['updated_at'] !== $tenant['created_at']): ?>
                • Updated <?= date('M j, Y', strtotime($tenant['updated_at'])) ?>
            <?php endif; ?>
        </p>
    </div>
  	<div class="card-footer">
        <div class="btn-group w-100" role="group" aria-label="Large button group">
          <button type="button" class="btn btn-sm btn-orange" onclick="manageTenant(<?= $tenant['id'] ?>)">
              <i class="fas fa-cog"></i> Manage
          </button>
          <button type="button" class="btn btn-sm btn-outline-orange" onclick="viewTenantSchedule(<?= $tenant['id'] ?>)">
              <i class="fas fa-calendar"></i> Schedule
          </button>
          <button type="button" class="btn btn-sm btn-outline-orange" onclick="editTenant(<?= $tenant['id'] ?>)">
              <i class="fas fa-edit"></i> Edit
          </button>
        </div>
    </div>
</div>
<?php endforeach;

if (empty($tenants)): ?>
<div class="col-12 text-center p-5">
    <i class="fas fa-building fa-3x text-muted mb-3"></i>
    <h5>No Organizations Found</h5>
    <p class="text-muted">Click "Add Organization" to create your first organization.</p>
</div>
<?php endif; ?>