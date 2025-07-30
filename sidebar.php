<?php
// Sidebar Component - Include this file in any page that needs the sidebar
// Make sure $role, $fullname, $email, and $profileImage variables are available

// Default values if not set
$role = $role ?? 'user';
$fullname = $fullname ?? 'User Name';
$email = $email ?? 'user@example.com';
$profileImage = $profileImage ?? 'default-avatar.png';
?>

<!-- Sidebar CSS -->
<style>
.sidebar {
  width: 280px;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-light) 100%);
  color: white;
  z-index: 1030;
  transition: all 0.3s ease;
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
  overflow-y: auto;
}

.sidebar-header {
  padding: 2rem 1.5rem 1.5rem;
  text-align: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.05);
}

.sidebar-brand {
  color: white;
  text-decoration: none;
  font-weight: 700;
  font-size: 1.3rem;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.sidebar-brand:hover {
  color: white;
  text-decoration: none;
  transform: scale(1.02);
  transition: transform 0.2s ease;
}

.sidebar-nav {
  padding: 1.5rem 0;
}

.nav-item {
  margin: 0.25rem 1rem;
}

.nav-link {
  color: rgba(255, 255, 255, 0.8) !important;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
  position: relative;
  overflow: hidden;
}

.nav-link:hover {
  color: white !important;
  background: rgba(255, 255, 255, 0.1);
  transform: translateX(5px);
  text-decoration: none;
}

.nav-link.active {
  color: white !important;
  background: rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  font-weight: 600;
}

.nav-link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 4px;
  background: white;
  border-radius: 0 2px 2px 0;
}

.nav-link i {
  width: 20px;
  text-align: center;
  font-size: 1.1rem;
}

.profile-section {
  margin-top: auto;
  padding: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.05);
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
}

.profile-header:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
}

.profile-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid rgba(255, 255, 255, 0.3);
}

.profile-info {
  flex: 1;
  min-width: 0;
}

.profile-name {
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-role {
  font-size: 0.85rem;
  opacity: 0.8;
  text-transform: capitalize;
}

.profile-dropdown {
  margin-top: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.profile-dropdown .nav-link {
  color: rgba(255, 255, 255, 0.8) !important;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.9rem;
  margin: 0.25rem 0;
}

.profile-dropdown .nav-link:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white !important;
}

.profile-dropdown .nav-link.text-danger:hover {
  background: rgba(220, 53, 69, 0.2);
}

.content-wrapper {
  margin-left: 280px;
  width: calc(100% - 280px);
  min-height: 100vh;
  transition: all 0.3s ease;
}

.sidebar-toggle {
  display: none;
  position: fixed;
  top: 1rem;
  left: 1rem;
  z-index: 1040;
  background: var(--primary-color);
  border: none;
  color: white;
  padding: 0.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    width: 100%;
    max-width: 300px;
  }
  
  .sidebar.show {
    transform: translateX(0);
  }
  
  .content-wrapper {
    margin-left: 0;
    width: 100%;
  }
  
  .sidebar-toggle {
    display: block;
  }
  
  .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1025;
    display: none;
  }
  
  .sidebar-overlay.show {
    display: block;
  }
}

/* Scrollbar Styling */
.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}
</style>

<!-- Sidebar Toggle Button (Mobile) -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
  <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
  <!-- Sidebar Header -->
  <div class="sidebar-header">
    <a href="home.php" class="sidebar-brand">
      <i class="fas fa-brain"></i>
      <span>Brain Cancer Detection</span>
    </a>
  </div>

  <!-- Navigation Menu -->
  <ul class="nav flex-column sidebar-nav">
    <!-- Dashboard/My Users Link - Role-based -->
    <?php if ($role !== 'admin' && $role !== 'small-admin' && $role !== 'small-admi'): ?>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analyze.php' ? 'active' : '' ?>" href="analyze.php">
          <i class="fas fa-chart-bar"></i>
          <span>Dashboards</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
          <i class="fas fa-users-cog"></i>
          <span>Dashboards</span>
        </a>
      </li>
    <?php elseif ($role === 'small-admin' || $role === 'small-admi'): ?>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
          <i class="fas fa-user-friends"></i>
          <span>My Users</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- Prediction Link -->
    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'predict.php' ? 'active' : '' ?>" href="predict.php">
        <i class="fas fa-microscope"></i>
        <span>Predictions</span>
      </a>  
    </li>

    <!-- Report Link -->
    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>" href="report.php">
        <i class="fas fa-file-medical"></i>
        <span>Report</span>
      </a>
    </li>

    <!-- Admin-only Links -->
    <?php if ($role === 'admin'): ?>
      <li class="nav-item">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'edit_user.php' ? 'active' : '' ?>" href="edit_user.php">
          <i class="fas fa-user-edit"></i>
          <span>Manage Users</span>
        </a>
      </li>
    <?php endif; ?>
  </ul>

  <!-- Profile Section -->
  <div class="profile-section">
    <div class="profile-header" onclick="toggleProfileDropdown()">
      <?php if (!empty($profileImage) && $profileImage !== 'default-avatar.png'): ?>
        <img src="<?= htmlspecialchars($profileImage); ?>" class="profile-avatar" alt="User">
      <?php else: ?>
        <div class="profile-avatar d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.2);">
          <i class="fas fa-user text-white"></i>
        </div>
      <?php endif; ?>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($fullname); ?></div>
        <div class="profile-role"><?= htmlspecialchars(ucfirst($role)); ?></div>
      </div>
      <i class="fas fa-chevron-down" id="profileChevron"></i>
    </div>

    <div class="profile-dropdown" id="profileDropdown" style="display: none;">
      <div class="text-center mb-3">
        <small class="opacity-75"><?= htmlspecialchars($email); ?></small>
      </div>
      
      <a class="nav-link" href="update_profile.php">
        <i class="fas fa-user-edit"></i>
        <span>Update Profile</span>
      </a>
      
      <a class="nav-link" href="upload_profile_image.php">
        <i class="fas fa-camera"></i>
        <span>Upload Image</span>
      </a>
      
      <a class="nav-link text-danger" href="#" onclick="confirmDelete()">
        <i class="fas fa-user-slash"></i>
        <span>Delete Account</span>
      </a>
      
      <a class="nav-link text-danger" href="logout.php">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>
</nav>

<!-- Content wrapper -->
<div class="content-wrapper" id="content">
  <!-- Your page content goes here -->

<!-- Sidebar JavaScript -->
<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  const content = document.getElementById('content');
  
  sidebar.classList.toggle('show');
  overlay.classList.toggle('show');
  
  if (window.innerWidth <= 768) {
    if (sidebar.classList.contains('show')) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'auto';
    }
  }
}

function toggleProfileDropdown() {
  const dropdown = document.getElementById('profileDropdown');
  const chevron = document.getElementById('profileChevron');
  
  if (dropdown.style.display === 'none') {
    dropdown.style.display = 'block';
    chevron.style.transform = 'rotate(180deg)';
  } else {
    dropdown.style.display = 'none';
    chevron.style.transform = 'rotate(0deg)';
  }
}

function confirmDelete() {
  if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
    window.location.href = 'delete_account.php';
  }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('.sidebar-toggle');
  
  if (window.innerWidth <= 768 && 
      !sidebar.contains(event.target) && 
      !toggle.contains(event.target) &&
      sidebar.classList.contains('show')) {
    toggleSidebar();
  }
});

// Handle window resize
window.addEventListener('resize', function() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (window.innerWidth > 768) {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    document.body.style.overflow = 'auto';
  }
});
</script>