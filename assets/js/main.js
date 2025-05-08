// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-toggled');
            document.querySelector('.sidebar').classList.toggle('toggled');
        });
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle file uploads
    const fileInputs = document.querySelectorAll('.custom-file-input');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Choose file';
            const label = this.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = fileName;
            }
        });
    });
    
    // Course code generator
    const generateCourseCodeBtn = document.getElementById('generateCourseCode');
    if (generateCourseCodeBtn) {
        generateCourseCodeBtn.addEventListener('click', function() {
            const courseNameInput = document.getElementById('course_name');
            const courseCodeInput = document.getElementById('course_code');
            
            if (courseNameInput && courseCodeInput) {
                const courseName = courseNameInput.value.trim();
                if (courseName) {
                    // Generate a course code based on the course name
                    const words = courseName.split(' ');
                    let code = '';
                    
                    // Take first 2-3 characters from first few words
                    for (let i = 0; i < Math.min(2, words.length); i++) {
                        if (words[i].length > 0) {
                            code += words[i].substring(0, Math.min(3, words[i].length)).toUpperCase();
                        }
                    }
                    
                    // Add random numbers to make it unique
                    code += Math.floor(100 + Math.random() * 900);
                    
                    courseCodeInput.value = code;
                }
            }
        });
    }
    
    // Confirm deletion
    const deleteButtons = document.querySelectorAll('.confirm-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Assignment submission form validation
    const submissionForm = document.getElementById('assignmentSubmissionForm');
    if (submissionForm) {
        submissionForm.addEventListener('submit', function(e) {
            const contentInput = document.getElementById('submission_content');
            const fileInput = document.getElementById('submission_file');
            
            if ((!contentInput || contentInput.value.trim() === '') && 
                (!fileInput || fileInput.files.length === 0)) {
                e.preventDefault();
                alert('Please provide submission content or upload a file.');
            }
        });
    }
    
    // Due date highlighting
    const dueDates = document.querySelectorAll('.due-date');
    dueDates.forEach(dateElement => {
        const dueDate = new Date(dateElement.getAttribute('data-due-date'));
        const now = new Date();
        const diffTime = dueDate - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) {
            dateElement.classList.add('overdue');
        } else if (diffDays <= 2) {
            dateElement.classList.add('due-soon');
        }
    });
    
    // Toggle password visibility
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.querySelector(this.getAttribute('data-target'));
            if (input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                // Toggle icon
                this.innerHTML = type === 'password' 
                    ? '<i class="fa fa-eye"></i>' 
                    : '<i class="fa fa-eye-slash"></i>';
            }
        });
    });
});