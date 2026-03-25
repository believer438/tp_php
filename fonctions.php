<?php
function redirect_with_message(string $page, string $type, string $message): void
{
    header('Location: ' . $page . '?type=' . urlencode($type) . '&message=' . urlencode($message));
    exit;
}

function page_alert(): array
{
    return [
        'type' => $_GET['type'] ?? '',
        'message' => $_GET['message'] ?? '',
    ];
}

function selected_value($currentValue, $expectedValue): string
{
    return (string) $currentValue === (string) $expectedValue ? 'selected' : '';
}

function find_product_stock(PDO $pdo, int $produitId): int
{
    $stmt = $pdo->prepare('SELECT stock FROM produits WHERE id = ?');
    $stmt->execute([$produitId]);
    return (int) $stmt->fetchColumn();
}

function product_exists(PDO $pdo, int $produitId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE id = ?');
    $stmt->execute([$produitId]);
    return (int) $stmt->fetchColumn() > 0;
}
?>
