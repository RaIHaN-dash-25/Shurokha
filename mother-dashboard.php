<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mother') {
  header('Location: login.php');
  exit;
}
include 'db.php';
$mysqli = new mysqli('localhost', 'root', '', 'Shurokha', 3306);
if ($mysqli->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
}
$doctor_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_week_slots'])) {
  $doctor = trim($_POST['doctor'] ?? '');
  $days = [];
  if ($doctor) {
    $allSlots = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '14:00:00', '15:00:00'];
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE full_name = ? AND role = "doctor"');
    $stmt->bind_param('s', $doctor);
    $stmt->execute();
    $res = $stmt->get_result();
    $doctorId = null;
    if ($row = $res->fetch_assoc()) {
      $doctorId = $row['id'];
    }
    $stmt->close();
    if ($doctorId) {
      for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+{$i} days"));
        $label = date('D', strtotime($date));
        $stmt = $mysqli->prepare('SELECT TIME(scheduled_at) as t FROM appointments WHERE doctor_user_id=? AND DATE(scheduled_at)=? AND status="scheduled"');
        $stmt->bind_param('is', $doctorId, $date);
        $stmt->execute();
        $res2 = $stmt->get_result();
        $booked = [];
        while ($row2 = $res2->fetch_assoc()) {
          $booked[] = $row2['t'];
        }
        $stmt->close();
        $slots = [];
        foreach ($allSlots as $slot) {
          if (!in_array($slot, $booked)) {
            $slots[] = [
              'value' => $slot,
              'label' => date('g:i A', strtotime($slot))
            ];
          }
        }
        $days[] = [
          'date' => $date,
          'label' => $label,
          'slots' => $slots
        ];
      }
    }
    $mysqli->close();
  }
  header('Content-Type: application/json');
  echo json_encode(['days' => $days]);
  exit;
}
$currentUserId = $_SESSION['user_id'] ?? 9;
$feedbackMessage = '';
// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Reschedule appointment
  if (isset($_POST['reschedule_appointment'])) {
    $appId = (int)($_POST['appointment_id'] ?? 0);
    $newDate = trim($_POST['new_date'] ?? '');
    $newTime = trim($_POST['new_time'] ?? '');
    if ($appId && $newDate && $newTime) {
      $newDateTime = $newDate . ' ' . $newTime;
      $stmt = $mysqli->prepare('UPDATE appointments SET scheduled_at=?, status="scheduled" WHERE id=? AND mother_user_id=?');
      $stmt->bind_param('sii', $newDateTime, $appId, $currentUserId);
      if ($stmt->execute()) {
        $feedbackMessage = 'Appointment rescheduled successfully.';
      } else {
        $feedbackMessage = 'Error rescheduling appointment.';
      }
      $stmt->close();
    }
  }
  // Cancel appointment
  if (isset($_POST['cancel_appointment_id'])) {
    $appId = (int)$_POST['cancel_appointment_id'];
    $stmt = $mysqli->prepare('UPDATE appointments SET status="cancelled" WHERE id=? AND mother_user_id=?');
    $stmt->bind_param('ii', $appId, $currentUserId);
    if ($stmt->execute()) {
      $feedbackMessage = 'Appointment cancelled successfully.';
    } else {
      $feedbackMessage = 'Error cancelling appointment.';
    }
    $stmt->close();
  }
  // Mark medication reminder status
  if (isset($_POST['mark_reminder_id'])) {
    $remId = (int)$_POST['mark_reminder_id'];
    $status = $_POST['status'] ?? 'taken';
    $stmt = $mysqli->prepare('INSERT INTO medication_reminder_logs (medication_reminder_id, status) VALUES (?, ?)');
    $stmt->bind_param('is', $remId, $status);
    if ($stmt->execute()) {
      $feedbackMessage = 'Reminder marked as ' . $status . '.';
    } else {
      $feedbackMessage = 'Error marking reminder.';
    }
    $stmt->close();
  }
  // Toggle reminder enable/disable
  if (isset($_POST['toggle_reminder_id'])) {
    $remId = (int)$_POST['toggle_reminder_id'];
    $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
    $stmt = $mysqli->prepare('UPDATE medication_reminders SET is_enabled=? WHERE id=? AND mother_user_id=?');
    $stmt->bind_param('iii', $isEnabled, $remId, $currentUserId);
    if ($stmt->execute()) {
      $feedbackMessage = 'Reminder ' . ($isEnabled ? 'enabled' : 'disabled') . '.';
    } else {
      $feedbackMessage = 'Error updating reminder.';
    }
    $stmt->close();
  }
  // Update notification settings
  if (isset($_POST['update_notification'])) {
    $smsEnabled = isset($_POST['sms_enabled']) ? 1 : 0;
    $appEnabled = isset($_POST['app_enabled']) ? 1 : 0;
    $emailEnabled = isset($_POST['email_enabled']) ? 1 : 0;
    $stmt = $mysqli->prepare('SELECT id FROM notification_settings WHERE user_id=?');
    $stmt->bind_param('i', $currentUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      $stmt2 = $mysqli->prepare('UPDATE notification_settings SET sms_enabled=?, app_push_enabled=?, email_enabled=? WHERE user_id=?');
      $stmt2->bind_param('iiii', $smsEnabled, $appEnabled, $emailEnabled, $currentUserId);
      $stmt2->execute();
      $stmt2->close();
    } else {
      $stmt2 = $mysqli->prepare('INSERT INTO notification_settings (user_id,sms_enabled,app_push_enabled,email_enabled) VALUES (?,?,?,?)');
      $stmt2->bind_param('iiii', $currentUserId, $smsEnabled, $appEnabled, $emailEnabled);
      $stmt2->execute();
      $stmt2->close();
    }
    $stmt->close();
    $feedbackMessage = 'Notification settings updated.';
  }
  // Add custom reminder
  if (isset($_POST['add_custom_reminder'])) {
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    if ($title && $date && $time) {
      $remindAt = $date . ' ' . $time;
      $stmt = $mysqli->prepare('INSERT INTO custom_reminders (mother_user_id,title,remind_at,status) VALUES (?,?,?,"pending")');
      $stmt->bind_param('iss', $currentUserId, $title, $remindAt);
      if ($stmt->execute()) {
        $feedbackMessage = 'Custom reminder added.';
      } else {
        $feedbackMessage = 'Error adding custom reminder.';
      }
      $stmt->close();
    }
  }
  // Update custom reminder status
  if (isset($_POST['mark_custom_reminder_id'])) {
    $custId = (int)$_POST['mark_custom_reminder_id'];
    $status = $_POST['status'] ?? 'done';
    $stmt = $mysqli->prepare('UPDATE custom_reminders SET status=? WHERE id=? AND mother_user_id=?');
    $stmt->bind_param('sii', $status, $custId, $currentUserId);
    if ($stmt->execute()) {
      $feedbackMessage = 'Custom reminder updated.';
    } else {
      $feedbackMessage = 'Error updating custom reminder.';
    }
    $stmt->close();
  }
}

// Fetch user info
$userName = 'Mother';
$stmt = $mysqli->prepare('SELECT full_name FROM users WHERE id=?');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $userName = $row['full_name'];
}
$stmt->close();

// Fetch due date
$dueDate = null;
$stmt = $mysqli->prepare('SELECT due_date FROM mother_profiles WHERE user_id=?');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $dueDate = $row['due_date'];
}
$stmt->close();

// Gestational week and trimester
$gestationalWeek = null;
$trimester = '';
if ($dueDate) {
  $dueTime = strtotime($dueDate);
  $startTime = strtotime('-280 days', $dueTime);
  $now = time();
  $gestationalWeek = floor(($now - $startTime) / (7 * 24 * 60 * 60));
  if ($gestationalWeek < 13) {
    $trimester = 'First Trimester';
  } elseif ($gestationalWeek < 28) {
    $trimester = 'Second Trimester';
  } elseif ($gestationalWeek < 40) {
    $trimester = 'Third Trimester';
  } else {
    $trimester = 'Post Term';
  }
}

// Health records
$healthRecords = [];
$stmt = $mysqli->prepare('SELECT record_date, weight_kg, blood_pressure_systolic, blood_pressure_diastolic, notes FROM health_records WHERE mother_user_id=? ORDER BY record_date DESC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $healthRecords[] = $row;
}
$stmt->close();

// Latest weight and BP
$latestWeight = null;
$weightTrend = null;
$bpSystolic = null;
$bpDiastolic = null;
if (count($healthRecords) > 0) {
  $latestWeight = (float)$healthRecords[0]['weight_kg'];
  $bpSystolic = $healthRecords[0]['blood_pressure_systolic'];
  $bpDiastolic = $healthRecords[0]['blood_pressure_diastolic'];
  if (count($healthRecords) > 1) {
    $prevWeight = (float)$healthRecords[1]['weight_kg'];
    $diff = $latestWeight - $prevWeight;
    if ($diff > 0) {
      $weightTrend = ['arrow' => '↑', 'diff' => $diff];
    } elseif ($diff < 0) {
      $weightTrend = ['arrow' => '↓', 'diff' => abs($diff)];
    } else {
      $weightTrend = ['arrow' => '→', 'diff' => 0];
    }
  }
}

// BP status
$bpStatus = 'N/A';
if ($bpSystolic && $bpDiastolic) {
  if ($bpSystolic < 90 || $bpDiastolic < 60) {
    $bpStatus = 'Low';
  } elseif ($bpSystolic <= 120 && $bpDiastolic <= 80) {
    $bpStatus = 'Normal';
  } else {
    $bpStatus = 'High';
  }
}

// Hemoglobin value
$hemoglobinValue = null;
$stmt = $mysqli->prepare('SELECT result FROM lab_reports WHERE mother_user_id=? AND result LIKE ? ORDER BY report_date DESC LIMIT 1');
$like = '%Hemoglobin%';
$stmt->bind_param('is', $currentUserId, $like);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $parts = explode(':', $row['result'], 2);
  if (isset($parts[1])) {
    $hemoglobinValue = trim($parts[1]);
  } else {
    $hemoglobinValue = $row['result'];
  }
}
$stmt->close();

// Appointments
$upcomingAppointments = [];
$pastAppointments = [];
$stmt = $mysqli->prepare('SELECT a.id, a.scheduled_at, a.status, a.notes, a.doctor_user_id, u.full_name AS doctor_name FROM appointments a JOIN users u ON u.id = a.doctor_user_id WHERE a.mother_user_id=? ORDER BY a.scheduled_at ASC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $apptTime = strtotime($row['scheduled_at']);
  if ($row['status'] === 'scheduled' && $apptTime >= time()) {
    $upcomingAppointments[] = $row;
  } else {
    $pastAppointments[] = $row;
  }
}
$stmt->close();

// Next appointment
$nextAppointment = null;
if (!empty($upcomingAppointments)) {
  $nextAppointment = $upcomingAppointments[0];
}

// Appointment details view
$viewAppointment = null;
if (isset($_GET['view_appt']) && is_numeric($_GET['view_appt'])) {
  $appId = (int)$_GET['view_appt'];
  // Fetch appointment along with doctor specialization and phone
  $stmt = $mysqli->prepare('SELECT a.id,a.scheduled_at,a.status,a.notes,a.doctor_user_id,u.full_name AS doctor_name,dp.specialization,dp.phone FROM appointments a JOIN users u ON u.id=a.doctor_user_id LEFT JOIN doctor_profiles dp ON dp.user_id=a.doctor_user_id WHERE a.id=? AND a.mother_user_id=?');
  $stmt->bind_param('ii', $appId, $currentUserId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $viewAppointment = $row;
  }
  $stmt->close();
}

// Reschedule appointment details
$rescheduleAppointment = null;
if (isset($_GET['reschedule']) && is_numeric($_GET['reschedule'])) {
  $resId = (int)$_GET['reschedule'];
  $stmt = $mysqli->prepare('SELECT a.id,a.scheduled_at,a.status,a.notes,a.doctor_user_id,u.full_name AS doctor_name,dp.specialization,dp.phone FROM appointments a JOIN users u ON u.id=a.doctor_user_id LEFT JOIN doctor_profiles dp ON dp.user_id=a.doctor_user_id WHERE a.id=? AND a.mother_user_id=?');
  $stmt->bind_param('ii', $resId, $currentUserId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $rescheduleAppointment = $row;
  }
  $stmt->close();
}

// Medication records
$medications = [];
$stmt = $mysqli->prepare('SELECT id,medication_name,dosage,frequency,start_date,end_date FROM medication_records WHERE mother_user_id=? ORDER BY start_date DESC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $medications[] = $row;
}
$stmt->close();

// Lab reports
$labReports = [];
$stmt = $mysqli->prepare('SELECT id,report_type,report_date,result,file_path FROM lab_reports WHERE mother_user_id=? ORDER BY report_date DESC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $labReports[] = $row;
}
$stmt->close();

// Medication reminders
$medReminders = [];
$stmt = $mysqli->prepare('SELECT mr.id, mr.remind_time, mr.is_enabled, mr.notes, mc.medication_name, mc.dosage FROM medication_reminders mr JOIN medication_records mc ON mr.medication_record_id = mc.id WHERE mr.mother_user_id=? ORDER BY mr.remind_time ASC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $remId = $row['id'];
  $status = 'not_taken';
  $stmt2 = $mysqli->prepare('SELECT status FROM medication_reminder_logs WHERE medication_reminder_id=? AND DATE(logged_at)=CURDATE() ORDER BY logged_at DESC LIMIT 1');
  $stmt2->bind_param('i', $remId);
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  if ($row2 = $res2->fetch_assoc()) {
    $status = $row2['status'];
  }
  $stmt2->close();
  $row['status'] = $status;
  $medReminders[] = $row;
}
$stmt->close();

// Vaccinations
$vaccinations = [];
$stmt = $mysqli->prepare('SELECT id,vaccine_name,scheduled_date,status,completed_date,notes FROM vaccinations WHERE mother_user_id=? ORDER BY scheduled_date ASC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $vaccinations[] = $row;
}
$stmt->close();

// Custom reminders
$customReminders = [];
$stmt = $mysqli->prepare('SELECT id,title,remind_at,status FROM custom_reminders WHERE mother_user_id=? ORDER BY remind_at ASC');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $customReminders[] = $row;
}
$stmt->close();

// Notification settings
$smsEnabled = 1;
$appEnabled = 1;
$emailEnabled = 1;
$stmt = $mysqli->prepare('SELECT sms_enabled, app_push_enabled, email_enabled FROM notification_settings WHERE user_id=?');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $smsEnabled = (int)$row['sms_enabled'];
  $appEnabled = (int)$row['app_push_enabled'];
  $emailEnabled = (int)$row['email_enabled'];
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mother Dashboard – Shurokha</title>
  <link rel="icon" type="image/png" href="logo-transparent.png" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/mother.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style></style>
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
          <a href="#" class="nav-link active" data-section="overview-section">
            <i class="bi bi-house-door"></i>
            Overview
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="health-section">
            <i class="bi bi-heart-pulse"></i>
            Health Records
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="appointments-section">
            <i class="bi bi-calendar-check"></i>
            Appointments
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="reminders-section">
            <i class="bi bi-bell"></i>
            Reminders
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" data-section="messages-section">
            <i class="bi bi-chat-dots"></i>
            Messages
          </a>
        </li>
      </ul>
    </nav>
    <!-- Emergency Call Button -->
    <button
      class="emergency-call-btn"
      id="emergencyCallBtn"
      aria-label="Emergency Call 999">
      <i class="bi bi-telephone-fill"></i>
    </button>
    <div class="emergency-tooltip" id="emergencyTooltip">Emergency: 999</div>
    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <div class="header">
        <div class="welcome-text" id="welcome-message">
          <h1>Welcome, <?php echo htmlspecialchars($userName); ?></h1>
          <p>Here's your pregnancy health overview</p>
          <?php if (!empty($feedbackMessage)): ?>
            <div class="alert alert-info mt-2 p-2"><?php echo htmlspecialchars($feedbackMessage); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Profile Button (Fixed in upper right) -->
      <div class="profile-section">
        <button class="profile-btn" id="profileBtn">
          <i class="bi bi-person-circle"></i>
        </button>
        <div class="profile-dropdown" id="profileDropdown">
          <button class="dropdown-item">
            <i class="bi bi-person me-2"></i>Profile Details
          </button>
          <button class="dropdown-item">
            <i class="bi bi-pencil me-2"></i>Update Profile
          </button>
          <button class="dropdown-item" onclick="logout()">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
          </button>
        </div>
      </div>
      <main class="p-4 animate__animated animate__fadeInUp">
        <?php if ($rescheduleAppointment): ?>
          <div class="card mb-4 p-3">
            <h4>Reschedule Appointment with <?php echo htmlspecialchars($rescheduleAppointment['doctor_name']); ?></h4>
            <form method="post" class="row g-3">
              <input type="hidden" name="reschedule_appointment" value="1">
              <input type="hidden" name="appointment_id" value="<?php echo $rescheduleAppointment['id']; ?>">
              <div class="col-md-6">
                <label for="new_date" class="form-label">New Date</label>
                <input type="date" class="form-control" id="new_date" name="new_date" value="<?php echo date('Y-m-d', strtotime($rescheduleAppointment['scheduled_at'])); ?>" required>
              </div>
              <div class="col-md-6">
                <label for="new_time" class="form-label">New Time</label>
                <input type="time" class="form-control" id="new_time" name="new_time" value="<?php echo date('H:i', strtotime($rescheduleAppointment['scheduled_at'])); ?>" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary">Confirm Reschedule</button>
                <a href="mother-dashboard.php" class="btn btn-secondary">Cancel</a>
              </div>
            </form>
          </div>
        <?php endif; ?>
        <?php if ($viewAppointment): ?>
          <div class="card mb-4 p-3">
            <h4>Appointment Details with <?php echo htmlspecialchars($viewAppointment['doctor_name']); ?></h4>
            <ul class="list-unstyled mb-0">
              <li><strong>Date:</strong> <?php echo date('F j, Y', strtotime($viewAppointment['scheduled_at'])); ?></li>
              <li><strong>Time:</strong> <?php echo date('g:i A', strtotime($viewAppointment['scheduled_at'])); ?></li>
              <li><strong>Doctor:</strong> <?php echo htmlspecialchars($viewAppointment['doctor_name']); ?></li>
              <?php if (!empty($viewAppointment['specialization'])): ?>
                <li><strong>Specialization:</strong> <?php echo htmlspecialchars($viewAppointment['specialization']); ?></li>
              <?php endif; ?>
              <?php if (!empty($viewAppointment['phone'])): ?>
                <li><strong>Phone:</strong> <?php echo htmlspecialchars($viewAppointment['phone']); ?></li>
              <?php endif; ?>
              <li><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($viewAppointment['status'])); ?></li>
              <?php if (!empty($viewAppointment['notes'])): ?>
                <li><strong>Notes:</strong> <?php echo htmlspecialchars($viewAppointment['notes']); ?></li>
              <?php endif; ?>
            </ul>
            <a href="mother-dashboard.php#appointments-section" class="btn btn-secondary mt-2">Close</a>
          </div>
        <?php endif; ?>
        <!-- Overview Section -->
        <section id="overview-section" class="dashboard-section">
          <h2
            class="fw-bold mb-4 text-primary animate__animated animate__fadeInDown">
            Overview
          </h2>
          <div class="overview-grid">
            <div class="overview-card bp animate__animated animate__fadeInUp">
              <span class="overview-icon"><i class="bi bi-heart-pulse-fill"></i></span>
              <div>
                <div class="card-title">Blood Pressure</div>
                <div class="card-value">
                  <?php if ($bpSystolic && $bpDiastolic) {
                    echo htmlspecialchars($bpSystolic . '/' . $bpDiastolic . ' mmHg');
                  } else {
                    echo 'N/A';
                  } ?>
                </div>
                <div class="card-status <?php echo ($bpStatus === 'High' ? 'text-danger' : ($bpStatus === 'Low' ? 'text-warning' : 'text-success')); ?>">
                  <?php echo htmlspecialchars($bpStatus); ?>
                </div>
              </div>
            </div>
            <div
              class="overview-card weight animate__animated animate__fadeInUp animate__delay-1s">
              <span class="overview-icon"><i class="bi bi-graph-up-arrow"></i></span>
              <div>
                <div class="card-title">Weight Trend</div>
                <div class="card-value">
                  <?php if ($latestWeight !== null): ?>
                    <?php echo htmlspecialchars(number_format($latestWeight, 1)); ?> kg
                    <?php if ($weightTrend): ?>
                      <span class="<?php echo $weightTrend['arrow'] === '↓' ? 'text-danger' : 'text-success'; ?>">
                        (<?php echo $weightTrend['arrow'] . ' ' . number_format($weightTrend['diff'], 1); ?> kg)
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </div>
                <div class="card-status">
                  <?php
                  if ($weightTrend) {
                    if ($weightTrend['arrow'] === '↓') {
                      echo 'Weight loss';
                    } elseif ($weightTrend['diff'] > 0) {
                      echo 'Healthy gain';
                    } else {
                      echo 'Stable';
                    }
                  } else {
                    echo 'N/A';
                  }
                  ?>
                </div>
              </div>
            </div>
            <div
              class="overview-card hemo animate__animated animate__fadeInUp animate__delay-2s">
              <span class="overview-icon"><i class="bi bi-droplet-half"></i></span>
              <div>
                <div class="card-title">Hemoglobin</div>
                <div class="card-value">
                  <?php echo $hemoglobinValue ? htmlspecialchars($hemoglobinValue) : 'N/A'; ?>
                </div>
                <div class="card-status <?php echo $hemoglobinValue ? 'text-success' : 'text-muted'; ?>">
                  <?php echo $hemoglobinValue ? 'Healthy' : 'N/A'; ?>
                </div>
              </div>
            </div>
            <div
              class="overview-card gest animate__animated animate__fadeInUp animate__delay-3s">
              <span class="overview-icon"><i class="bi bi-calendar-week"></i></span>
              <div>
                <div class="card-title">Gestational Week</div>
                <div class="card-value">
                  <?php echo $gestationalWeek !== null ? 'Week ' . htmlspecialchars($gestationalWeek) : 'N/A'; ?>
                </div>
                <div class="card-status">
                  <?php echo $trimester ? htmlspecialchars($trimester) : 'N/A'; ?>
                </div>
              </div>
            </div>
            <div
              class="overview-card risk animate__animated animate__fadeInUp animate__delay-4s">
              <span class="overview-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
              <div>
                <div class="card-title">High-Risk Status</div>
                <?php $isHighRisk = ($bpStatus === 'High' || ($weightTrend && $weightTrend['arrow'] === '↓')); ?>
                <div class="card-value <?php echo $isHighRisk ? 'text-danger' : 'text-success'; ?>">
                  <?php echo $isHighRisk ? 'Yes' : 'No'; ?>
                </div>
                <div class="card-status">
                  <?php echo $isHighRisk ? 'Requires attention' : 'Low risk'; ?>
                </div>
              </div>
            </div>
          </div>
          <div
            class="next-appointment-card animate__animated animate__fadeInUp animate__delay-2s">
            <div class="fw-bold mb-2 fs-5">
              <i class="bi bi-calendar-event me-2 text-primary"></i>Next Appointment
            </div>
            <?php if ($nextAppointment): ?>
              <div><b>Date:</b> <?php echo date('F j, Y', strtotime($nextAppointment['scheduled_at'])); ?></div>
              <div><b>Time:</b> <?php echo date('g:i A', strtotime($nextAppointment['scheduled_at'])); ?></div>
              <div><b>Doctor:</b> <?php echo htmlspecialchars($nextAppointment['doctor_name']); ?></div>
              <?php if (!empty($nextAppointment['notes'])): ?>
                <div><b>Note:</b> <?php echo htmlspecialchars($nextAppointment['notes']); ?></div>
              <?php endif; ?>
              <a href="?reschedule=<?php echo $nextAppointment['id']; ?>" class="btn btn-primary btn-sm mt-2">Reschedule</a>
            <?php else: ?>
              <div>No upcoming appointments.</div>
            <?php endif; ?>
          </div>
          <div class="overview-charts">
            <div
              class="overview-chart-card animate__animated animate__fadeInUp animate__delay-3s">
              <div class="fw-bold mb-2">
                <i class="bi bi-activity text-success me-2"></i>Weight/BP
                Trend
              </div>
              <canvas id="overviewWeightBPChart" height="180"></canvas>
            </div>
            <div
              class="overview-chart-card animate__animated animate__fadeInUp animate__delay-4s">
              <div class="fw-bold mb-2">
                <i class="bi bi-pie-chart-fill text-accent me-2"></i>Checkups
                Progress
              </div>
              <canvas id="overviewCheckupsChart" height="180"></canvas>
            </div>
          </div>
        </section>
        <!-- Health Records Section -->
        <section id="health-section" class="dashboard-section d-none">
          <h2
            class="fw-bold mb-4 text-primary animate__animated animate__fadeInDown">
            Health Records
          </h2>
          <div
            class="medical-history-table animate__animated animate__fadeInUp">
            <div class="fw-bold mb-2 p-3">
              <i class="bi bi-journal-medical text-primary me-2"></i>Medical
              History
            </div>
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Doctor</th>
                  <th>Test/Checkup</th>
                  <th>Result</th>
                  <th>Blood Pressure</th>
                  <th>Weight (kg)</th>
                  <th>Hemoglobin</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($healthRecords as $rec): ?>
                  <?php
                  $hemo = 'N/A';
                  foreach ($labReports as $lab) {
                    if ($lab['report_date'] === $rec['record_date'] && stripos($lab['result'], 'Hemoglobin') !== false) {
                      $parts = explode(':', $lab['result'], 2);
                      $hemo = isset($parts[1]) ? trim($parts[1]) : $lab['result'];
                      break;
                    }
                  }
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($rec['record_date']); ?></td>
                    <td>N/A</td>
                    <td><?php echo htmlspecialchars($rec['notes'] ?: 'General Check'); ?></td>
                    <td>N/A</td>
                    <td><?php echo htmlspecialchars($rec['blood_pressure_systolic'] . '/' . $rec['blood_pressure_diastolic']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($rec['weight_kg'], 1)); ?></td>
                    <td><?php echo htmlspecialchars($hemo); ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($healthRecords)): ?>
                  <tr>
                    <td colspan="7" class="text-center">No health records found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="health-grid">
            <div class="health-card animate__animated animate__fadeInUp">
              <span class="health-icon"><i class="bi bi-calendar-week"></i></span>
              <div>
                <div class="card-title">Weeks of Pregnancy</div>
                <div class="card-value">28</div>
                <div class="card-status">Second Trimester</div>
              </div>
            </div>
            <div
              class="health-card animate__animated animate__fadeInUp animate__delay-1s">
              <span class="health-icon"><i class="bi bi-activity"></i></span>
              <div style="width: 180px">
                <div class="card-title">Vitals Over Time</div>
                <canvas id="vitalsChart" height="90"></canvas>
              </div>
            </div>
            <div
              class="health-card animate__animated animate__fadeInUp animate__delay-2s">
              <span class="health-icon"><i class="bi bi-graph-up-arrow"></i></span>
              <div style="width: 180px">
                <div class="card-title">Health Trends</div>
                <canvas id="trendsChart" height="90"></canvas>
              </div>
            </div>
          </div>
          <div class="row g-4 mb-4">
            <div class="col-lg-6">
              <div
                class="medication-table animate__animated animate__fadeInUp animate__delay-3s">
                <div class="fw-bold mb-2 p-3">
                  <i class="bi bi-capsule-pill text-danger me-2"></i>Medication Record
                </div>
                <table class="table mb-0">
                  <thead>
                    <tr>
                      <th>Medication</th>
                      <th>Dosage</th>
                      <th>Status</th>
                      <th>Start</th>
                      <th>End</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $today = date('Y-m-d');
                    foreach ($medications as $med):
                      $status = 'Ongoing';
                      $badgeClass = 'bg-success';
                      if (!empty($med['start_date']) && !empty($med['end_date'])) {
                        if ($today < $med['start_date']) {
                          $status = 'Pending';
                          $badgeClass = 'bg-warning';
                        } elseif ($today > $med['end_date']) {
                          $status = 'Completed';
                          $badgeClass = 'bg-secondary';
                        }
                      }
                    ?>
                      <tr>
                        <td><?php echo htmlspecialchars($med['medication_name']); ?></td>
                        <td><?php echo htmlspecialchars($med['dosage']); ?></td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $status; ?></span></td>
                        <td><?php echo htmlspecialchars($med['start_date'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($med['end_date'] ?: '-'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (empty($medications)): ?>
                      <tr>
                        <td colspan="5" class="text-center">No medication records found.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-lg-6">
              <div
                class="lab-table animate__animated animate__fadeInUp animate__delay-4s">
                <div class="fw-bold mb-2 p-3">
                  <i class="bi bi-file-earmark-medical text-primary me-2"></i>Lab Reports
                </div>
                <table class="table mb-0">
                  <thead>
                    <tr>
                      <th>Type</th>
                      <th>Date</th>
                      <th>Result</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($labReports as $report): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                        <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                        <td><?php echo htmlspecialchars($report['result']); ?></td>
                        <td>
                          <?php if (!empty($report['file_path'])): ?>
                            <a href="<?php echo htmlspecialchars($report['file_path']); ?>" target="_blank" class="lab-action-btn">View</a>
                            <a href="<?php echo htmlspecialchars($report['file_path']); ?>" download class="lab-action-btn">Download</a>
                          <?php else: ?>
                            N/A
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (empty($labReports)): ?>
                      <tr>
                        <td colspan="4" class="text-center">No lab reports found.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
        <!-- Appointments Section -->
        <section
          id="appointments-section"
          class="dashboard-section d-none appointments-section">
          <h2
            class="fw-bold mb-4 text-primary animate__animated animate__fadeInDown">
            Appointments
          </h2>
          <div class="calendar-card animate__animated animate__fadeInUp">
            <div class="calendar-header">
              <button class="calendar-nav-btn" id="calendarPrevBtn">
                <i class="bi bi-chevron-left"></i>
              </button>
              <div class="fw-bold fs-5" id="calendarMonthLabel">
                July 2025
              </div>
              <button class="calendar-nav-btn" id="calendarNextBtn">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
            <table class="calendar-table" id="calendarTable">
              <!-- Calendar will be rendered by JS -->
            </table>
          </div>
          <div
            class="upcoming-appointment-card animate__animated animate__fadeInUp animate__delay-1s">
            <?php if (!empty($upcomingAppointments)): ?>
              <?php foreach ($upcomingAppointments as $appt): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <div class="fw-bold fs-5 mb-1">
                      <i class="bi bi-calendar-check text-success me-2"></i>Upcoming Appointment
                    </div>
                    <div>
                      <b>Date:</b> <?php echo date('F j, Y', strtotime($appt['scheduled_at'])); ?> &nbsp;
                      <b>Time:</b> <?php echo date('g:i A', strtotime($appt['scheduled_at'])); ?>
                    </div>
                    <div>
                      <b>Doctor:</b> <?php echo htmlspecialchars($appt['doctor_name']); ?>
                    </div>
                    <?php if (!empty($appt['notes'])): ?>
                      <div><b>Note:</b> <?php echo htmlspecialchars($appt['notes']); ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="text-end">
                    <a href="?view_appt=<?php echo $appt['id']; ?>#appointments-section" class="btn btn-primary btn-sm">View Details</a>
                    <a href="?reschedule=<?php echo $appt['id']; ?>#appointments-section" class="btn btn-primary btn-sm">Reschedule</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="cancel_appointment_id" value="<?php echo $appt['id']; ?>">
                      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this appointment?');">Cancel</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="mb-0">No upcoming appointments.</p>
            <?php endif; ?>
          </div>
          <div
            class="past-appointments-table animate__animated animate__fadeInUp animate__delay-2s">
            <div class="fw-bold mb-2 p-3">
              <i class="bi bi-clock-history text-accent me-2"></i>Past
              Appointments
            </div>
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Doctor</th>
                  <th>Summary</th>
                  <th>Prescription</th>
                  <th>Follow-up</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pastAppointments as $pAppt): ?>
                  <tr>
                    <td><?php echo date('F j', strtotime($pAppt['scheduled_at'])); ?></td>
                    <td><?php echo htmlspecialchars($pAppt['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($pAppt['notes'] ?: '-'); ?></td>
                    <td>-</td>
                    <td>
                      <?php
                      if ($pAppt['status'] === 'cancelled') {
                        echo 'Cancelled';
                      } elseif ($pAppt['status'] === 'completed') {
                        echo 'No';
                      } else {
                        echo 'Yes';
                      }
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($pastAppointments)): ?>
                  <tr>
                    <td colspan="5" class="text-center">No past appointments.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div
            class="book-appointment-form animate__animated animate__fadeInUp animate__delay-3s">
            <div class="fw-bold mb-3 fs-5">
              <i class="bi bi-plus-circle text-primary me-2"></i>Book New
              Appointment
            </div>
            <?php
            // Handle book appointment POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
              $department = trim($_POST['department'] ?? '');
              $doctor = trim($_POST['doctor'] ?? '');
              $date = trim($_POST['date'] ?? '');
              $time = trim($_POST['time'] ?? '');
              $feedbackMessage = '';
              if ($department && $doctor && $date && $time) {
                // Find doctor_user_id by name (simple fallback, should use real IDs in production)
                $stmt = $mysqli->prepare('SELECT id FROM users WHERE full_name = ? AND role = "doctor"');
                $stmt->bind_param('s', $doctor);
                $stmt->execute();
                $res = $stmt->get_result();
                $doctorId = null;
                if ($row = $res->fetch_assoc()) {
                  $doctorId = $row['id'];
                }
                $stmt->close();
                if ($doctorId) {
                  $scheduledAt = $date . ' ' . $time;
                  $stmt = $mysqli->prepare('INSERT INTO appointments (mother_user_id, doctor_user_id, scheduled_at, status, notes) VALUES (?, ?, ?, "scheduled", "")');
                  $stmt->bind_param('iis', $currentUserId, $doctorId, $scheduledAt);
                  if ($stmt->execute()) {
                    $feedbackMessage = 'Appointment booked successfully!';
                  } else {
                    $feedbackMessage = 'Failed to book appointment. Please try again.';
                  }
                  $stmt->close();
                } else {
                  $feedbackMessage = 'Selected doctor not found.';
                }
              } else {
                $feedbackMessage = 'Please fill in all fields.';
              }
              echo '<div class="alert alert-info mt-2">' . htmlspecialchars($feedbackMessage) . '</div>';
            }
            ?>
            <?php
            // AJAX endpoint for available slots for a week (must be at the very top before any output)

            // Fetch doctors and departments for dynamic dropdowns
            $departments = [];
            $doctorsByDept = [];
            $doctorOptions = [];
            $stmt = $mysqli->prepare('SELECT u.full_name, dp.specialization FROM users u JOIN doctor_profiles dp ON u.id = dp.user_id WHERE u.role = "doctor"');
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
              $dept = $row['specialization'] ?: 'General';
              $departments[$dept] = true;
              $doctorsByDept[$dept][] = $row['full_name'];
              $doctorOptions[] = $row['full_name'];
            }
            $stmt->close();
            ?>
            <form method="post" autocomplete="off" id="bookAppointmentForm">
              <input type="hidden" name="book_appointment" value="1">
              <div class="row g-3">
                <div class="col-md-6">
                  <label>Department:</label>
                  <select class="form-select" name="department" id="departmentSelect" required>
                    <option value="">Select Department</option>
                    <?php foreach (array_keys($departments) as $dept): ?>
                      <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label>Doctor:</label>
                  <select class="form-select" name="doctor" id="doctorSelect" required>
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctorOptions as $doc): ?>
                      <option value="<?php echo htmlspecialchars($doc); ?>"><?php echo htmlspecialchars($doc); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <input type="hidden" name="date" id="selectedDate" required />
                <input type="hidden" name="time" id="selectedTime" required />
                <div class="col-12">
                  <label>Choose a Slot:</label>
                  <div id="slotsTableContainer">
                    <div class="text-muted">Select department and doctor to view available slots.</div>
                  </div>
                </div>
              </div>
              <div class="mt-4">
                <button class="btn btn-primary" type="submit">
                  Book Appointment
                </button>
              </div>
            </form>
            <script>
              // Dynamic doctor dropdown based on department
              const doctorsByDept = <?php echo json_encode($doctorsByDept); ?>;
              document.getElementById('departmentSelect').addEventListener('change', function() {
                const dept = this.value;
                const doctorSelect = document.getElementById('doctorSelect');
                doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
                if (doctorsByDept[dept]) {
                  doctorsByDept[dept].forEach(function(doc) {
                    const opt = document.createElement('option');
                    opt.value = doc;
                    opt.textContent = doc;
                    doctorSelect.appendChild(opt);
                  });
                }
                document.getElementById('slotsTableContainer').innerHTML = '<div class="text-muted">Select doctor to view available slots.</div>';
              });
              document.getElementById('doctorSelect').addEventListener('change', updateSlotsTable);

              function updateSlotsTable() {
                const doctor = document.getElementById('doctorSelect').value;
                const slotsTableContainer = document.getElementById('slotsTableContainer');
                slotsTableContainer.innerHTML = '<div class="text-muted">Loading slots...</div>';
                if (!doctor) {
                  slotsTableContainer.innerHTML = '<div class="text-muted">Select doctor to view available slots.</div>';
                  return;
                }
                // AJAX to fetch available slots for the week
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'mother-dashboard.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                  if (xhr.status === 200) {
                    try {
                      const data = JSON.parse(xhr.responseText);
                      renderSlotsTable(data);
                    } catch (e) {
                      slotsTableContainer.innerHTML = '<div class="text-danger">Failed to load slots.</div>';
                    }
                  } else {
                    slotsTableContainer.innerHTML = '<div class="text-danger">Failed to load slots.</div>';
                  }
                };
                xhr.send('fetch_week_slots=1&doctor=' + encodeURIComponent(doctor));
              }

              function renderSlotsTable(data) {
                const slotsTableContainer = document.getElementById('slotsTableContainer');
                if (!data || !data.days || data.days.length === 0) {
                  slotsTableContainer.innerHTML = '<div class="text-muted">No slots available.</div>';
                  return;
                }
                let html = '<table class="table table-bordered table-sm align-middle mb-0"><thead><tr>';
                data.days.forEach(function(day) {
                  html += '<th class="text-center">' + day.label + '<br><span class="small">' + day.date + '</span></th>';
                });
                html += '</tr></thead><tbody>';
                let maxSlots = 0;
                data.days.forEach(function(day) {
                  if (day.slots.length > maxSlots) maxSlots = day.slots.length;
                });
                for (let i = 0; i < maxSlots; i++) {
                  html += '<tr>';
                  data.days.forEach(function(day) {
                    if (day.slots[i]) {
                      html += '<td class="text-center"><button type="button" class="btn btn-outline-primary btn-sm slot-btn" data-date="' + day.date + '" data-time="' + day.slots[i].value + '">' + day.slots[i].label + '</button></td>';
                    } else {
                      html += '<td></td>';
                    }
                  });
                  html += '</tr>';
                }
                html += '</tbody></table>';
                slotsTableContainer.innerHTML = html;
                document.querySelectorAll('.slot-btn').forEach(function(btn) {
                  btn.addEventListener('click', function() {
                    document.getElementById('selectedDate').value = this.getAttribute('data-date');
                    document.getElementById('selectedTime').value = this.getAttribute('data-time');
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                  });
                });
              }
            </script>
          </div>
        </section>
        <!-- Reminders Section -->
        <section id="reminders-section" class="dashboard-section d-none">
          <h2
            class="fw-bold mb-4 text-primary animate__animated animate__fadeInDown">
            Reminders
          </h2>
          <div class="reminder-cards animate__animated animate__fadeInUp">
            <?php foreach ($medReminders as $rem): ?>
              <div class="reminder-card animate__animated animate__fadeInUp<?php echo ($rem['status'] === 'taken' ? ' taken' : ''); ?>">
                <div class="reminder-title">
                  <span class="reminder-icon"><i class="bi bi-capsule-pill"></i></span><?php echo htmlspecialchars($rem['medication_name']); ?>
                </div>
                <div class="reminder-time">Time: <?php echo date('g:i A', strtotime($rem['remind_time'])); ?></div>
                <div class="reminder-dosage">Dosage: <?php echo htmlspecialchars($rem['dosage']); ?></div>
                <div class="reminder-toggle d-flex align-items-center">
                  <form method="post" class="me-2">
                    <input type="hidden" name="mark_reminder_id" value="<?php echo $rem['id']; ?>">
                    <input type="hidden" name="status" value="<?php echo ($rem['status'] === 'taken' ? 'not_taken' : 'taken'); ?>">
                    <label class="reminder-switch mb-0">
                      <input type="checkbox" onchange="this.form.submit()" <?php echo ($rem['status'] === 'taken' ? 'checked' : ''); ?> />
                      <span class="reminder-slider"></span>
                    </label>
                  </form>
                  <span class="reminder-status"><?php echo ($rem['status'] === 'taken' ? 'Taken' : 'Not Taken'); ?></span>
                  <form method="post" class="ms-2">
                    <input type="hidden" name="toggle_reminder_id" value="<?php echo $rem['id']; ?>">
                    <?php if ($rem['is_enabled']): ?>
                      <input type="hidden" name="is_enabled" value="0">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Disable</button>
                    <?php else: ?>
                      <input type="hidden" name="is_enabled" value="1">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Enable</button>
                    <?php endif; ?>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($medReminders)): ?>
              <p>No medication reminders found.</p>
            <?php endif; ?>
          </div>
          <div
            class="vaccination-timeline animate__animated animate__fadeInUp animate__delay-1s">
            <?php foreach ($vaccinations as $vac): ?>
              <?php
              $class = htmlspecialchars($vac['status']);
              $dateDisplay = ($vac['status'] === 'completed' ? $vac['completed_date'] : $vac['scheduled_date']);
              $tooltip = ($vac['status'] === 'completed' ? 'Completed on ' : ($vac['status'] === 'upcoming' ? 'Upcoming: ' : 'Missed: ')) . date('M j', strtotime($dateDisplay));
              ?>
              <div class="timeline-node <?php echo $class; ?>">
                <div class="timeline-dot"></div>
                <div class="timeline-label"><?php echo htmlspecialchars($vac['vaccine_name']); ?></div>
                <div class="timeline-date"><?php echo date('M j', strtotime($dateDisplay)); ?></div>
                <div class="timeline-tooltip"><?php echo htmlspecialchars($tooltip); ?></div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($vaccinations)): ?>
              <p>No vaccinations scheduled.</p>
            <?php endif; ?>
          </div>
          <div
            class="notification-settings animate__animated animate__fadeInUp animate__delay-2s">
            <div class="fw-bold mb-2 fs-5">
              <i class="bi bi-bell me-2 text-primary"></i>Notification
              Settings
            </div>
            <form method="post" class="d-inline">
              <input type="hidden" name="update_notification" value="1">
              <label class="toggle-switch me-1">
                <input type="checkbox" name="sms_enabled" onchange="this.form.submit()" <?php echo ($smsEnabled ? 'checked' : ''); ?> />
                <span class="toggle-slider"></span>
              </label>
              SMS Notifications
              <label class="toggle-switch ms-3 me-1">
                <input type="checkbox" name="app_enabled" onchange="this.form.submit()" <?php echo ($appEnabled ? 'checked' : ''); ?> />
                <span class="toggle-slider"></span>
              </label>
              App Notifications
              <label class="toggle-switch ms-3 me-1">
                <input type="checkbox" name="email_enabled" onchange="this.form.submit()" <?php echo ($emailEnabled ? 'checked' : ''); ?> />
                <span class="toggle-slider"></span>
              </label>
              Email Notifications
            </form>
          </div>
          <div
            class="custom-reminder-form animate__animated animate__fadeInUp animate__delay-3s">
            <div class="fw-bold mb-3 fs-5">
              <i class="bi bi-plus-circle text-primary me-2"></i>Add Custom
              Reminder
            </div>
            <form method="post" autocomplete="off" class="mb-4">
              <input type="hidden" name="add_custom_reminder" value="1">
              <div class="form-floating mb-3">
                <input
                  type="text"
                  class="form-control"
                  id="customReminderTitle"
                  name="title"
                  placeholder="Title"
                  required />
                <label for="customReminderTitle">Title</label>
              </div>
              <div class="form-floating mb-3">
                <input
                  type="date"
                  class="form-control"
                  id="customReminderDate"
                  name="date"
                  placeholder="Date"
                  required />
                <label for="customReminderDate">Date</label>
              </div>
              <div class="form-floating mb-3">
                <input
                  type="time"
                  class="form-control"
                  id="customReminderTime"
                  name="time"
                  placeholder="Time"
                  required />
                <label for="customReminderTime">Time</label>
              </div>
              <button class="btn btn-primary" type="submit">
                Add Reminder
              </button>
            </form>
            <div class="custom-reminder-list">
              <?php foreach ($customReminders as $crem): ?>
                <div class="custom-reminder-item border rounded p-2 mb-2">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-bold"><?php echo htmlspecialchars($crem['title']); ?></div>
                      <div class="text-muted" style="font-size: 0.9rem;">
                        <?php echo date('M j, Y g:i A', strtotime($crem['remind_at'])); ?>
                      </div>
                      <span class="badge <?php echo ($crem['status'] === 'done' ? 'bg-success' : ($crem['status'] === 'missed' ? 'bg-danger' : 'bg-warning')); ?>">
                        <?php echo ucfirst($crem['status']); ?>
                      </span>
                    </div>
                    <div>
                      <?php if ($crem['status'] !== 'done'): ?>
                        <form method="post" class="d-inline">
                          <input type="hidden" name="mark_custom_reminder_id" value="<?php echo $crem['id']; ?>">
                          <input type="hidden" name="status" value="done">
                          <button type="submit" class="btn btn-sm btn-success">Mark Done</button>
                        </form>
                      <?php endif; ?>
                      <?php if ($crem['status'] !== 'missed'): ?>
                        <form method="post" class="d-inline">
                          <input type="hidden" name="mark_custom_reminder_id" value="<?php echo $crem['id']; ?>">
                          <input type="hidden" name="status" value="missed">
                          <button type="submit" class="btn btn-sm btn-danger">Mark Missed</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (empty($customReminders)): ?>
                <p class="text-muted">No custom reminders added.</p>
              <?php endif; ?>
            </div>
          </div>
        </section>
        <!-- Messages Section -->
        <section id="messages-section" class="dashboard-section d-none">
          <h2
            class="fw-bold mb-4 text-primary animate__animated animate__fadeInDown">
            Messages
          </h2>
          <div class="messages-container animate__animated animate__fadeInUp">
            <div class="messages-sidebar">
              <div class="messages-search">
                <input type="text" placeholder="Search conversations..." />
                <i class="bi bi-search"></i>
              </div>
              <div class="conversation-list">
                <div class="conversation-item selected">
                  <div class="conversation-avatar">DR</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Rahman</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
                <div class="conversation-item">
                  <div class="conversation-avatar">DS</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Sultana</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
                <div class="conversation-item">
                  <div class="conversation-avatar">DN</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Nazneen</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
                <div class="conversation-item">
                  <div class="conversation-avatar">DA</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Arefin</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
                <div class="conversation-item">
                  <div class="conversation-avatar">DK</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Karim</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
                <div class="conversation-item">
                  <div class="conversation-avatar">DM</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Mahmud</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
                <div class="conversation-item">
                  <div class="conversation-avatar">DH</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Hossain</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
                <div class="conversation-item">
                  <div class="conversation-avatar">DI</div>
                  <div class="conversation-info">
                    <div class="conversation-name">Dr. Islam</div>
                    <div class="conversation-role">Doctor</div>
                    <div class="conversation-preview">No messages yet</div>
                  </div>
                  <div class="conversation-date"></div>
                </div>
              </div>
            </div>
            <div class="chat-panel">
              <div class="chat-header">
                <div class="chat-header-avatar">DR</div>
                <div class="chat-header-info">
                  <div class="chat-header-name">Dr. Rahman</div>
                  <div class="chat-header-role">Doctor</div>
                </div>
                <div class="chat-header-actions">
                  <button class="icon-btn">
                    <i class="bi bi-telephone"></i>
                  </button>
                  <button class="icon-btn">
                    <i class="bi bi-camera-video"></i>
                  </button>
                  <button class="icon-btn">
                    <i class="bi bi-three-dots"></i>
                  </button>
                </div>
              </div>
              <div class="chat-messages-list" id="chatMessagesList">
                <!-- No messages yet -->
              </div>
              <form
                class="chat-input-row"
                id="chatInputForm"
                autocomplete="off">
                <input
                  type="text"
                  class="chat-input"
                  id="chatInputBox"
                  placeholder="Type your message..." />
                <button class="icon-btn" type="button" title="Attach file">
                  <i class="bi bi-paperclip"></i>
                </button>
                <button class="send-btn" type="submit">
                  <i class="bi bi-send"></i>
                </button>
              </form>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>

    // Sidebar navigation logic
    const navLinks = document.querySelectorAll(
      ".sidebar .nav-link[data-section]"
    );
    const sections = document.querySelectorAll(".dashboard-section");

    function showSection(sectionId) {
      sections.forEach((sec) => sec.classList.add("d-none"));
      document.getElementById(sectionId).classList.remove("d-none");
      document.getElementById(sectionId).classList.add("animate__fadeInUp");

      // Show/hide welcome message based on section
      const welcomeMessage = document.getElementById("welcome-message");
      if (sectionId === "overview-section") {
        welcomeMessage.style.display = "block";
      } else {
        welcomeMessage.style.display = "none";
      }
    }
    navLinks.forEach((link) => {
      link.addEventListener("click", function(e) {
        e.preventDefault();
        navLinks.forEach((l) => l.classList.remove("active"));
        this.classList.add("active");
        showSection(this.getAttribute("data-section"));
      });
    });
    // Show Overview by default
    showSection("overview-section");
    // Charts for Overview
    new Chart(
      document.getElementById("overviewWeightBPChart").getContext("2d"), {
        type: "line",
        data: {
          labels: ["Week 24", "Week 25", "Week 26", "Week 27", "Week 28"],
          datasets: [{
              label: "Weight (kg)",
              data: [63.5, 64, 64.5, 65, 65],
              borderColor: "#10b981",
              backgroundColor: "rgba(16,185,129,0.08)",
              tension: 0.4,
              fill: true,
              pointRadius: 5,
              pointBackgroundColor: "#10b981",
            },
            {
              label: "BP (mmHg)",
              data: [117, 118, 119, 118, 118],
              borderColor: "#1e3a8a",
              backgroundColor: "rgba(30,58,138,0.08)",
              tension: 0.4,
              fill: false,
              pointRadius: 5,
              pointBackgroundColor: "#1e3a8a",
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              display: true,
              labels: {
                color: "#1e3a8a",
                font: {
                  weight: "bold"
                }
              },
            },
          },
          scales: {
            x: {
              ticks: {
                color: "#1e3a8a"
              }
            },
            y: {
              ticks: {
                color: "#1e3a8a"
              }
            },
          },
        },
      }
    );
    new Chart(
      document.getElementById("overviewCheckupsChart").getContext("2d"), {
        type: "doughnut",
        data: {
          labels: ["Completed", "Upcoming", "Missed"],
          datasets: [{
            data: [8, 2, 1],
            backgroundColor: ["#10b981", "#f59e42", "#ef4444"],
            borderWidth: 2,
          }, ],
        },
        options: {
          cutout: "70%",
          plugins: {
            legend: {
              display: true,
              labels: {
                color: "#1e3a8a",
                font: {
                  weight: "bold"
                }
              },
            },
          },
        },
      }
    );
    // Charts for Health Records
    new Chart(document.getElementById("vitalsChart").getContext("2d"), {
      type: "line",
      data: {
        labels: ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5"],
        datasets: [{
            label: "Blood Pressure",
            data: [120, 122, 125, 121, 124],
            borderColor: "#1e3a8a",
            backgroundColor: "rgba(30,58,138,0.08)",
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: "#1e3a8a",
          },
          {
            label: "Weight (kg)",
            data: [60, 61, 61.5, 62, 62.5],
            borderColor: "#10b981",
            backgroundColor: "rgba(16,185,129,0.08)",
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: "#10b981",
          },
          {
            label: "Hemoglobin",
            data: [11, 11.2, 11.5, 11.3, 11.6],
            borderColor: "#a21caf",
            backgroundColor: "rgba(162,28,175,0.08)",
            tension: 0.4,
            fill: false,
            pointRadius: 5,
            pointBackgroundColor: "#a21caf",
          },
          {
            label: "Fetal Heart Rate",
            data: [140, 142, 139, 141, 143],
            borderColor: "#f59e42",
            backgroundColor: "rgba(245,158,66,0.08)",
            tension: 0.4,
            fill: false,
            pointRadius: 5,
            pointBackgroundColor: "#f59e42",
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: true,
            labels: {
              color: "#1e3a8a",
              font: {
                weight: "bold"
              }
            },
          },
        },
        scales: {
          x: {
            ticks: {
              color: "#1e3a8a"
            }
          },
          y: {
            ticks: {
              color: "#1e3a8a"
            }
          },
        },
      },
    });
    new Chart(document.getElementById("trendsChart").getContext("2d"), {
      type: "line",
      data: {
        labels: ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5"],
        datasets: [{
          label: "Energy",
          data: [80, 85, 90, 88, 92],
          borderColor: "#f59e42",
          backgroundColor: "rgba(245,158,66,0.08)",
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointBackgroundColor: "#f59e42",
        }, ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: true,
            labels: {
              color: "#1e3a8a",
              font: {
                weight: "bold"
              }
            },
          },
        },
        scales: {
          x: {
            ticks: {
              color: "#1e3a8a"
            }
          },
          y: {
            ticks: {
              color: "#1e3a8a"
            }
          },
        },
      },
    });
    // Profile dropdown logic
    const profileBtn = document.getElementById("profileBtn");
    const profileDropdown = document.getElementById("profileDropdown");

    profileBtn.addEventListener("click", function() {
      profileDropdown.classList.toggle("show");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function(e) {
      if (
        !profileBtn.contains(e.target) &&
        !profileDropdown.contains(e.target)
      ) {
        profileDropdown.classList.remove("show");
      }
    });

    function logout() {
      localStorage.removeItem("loggedIn");
      window.location.href = "login.php";
    }
    // Chat logic for new UI
    const chatInputForm = document.getElementById("chatInputForm");
    const chatInputBox = document.getElementById("chatInputBox");
    const chatMessagesList = document.getElementById("chatMessagesList");
    chatInputForm.onsubmit = function(e) {
      e.preventDefault();
      const msg = chatInputBox.value.trim();
      if (msg) {
        const bubble = document.createElement("div");
        bubble.className =
          "chat-bubble user animate__animated animate__fadeInUp";
        bubble.innerHTML = msg + '<span class="chat-time">Now</span>';
        chatMessagesList.appendChild(bubble);
        chatMessagesList.scrollTop = chatMessagesList.scrollHeight;
        chatInputBox.value = "";
        // Simulate doctor reply
        setTimeout(function() {
          const reply = document.createElement("div");
          reply.className =
            "chat-bubble doctor animate__animated animate__fadeInUp";
          reply.innerHTML =
            'Thank you for your message. I will get back to you soon.<span class="chat-time">Now</span>';
          chatMessagesList.appendChild(reply);
          chatMessagesList.scrollTop = chatMessagesList.scrollHeight;
        }, 1200);
      }
    };

    // --- Calendar Logic ---
    const calendarTable = document.getElementById("calendarTable");
    const calendarMonthLabel = document.getElementById("calendarMonthLabel");
    const calendarPrevBtn = document.getElementById("calendarPrevBtn");
    const calendarNextBtn = document.getElementById("calendarNextBtn");
    // Example appointments (should be dynamic in real app)
    const appointments = [{
        date: "2025-07-20",
        name: "Rina Akter",
        time: "10:00 AM"
      },
      {
        date: "2025-07-21",
        name: "Salma Begum",
        time: "11:00 AM"
      },
      {
        date: "2025-08-02",
        name: "Dr. Arefin",
        time: "9:00 AM"
      },
    ];
    let calendarDate = new Date(2025, 6, 1); // July 2025 (month is 0-indexed)
    function renderCalendar() {
      const year = calendarDate.getFullYear();
      const month = calendarDate.getMonth();
      const today = new Date();
      calendarMonthLabel.textContent = calendarDate.toLocaleString(
        "default", {
          month: "long",
          year: "numeric"
        }
      );
      // Find first day of week and number of days in month
      const firstDay = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const prevMonthDays = new Date(year, month, 0).getDate();
      let html = "<thead><tr>";
      ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].forEach(
        (d) => (html += `<th>${d}</th>`)
      );
      html += "</tr></thead><tbody>";
      let day = 1;
      let nextMonthDay = 1;
      for (let row = 0; row < 6; row++) {
        html += "<tr>";
        for (let col = 0; col < 7; col++) {
          let cellClass = "";
          let cellDay = "";
          let cellDate = "";
          let cellContent = "";
          let tooltip = "";
          if (row === 0 && col < firstDay) {
            // Previous month
            cellClass = "other-month";
            cellDay = prevMonthDays - firstDay + col + 1;
            cellDate = new Date(year, month - 1, cellDay);
          } else if (day > daysInMonth) {
            // Next month
            cellClass = "other-month";
            cellDay = nextMonthDay++;
            cellDate = new Date(year, month + 1, cellDay);
          } else {
            // Current month
            cellDay = day;
            cellDate = new Date(year, month, day);
            // Highlight today
            if (
              cellDate.getFullYear() === today.getFullYear() &&
              cellDate.getMonth() === today.getMonth() &&
              cellDate.getDate() === today.getDate()
            ) {
              cellClass = "today";
            }
            // Check for appointment
            const appt = appointments.find((a) => {
              const d = new Date(a.date);
              return (
                d.getFullYear() === year &&
                d.getMonth() === month &&
                d.getDate() === day
              );
            });
            if (appt) {
              cellClass += " appointment-day";
              tooltip = `<div class='calendar-tooltip'><b>${appt.name}</b><br>${appt.time}</div>`;
            }
            cellContent = `${day}${tooltip}`;
            day++;
          }
          if (!cellContent) cellContent = cellDay;
          html += `<td class="${cellClass.trim()}">${cellContent}</td>`;
        }
        html += "</tr>";
        if (day > daysInMonth && nextMonthDay > 7) break;
      }
      html += "</tbody>";
      calendarTable.innerHTML = html;
      // Click to show tooltip (for mobile)
      Array.from(
        calendarTable.querySelectorAll("td.appointment-day")
      ).forEach((td) => {
        td.addEventListener("click", function(e) {
          e.stopPropagation();
          calendarTable
            .querySelectorAll("td.appointment-day")
            .forEach((cell) => cell.classList.remove("active"));
          td.classList.add("active");
        });
      });
      document.addEventListener("click", function() {
        calendarTable
          .querySelectorAll("td.appointment-day")
          .forEach((cell) => cell.classList.remove("active"));
      });
    }
    calendarPrevBtn.onclick = function() {
      calendarDate.setMonth(calendarDate.getMonth() - 1);
      renderCalendar();
    };
    calendarNextBtn.onclick = function() {
      calendarDate.setMonth(calendarDate.getMonth() + 1);
      renderCalendar();
    };
    renderCalendar();

    // Custom Reminder Logic
    const addCustomReminderForm = document.getElementById(
      "addCustomReminderForm"
    );
    const customReminderList = document.getElementById("customReminderList");
    addCustomReminderForm.onsubmit = function(e) {
      e.preventDefault();
      const title = document
        .getElementById("customReminderTitle")
        .value.trim();
      const date = document.getElementById("customReminderDate").value;
      const time = document.getElementById("customReminderTime").value;
      if (title && date && time) {
        const item = document.createElement("div");
        item.className =
          "custom-reminder-item animate__animated animate__fadeInUp";
        item.innerHTML = `<span><b>${title}</b> &mdash; ${date} ${time}</span> <button class='delete-btn'>Delete</button>`;
        item.querySelector(".delete-btn").onclick = function() {
          item.remove();
        };
        customReminderList.appendChild(item);
        addCustomReminderForm.reset();
      }
    };

    // Initialize charts
    initializeCharts();

    // Emergency call button functionality
    const emergencyCallBtn = document.getElementById("emergencyCallBtn");
    const emergencyTooltip = document.getElementById("emergencyTooltip");

    emergencyCallBtn.addEventListener("click", function() {
      if (confirm("Do you want to make an emergency call to 999?")) {
        window.open("tel:999", "_self");
      }
    });

    // Show tooltip on hover
    emergencyCallBtn.addEventListener("mouseenter", function() {
      emergencyTooltip.style.opacity = "1";
    });

    emergencyCallBtn.addEventListener("mouseleave", function() {
      emergencyTooltip.style.opacity = "0";
    });
  </script>
</body>

</html>