<?php

$relativePath = Helpers::getRelativePath('pages');
$homePath = Helpers::getRelativePath() . 'index.php';
$phoneHref = preg_replace('/\s+/', '', Helpers::WEBSITE_PHONE);
?>

<footer class="site-footer">
    <div class="container-fluid container-xl">
        <div class="row g-4">
            <div class="col-lg-4 col-md-12">
                <div class="footer-logo">
                    <a href="<?= $homePath ?>">
                        <div><?php echo Helpers::WEBSITE_NAME ?></div>
                        <div>RESTORAN</div>
                    </a>
                </div>
                <p class="mt-3 footer-description">
                    <?php echo Helpers::WEBSITE_DESCRIPTION ?>
                </p>
            </div>

            <div class="col-lg-4 col-md-6">
                <h5 class="footer-heading">Kontakt</h5>
                <ul class="footer-links">
                    <li>
                        <i class="fa-solid fa-location-dot me-2"></i>
                        <a href="https://www.google.com/maps?q=<?php echo urlencode(Helpers::WEBSITE_ADDRESS); ?>" target="_blank">
                            <?php echo Helpers::WEBSITE_ADDRESS ?>
                        </a>
                    </li>
                    <li>
                        <i class="fa-solid fa-phone me-2"></i>
                        <a href="tel:<?php echo $phoneHref ?>">
                            <?php echo Helpers::WEBSITE_PHONE ?>
                        </a>
                    </li>
                    <li>
                        <i class="fa-solid fa-envelope me-2"></i>
                        <a href="mailto:<?php echo Helpers::WEBSITE_EMAIL ?>">
                            <?php echo Helpers::WEBSITE_EMAIL ?>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-6">
                <h5 class="footer-heading">Radno vreme</h5>
                <ul class="footer-links">
                    <li>
                        <span class="text-white">Pon - Pet:</span>
                        <span class="ms-2"><?php echo Helpers::WEBSITE_WORKING_DAYS ?></span>
                    </li>
                    <li>
                        <span class="text-white">Sub - Ned:</span>
                        <span class="ms-2"><?php echo Helpers::WEBSITE_WORKING_WEEKEND ?></span>
                    </li>
                </ul>

                <div class="social-links mt-4">
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom text-center">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo Helpers::WEBSITE_NAME ?>. Sva prava pridržana.
            </p>
        </div>
    </div>
</footer>

<style>
    .site-footer {
        backdrop-filter: blur(5px);
        padding: 60px 0 30px;
    }

    .footer-logo a {
        display: block;
        color: white;
    }

    .footer-logo a>div:first-child {
        font-size: 36px;
        line-height: 0.75;
        font-family: serif;
    }

    .footer-logo a>div:last-child {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.3em;
        line-height: 0.75;
        margin-top: 8px;
        opacity: 0.6;
    }

    .footer-description {
        color: #636363;
        font-size: 14px;
        line-height: 1.6;
    }

    .footer-heading {
        color: white;
        font-size: 18px;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .footer-links {
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        color: #a5a5a5;
        font-size: 14px;
        margin-bottom: 12px;
        display: flex;
        align-items: flex-start;
    }

    .footer-links i {
        color: #4A6A6A;
        margin-top: 3px;
    }

    .footer-links a {
        color: #a5a5a5;
    }

    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        color: #a5a5a5;
        transition: all 0.3s ease;
    }

    .social-links a:hover {
        background: #ffa07f;
        border-color: #ffa07f;
        color: white;
        transform: translateY(-3px);
    }

    .footer-bottom {
        padding-top: 30px;
        margin-top: 40px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .footer-bottom p {
        color: #636363;
        font-size: 13px;
    }

    @media (max-width: 768px) {
        .site-footer {
            padding: 40px 0 20px;
        }

        .footer-logo a>div:first-child {
            font-size: 28px;
        }
    }
</style>
