<?php

declare(strict_types=1);

require_once __DIR__ . '/../../autoload.php';

$typeParam = $_GET['type'] ?? 'SERVER_ERROR';
$customMessage = isset($_GET['message']) ? trim($_GET['message']) : null;
$redirectTo = isset($_GET['redirect']) ? trim($_GET['redirect']) : null;

$errorType = ErrorType::tryFrom($typeParam) ?? ErrorType::SERVER_ERROR;

http_response_code($errorType->httpCode());

$pageTitle = $errorType->label();

?>
<!doctype html>
<html lang="sr">
<?php Helpers::renderHead($pageTitle) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="container-fluid container-lg d-flex justify-content-center">
            <div class="glass-card text-center" style="max-width: 600px !important;">
                <div class="mb-4">
                    <i class="fa-solid <?= $errorType->icon() ?>"
                       style="font-size: 48px; color: rgba(74, 106, 106, 0.6);"></i>
                </div>

                <h2 class="pb-0 mb-3" style="font-size: 42px;">
                    <?= htmlspecialchars($errorType->label()) ?>
                </h2>

                <p class="mb-4" style="color: rgba(255, 255, 255, 0.5); font-size: 1rem;">
                    <?= htmlspecialchars($errorType->description()) ?>
                </p>

                <?php if ($customMessage !== null && $customMessage !== ''): ?>
                    <div class="mb-4 p-3 rounded-3"
                         style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
                        <p class="mb-0 small" style="color: rgba(255, 255, 255, 0.6);">
                            <i class="fa-solid fa-info-circle me-2" style="color: rgba(74, 106, 106, 0.6);"></i>
                            <?= htmlspecialchars($customMessage) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mt-4">
                    <a href="<?= Helpers::getRelativePath() ?>" class="btn btn-cta px-4 py-2 fw-bold">
                        <i class="fa-solid fa-house me-2"></i> Početna
                    </a>

                    <?php if ($redirectTo !== null && $redirectTo !== ''): ?>
                        <a href="<?= htmlspecialchars($redirectTo) ?>" class="btn btn-outline-light px-4 py-2 fw-bold">
                            <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                        </a>
                    <?php else: ?>
                        <button onclick="history.back()" class="btn btn-outline-light px-4 py-2 fw-bold">
                            <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>
</body>

</html>
