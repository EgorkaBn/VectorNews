<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/news.json';

$fallbackPayload = [
    'generatedAt' => gmdate('c'),
    'updatedAt' => gmdate('c'),
    'stats' => [
        'articles' => 0,
        'clusters' => 0,
        'sources' => 0,
    ],
    'highlights' => [],
    'clusters' => [],
    'articles' => [],
    'legal' => [
        'mode' => 'aggregator',
        'copyright' => 'Показываются только заголовки, краткие описания, источник и ссылка на оригинал.',
    ],
];

if (!file_exists($file) || filesize($file) < 10) {
    echo json_encode($fallbackPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$raw = file_get_contents($file);
if ($raw === false) {
    echo json_encode($fallbackPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode($fallbackPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$articles = $data['articles'] ?? [];
$clusters = $data['clusters'] ?? [];

$theme = trim((string)($_GET['theme'] ?? ''));
$region = trim((string)($_GET['region'] ?? ''));
$query = trim((string)($_GET['q'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'top'));
$limit = max(1, min(200, (int)($_GET['limit'] ?? 120)));

$filterArticles = static function (array $items) use ($theme, $region, $query): array {
    return array_values(array_filter($items, static function (array $item) use ($theme, $region, $query): bool {
        if ($theme !== '' && ($item['theme'] ?? '') !== $theme) {
            return false;
        }
        if ($region !== '' && !in_array($region, $item['regions'] ?? [], true)) {
            return false;
        }
        if ($query !== '') {
            $haystack = mb_strtolower(
                implode(' ', [
                    $item['title'] ?? '',
                    $item['description'] ?? '',
                    implode(' ', $item['entities'] ?? []),
                    implode(' ', $item['categories'] ?? []),
                    $item['source'] ?? '',
                ]),
                'UTF-8'
            );
            if (!str_contains($haystack, mb_strtolower($query, 'UTF-8'))) {
                return false;
            }
        }
        return true;
    }));
};

$articles = $filterArticles($articles);
$clusters = $filterArticles($clusters);

$sorter = static function (array &$items, string $sort): void {
    usort($items, static function (array $a, array $b) use ($sort): int {
        if ($sort === 'latest') {
            return strtotime((string)($b['publishedAt'] ?? '')) <=> strtotime((string)($a['publishedAt'] ?? ''));
        }
        if ($sort === 'sources') {
            return (int)($b['sourceCount'] ?? 0) <=> (int)($a['sourceCount'] ?? 0);
        }
        return (float)($b['importanceScore'] ?? 0) <=> (float)($a['importanceScore'] ?? 0);
    });
};

$sorter($articles, $sort);
$sorter($clusters, $sort);

$articles = array_slice($articles, 0, $limit);
$clusters = array_slice($clusters, 0, max(20, (int)ceil($limit / 3)));

$data['articles'] = $articles;
$data['clusters'] = $clusters;
$data['highlights'] = array_slice($clusters, 0, 6);
$data['stats'] = [
    'articles' => count($articles),
    'clusters' => count($clusters),
    'sources' => count(array_unique(array_map(static fn(array $item): string => (string)($item['source'] ?? $item['primarySource'] ?? ''), $articles))),
];
$data['request'] = [
    'theme' => $theme,
    'region' => $region,
    'q' => $query,
    'sort' => $sort,
    'limit' => $limit,
];

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
