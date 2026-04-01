<?php
require 'init.php';
?>

<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Home") ?>
<body>
    <?php include Helpers::getFullPath("components", "header.php") ?>
    <main style="min-height: 200vh">
    </main>
    <?php include Helpers::getFullPath("components", "footer.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        "use strict";
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            if (window.scrollY > 50) {
                header.style.background = 'rgba(5, 5, 5, 0.95)';
                header.style.padding = '5px 0';
            } else {
                header.style.background = 'rgba(10, 10, 10, 0.7)';
                header.style.padding = '10px 0';
            }
        });
    </script>
</body>
</html>