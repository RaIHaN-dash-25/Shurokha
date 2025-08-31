<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}
include 'db.php';
$doctor_id = $_SESSION['user_id'];
// Doctor info
$doctor = $conn->query("SELECT u.full_name, d.specialization FROM users u JOIN doctor_profiles d ON u.id = d.user_id WHERE u.id = $doctor_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Shurokha</title>
    <link rel="icon" type="image/png" href="logo-transparent.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .logo-circle {
            width: 40px;
            height: 40px;
            background: #FF6633;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(255, 102, 51, 0.3);
        }

        .sidebar-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(45deg, #3498db, #e74c3c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.5rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: width 0.5s ease;
        }

        .nav-link:hover::before {
            width: 100%;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            background: var(--light-bg);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: url('logo-transparent.png') no-repeat center center;
            background-size: contain;
            opacity: 0.03;
            z-index: 0;
        }

        .main-content>* {
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo-circle {
            width: 35px;
            height: 35px;
            background: #FF6633;
            border-radius: 50%;
            box-shadow: 0 3px 10px rgba(255, 102, 51, 0.3);
        }

        .header-logo-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .welcome-text h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2.2rem;
        }

        .welcome-text p {
            color: #666;
            font-size: 1.1rem;
            margin: 0;
        }

        .profile-section {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .profile-btn {
            background: none;
            border: none;
            outline: none;
            font-size: 2rem;
            color: var(--primary-color);
            cursor: pointer;
            border-radius: 50%;
            transition: background 0.2s;
            padding: 0.2rem 0.4rem;
        }

        .profile-btn:hover {
            background: #e0e7ff;
        }

        .profile-dropdown {
            position: fixed !important;
            top: 80px !important;
            right: 30px !important;
            background: #fff !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12) !important;
            padding: 1rem 0 !important;
            min-width: 200px !important;
            z-index: 2147483647 !important;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            border: 1px solid #e0e7ff !important;
        }

        .profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown .dropdown-item {
            padding: 0.7rem 1.2rem;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
            font-size: 0.95rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .profile-dropdown .dropdown-item:hover {
            background: #e0e7ff;
            color: var(--accent-color);
        }

        .profile-dropdown .dropdown-divider {
            height: 1px;
            background: #e0e7ff;
            margin: 0.5rem 0;
        }

        .profile-dropdown .profile-info {
            padding: 0.7rem 1.2rem;
            border-bottom: 1px solid #e0e7ff;
            margin-bottom: 0.5rem;
        }

        .profile-dropdown .profile-info .name {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1rem;
        }

        .profile-dropdown .profile-info .role {
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        /* Section Styles */
        .section {
            display: none;
            animation: fadeInUp 0.5s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid var(--secondary-color);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(231, 76, 60, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card h3 {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #666;
            font-size: 1rem;
            margin: 0;
            font-weight: 500;
        }

        .stat-card i {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            color: rgba(52, 152, 219, 0.2);
        }

        /* Alerts Section */
        .alerts-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .alerts-section h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .alert-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--accent-color);
        }

        .alert-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-item.urgent {
            background: rgba(231, 76, 60, 0.1);
            border-left-color: var(--accent-color);
        }

        .alert-item.general {
            background: rgba(39, 174, 96, 0.1);
            border-left-color: var(--success-color);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .alert-item.urgent .alert-icon {
            background: var(--accent-color);
            color: white;
        }

        .alert-item.general .alert-icon {
            background: var(--success-color);
            color: white;
        }

        .alert-content h5 {
            margin: 0;
            font-weight: 600;
            color: var(--primary-color);
        }

        .alert-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-container h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .table {
            margin: 0;
        }

        .table th {
            background: var(--light-bg);
            color: var(--primary-color);
            font-weight: 600;
            border: none;
            padding: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 1rem;
            border: none;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            transition: all 0.3s ease;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.05);
            transform: scale(1.01);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pregnant {
            background: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }

        .status-postpartum {
            background: rgba(39, 174, 96, 0.2);
            color: var(--success-color);
        }

        .status-confirmed {
            background: rgba(39, 174, 96, 0.2);
            color: var(--success-color);
        }

        .status-pending {
            background: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: var(--secondary-color);
            color: white;
        }

        .btn-view:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-details {
            background: var(--success-color);
            color: white;
        }

        .btn-details:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-confirm {
            background: var(--warning-color);
            color: white;
        }

        .btn-confirm:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }

        .btn-update {
            background: var(--primary-color);
            color: white;
        }

        .btn-update:hover {
            background: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        /* Messages Section - Matching Mother Dashboard */
        .messages-container {
            display: flex;
            height: 600px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .contacts-panel {
            width: 300px;
            background: var(--light-bg);
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .contacts-header {
            padding: 1.5rem;
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .contact-list {
            flex: 1;
            overflow-y: auto;
        }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            background: rgba(52, 152, 219, 0.1);
        }

        .contact-item.active {
            background: var(--secondary-color);
            color: white;
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 1rem;
            font-size: 1.1rem;
        }

        .contact-info h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .contact-info p {
            margin: 0;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .chat-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1.5rem;
            background: white;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header h5 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .chat-action-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            background: var(--light-bg);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chat-action-btn:hover {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.1);
        }

        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
        }

        .empty-chat i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-chat h5 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .chat-input {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #eee;
        }

        .input-group {
            display: flex;
            gap: 0.5rem;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-send {
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-send:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        .btn-attach {
            background: var(--light-bg);
            color: var(--primary-color);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-attach:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .messages-container {
                flex-direction: column;
                height: auto;
            }

            .contacts-panel {
                width: 100%;
                height: 300px;
            }
        }
    </style>
</head>

<body>
    <?php
    // session_start() removed (duplicate)
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
        header('Location: login.php');
        exit;
    }
    include 'db.php';
    $doctor_id = $_SESSION['user_id'];
    // Doctor info
    $doctor = $conn->query("SELECT u.full_name, d.specialization FROM users u JOIN doctor_profiles d ON u.id = d.user_id WHERE u.id = $doctor_id")->fetch_assoc();
    ?>
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
                    <a href="#" class="nav-link" data-section="patients">
                        <i class="bi bi-people"></i>
                        Patient List
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="appointments">
                        <i class="bi bi-calendar-check"></i>
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="messages">
                        <i class="bi bi-chat-dots"></i>
                        Messages
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="records">
                        <i class="bi bi-file-medical"></i>
                        Medical Records
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-text" id="welcome-message">
                    <h1>Welcome, <?php echo htmlspecialchars($doctor['full_name'] ?? 'Doctor'); ?></h1>
                    <p>Specialization: <?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?></p>
                </div>
                <div class="profile-section">
                    <button class="profile-btn" id="profileBtn" aria-label="Profile">
                        <i class="bi bi-person-circle"></i>
                    </button>
                </div>
            </div>

            <!-- Profile Dropdown (Outside of profile-section) -->
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-info">
                    <div class="name"><?php echo htmlspecialchars($doctor['full_name'] ?? 'Doctor'); ?></div>
                    <div class="role"><?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?></div>
                </div>
                <button class="dropdown-item">
                    <i class="bi bi-person"></i>Profile Details
                </button>
                <button class="dropdown-item">
                    <i class="bi bi-pencil"></i>Update Profile
                </button>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item" onclick="logout()">
                    <i class="bi bi-box-arrow-right"></i>Logout
                </button>
            </div>

            <!-- Overview Section -->
            <div class="section active" id="overview">
                <div class="stats-grid">
                    <?php
                    // Total Patients
                    $totalPatients = $conn->query("SELECT COUNT(DISTINCT mother_user_id) as cnt FROM appointments WHERE doctor_user_id=$doctor_id")->fetch_assoc()['cnt'];
                    // Today's Appointments
                    $today = date('Y-m-d');
                    $todaysAppointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_user_id=$doctor_id AND DATE(scheduled_at)='$today'")->fetch_assoc()['cnt'];
                    // Unread Messages
                    $unreadMessages = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE receiver_user_id=$doctor_id AND read_at IS NULL")->fetch_assoc()['cnt'];
                    // High-Risk Cases (example: mothers with BP > 140/90 in health_records)
                    $highRisk = $conn->query("SELECT COUNT(DISTINCT mother_user_id) as cnt FROM health_records WHERE blood_pressure_systolic > 140 OR blood_pressure_diastolic > 90")->fetch_assoc()['cnt'];
                    ?>
                    <div class="stat-card">
                        <i class="bi bi-people"></i>
                        <h3><?php echo $totalPatients; ?></h3>
                        <p>Total Patients</p>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-calendar-check"></i>
                        <h3><?php echo $todaysAppointments; ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-chat-dots"></i>
                        <h3><?php echo $unreadMessages; ?></h3>
                        <p>Unread Messages</p>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-exclamation-triangle"></i>
                        <h3><?php echo $highRisk; ?></h3>
                        <p>High-Risk Cases</p>
                    </div>
                </div>

                <div class="alerts-section">
                    <h3>Alerts</h3>
                    <?php
                    // Example: show mothers with high BP
                    $alerts = $conn->query("SELECT u.full_name, m.id, h.blood_pressure_systolic, h.blood_pressure_diastolic FROM health_records h JOIN mother_profiles m ON h.mother_user_id = m.user_id JOIN users u ON m.user_id = u.id WHERE (h.blood_pressure_systolic > 140 OR h.blood_pressure_diastolic > 90) ORDER BY h.record_date DESC LIMIT 3");
                    if ($alerts && $alerts->num_rows > 0) {
                        while ($a = $alerts->fetch_assoc()) {
                            echo '<div class="alert-item urgent"><div class="alert-icon"><i class="bi bi-exclamation-triangle"></i></div><div class="alert-content"><h5>' . htmlspecialchars($a['full_name']) . ' - High BP (' . $a['blood_pressure_systolic'] . '/' . $a['blood_pressure_diastolic'] . ')</h5><p>Urgent attention required</p></div></div>';
                        }
                    } else {
                        echo '<div class="alert-item general"><div class="alert-icon"><i class="bi bi-file-earmark-text"></i></div><div class="alert-content"><h5>No urgent alerts</h5><p>All patients stable</p></div></div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Patient List Section -->
            <div class="section" id="patients">
                <!-- Pregnant Patients -->
                <div class="table-container">
                    <h3><i class="bi bi-heart-fill text-danger me-2"></i>Pregnant Patients</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Week</th>
                                    <th>Last Visit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pregnant = $conn->query("SELECT u.full_name, m.phone, m.due_date, m.user_id FROM mother_profiles m JOIN users u ON m.user_id = u.id WHERE m.due_date >= CURDATE() AND m.user_id IN (SELECT mother_user_id FROM appointments WHERE doctor_user_id=$doctor_id)");
                                if ($pregnant && $pregnant->num_rows > 0) {
                                    while ($row = $pregnant->fetch_assoc()) {
                                        $weeks = round((strtotime($row['due_date']) - time()) / (7 * 24 * 60 * 60));
                                        echo '<tr><td>' . htmlspecialchars($row['full_name']) . '</td><td>' . htmlspecialchars($row['phone']) . '</td><td>' . $weeks . '</td><td>' . $row['due_date'] . '</td><td><a href="#" class="btn-action btn-view">View</a></td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No pregnant patients found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Postpartum Patients -->
                <div class="table-container">
                    <h3><i class="bi bi-baby text-info me-2"></i>Postpartum Patients</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Baby Age</th>
                                    <th>Last Visit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $postpartum = $conn->query("SELECT u.full_name, m.phone, m.due_date, m.user_id FROM mother_profiles m JOIN users u ON m.user_id = u.id WHERE m.due_date < CURDATE() AND m.user_id IN (SELECT mother_user_id FROM appointments WHERE doctor_user_id=$doctor_id)");
                                if ($postpartum && $postpartum->num_rows > 0) {
                                    while ($row = $postpartum->fetch_assoc()) {
                                        $baby_age = round((time() - strtotime($row['due_date'])) / (7 * 24 * 60 * 60));
                                        echo '<tr><td>' . htmlspecialchars($row['full_name']) . '</td><td>' . htmlspecialchars($row['phone']) . '</td><td>' . $baby_age . ' weeks</td><td>' . $row['due_date'] . '</td><td><a href="#" class="btn-action btn-view">View</a></td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No postpartum patients found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Appointments Section -->
            <div class="section" id="appointments">
                <!-- Confirmed Appointments -->
                <div class="table-container">
                    <h3><i class="bi bi-check-circle-fill text-success me-2"></i>Confirmed Appointments</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Phone</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $confirmed = $conn->query("SELECT a.*, u.full_name, m.phone FROM appointments a JOIN users u ON a.mother_user_id = u.id JOIN mother_profiles m ON m.user_id = u.id WHERE a.doctor_user_id=$doctor_id AND a.status='scheduled' ORDER BY a.scheduled_at ASC");
                                if ($confirmed && $confirmed->num_rows > 0) {
                                    while ($row = $confirmed->fetch_assoc()) {
                                        $date = date('M d', strtotime($row['scheduled_at']));
                                        $time = date('h:i A', strtotime($row['scheduled_at']));
                                        echo '<tr><td>' . $date . '</td><td>' . $time . '</td><td>' . htmlspecialchars($row['full_name']) . '</td><td>' . htmlspecialchars($row['phone']) . '</td><td><a href="#" class="btn-action btn-details">Details</a></td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No confirmed appointments.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pending Appointments -->
                <div class="table-container">
                    <h3><i class="bi bi-clock-fill text-warning me-2"></i>Pending Appointments</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Phone</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pending = $conn->query("SELECT a.*, u.full_name, m.phone FROM appointments a JOIN users u ON a.mother_user_id = u.id JOIN mother_profiles m ON m.user_id = u.id WHERE a.doctor_user_id=$doctor_id AND a.status='scheduled' AND a.scheduled_at > NOW() ORDER BY a.scheduled_at ASC");
                                if ($pending && $pending->num_rows > 0) {
                                    while ($row = $pending->fetch_assoc()) {
                                        $date = date('M d', strtotime($row['scheduled_at']));
                                        $time = date('h:i A', strtotime($row['scheduled_at']));
                                        echo '<tr><td>' . $date . '</td><td>' . $time . '</td><td>' . htmlspecialchars($row['full_name']) . '</td><td>' . htmlspecialchars($row['phone']) . '</td><td><a href="#" class="btn-action btn-confirm">Confirm</a></td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No pending appointments.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Messages Section -->
            <div class="section" id="messages">
                <div class="messages-container">
                    <div class="contacts-panel">
                        <div class="contacts-header">
                            <i class="bi bi-chat-dots me-2"></i>Messages
                        </div>
                        <div class="contact-list">
                            <?php
                            $contacts = $conn->query("SELECT DISTINCT u.id, u.full_name, m.phone FROM users u JOIN mother_profiles m ON u.id = m.user_id JOIN appointments a ON a.mother_user_id = u.id WHERE a.doctor_user_id = $doctor_id");
                            if ($contacts && $contacts->num_rows > 0) {
                                while ($row = $contacts->fetch_assoc()) {
                                    $initial = strtoupper(substr($row['full_name'], 0, 1));
                                    echo '<div class="contact-item" data-contact="' . $row['id'] . '"><div class="contact-avatar">' . $initial . '</div><div class="contact-info"><h6>' . htmlspecialchars($row['full_name']) . '</h6><p>' . htmlspecialchars($row['phone']) . '</p></div></div>';
                                }
                            } else {
                                echo '<div class="contact-item"><div class="contact-avatar">?</div><div class="contact-info"><h6>No contacts</h6><p>-</p></div></div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="chat-panel">
                        <div class="chat-header">
                            <h5>Select a contact to start messaging</h5>
                            <div class="chat-actions">
                                <button class="chat-action-btn">
                                    <i class="bi bi-search"></i>
                                </button>
                                <button class="chat-action-btn">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chat-messages">
                            <div class="empty-chat">
                                <i class="bi bi-chat-dots"></i>
                                <h5>No messages yet</h5>
                                <p>Start a conversation with a patient</p>
                            </div>
                        </div>
                        <div class="chat-input">
                            <div class="input-group">
                                <button class="btn-attach">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <input type="text" class="form-control" placeholder="Type your message...">
                                <button class="btn-send">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Records Section -->
            <div class="section" id="records">
                <div class="table-container">
                    <h3>Medical Records</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Last Visit</th>
                                    <th>Vitals</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $records = $conn->query("SELECT u.full_name, h.record_date, h.blood_pressure_systolic, h.blood_pressure_diastolic, h.notes FROM health_records h JOIN users u ON h.mother_user_id = u.id WHERE h.mother_user_id IN (SELECT mother_user_id FROM appointments WHERE doctor_user_id=$doctor_id) ORDER BY h.record_date DESC LIMIT 20");
                                if ($records && $records->num_rows > 0) {
                                    while ($row = $records->fetch_assoc()) {
                                        $vitals = 'BP: ' . $row['blood_pressure_systolic'] . '/' . $row['blood_pressure_diastolic'];
                                        echo '<tr><td>' . htmlspecialchars($row['full_name']) . '</td><td>' . $row['record_date'] . '</td><td>' . $vitals . '</td><td>' . htmlspecialchars($row['notes']) . '</td><td><a href="#" class="btn-action btn-update">Update</a></td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No medical records found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation functionality
            const navLinks = document.querySelectorAll('.nav-link');
            const sections = document.querySelectorAll('.section');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remove active class from all links and sections
                    navLinks.forEach(l => l.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));

                    // Add active class to clicked link
                    this.classList.add('active');

                    // Show corresponding section
                    const sectionId = this.getAttribute('data-section');
                    document.getElementById(sectionId).classList.add('active');

                    // Show/hide welcome message based on section
                    const welcomeMessage = document.getElementById('welcome-message');
                    if (sectionId === 'overview') {
                        welcomeMessage.style.display = 'block';
                    } else {
                        welcomeMessage.style.display = 'none';
                    }
                });
            });

            // Profile dropdown functionality
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
                });
            } else {
                console.error('Profile elements not found');
            }

            // Contact selection in messages
            const contactItems = document.querySelectorAll('.contact-item');
            contactItems.forEach(item => {
                item.addEventListener('click', function() {
                    contactItems.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');

                    // Update chat header
                    const contactName = this.querySelector('h6').textContent;
                    document.querySelector('.chat-header h5').textContent = contactName;

                    // Clear empty chat message
                    const emptyChat = document.querySelector('.empty-chat');
                    if (emptyChat) {
                        emptyChat.innerHTML = `
                            <i class="bi bi-chat-dots"></i>
                            <h5>No messages yet</h5>
                            <p>Start a conversation with ${contactName}</p>
                        `;
                    }
                });
            });

            // Send message functionality
            const sendBtn = document.querySelector('.btn-send');
            const messageInput = document.querySelector('.form-control');

            sendBtn.addEventListener('click', function() {
                const message = messageInput.value.trim();
                if (message) {
                    // Here you would typically send the message
                    console.log('Sending message:', message);
                    messageInput.value = '';
                }
            });

            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendBtn.click();
                }
            });
        });

        function logout() {
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('userRole');
            window.location.href = 'login.php';
        }
    </script>
</body>

</html>