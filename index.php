<?php
require 'init.php';
?>

<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Home") ?>
<body>
    <?php include Helpers::getFullPath("components", "header.php") ?>
    <main style="height: 200vh">
        <section id="main-image-div">
            test
        </section>
    </main>
    <?php include Helpers::getFullPath("components", "footer.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>