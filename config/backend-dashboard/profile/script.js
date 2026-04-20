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

        // Format nomor telepon input
        document.addEventListener('DOMContentLoaded', function() {
            var phoneInput = document.querySelector('input[name="no_hp"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
        });