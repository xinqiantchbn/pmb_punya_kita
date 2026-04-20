// Password Strength Checker
        const passwordInput = document.getElementById('new_password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        const passwordMatchSuccess = document.getElementById('passwordMatchSuccess');
        
        // Requirements elements
        const reqLength = document.getElementById('reqLength');
        const reqUppercase = document.getElementById('reqUppercase');
        const reqLowercase = document.getElementById('reqLowercase');
        const reqNumber = document.getElementById('reqNumber');
        const reqSpecial = document.getElementById('reqSpecial');
        
        // Check password strength
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check length
                if (password.length >= 8) {
                    strength += 20;
                    updateRequirement(reqLength, true);
                } else {
                    updateRequirement(reqLength, false);
                }
                
                // Check uppercase
                if (/[A-Z]/.test(password)) {
                    strength += 20;
                    updateRequirement(reqUppercase, true);
                } else {
                    updateRequirement(reqUppercase, false);
                }
                
                // Check lowercase
                if (/[a-z]/.test(password)) {
                    strength += 20;
                    updateRequirement(reqLowercase, true);
                } else {
                    updateRequirement(reqLowercase, false);
                }
                
                // Check numbers
                if (/[0-9]/.test(password)) {
                    strength += 20;
                    updateRequirement(reqNumber, true);
                } else {
                    updateRequirement(reqNumber, false);
                }
                
                // Check special characters
                if (/[^A-Za-z0-9]/.test(password)) {
                    strength += 20;
                    updateRequirement(reqSpecial, true);
                } else {
                    updateRequirement(reqSpecial, false);
                }
                
                // Update strength bar and text
                strengthFill.style.width = strength + '%';
                
                if (strength < 40) {
                    strengthFill.style.backgroundColor = '#dc3545';
                    strengthText.textContent = 'Password Lemah';
                    strengthText.style.color = '#dc3545';
                } else if (strength < 80) {
                    strengthFill.style.backgroundColor = '#ffc107';
                    strengthText.textContent = 'Password Sedang';
                    strengthText.style.color = '#ffc107';
                } else {
                    strengthFill.style.backgroundColor = '#28a745';
                    strengthText.textContent = 'Password Kuat';
                    strengthText.style.color = '#28a745';
                }
                
                // Check password match
                checkPasswordMatch();
            });
        }
        
        // Check password confirmation
        if (confirmPassword) {
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }
        
        function checkPasswordMatch() {
            if (!passwordInput || !confirmPassword) return;
            
            const password = passwordInput.value;
            const confirm = confirmPassword.value;
            
            if (confirm === '') {
                passwordMatch.style.display = 'none';
                passwordMatchSuccess.style.display = 'none';
                return;
            }
            
            if (password === confirm) {
                passwordMatch.style.display = 'none';
                passwordMatchSuccess.style.display = 'inline';
            } else {
                passwordMatch.style.display = 'inline';
                passwordMatchSuccess.style.display = 'none';
            }
        }
        
        function updateRequirement(element, isValid) {
            if (element) {
                if (isValid) {
                    element.classList.remove('invalid');
                    element.classList.add('valid');
                    element.querySelector('i').className = 'fas fa-check';
                } else {
                    element.classList.remove('valid');
                    element.classList.add('invalid');
                    element.querySelector('i').className = 'fas fa-times';
                }
            }
        }
        
        // Form validation untuk Ubah Password
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('current_password')?.value;
                const newPassword = document.getElementById('new_password')?.value;
                const confirmPass = document.getElementById('confirm_password')?.value;
                
                // Check current password
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Password saat ini harus diisi!');
                    return;
                }
                
                // Check password match
                if (newPassword !== confirmPass) {
                    e.preventDefault();
                    alert('Password baru dan konfirmasi password tidak cocok!');
                    return;
                }
                
                // Check password strength
                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('Password minimal 8 karakter!');
                    return;
                }
                
                // Show confirmation
                if (!confirm('Apakah Anda yakin ingin mengubah password?')) {
                    e.preventDefault();
                }
            });
        }
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 5px 20px rgba(183, 28, 28, 0.2)';
            } else {
                navbar.style.boxShadow = '0 2px 15px rgba(198, 40, 40, 0.2)';
            }
        });
        
        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Toggle password visibility dengan eye icon
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            
            const button = input.parentElement.querySelector('.password-toggle');
            if (!button) return;
            
            const icon = button.querySelector('i');
            if (!icon) return;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }