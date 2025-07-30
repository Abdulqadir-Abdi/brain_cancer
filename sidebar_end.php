</div> <!-- End of content wrapper -->
</div> <!-- End of sidebar container -->

<!-- JavaScript for sidebar functionality -->
<script>
// Confirm delete account function
function confirmDelete() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        window.location.href = 'delete_account.php';
    }
}

// Auto-expand profile dropdown on hover (optional)
document.addEventListener('DOMContentLoaded', function() {
    const profileDropdown = document.querySelector('#profileDropdown');
    const profileLink = document.querySelector('[data-toggle="collapse"]');
    
    if (profileLink && profileDropdown) {
        profileLink.addEventListener('mouseenter', function() {
            profileDropdown.classList.add('show');
        });
        
        profileDropdown.addEventListener('mouseleave', function() {
            profileDropdown.classList.remove('show');
        });
    }
});
</script>