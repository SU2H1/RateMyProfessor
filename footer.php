    </div><!-- close main-container -->
    
    <footer>
        <div class="footer-container">
            <div class="footer-info">
                <h3>Gaku Neko</h3>
                <p>A platform for students to rate and review courses and professors.</p>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="search.php">Search</a></li>
                    <li><a href="tipsandtricks.php">Tips & Tricks</a></li>
                </ul>
            </div>
            
            <div class="footer-legal">
                <p>&copy; <?php echo date("Y"); ?> Gaku Neko. All rights reserved.</p>
                <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
            </div>
        </div>
    </footer>
    
    <style>
        /* Footer Styles */
        footer {
            background-color: #1e3a8a;
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .footer-info h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .footer-links h4 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .footer-links ul {
            list-style: none;
        }
        
        .footer-links ul li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .footer-legal {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .footer-legal a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .footer-legal a:hover {
            color: white;
            text-decoration: underline;
        }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // User dropdown toggle
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', function() {
                userDropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenuBtn.contains(event.target) && !userDropdown.contains(event.target)) {
                    userDropdown.classList.remove('show');
                }
            });
        }
        
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mainNav = document.getElementById('main-nav');
        
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('show');
                
                // Change icon based on menu state
                const icon = mobileMenuToggle.querySelector('i');
                if (mainNav.classList.contains('show')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
    });
    </script>
</body>
</html>