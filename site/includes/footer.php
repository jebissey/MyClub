<?php
require_once 'Globals.php';
?>    
        <footer class="bg-dark text-white mt-auto h-25">
            <div class="container">
                <p class="mb-0"><?php echo VERSION; ?> &#169; JEB <a href="<?php echo "LegalNotices.php" ?>">Mentions légales</a></p>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var width = screen.width;
                var height = screen.height;
                document.cookie = "screen_resolution=" + width + "x" + height;
            });
        </script>
    </body>
</html>

<?php
require_once __DIR__ . '/afterFooter.php';
?>