<?php
// functions.php - Расширенные функции для работы с MySQL базой данных

require_once 'admin/config/database.php';

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

/**
 * Форматирование даты
 */
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

/**
 * Форматирование даты и времени
 */
function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

/**
 * Подсчет слов в тексте
 */
function countWords($text) {
    return str_word_count(strip_tags($text));
}

/**
 * Автоматический расчет времени чтения
 */
function calculateReadingTime($content) {
    $words = countWords($content);
    return max(1, round($words / 200)); // 200 слов в минуту
}

/**
 * Безопасное экранирование HTML
 */
function sanitizeString($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Безопасная очистка HTML с разрешенными тегами
 */
function sanitizeHTML($value) {
    $allowedTags = '<p><br><strong><em><u><ol><ul><li><h3><h4><blockquote>';
    return strip_tags($value, $allowedTags);
}

/**
 * Валидация email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


// ========== ФУНКЦИИ ДЛЯ РАБОТЫ С АВТОРАМИ ==========

/**
 * Получение всех авторов
 */
function getAuthors() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT * FROM users ORDER BY username");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting authors: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение автора по ID
 */
function getAuthor($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE userId = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
        $article['author'] = [
            'username' => $article['author_name']
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting author: " . $e->getMessage());
        return null;
    }
}


// ========== ФУНКЦИИ ДЛЯ РАБОТЫ СО СТАТЬЯМИ ==========

/**
 * Получение статьи по ID с полными данными
 */
function getArticle($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                u.username as author_name,
                u.email as author_email
            FROM texts t
            JOIN users u ON t.userIdText = u.userId
            WHERE t.idTexts = ? 
        ");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        if (!$article) {
            return null;
        }
        
        // Формируем структуру 
        $article['author'] = [
            'username' => $article['author_name'],
            'email' => $article['author_email']
        ];
        
        
        return $article;
    } catch (PDOException $e) {
        error_log("Error getting text: " . $e->getMessage());
        return null;
    }
}

/**
 * Получение всех опубликованных статей
 */
function getAllArticles($limit = null, $offset = 0) {
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "
            SELECT 
                t.*,
                u.username as author_name,
                u.email as author_email
            FROM texts t
            JOIN users u ON t.userIdText = u.userId
        ";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->query($sql);
        $articles = $stmt->fetchAll();
        
        // Дополняем данные для каждой статьи
        foreach ($articles as &$article) {
            $article['author'] = [
                'username' => $article['author_name'],
                'email' => $article['author_email'],
                'textTitle' => $article['textTitle']
            ];
        }
        
        return $articles;
    } catch (PDOException $e) {
        error_log("Error getting all texts: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение статей с пагинацией 
 */
function getArticlesWithPagination($page = 1, $perPage = 5) {
    try {
        $pdo = getDatabaseConnection();
        
        // Подсчитываем общее количество статей
        $countStmt = $pdo->query("
            SELECT COUNT(*) FROM texts 
        ");
        $totalArticles = $countStmt->fetchColumn();
        
        // Вычисляем OFFSET
        $offset = ($page - 1) * $perPage;
        
        // Получаем статьи для текущей страницы
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                u.username as author_name,
                u.email as author_email
            FROM texts t
            JOIN users u ON t.userIdText = u.userId
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);
        $articles = $stmt->fetchAll();
        
        // Дополняем данные
        foreach ($articles as &$article) {
            $article['author'] = [
                'username' => $article['author_name'],
                'email' => $article['author_email']
            ];
        }
        
        return [
            'texts' => $articles,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_texts' => $totalArticles,
                'total_pages' => ceil($totalArticles / $perPage),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($totalArticles / $perPage),
                'prev_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < ceil($totalArticles / $perPage) ? $page + 1 : null
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error getting paginated texts: " . $e->getMessage());
        return [
            'texts' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => $perPage,
                'total_texts' => 0,
                'total_pages' => 0,
                'has_prev' => false,
                'has_next' => false,
                'prev_page' => null,
                'next_page' => null
            ]
        ];
    }
}

/**
 * Генерация HTML для пагинации 
 */
function renderPagination($pagination, $baseUrl = 'index.php', $queryParams = []) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Формируем базовый URL с параметрами
    $buildUrl = function($page) use ($baseUrl, $queryParams) {
        $params = array_merge($queryParams, ['page' => $page]);
        return $baseUrl . '?' . http_build_query($params);
    };
    
    // Кнопка "Предыдущая"
    if ($pagination['has_prev']) {
        $html .= '<a href="' . $buildUrl($pagination['prev_page']) . '" class="pagination-btn">← Предыдущая</a>';
    }
    
    // Номера страниц (показываем до 7 страниц)
    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    
    $start = max(1, $current - 3);
    $end = min($total, $current + 3);
    
    // Показываем первую страницу если она не входит в диапазон
    if ($start > 1) {
        $html .= '<a href="' . $buildUrl(1) . '" class="pagination-btn">1</a>';
        if ($start > 2) {
            $html .= '<span class="pagination-dots">...</span>';
        }
    }
    
    // Показываем страницы в диапазоне
    for ($i = $start; $i <= $end; $i++) {
        $isActive = ($i === $current);
        $class = $isActive ? 'pagination-btn active' : 'pagination-btn';
        $html .= '<a href="' . $buildUrl($i) . '" class="' . $class . '">' . $i . '</a>';
    }
    
    // Показываем последнюю страницу если она не входит в диапазон
    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= '<span class="pagination-dots">...</span>';
        }
        $html .= '<a href="' . $buildUrl($total) . '" class="pagination-btn">' . $total . '</a>';
    }
    
    // Кнопка "Следующая"
    if ($pagination['has_next']) {
        $html .= '<a href="' . $buildUrl($pagination['next_page']) . '" class="pagination-btn">Следующая →</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Поиск статей
 */
function searchArticles($query) {
    if (empty(trim($query))) {
        return getAllArticles();
    }
    
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                u.username as author_name,
                u.email as author_email
            FROM texts t
            JOIN users u ON t.userIdText = u.userId
            WHERE (t.textTitle LIKE ? OR t.userText LIKE ?)
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        $articles = $stmt->fetchAll();
        
        // Дополняем данные
        foreach ($articles as &$article) {
            $article['author'] = [
                'username' => $article['author_name'],
                'email' => $article['author_email'],
                'textTitle' => $article['textTitle']
            ];
        }
        
        return $articles;
    } catch (PDOException $e) {
        error_log("Error searching texts: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение последних статей
 */
function getRecentArticles($limit = 5) {
    return getAllArticles($limit);
}

// ========== CRUD ОПЕРАЦИИ ДЛЯ СТАТЕЙ ==========

/**
 * Создание новой статьи
 */
function createArticle($articleData) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO texts (textTitle, userText, userIdText)
            VALUES (?, ?, ?)
        ");
        
        
        $result = $stmt->execute([
            $articleData['textTitle'],
            $articleData['userText'],
            $articleData['userIdText']
        ]);
        
        if ($result) {
            $articleId = $pdo->lastInsertId();
            
            return $articleId;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error creating article: " . $e->getMessage());
        return false;
    }
}


/**
 * Обновление статьи
 */
function updateArticle($id, $articleData) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            UPDATE texts
            SET textTitle = ?, userText = ?
            WHERE idTexts = ?
        ");
                
        $result = $stmt->execute([
            $articleData['textTitle'],
            $articleData['userText'],
            $id
        ]);
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating article: " . $e->getMessage());
        return false;
    }
}

/**
 * Удаление статьи
 */
function deleteArticle($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("DELETE FROM texts WHERE idTexts = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting article: " . $e->getMessage());
        return false;
    }
}


// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========


/**
 * Статистика 
 */
function getWordsStats() {
    try {
        $pdo = getDatabaseConnection();
        
        $stats = [];
        
        // Количество статей
        $stmt = $pdo->query("SELECT COUNT(*) FROM userWords WHERE descriptionStatus = 3");
        $stats['knownWords'] = $stmt->fetchColumn();

        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting stats: " . $e->getMessage());
        return ['knownWords' => 0 ];
    }
}

/**
 * Валидация данных статьи
 */
function validateArticleData($data) {
    $errors = [];
    
    if (empty(trim($data['textTitle']))) {
        $errors[] = 'Заголовок обязателен';
    }
    
    if (strlen(trim($data['textTitle'])) > 255) {
        $errors[] = 'Заголовок слишком длинный (максимум 255 символов)';
    }
    
    if (empty(trim($data['userText']))) {
        $errors[] = 'Содержимое обязательно';
    }

    
    return $errors;
}


/**
 * Получение слов с пагинацией 
 */
function getWordsWithPagination($page = 1, $perPage = 120) {
    try {
        $pdo = getDatabaseConnection();

        $textWords = $pdo->query("
        SELECT userText FROM texts WHERE idTexts = ?
        ");

        //Разбиваем текст на слова
        $allWords = explode(' ', $textWords);

        
        // Подсчитываем общее количество слов в статье
        $totalWords = count($allWords);

        
        // Вычисляем OFFSET
        $offset = ($page - 1) * $perPage;
        

        // Получаем слова для текущей страницы
        $stmt = $pdo->prepare("
            SELECT 
                w.*,
                u.username as author_name
            FROM userWords w
            JOIN users u ON w.idUserWords = u.userId
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);
        $words = $stmt->fetchAll();
        
        // Дополняем данные
        foreach ($words as &$word) {
            $words['allWords'] = [
                'word' => $word['word'],
                'translation' => $word['translation'],
                'username' => $word['author_name'],
                'translation' => $word['translation'],
                'idUserWords' => $word['id'],
                'descriptionStatus'=> $word['descriptionStatus']
            ];
        }
        
        return [
            'allWords' => $words,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_texts' => $totalWords,
                'total_pages' => ceil($totalWords / $perPage),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($totalWords / $perPage),
                'prev_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < ceil($totalWords / $perPage) ? $page + 1 : null
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error getting paginated texts: " . $e->getMessage());
        return [
            'allWords' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => $perPage,
                'total_texts' => 0,
                'total_pages' => 0,
                'has_prev' => false,
                'has_next' => false,
                'prev_page' => null,
                'next_page' => null
            ]
        ];
    }
}

/**
 * Генерация HTML для пагинации 
 */
function renderWordPagination($pagination, $baseUrl = 'article.php', $queryParams = []) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<div class="wordsPagination">';
    
    // Формируем базовый URL с параметрами
    $buildUrl = function($page, $id) use ($baseUrl, $queryParams) {
        $params = array_merge($queryParams, ['page' => $page]);
        return $baseUrl . '?id=' . $id . '&' . http_build_query($params);
    };
    
    
    // Номера страниц (показываем до 7 страниц)
    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    
    $start = max(1, $current - 3);
    $end = min($total, $current + 3);
    
    // Показываем первую страницу если она не входит в диапазон
    if ($start > 1) {
        $html .= '<a href="' . $buildUrl(1, $pagination['article_Id']) . '" class="pagination-btn">1</a>';
        if ($start > 2) {
            $html .= '<span class="pagination-dots">...</span>';
        }
    }
    
    // Показываем страницы в диапазоне
    for ($i = $start; $i <= $end; $i++) {
        $isActive = ($i === $current);
        $class = $isActive ? 'pagination-btn active' : 'pagination-btn';
        $html .= '<a href="' . $buildUrl($i, $pagination['article_Id']) . '" class="' . $class . '">' . $i . '</a>';
    }
    
    // Показываем последнюю страницу если она не входит в диапазон
    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= '<span class="pagination-dots">...</span>';
        }
        $html .= '<a href="' . $buildUrl($total, $pagination['article_Id']) . '" class="pagination-btn">' . $total . '</a>';
    }
    
    $html .= '</div>';
    return $html;
}

function previousPagination($pagination, $baseUrl = 'article.php', $queryParams = []) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    
    // Формируем базовый URL с параметрами
    $buildUrl = function($page, $id) use ($baseUrl, $queryParams) {
        $params = array_merge($queryParams, ['page' => $page]);
        return $baseUrl . '?id=' . $id . '&' . http_build_query($params);
    };
    
    // Кнопка "Предыдущая"
    if ($pagination['has_prev']) {
        $html = $buildUrl($pagination['prev_page'], $pagination['article_Id']);
    }
    return $html;
    
}



function nextPagination($pagination, $baseUrl = 'article.php', $queryParams = []) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    // Формируем базовый URL с параметрами
    $buildUrl = function($page, $id) use ($baseUrl, $queryParams) {
        $params = array_merge($queryParams, ['page' => $page]);
        return $baseUrl . '?id=' . $id . '&' . http_build_query($params);
    };

    // Кнопка "Следующая"
    if ($pagination['has_next']) {
        $html = $buildUrl($pagination['next_page'], $pagination['article_Id']);
    }
    
    return $html;
}


function getWord($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                w.*,
                u.username as author_name
            FROM userWords w
            JOIN users u ON w.idUserWords = u.userId
            WHERE w.idUserWords = ? 
        ");
        $stmt->execute([$id]);
        $word = $stmt->fetch();
        
        if (!$word) {
            return null;
        }
        
        // Формируем структуру 
        $word['info'] = [
                'word' => $word['word'],
                'translation' => $word['translation'],
                'username' => $word['author_name'],
                'translation' => $word['translation'],
                'idUserWords' => $word['idUserWords'],
                'descriptionStatus'=> $word['descriptionStatus']
        ];
        
        
        return $word;
    } catch (PDOException $e) {
        error_log("Error getting text: " . $e->getMessage());
        return null;
    }
}


/**
 * Получение статусов слов
 */
function getWords($words) {
    try {
        $pdo = getDatabaseConnection();

        $values = implode('\',\'', $words);
        $values = '(\'' . $values . '\')';
        $sql = "
            SELECT 
                u.word,
                u.descriptionStatus,
                u.translation
                FROM userWords u
                WHERE u.word IN
                " . $values;
        
        $stmt = $pdo->query($sql);
        $wordStatuses = $stmt->fetchAll();
        
        return $wordStatuses;
    } catch (PDOException $e) {
        error_log("Error getting words: " . $e->getMessage());
        return [];
    }
}


function saveWord($userWord){


    $wordStatuses = getWords([$userWord['word']]);
    if( count($wordStatuses) > 0 ){
        return updateWord($userWord);
    } else {
        return addWord($userWord);
    }


}


function updateWord($userWord){
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            UPDATE userWords 
            SET translation = ?, descriptionStatus = ?
            WHERE word = ? AND usersForeign = ?
        ");
        
        $result = $stmt->execute([
            $userWord['translation'],
            $userWord['descriptionStatus'],
            $userWord['word'],
            $userWord['usersForeign']
        ]);
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating word: " . $e->getMessage());
        return false;
    }
}

function addWord($userWord){
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO userWords (word, translation, usersForeign, descriptionStatus)
            VALUES (?,?,?,?)
        ");
        
        $result = $stmt->execute([
            $userWord['word'],
            $userWord['translation'],
            $userWord['usersForeign'],
            $userWord['descriptionStatus']
        ]);
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error adding word: " . $e->getMessage());
        return false;
    }
}


