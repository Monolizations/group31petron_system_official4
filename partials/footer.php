
  </main>

  <style>
    .fixed-footer {
        position: fixed !important;
        bottom: 0 !important;
        left: 250px !important;
        width: calc(100% - 250px) !important;
        height: 40px !important;
        background-color: #ffffff !important;
        border-top: 1px solid #e0e0e0 !important;
        z-index: 9999 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 20px !important;
        font-size: 0.85em !important;
        color: #666666 !important;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;
    }
    
    /* Mobile footer - full width */
    @media (max-width: 991px) {
        .fixed-footer {
            left: 0 !important;
            width: 100% !important;
        }
    }
    
    /* Ensure footer is always visible */
    .fixed-footer * {
        pointer-events: auto !important;
    }
    
    .footer-content {
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
    }
    
    /* Override any conflicting styles */
    body {
        padding-bottom: 40px !important; /* Account for fixed footer */
    }
    
    main {
        padding-bottom: 60px !important; /* Account for fixed footer */
    }
</style>

  <!-- FIXED FOOTER -->
  <footer class="fixed-footer">
    <div class="footer-content">
      <div class="footer-center">
        <span>&copy; 2026 Petron Management System</span>
        <span id="footer-clock" style="margin-left: 20px;"></span>
      </div>
    </div>
  </footer>

  <div class="toast" id="toast"></div>
  <script src="../assets/js/app.js"></script>
</main>

  <script>
    function updateFooterClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        document.getElementById('footer-clock').innerHTML = '<i class="far fa-clock"></i> ' + now.toLocaleDateString('en-US', options);
    }
    setInterval(updateFooterClock, 1000);
    updateFooterClock();
  </script>
</body>
</html>
