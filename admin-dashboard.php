<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('Location: login.php');
  exit;
}
// -----------------------------------------------------------------------------
// Data loading and dynamic dashboard preparation
// Load the serialized database data from JSON. If the file is missing or
// invalid we fallback to empty arrays so the dashboard still loads gracefully.
$dataFile = __DIR__ . '/shurokha_data.json';
$data = [];
if (file_exists($dataFile)) {
  $json = file_get_contents($dataFile);
  $data = json_decode($json, true);
  if (!is_array($data)) {
    $data = [];
  }
}

$users           = $data['users'] ?? [];
$motherProfiles   = $data['mother_profiles'] ?? [];
$doctorProfiles   = $data['doctor_profiles'] ?? [];
$appointments     = $data['appointments'] ?? [];
$healthRecords    = $data['health_records'] ?? [];
$customReminders  = $data['custom_reminders'] ?? [];

// Build quick lookup maps
$usersById = [];
foreach ($users as $u) {
  $usersById[$u['id']] = $u;
}
$doctorProfilesByUser = [];
foreach ($doctorProfiles as $dp) {
  $doctorProfilesByUser[$dp['user_id']] = $dp;
}

// Handle POST requests for status updates (approve users/doctors)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $updated = false;
  if ($_POST['action'] === 'updateUserStatus' && !empty($_POST['user_id']) && isset($_POST['new_status'])) {
    $uid       = (string)$_POST['user_id'];
    $newStatus = $_POST['new_status'];
    foreach ($users as &$u) {
      if ($u['id'] == $uid) {
        $u['status'] = $newStatus;
        $updated = true;
        break;
      }
    }
    unset($u);
  }
  if ($_POST['action'] === 'updateDoctorStatus' && !empty($_POST['user_id']) && isset($_POST['new_status'])) {
    $uid       = (string)$_POST['user_id'];
    $newStatus = $_POST['new_status'];
    foreach ($users as &$u) {
      if ($u['id'] == $uid) {
        $u['status'] = $newStatus;
        $updated = true;
        break;
      }
    }
    unset($u);
  }
  if ($updated) {
    // Write back updated users into JSON
    $data['users'] = $users;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }
}

// Compute helper maps for last visits (latest health record per mother)
$lastVisitMap = [];
foreach ($healthRecords as $hr) {
  if (!isset($hr['mother_user_id']) || !isset($hr['record_date'])) continue;
  $mid = $hr['mother_user_id'];
  if (!isset($lastVisitMap[$mid]) || $hr['record_date'] > $lastVisitMap[$mid]) {
    $lastVisitMap[$mid] = $hr['record_date'];
  }
}

// Prepare lists and counts
$mothersInfo = [];
$motherAppointments = [];
$totalUsers = count($users);
$currentPregnant = 0;
$postpartumCount = 0;
$approvedDoctors = 0;
$clinicsListed = count($doctorProfiles);
$activeTips = 0;

// Precompute active tips
foreach ($customReminders as $cr) {
  if (isset($cr['status']) && strtolower($cr['status']) === 'pending') {
    $activeTips++;
  }
}

$currentDate = new DateTime(date('Y-m-d'));

// Iterate over mother profiles to compute details, trimester and appointments
foreach ($motherProfiles as $mp) {
  $uid = $mp['user_id'];
  if (!isset($usersById[$uid])) continue;
  $user = $usersById[$uid];
  $dueDateStr = $mp['due_date'] ?? '';
  $trimester = 'N/A';
  if ($dueDateStr) {
    try {
      $dueDate = new DateTime($dueDateStr);
      if ($dueDate < $currentDate) {
        $trimester = 'Postpartum';
        $postpartumCount++;
      } else {
        $interval = $dueDate->diff($currentDate);
        $daysRemaining = $interval->days;
        $totalPreg = 280;
        $daysPassed = $totalPreg - $daysRemaining;
        if ($daysPassed <= 84) {
          $trimester = '1st';
        } elseif ($daysPassed <= 189) {
          $trimester = '2nd';
        } else {
          $trimester = '3rd';
        }
        $currentPregnant++;
      }
    } catch (Exception $e) {
      $trimester = 'N/A';
    }
  }
  // Build detail info
  $detailInfo = [
    'date_of_birth'      => $mp['date_of_birth'] ?? '',
    'address'            => $mp['address'] ?? '',
    'phone'              => $mp['phone'] ?: ($user['phone'] ?? ''),
    'due_date'           => $mp['due_date'] ?? '',
    'blood_type'         => $mp['blood_type'] ?? '',
    'emergency_contact'  => $mp['emergency_contact'] ?? ''
  ];
  // Last visit
  $lastVisit = $lastVisitMap[$uid] ?? 'N/A';
  $statusLower = strtolower($user['status']);
  // Collect appointments
  foreach ($appointments as $appt) {
    if ($appt['mother_user_id'] != $uid) continue;
    $docId = $appt['doctor_user_id'];
    $doctorName = isset($usersById[$docId]) ? $usersById[$docId]['full_name'] : 'Unknown';
    $docSpec   = isset($doctorProfilesByUser[$docId]) ? $doctorProfilesByUser[$docId]['specialization'] : '';
    $motherAppointments[$uid][] = [
      'scheduled_at'  => $appt['scheduled_at'],
      'status'        => $appt['status'],
      'notes'         => $appt['notes'] ?? '',
      'doctor_name'   => $doctorName,
      'specialization' => $docSpec
    ];
  }
  // Build mothersInfo entry
  $mothersInfo[] = [
    'user_id'   => $uid,
    'full_name' => $user['full_name'],
    'status'    => $user['status'],
    'status_lower' => $statusLower,
    'trimester' => $trimester,
    'last_visit' => $lastVisit,
    'details'   => $detailInfo
  ];
}

// Compute doctors info and approved count
$doctorsInfo = [];
foreach ($doctorProfiles as $dp) {
  $uid = $dp['user_id'];
  if (!isset($usersById[$uid])) continue;
  $user = $usersById[$uid];
  $doctorsInfo[] = [
    'user_id'      => $uid,
    'full_name'    => $user['full_name'],
    'specialization' => $dp['specialization'],
    'status'       => $user['status'],
    'phone'        => $dp['phone'] ?: ($user['phone'] ?? ''),
    'status_lower' => strtolower($user['status'])
  ];
  if (strtolower($user['status']) === 'active') {
    $approvedDoctors++;
  }
}

// Statistics for analytics cards
$statTotalUsers         = $totalUsers;
$statActiveDoctors      = $approvedDoctors;
$statMonthlyAppointments = count($appointments);
$statActiveClinics      = $clinicsListed;

// Chart data: user growth per month
$userGrowthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$userGrowthCounts = array_fill(0, 12, 0);
foreach ($users as $u) {
  if (!empty($u['created_at'])) {
    try {
      $dt = new DateTime($u['created_at']);
      $idx = (int)$dt->format('n') - 1;
      if ($idx >= 0 && $idx < 12) {
        $userGrowthCounts[$idx]++;
      }
    } catch (Exception $e) {
    }
  }
}

// Chart data: user distribution by status
$userStatusCounts = [];
foreach ($users as $u) {
  $s = $u['status'] ?? 'Unknown';
  $userStatusCounts[$s] = ($userStatusCounts[$s] ?? 0) + 1;
}
$userDistributionLabels = array_keys($userStatusCounts);
$userDistributionCounts = array_values($userStatusCounts);

// Colour palette for distribution chart
$statusColorPalette = ['#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#34d399', '#f472b6', '#ec4899'];
$userDistributionColors = array_slice($statusColorPalette, 0, count($userDistributionCounts));

// Chart data: appointments per month
$appointmentCounts = [];
foreach ($appointments as $ap) {
  if (!empty($ap['scheduled_at'])) {
    try {
      $dt = new DateTime($ap['scheduled_at']);
      $label = $dt->format('M');
      $appointmentCounts[$label] = ($appointmentCounts[$label] ?? 0) + 1;
    } catch (Exception $e) {
    }
  }
}
ksort($appointmentCounts);
$appointmentsLabels = array_keys($appointmentCounts);
$appointmentsValues = array_values($appointmentCounts);

// Chart data: doctor specializations distribution
$specCounts = [];
foreach ($doctorProfiles as $dp) {
  $spec = $dp['specialization'] ?? 'Unknown';
  $specCounts[$spec] = ($specCounts[$spec] ?? 0) + 1;
}
$specializationsLabels = array_keys($specCounts);
$specializationsValues = array_values($specCounts);
$specColorPalette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#34d399', '#f472b6', '#ec4899'];
$specializationsColors = array_slice($specColorPalette, 0, count($specializationsValues));

// -----------------------------------------------------------------------------
// Compute trending statistics for analytics cards
// For user growth, compare the number of users added this month vs previous month.
$currentMonthIndex = (int)date('n') - 1; // 0-based index for month
$prevMonthIndex    = ($currentMonthIndex - 1 + 12) % 12;
$userGrowthPrev    = $userGrowthCounts[$prevMonthIndex] ?? 0;
$userGrowthCurr    = $userGrowthCounts[$currentMonthIndex] ?? 0;
if ($userGrowthPrev > 0) {
  $userGrowthTrend = (($userGrowthCurr - $userGrowthPrev) / $userGrowthPrev) * 100;
} else {
  $userGrowthTrend = 0;
}

// For appointments, compare the last two months of appointment counts if available
$appointmentsTrend = 0;
if (count($appointmentsValues) >= 2) {
  $lastIndex = count($appointmentsValues) - 1;
  $prevIndex = $lastIndex - 1;
  $appointmentsPrev = $appointmentsValues[$prevIndex];
  $appointmentsCurr = $appointmentsValues[$lastIndex];
  if ($appointmentsPrev > 0) {
    $appointmentsTrend = (($appointmentsCurr - $appointmentsPrev) / $appointmentsPrev) * 100;
  }
}

// For active doctors and clinics we currently do not track historical month-on-month changes,
// so we set their trend values to 0. If historical data becomes available, the same
// approach as above can be applied.
$doctorsTrend = 0;
$clinicsTrend = 0;

// Format trend info with arrow icons and colours for display in analytics cards
$userTrendPercent        = round($userGrowthTrend);
$userTrendIcon           = ($userGrowthTrend >= 0 ? 'bi-arrow-up' : 'bi-arrow-down');
$userTrendColor          = ($userGrowthTrend >= 0 ? 'text-success' : 'text-danger');

$appointmentsTrendPercent = round($appointmentsTrend);
$appointmentsTrendIcon   = ($appointmentsTrend >= 0 ? 'bi-arrow-up' : 'bi-arrow-down');
$appointmentsTrendColor  = ($appointmentsTrend >= 0 ? 'text-success' : 'text-danger');

$doctorsTrendPercent     = round($doctorsTrend);
$doctorsTrendIcon        = ($doctorsTrend >= 0 ? 'bi-arrow-up' : 'bi-arrow-down');
$doctorsTrendColor       = ($doctorsTrend >= 0 ? 'text-success' : 'text-danger');

$clinicsTrendPercent     = round($clinicsTrend);
$clinicsTrendIcon        = ($clinicsTrend >= 0 ? 'bi-arrow-up' : 'bi-arrow-down');
$clinicsTrendColor       = ($clinicsTrend >= 0 ? 'text-success' : 'text-danger');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – Shurokha</title>
  <link rel="icon" type="image/png" href="logo-transparent.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <link rel="stylesheet" href="css/admin-dashboard.css">
  <link rel="stylesheet" href="css/admin.css">
  <style>

  </style>
</head>

<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <nav class="sidebar">
      <div class="sidebar-header">
        <h3>Shurokha</h3>
      </div>
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="#" class="nav-link active" data-section="overview">
            <i class="bi bi-house-door"></i>
            Overview
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="user-management">
            <i class="bi bi-people"></i>
            User Management
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="doctor-management">
            <i class="bi bi-person-badge"></i>
            Doctor Management
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="clinics">
            <i class="bi bi-building"></i>
            Clinics
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="analytics">
            <i class="bi bi-graph-up"></i>
            Analytics
          </a>
        </li>
      </ul>
    </nav>
    <!-- Main Content -->
    <div class="main-content">
      <div class="admin-header">
        <button class="admin-profile-icon-btn" id="adminProfileIconBtn" aria-label="Profile"><i class="bi bi-person-circle"></i></button>
      </div>
      <div class="admin-profile-dropdown" id="adminProfileDropdown">
        <div class="profile-info">
          <div class="name">Admin User</div>
          <div class="role">System Administrator</div>
        </div>
        <a href="#" class="dropdown-item"><i class="bi bi-person"></i>Profile</a>
        <a href="#" class="dropdown-item"><i class="bi bi-gear"></i>Settings</a>
        <div class="dropdown-divider"></div>
        <a href="login.php" class="dropdown-item"><i class="bi bi-box-arrow-right"></i>Logout</a>
      </div>
      <main class="admin-main flex-grow-1">
        <!-- Welcome Message (Only on Overview) -->
        <div id="welcome-message" class="mb-4">
          <div class="admin-section-title">Welcome, Admin</div>
          <div class="mb-4">Here's your platform overview.</div>
        </div>

        <!-- Overview Section -->
        <section id="overview" class="content-section">
          <div class="admin-cards">
            <div class="admin-card">
              <div class="card-title">Total Users</div>
              <div class="card-value"><?php echo $statTotalUsers; ?></div>
            </div>
            <div class="admin-card">
              <div class="card-title">Currently Pregnant</div>
              <div class="card-value"><?php echo $currentPregnant; ?></div>
            </div>
            <div class="admin-card">
              <div class="card-title">Postpartum</div>
              <div class="card-value"><?php echo $postpartumCount; ?></div>
            </div>
            <div class="admin-card">
              <div class="card-title">Approved Doctors</div>
              <div class="card-value"><?php echo $approvedDoctors; ?></div>
            </div>
            <div class="admin-card">
              <div class="card-title">Clinics Listed</div>
              <div class="card-value"><?php echo $clinicsListed; ?></div>
            </div>
            <div class="admin-card">
              <div class="card-title">Active Weekly Tips</div>
              <div class="card-value"><?php echo $activeTips; ?></div>
            </div>
          </div>
        </section>
        <!-- User Management Section -->
        <section id="user-management" class="content-section mb-5">
          <div class="container-fluid">
            <div class="row">
              <div class="col-12">
                <div class="section-header">
                  <h2><i class="bi bi-people"></i> User Management</h2>
                  <p>Manage registered mothers and their information</p>
                </div>

                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Registered Users</h5>
                    <button class="btn btn-primary"><i class="bi bi-plus"></i> Add User</button>
                  </div>
                  <div class="card-body">
                    <!-- Search bar -->
                    <div class="mb-3">
                      <input type="text" id="userSearchInput" class="form-control" placeholder="Search users by name, phone or status...">
                    </div>
                    <div class="table-responsive">
                      <table class="table table-hover" id="userTable">
                        <thead>
                          <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Trimester</th>
                            <th>Last Visit</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($mothersInfo as $info):
                            // Determine badge class based on user status
                            $badgeClass = 'bg-secondary';
                            if ($info['status_lower'] === 'active') {
                              $badgeClass = 'bg-success';
                            } elseif ($info['status_lower'] === 'pending') {
                              $badgeClass = 'bg-warning';
                            } elseif ($info['status_lower'] === 'inactive') {
                              $badgeClass = 'bg-danger';
                            }
                            $dataName  = strtolower($info['full_name']);
                            $dataPhone = $info['details']['phone'];
                            $dataStatus = strtolower($info['status']);
                          ?>
                            <tr data-name="<?= htmlspecialchars($dataName) ?>" data-phone="<?= htmlspecialchars($dataPhone) ?>" data-status="<?= htmlspecialchars($dataStatus) ?>">
                              <td>
                                <strong><?= htmlspecialchars($info['full_name']) ?></strong>
                                <button type="button" class="btn btn-link p-0 text-primary view-details ms-1" data-bs-toggle="modal" data-bs-target="#motherModal<?= $info['user_id']; ?>">
                                  <i class="bi bi-eye"></i>
                                </button>
                              </td>
                              <td><?= htmlspecialchars($info['details']['phone']) ?></td>
                              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($info['status_lower'])) ?></span></td>
                              <td><?= htmlspecialchars($info['trimester']) ?></td>
                              <td><?= htmlspecialchars($info['last_visit']) ?></td>
                              <td>
                                <button class="btn btn-sm btn-outline-primary me-1">Edit</button>
                                <?php if ($info['status_lower'] === 'pending'): ?>
                                  <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="updateUserStatus">
                                    <input type="hidden" name="user_id" value="<?= $info['user_id']; ?>">
                                    <input type="hidden" name="new_status" value="active">
                                    <button type="submit" class="btn btn-sm btn-warning">Approve</button>
                                  </form>
                                <?php else: ?>
                                  <span class="badge bg-success">Approved</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <!-- Modals for Mother Details -->
                    <?php foreach ($mothersInfo as $info):
                      $mid    = $info['user_id'];
                      $detail = $info['details'];
                    ?>
                      <div class="modal fade" id="motherModal<?= $mid ?>" tabindex="-1" aria-labelledby="motherModalLabel<?= $mid ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="motherModalLabel<?= $mid ?>">Mother Details – <?= htmlspecialchars($info['full_name']) ?></h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <h6>Profile Details</h6>
                              <ul class="list-group mb-3">
                                <li class="list-group-item"><strong>Date of Birth:</strong> <?= htmlspecialchars($detail['date_of_birth']) ?></li>
                                <li class="list-group-item"><strong>Address:</strong> <?= htmlspecialchars($detail['address']) ?></li>
                                <li class="list-group-item"><strong>Phone:</strong> <?= htmlspecialchars($detail['phone']) ?></li>
                                <li class="list-group-item"><strong>Due Date:</strong> <?= htmlspecialchars($detail['due_date']) ?></li>
                                <li class="list-group-item"><strong>Blood Type:</strong> <?= htmlspecialchars($detail['blood_type']) ?></li>
                                <li class="list-group-item"><strong>Emergency Contact:</strong> <?= htmlspecialchars($detail['emergency_contact']) ?></li>
                              </ul>
                              <h6>Appointment History</h6>
                              <?php if (!empty($motherAppointments[$mid])): ?>
                                <div class="table-responsive">
                                  <table class="table table-bordered">
                                    <thead>
                                      <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Notes</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($motherAppointments[$mid] as $appt): ?>
                                        <tr>
                                          <td><?= htmlspecialchars($appt['scheduled_at']) ?></td>
                                          <td><?= htmlspecialchars($appt['status']) ?></td>
                                          <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                                          <td><?= htmlspecialchars($appt['specialization']) ?></td>
                                          <td><?= htmlspecialchars($appt['notes']) ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    </tbody>
                                  </table>
                                </div>
                              <?php else: ?>
                                <p>No appointments found.</p>
                              <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Doctor Management Section -->
        <section id="doctor-management" class="content-section mb-5">
          <div class="container-fluid">
            <div class="row">
              <div class="col-12">
                <div class="section-header">
                  <h2><i class="bi bi-person-badge"></i> Doctor Management</h2>
                  <p>Manage registered doctors and their specializations</p>
                </div>

                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Registered Doctors</h5>
                    <button class="btn btn-primary"><i class="bi bi-plus"></i> Add Doctor</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Phone</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($doctorsInfo as $doc):
                            $badgeClass = 'bg-secondary';
                            if ($doc['status_lower'] === 'active') {
                              $badgeClass = 'bg-success';
                            } elseif ($doc['status_lower'] === 'pending') {
                              $badgeClass = 'bg-warning';
                            } elseif ($doc['status_lower'] === 'inactive') {
                              $badgeClass = 'bg-danger';
                            }
                          ?>
                            <tr>
                              <td><strong><?= htmlspecialchars($doc['full_name']) ?></strong></td>
                              <td><?= htmlspecialchars($doc['specialization']) ?></td>
                              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($doc['status_lower'])) ?></span></td>
                              <td><?= htmlspecialchars($doc['phone']) ?></td>
                              <td>
                                <?php if ($doc['status_lower'] !== 'active'): ?>
                                  <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="updateDoctorStatus">
                                    <input type="hidden" name="user_id" value="<?= $doc['user_id']; ?>">
                                    <input type="hidden" name="new_status" value="active">
                                    <button type="submit" class="btn btn-sm btn-success me-1">Approve</button>
                                  </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary">Update</button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Clinics Section -->
        <section id="clinics" class="content-section mb-5">
          <div class="container-fluid">
            <div class="row">
              <div class="col-12">
                <div class="section-header">
                  <h2><i class="bi bi-buildings"></i> Clinics Management</h2>
                  <p>Manage registered clinics and their information</p>
                </div>

                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Registered Clinics</h5>
                    <button class="btn btn-primary"><i class="bi bi-plus"></i> Add Clinic</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Clinic Name</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td><strong>Dhaka Medical Center</strong></td>
                            <td>Dhanmondi, Dhaka</td>
                            <td>01712345678</td>
                            <td><span class="badge bg-success">Active</span></td>
                            <td>
                              <span class="badge bg-success">Activated</span>
                            </td>
                          </tr>
                          <tr>
                            <td><strong>Chittagong Women's Clinic</strong></td>
                            <td>Agrabad, Chittagong</td>
                            <td>01887654321</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                            <td>
                              <button class="btn btn-sm btn-warning">Activate</button>
                            </td>
                          </tr>
                          <tr>
                            <td><strong>Sylhet Maternity Hospital</strong></td>
                            <td>Zindabazar, Sylhet</td>
                            <td>01611223344</td>
                            <td><span class="badge bg-success">Active</span></td>
                            <td>
                              <span class="badge bg-success">Activated</span>
                            </td>
                          </tr>
                          <tr>
                            <td><strong>Rajshahi Medical Complex</strong></td>
                            <td>Shaheb Bazar, Rajshahi</td>
                            <td>01999888777</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                            <td>
                              <button class="btn btn-sm btn-warning">Activate</button>
                            </td>
                          </tr>
                          <tr>
                            <td><strong>Khulna Women's Care</strong></td>
                            <td>Khalishpur, Khulna</td>
                            <td>01555666777</td>
                            <td><span class="badge bg-success">Active</span></td>
                            <td>
                              <span class="badge bg-success">Activated</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Analytics Section -->
        <section id="analytics" class="content-section mb-5">
          <div class="container-fluid">
            <div class="row">
              <div class="col-12">
                <div class="section-header">
                  <h2><i class="bi bi-bar-chart-line"></i> Analytics Dashboard</h2>
                  <p>Comprehensive insights and performance metrics</p>
                </div>
              </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
              <!-- Total Users Card -->
              <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="text-muted">Total Users</h6>
                        <h3 class="mb-0"><?php echo $statTotalUsers; ?></h3>
                        <small class="<?php echo $userTrendColor; ?>">
                          <i class="bi <?php echo $userTrendIcon; ?>"></i>
                          <?php echo abs($userTrendPercent); ?>% change
                        </small>
                      </div>
                      <div class="stat-icon">
                        <i class="bi bi-people"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Active Doctors Card -->
              <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="text-muted">Active Doctors</h6>
                        <h3 class="mb-0"><?php echo $statActiveDoctors; ?></h3>
                        <small class="<?php echo $doctorsTrendColor; ?>">
                          <i class="bi <?php echo $doctorsTrendIcon; ?>"></i>
                          <?php echo abs($doctorsTrendPercent); ?>% change
                        </small>
                      </div>
                      <div class="stat-icon">
                        <i class="bi bi-person-badge"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Monthly Appointments Card -->
              <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="text-muted">Monthly Appointments</h6>
                        <h3 class="mb-0"><?php echo $statMonthlyAppointments; ?></h3>
                        <small class="<?php echo $appointmentsTrendColor; ?>">
                          <i class="bi <?php echo $appointmentsTrendIcon; ?>"></i>
                          <?php echo abs($appointmentsTrendPercent); ?>% change
                        </small>
                      </div>
                      <div class="stat-icon">
                        <i class="bi bi-calendar-check"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Active Clinics Card -->
              <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="text-muted">Active Clinics</h6>
                        <h3 class="mb-0"><?php echo $statActiveClinics; ?></h3>
                        <small class="<?php echo $clinicsTrendColor; ?>">
                          <i class="bi <?php echo $clinicsTrendIcon; ?>"></i>
                          <?php echo abs($clinicsTrendPercent); ?>% change
                        </small>
                      </div>
                      <div class="stat-icon">
                        <i class="bi bi-buildings"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
              <div class="col-xl-8 mb-4">
                <div class="card">
                  <div class="card-header">
                    <h5 class="mb-0">User Growth Trend</h5>
                  </div>
                  <div class="card-body">
                    <canvas id="userGrowthChart" height="100"></canvas>
                  </div>
                </div>
              </div>
              <div class="col-xl-4 mb-4">
                <div class="card">
                  <div class="card-header">
                    <h5 class="mb-0">User Distribution</h5>
                  </div>
                  <div class="card-body">
                    <canvas id="userDistributionChart" height="200"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Charts -->
            <div class="row">
              <div class="col-xl-6 mb-4">
                <div class="card">
                  <div class="card-header">
                    <h5 class="mb-0">Monthly Appointments</h5>
                  </div>
                  <div class="card-body">
                    <canvas id="appointmentsChart" height="100"></canvas>
                  </div>
                </div>
              </div>
              <div class="col-xl-6 mb-4">
                <div class="card">
                  <div class="card-header">
                    <h5 class="mb-0">Doctor Specializations</h5>
                  </div>
                  <div class="card-body">
                    <canvas id="specializationsChart" height="100"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Profile dropdown functionality
    const adminProfileIconBtn = document.getElementById('adminProfileIconBtn');
    const adminProfileDropdown = document.getElementById('adminProfileDropdown');

    if (adminProfileIconBtn && adminProfileDropdown) {
      adminProfileIconBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        adminProfileDropdown.classList.toggle('show');
      });

      document.addEventListener('click', function(e) {
        if (!adminProfileIconBtn.contains(e.target) && !adminProfileDropdown.contains(e.target)) {
          adminProfileDropdown.classList.remove('show');
        }
      });
    } else {
      console.error('Profile elements not found');
    }

    // Initialize charts when Analytics section is shown
    let chartsInitialized = false;

    // Initialize Analytics Charts
    function initializeAnalyticsCharts() {
      if (chartsInitialized) return;

      // Dynamic data arrays passed from PHP for charts
      const userGrowthLabels = <?php echo json_encode($userGrowthLabels); ?>;
      const userGrowthData = <?php echo json_encode($userGrowthCounts); ?>;
      const userDistributionLabels = <?php echo json_encode($userDistributionLabels); ?>;
      const userDistributionData = <?php echo json_encode($userDistributionCounts); ?>;
      const userDistributionColors = <?php echo json_encode($userDistributionColors); ?>;
      const appointmentsLabels = <?php echo json_encode($appointmentsLabels); ?>;
      const appointmentsValues = <?php echo json_encode($appointmentsValues); ?>;
      const specializationsLabels = <?php echo json_encode($specializationsLabels); ?>;
      const specializationsValues = <?php echo json_encode($specializationsValues); ?>;
      const specializationsColors = <?php echo json_encode($specializationsColors); ?>;

      // User Growth Chart
      const userGrowthCtx = document.getElementById('userGrowthChart');
      if (userGrowthCtx) {
        new Chart(userGrowthCtx, {
          type: 'line',
          data: {
            labels: userGrowthLabels,
            datasets: [{
              label: 'New Users',
              data: userGrowthData,
              borderColor: '#2563eb',
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              tension: 0.4,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: '#e0e7ff'
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      // User Distribution Chart
      const userDistributionCtx = document.getElementById('userDistributionChart');
      if (userDistributionCtx) {
        new Chart(userDistributionCtx, {
          type: 'doughnut',
          data: {
            labels: userDistributionLabels,
            datasets: [{
              data: userDistributionData,
              backgroundColor: userDistributionColors,
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom'
              }
            }
          }
        });
      }

      // Appointments Chart
      const appointmentsCtx = document.getElementById('appointmentsChart');
      if (appointmentsCtx) {
        new Chart(appointmentsCtx, {
          type: 'bar',
          data: {
            labels: appointmentsLabels,
            datasets: [{
              label: 'Appointments',
              data: appointmentsValues,
              backgroundColor: '#8b5cf6',
              borderRadius: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: '#e0e7ff'
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      // Specializations Chart
      const specializationsCtx = document.getElementById('specializationsChart');
      if (specializationsCtx) {
        new Chart(specializationsCtx, {
          type: 'pie',
          data: {
            labels: specializationsLabels,
            datasets: [{
              data: specializationsValues,
              backgroundColor: specializationsColors,
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom'
              }
            }
          }
        });
      }

      chartsInitialized = true;
    }

    // Sidebar navigation logic
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    const contentSections = document.querySelectorAll('.content-section');

    // Hide all sections initially except Overview
    contentSections.forEach(section => {
      section.style.display = 'none';
    });

    // Show Overview section by default
    const overviewSection = document.getElementById('overview');
    if (overviewSection) {
      overviewSection.style.display = 'block';
    }

    navLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();

        // Remove active class from all links
        navLinks.forEach(l => l.classList.remove('active'));

        // Add active class to clicked link
        this.classList.add('active');

        // Hide all content sections
        contentSections.forEach(section => {
          section.style.display = 'none';
        });

        // Show the corresponding section
        const targetSection = this.getAttribute('data-section');
        const sectionToShow = document.getElementById(targetSection);
        if (sectionToShow) {
          sectionToShow.style.display = 'block';
          // Initialize charts if Analytics section is shown
          if (targetSection === 'analytics') {
            setTimeout(initializeAnalyticsCharts, 100);
          }
        }

        // Show/hide welcome message based on section
        const welcomeMessage = document.getElementById('welcome-message');
        if (targetSection === 'overview') {
          welcomeMessage.style.display = 'block';
        } else {
          welcomeMessage.style.display = 'none';
        }
      });
    });

    // Search filter for the user management table
    const userSearchInput = document.getElementById('userSearchInput');
    if (userSearchInput) {
      userSearchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#userTable tbody tr').forEach(row => {
          const name = row.getAttribute('data-name') || '';
          const phone = row.getAttribute('data-phone') || '';
          const status = row.getAttribute('data-status') || '';
          if (name.includes(query) || phone.includes(query) || status.includes(query)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    }
  </script>
  </div>
  </div>
</body>

</html>