<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
set_time_limit(300);
date_default_timezone_set('UTC');

const OUTPUT_FILE = __DIR__ . '/news.json';
const MAX_ITEMS_PER_FEED = 40;
const MAX_DESCRIPTION = 320;
const MAX_AGE_DAYS = 7;

$feeds = [
    ['name' => 'ТАСС', 'url' => 'https://tass.ru/rss/v2.xml', 'bias' => 'wire'],
    ['name' => 'Ведомости', 'url' => 'https://www.vedomosti.ru/rss/news', 'bias' => 'business'],
    ['name' => 'РИА Новости', 'url' => 'https://ria.ru/export/rss2/index.xml', 'bias' => 'wire'],
    ['name' => 'Интерфакс', 'url' => 'https://www.interfax.ru/rss.asp', 'bias' => 'wire'],
    ['name' => 'NPR', 'url' => 'https://feeds.npr.org/1001/rss.xml', 'bias' => 'analysis'],
    ['name' => 'ABC News', 'url' => 'https://abcnews.go.com/abcnews/topstories', 'bias' => 'general'],
    ['name' => 'The New York Times', 'url' => 'https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml', 'bias' => 'general'],
    ['name' => 'NHK World', 'url' => 'https://www3.nhk.or.jp/rss/news/cat0.xml', 'bias' => 'general'],
    ['name' => 'Japan Today', 'url' => 'https://japantoday.com/feed', 'bias' => 'general'],
    ['name' => 'Asia-Plus', 'url' => 'https://asiaplustj.info/en/rss.xml', 'bias' => 'regional'],
    ['name' => 'Al Jazeera', 'url' => 'https://www.aljazeera.com/xml/rss/all.xml', 'bias' => 'general'],
    ['name' => 'The Times of Israel', 'url' => 'https://www.timesofisrael.com/rss-feeds/', 'bias' => 'regional'],
    ['name' => 'Jerusalem Post', 'url' => 'https://www.jpost.com/rss', 'bias' => 'regional'],
    ['name' => 'The Guardian', 'url' => 'https://www.theguardian.com/world/rss', 'bias' => 'general'],
    ['name' => 'Deutsche Welle', 'url' => 'https://rss.dw.com/rdf/ru-news', 'bias' => 'general'],
    ['name' => 'Euronews', 'url' => 'https://www.euronews.com/rss?level=theme&name=news', 'bias' => 'general'],
    ['name' => 'Sky News World', 'url' => 'https://feeds.skynews.com/feeds/rss/world.xml', 'bias' => 'general'],
    ['name' => 'BleepingComputer', 'url' => 'https://www.bleepingcomputer.com/feed/', 'bias' => 'tech'],
    ['name' => 'TechCrunch', 'url' => 'https://techcrunch.com/feed/', 'bias' => 'tech'],
    ['name' => 'ScienceDaily', 'url' => 'https://www.sciencedaily.com/rss/all.xml', 'bias' => 'science'],
    ['name' => 'Nature', 'url' => 'https://www.nature.com/nature.rss', 'bias' => 'science'],
    ['name' => 'ESPN', 'url' => 'https://www.espn.com/espn/rss/news', 'bias' => 'sport'],
];

$themeMap = [
    'politics' => ['president','government','election','parliament','minister','санкц','президент','правительств','выбор','министр','конгресс','kremlin','white house','дипломат'],
    'economy' => ['economy','market','inflation','stocks','bank','oil','gas','trade','business','economic','эконом','рынок','инфляц','нефт','газ','банк','валют','бирж','ставк'],
    'technology' => ['ai','artificial intelligence','startup','apple','google','microsoft','chip','semiconductor','openai','robot','device','software','tech','нейросет','технолог','смартфон','процессор'],
    'cyber' => ['cyber','hacker','hack','breach','malware','ransomware','phishing','ddos','security','vulnerability','кибер','хак','утечк','взлом','шифроваль','уязвим'],
    'science' => ['science','research','discovery','space','nasa','spacex','lab','quantum','astronomy','medicine','исследован','наук','открыт','космос','медицин','генет'],
    'conflict' => ['war','military','missile','drone','strike','attack','conflict','army','troops','gaza','ukraine','israel','хамас','войн','военн','дрон','ракет','атака','удар','армия','конфликт'],
    'incidents' => ['fire','crash','explosion','flood','earthquake','police','crime','accident','arrest','пожар','взрыв','авари','землетряс','наводнен','криминал','полици'],
    'sport' => ['sport','football','soccer','tennis','nba','nhl','olympic','match','goal','спорт','футбол','теннис','матч','олимпиад','хоккей','гол'],
    'culture' => ['film','music','festival','art','series','movie','culture','actor','book','культур','фильм','музык','сериал','книга','театр'],
];

$regionMap = [
    'russia' => ['russia','moscow','kremlin','росси','москв'],
    'usa' => ['usa','u.s.','united states','washington','white house','америк','сша','вашингтон'],
    'europe' => ['europe','eu ','brussels','berlin','france','germany','italy','european','европ','брюссел','герман','франц'],
    'middleeast' => ['israel','gaza','iran','syria','lebanon','saudi','uae','middle east','израил','газа','иран','сири','ливан'],
    'asia' => ['japan','china','india','tokyo','beijing','asia','япони','кита','инд','ази'],
];

function fetch_rss(string $url): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Vector2026NewsBot/1.0 (+aggregator; contact: admin@example.com)',
        CURLOPT_HTTPHEADER => [
            'Accept: application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
            'Accept-Language: ru,en;q=0.9',
            'Cache-Control: no-cache',
        ],
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $code >= 400) {
        echo "[WARN] {$url} :: HTTP {$code} {$error}\n";
        return null;
    }

    return $body;
}

function clean_text(?string $text, int $limit = MAX_DESCRIPTION): string {
    $text = html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/<[^>]+>/', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', (string)$text);
    $text = trim((string)$text);
    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
    if (mb_strlen($text, 'UTF-8') > $limit) {
        $text = rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '…';
    }
    return $text;
}

function parse_link(SimpleXMLElement $item): string {
    if (isset($item->link)) {
        if ($item->link instanceof SimpleXMLElement && isset($item->link['href'])) {
            return trim((string)$item->link['href']);
        }
        return trim((string)$item->link);
    }
    if (isset($item->guid)) {
        return trim((string)$item->guid);
    }
    return '';
}

function parse_date(SimpleXMLElement $item): string {
    foreach (['pubDate', 'published', 'updated'] as $field) {
        if (isset($item->{$field})) {
            $value = trim((string)$item->{$field});
            if ($value !== '') {
                $ts = strtotime($value);
                if ($ts !== false) {
                    return gmdate('c', $ts);
                }
            }
        }
    }
    return gmdate('c');
}

function parse_categories(SimpleXMLElement $item): array {
    $categories = [];
    if (isset($item->category)) {
        foreach ($item->category as $cat) {
            $raw = trim((string)$cat);
            if ($raw !== '') {
                $categories[] = $raw;
            }
            if (isset($cat['term'])) {
                $term = trim((string)$cat['term']);
                if ($term !== '') {
                    $categories[] = $term;
                }
            }
        }
    }
    $categories = array_values(array_unique(array_map(static fn(string $v): string => clean_text($v, 60), $categories)));
    return $categories;
}

function normalize_title(string $title): string {
    $title = mb_strtolower($title, 'UTF-8');
    $title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $title);
    $title = preg_replace('/\s+/u', ' ', (string)$title);
    return trim((string)$title);
}

function classify_theme(array $article, array $themeMap): string {
    $text = mb_strtolower(implode(' ', [
        $article['title'] ?? '',
        $article['description'] ?? '',
        implode(' ', $article['categories'] ?? []),
    ]), 'UTF-8');

    $bestTheme = 'all';
    $bestScore = 0;
    foreach ($themeMap as $theme => $keywords) {
        $score = 0;
        foreach ($keywords as $word) {
            if (mb_strpos($text, $word, 0, 'UTF-8') !== false) {
                $score += 1;
                if (mb_strpos(mb_strtolower($article['title'] ?? '', 'UTF-8'), $word, 0, 'UTF-8') !== false) {
                    $score += 2;
                }
            }
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestTheme = $theme;
        }
    }
    return $bestTheme;
}

function detect_regions(array $article, array $regionMap): array {
    $text = mb_strtolower(implode(' ', [
        $article['title'] ?? '',
        $article['description'] ?? '',
        implode(' ', $article['categories'] ?? []),
        $article['source'] ?? '',
    ]), 'UTF-8');

    $regions = [];
    foreach ($regionMap as $region => $keywords) {
        foreach ($keywords as $word) {
            if (mb_strpos($text, $word, 0, 'UTF-8') !== false) {
                $regions[] = $region;
                break;
            }
        }
    }
    if ($regions === []) {
        $regions[] = 'global';
    }
    return array_values(array_unique($regions));
}

function extract_entities(array $article): array {
    $pool = implode(' ', [$article['title'] ?? '', $article['description'] ?? '']);
    preg_match_all('/\b([A-ZА-ЯЁ][A-Za-zА-Яа-яЁё0-9\-]{2,}(?:\s+[A-ZА-ЯЁ][A-Za-zА-Яа-яЁё0-9\-]{2,}){0,2})/u', $pool, $matches);
    $entities = array_values(array_unique(array_map(static fn(string $v): string => clean_text($v, 48), $matches[1] ?? [])));
    return array_slice(array_filter($entities), 0, 8);
}

function compute_importance(array $article, array $sourceWeights): float {
    $ageHours = max(1, (time() - strtotime((string)$article['publishedAt'])) / 3600);
    $freshness = max(0, 30 - min(30, $ageHours));
    $titleWeight = min(18, (float)mb_strlen((string)$article['title'], 'UTF-8') / 6);
    $themeBoost = match ($article['theme']) {
        'conflict' => 14,
        'politics' => 12,
        'economy' => 11,
        'cyber' => 10,
        'technology' => 9,
        'science' => 8,
        'incidents' => 9,
        'sport' => 6,
        'culture' => 5,
        default => 4,
    };
    $sourceWeight = $sourceWeights[$article['source']] ?? 5;
    return round($freshness + $titleWeight + $themeBoost + $sourceWeight, 2);
}

function story_key(array $article): string {
    $normalized = normalize_title((string)$article['title']);
    $tokens = preg_split('/\s+/u', $normalized) ?: [];
    $stop = ['the','a','an','and','or','to','of','in','on','for','with','at','from','по','и','в','на','с','к','из','о','об','для','что','как','это'];
    $tokens = array_values(array_filter($tokens, static fn(string $token): bool => mb_strlen($token, 'UTF-8') > 3 && !in_array($token, $stop, true)));
    $tokens = array_slice($tokens, 0, 6);
    return implode('-', $tokens);
}

$sourceWeights = [];
foreach ($feeds as $feed) {
    $sourceWeights[$feed['name']] = match ($feed['bias']) {
        'wire' => 10,
        'business' => 9,
        'analysis' => 8,
        'tech' => 8,
        'science' => 8,
        'regional' => 7,
        'sport' => 6,
        default => 7,
    };
}

$articles = [];
$seen = [];
$feedHealth = [];

foreach ($feeds as $feed) {
    $raw = fetch_rss($feed['url']);
    if ($raw === null) {
        $feedHealth[] = ['source' => $feed['name'], 'ok' => false];
        continue;
    }

    $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$xml) {
        echo "[WARN] XML parse error :: {$feed['name']}\n";
        $feedHealth[] = ['source' => $feed['name'], 'ok' => false];
        continue;
    }

    $items = [];
    if (isset($xml->channel->item)) {
        $items = $xml->channel->item;
    } elseif (isset($xml->entry)) {
        $items = $xml->entry;
    }

    $count = 0;
    foreach ($items as $item) {
        if ($count >= MAX_ITEMS_PER_FEED) {
            break;
        }

        $title = clean_text((string)($item->title ?? ''), 180);
        if ($title === '') {
            continue;
        }

        $description = '';
        if (isset($item->description)) {
            $description = clean_text((string)$item->description);
        } elseif (isset($item->summary)) {
            $description = clean_text((string)$item->summary);
        }
        if ($description === '') {
            $description = 'Краткое описание недоступно. Подробнее — по ссылке на оригинальную публикацию.';
        }

        $url = parse_link($item);
        if ($url === '') {
            continue;
        }

        $publishedAt = parse_date($item);
        if (strtotime($publishedAt) < strtotime('-' . MAX_AGE_DAYS . ' days')) {
            continue;
        }

        $categories = parse_categories($item);
        $id = md5($feed['name'] . '|' . $url . '|' . $title);
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;

        $article = [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'source' => $feed['name'],
            'sourceType' => $feed['bias'],
            'publishedAt' => $publishedAt,
            'categories' => $categories,
        ];
        $article['theme'] = classify_theme($article, $themeMap);
        $article['regions'] = detect_regions($article, $regionMap);
        $article['entities'] = extract_entities($article);
        $article['storyKey'] = story_key($article);
        $article['importanceScore'] = compute_importance($article, $sourceWeights);
        $article['trustLabel'] = in_array($feed['bias'], ['wire','business','analysis'], true) ? 'Высокая' : 'Стандартная';
        $article['whyItMatters'] = match ($article['theme']) {
            'economy' => 'Может повлиять на рынки, цены, валюты и решения бизнеса.',
            'politics' => 'Влияет на политику, международные отношения и регулирование.',
            'technology' => 'Задает направление для AI, платформ и цифровых сервисов.',
            'cyber' => 'Важно для безопасности данных, компаний и госструктур.',
            'science' => 'Показывает новые исследования и технологические прорывы.',
            'conflict' => 'Имеет прямое влияние на безопасность, дипломатию и гуманитарную повестку.',
            'incidents' => 'Важно из-за рисков, последствий и возможного продолжения событий.',
            'sport' => 'Актуально для крупных турниров, команд и фанатской аудитории.',
            'culture' => 'Отражает тренды медиа, культуры и общественного внимания.',
            default => 'Помогает быстро понять главные изменения в повестке.',
        };
        $articles[] = $article;
        $count++;
    }

    $feedHealth[] = ['source' => $feed['name'], 'ok' => true, 'items' => $count];
    echo "[OK] {$feed['name']} :: {$count}\n";
}

usort($articles, static fn(array $a, array $b): int => strtotime($b['publishedAt']) <=> strtotime($a['publishedAt']));

$clusters = [];
foreach ($articles as $article) {
    $key = $article['storyKey'] !== '' ? $article['storyKey'] : substr($article['id'], 0, 12);
    if (!isset($clusters[$key])) {
        $clusters[$key] = [
            'id' => 'cluster-' . md5($key),
            'storyKey' => $key,
            'title' => $article['title'],
            'description' => $article['description'],
            'summary' => $article['description'],
            'url' => $article['url'],
            'theme' => $article['theme'],
            'regions' => $article['regions'],
            'categories' => $article['categories'],
            'entities' => $article['entities'],
            'publishedAt' => $article['publishedAt'],
            'primarySource' => $article['source'],
            'sourceCount' => 0,
            'importanceScore' => 0,
            'coverage' => [],
            'items' => [],
            'whyItMatters' => $article['whyItMatters'],
        ];
    }

    $clusters[$key]['coverage'][] = $article['source'];
    $clusters[$key]['items'][] = [
        'id' => $article['id'],
        'title' => $article['title'],
        'source' => $article['source'],
        'url' => $article['url'],
        'publishedAt' => $article['publishedAt'],
    ];
    $clusters[$key]['sourceCount'] = count(array_unique($clusters[$key]['coverage']));
    $clusters[$key]['importanceScore'] = max($clusters[$key]['importanceScore'], (float)$article['importanceScore']) + min(5, $clusters[$key]['sourceCount'] * 0.45);

    if (strtotime($article['publishedAt']) > strtotime($clusters[$key]['publishedAt'])) {
        $clusters[$key]['publishedAt'] = $article['publishedAt'];
    }

    if (mb_strlen($article['title'], 'UTF-8') < mb_strlen($clusters[$key]['title'], 'UTF-8')) {
        $clusters[$key]['title'] = $article['title'];
    }

    $clusters[$key]['entities'] = array_values(array_unique(array_merge($clusters[$key]['entities'], $article['entities'])));
    $clusters[$key]['regions'] = array_values(array_unique(array_merge($clusters[$key]['regions'], $article['regions'])));
    $clusters[$key]['categories'] = array_values(array_unique(array_merge($clusters[$key]['categories'], $article['categories'])));
}

$clusters = array_values(array_map(static function (array $cluster): array {
    $cluster['coverage'] = array_values(array_unique($cluster['coverage']));
    usort($cluster['items'], static fn(array $a, array $b): int => strtotime($b['publishedAt']) <=> strtotime($a['publishedAt']));
    $cluster['summary'] = $cluster['description'] . ' Источников: ' . $cluster['sourceCount'] . '.';
    return $cluster;
}, $clusters));

usort($clusters, static fn(array $a, array $b): int => (float)$b['importanceScore'] <=> (float)$a['importanceScore']);

$payload = [
    'generatedAt' => gmdate('c'),
    'updatedAt' => gmdate('c'),
    'stats' => [
        'articles' => count($articles),
        'clusters' => count($clusters),
        'sources' => count(array_unique(array_column($articles, 'source'))),
    ],
    'themeStats' => array_count_values(array_map(static fn(array $item): string => $item['theme'], $articles)),
    'regionStats' => array_count_values(array_merge(...array_map(static fn(array $item): array => $item['regions'], $articles))),
    'highlights' => array_slice($clusters, 0, 6),
    'clusters' => $clusters,
    'articles' => $articles,
    'feedHealth' => $feedHealth,
    'legal' => [
        'mode' => 'aggregator',
        'copyright' => 'Сервис публикует заголовок, краткое описание, источник и прямую ссылку на оригинал.',
        'notice' => 'Оригинальные материалы принадлежат их правообладателям. Для полного чтения используется переход на сайт источника.',
        'advice' => 'Для публикации в открытом доступе рекомендуется дополнительно разместить страницу контактов для правообладателей и политику обработки обращений.',
    ],
];

file_put_contents(OUTPUT_FILE, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
echo "Saved payload with " . count($articles) . " articles and " . count($clusters) . " clusters\n";
