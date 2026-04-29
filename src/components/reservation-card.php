<?php

declare(strict_types=1);

/**
 * @param array{
 *     reservation: array,
 *     arrived: bool,
 *     user: array|null
 * } $data
 */

function renderReservationCard(array $data): void
{
    $res = $data['reservation'];
    $arrived = $data['arrived'];
    $resUser = $data['user'];
    $hasComment = !empty($res['comment']);

    $time = substr($res['start_time'], 0, 5);
    $endTime = substr($res['end_time'], 0, 5);
    $userName = trim(($resUser['first_name'] ?? '') . ' ' . ($resUser['last_name'] ?? ''));
    if (!$userName) {
        $userName = $resUser['email'] ?? 'Nepoznat';
    }
    ?>

    <div class="res-card <?= $arrived ? 'checked-in' : '' ?>"
         data-reservation-id="<?= $res['id'] ?>">

        <div class="res-card-header">
            <div class="res-time">
                <span class="time-start fs-1 fw-medium"><?= htmlspecialchars($time) ?></span>
                <span class="time-separator fs-1">-</span>
                <span class="time-end fs-1 fw-medium"><?= htmlspecialchars($endTime) ?></span>
            </div>
            <div class="res-status <?= $arrived ? 'arrived' : 'pending' ?>">
                <?= $arrived ? 'Prijavljen' : 'Na cekanju' ?>
            </div>
        </div>

        <div class="res-card-body">
            <div class="res-code-wrapper">
                <span class="res-code-label">Kod:</span>
                <span class="res-code"><?= htmlspecialchars($res['reservation_code']) ?></span>
            </div>

            <div class="res-subtitle">Gost</div>
            <div class="res-user">
                <i class="fa-solid fa-user"></i>
                <span><?= htmlspecialchars($userName) ?></span>
            </div>
            <div class="res-contact">
                <i class="fa-solid fa-envelope"></i>
                <span><?= htmlspecialchars($resUser['email'] ?? '') ?></span>
            </div>
            <div class="res-contact">
                <i class="fa-solid fa-phone"></i>
                <span><?= htmlspecialchars($resUser['phone'] ?? 'Bez telefona') ?></span>
            </div>

            <?php if ($hasComment): ?>
                <div class="res-subtitle">Komentar</div>
                <div class="res-comment">
                    <i class="fa-solid fa-comment"></i>
                    <span><?= htmlspecialchars($res['comment']) ?></span>
                </div>
            <?php endif; ?>

            <div class="res-actions mt-3">
                <?php if (!$arrived): ?>
                    <button type="button" class="btn btn-success btn-sm"
                            onclick="event.stopPropag ation(); quickCheckIn(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>')">
                        <i class="fa-solid fa-check me-1"></i>Prijavi
                    </button>
                <?php endif; ?>

                <?php if ($arrived && !$hasComment): ?>
                    <button type="button" class="btn btn-outline-light btn-sm"
                            onclick="event.stopPropagation(); openCommentModal(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>')">
                        <i class="fa-solid fa-plus me-1"></i>Komentar
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}