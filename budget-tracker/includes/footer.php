</div> <!-- Close #page-content-wrapper -->
</div> <!-- Close #wrapper -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="js/app.js"></script>
    <script src="js/clock.js"></script>
    <script src="js/smooth-interactions.js"></script>
    <!-- Chat Widget -->
    <?php include 'includes/chat_widget.php'; ?>

    <script>
        // Logout Confirmation
        function confirmLogout(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Ready to Leave?',
                text: "Select 'Logout' below if you are ready to end your current session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
</body>

</html>
