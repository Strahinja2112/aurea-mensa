<?php

declare(strict_types=1);

ob_start();

try {
    require_once '../../autoload.php';

    header('Content-Type: application/json; charset=UTF-8');

    $user = Auth::getUser();

    if (!$user || !$user->isAdmin()) {
        throw new Exception('Samo administrator može menjati raspored stolova.');
    }

    $allowedTableShapes = ['round', 'square', 'rectangle', 'oval', 'booth'];
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $tables = $data['tables'] ?? null;
    $deletedIds = $data['deleted_ids'] ?? [];

    if (!is_array($tables)) {
        throw new Exception('Neispravan zahtev.');
    }

    if (!is_array($deletedIds)) {
        throw new Exception('Lista obrisanih stolova nije ispravna.');
    }

    db()->beginTransaction();

    try {
        foreach ($deletedIds as $deletedId) {
            $id = (int)$deletedId;

            if ($id > 0) {
                db()->restaurantTables->delete([
                    'where' => ['id' => $id]
                ]);
            }
        }

        foreach ($tables as $table) {
            $id = (int)($table['id'] ?? 0);
            $name = trim((string)($table['name'] ?? ''));
            $seats = (int)($table['seats'] ?? 0);
            $layoutX = (float)($table['layout_x'] ?? 50);
            $layoutY = (float)($table['layout_y'] ?? 50);
            $layoutRotation = (int)($table['layout_rotation'] ?? 0);
            $shape = (string)($table['table_shape'] ?? 'round');

            if ($name === '') {
                throw new Exception('Naziv stola je obavezan.');
            }

            if ($seats < 1 || $seats > 20) {
                throw new Exception('Broj mesta mora biti između 1 i 20.');
            }

            if (!in_array($shape, $allowedTableShapes, true)) {
                throw new Exception('Oblik stola nije ispravan.');
            }

            $tableData = [
                'name' => $name,
                'seats' => $seats,
                'location_description' => trim((string)($table['location_description'] ?? '')) ?: null,
                'is_smoking' => !empty($table['is_smoking']) ? 1 : 0,
                'layout_x' => max(4, min(96, $layoutX)),
                'layout_y' => max(8, min(92, $layoutY)),
                'layout_rotation' => (($layoutRotation % 360) + 360) % 360,
                'table_shape' => $shape,
            ];

            if ($id <= 0) {
                db()->restaurantTables->create([
                    'data' => $tableData
                ]);
                continue;
            }

            $updated = db()->restaurantTables->update([
                'where' => ['id' => $id],
                'data' => $tableData
            ]);

            if (!$updated) {
                throw new Exception('Sto nije pronađen.');
            }
        }

        db()->commit();
    } catch (Exception $exception) {
        db()->rollBack();
        throw $exception;
    }

    ob_end_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Raspored stolova je sačuvan.'
    ]);
} catch (Exception $exception) {
    if (ob_get_length()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage()
    ]);
}

exit;
