<?php

require_once 'db.php';

$pdo = getDB();

$requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
$note = $_POST['note'] ?? '';

if (!$requestId) {
    die('Invalid request ID');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT * FROM dispense_requests WHERE id = ? FOR UPDATE"
    );
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();

    if (!$req) {
        throw new Exception('Request not found');
    }

    if ($req['status'] !== 'pending') {
        throw new Exception('Already processed');
    }

    $stmt = $pdo->prepare(
        "SELECT quantity_on_hand FROM stock_items WHERE id = ? FOR UPDATE"
    );
    $stmt->execute([$req['stock_item_id']]);
    $onHand = $stmt->fetchColumn();

    if ($onHand === false) {
        throw new Exception('Stock item not found');
    }

    if ($onHand < $req['quantity_requested']) {
        throw new Exception('Not enough stock');
    }

    $stmt = $pdo->prepare(
        "UPDATE stock_items
         SET quantity_on_hand = quantity_on_hand - ?
         WHERE id = ?"
    );
    $stmt->execute([
        $req['quantity_requested'],
        $req['stock_item_id']
    ]);

    $stmt = $pdo->prepare(
        "UPDATE dispense_requests
         SET status = 'fulfilled'
         WHERE id = ?"
    );
    $stmt->execute([$requestId]);

    $pdo->commit();

    echo "Fulfilled. Note logged: " .
        htmlspecialchars($note, ENT_QUOTES, 'UTF-8');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die($e->getMessage());
}
