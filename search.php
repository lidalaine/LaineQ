<?php
require_once 'functions.php';

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1> Ошибка подключения к базе данных</h1>";
    echo "<p><a href='index.php'>← Назад к главной</a></p>";
    echo "</body></html>";
    exit;
}

// Получаем поисковый запрос
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Выполняем поиск
$searchResults = searchArticles($query);
$totalResults = count($searchResults);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск<?php echo $query ? ': ' . htmlspecialchars($query) : '' ?> | LaineQ</title>
    <link rel="stylesheet" href="style_new.css">
    <link rel="icon" href="images/fav.png">
</head>
<body>
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">← Назад на главную</a>
        </nav>
        
        <header class="search-header">
            <h1>Поиск</h1>
            
            <!-- Форма поиска -->
            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Введите поисковый запрос..." 
                    value="<?php echo htmlspecialchars($query) ?>"
                    autofocus
                >
                <button type="submit" class="search-btn">Найти</button>
            </form>
        </header>
        
        <!-- Результаты поиска -->
        <main class="search-results">
            <?php if ($query): ?>
                <div class="results-info">
                    <h2>Результаты поиска</h2>
                    <p>
                        <?php if ($totalResults > 0): ?>
                            Найдено <strong><?php echo $totalResults ?></strong> 
                            <?php 
                            if ($totalResults == 1) {
                                echo 'книга';
                            } elseif ($totalResults >= 2 && $totalResults <= 4) {
                                echo 'книги';
                            } else {
                                echo 'книг';
                            }
                            ?> 
                            по запросу "<strong><?php echo htmlspecialchars($query) ?></strong>"
                        <?php else: ?>
                            По запросу "<strong><?php echo htmlspecialchars($query) ?></strong>" ничего не найдено
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($totalResults > 0): ?>
                    <div class="results-grid">
                        <?php foreach ($searchResults as $article): ?>
                        <article class="result-card">
                            <h3 class="result-title">
                                <a href='article.php?id=<?php echo $article['idTexts'] ?>&page=1'>
                                    <?php echo htmlspecialchars($article['textTitle']) ?>
                                </a>
                            </h3>
                            
                            
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <h3> Вернуться в библиотеку</h3>


                        
                        <a href="index.php" class="btn" style="margin-top: 1rem;"> Библиотека</a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                    
                    <!-- Показываем последние статьи как альтернативу -->
                    <?php 
                    $recentArticles = getRecentArticles(3);
                    if (!empty($recentArticles)): 
                    ?>
                        <h3 style="margin-top: 2rem;"> Последние книги:</h3>
                        <div class="articles-grid" style="margin-top: 1rem;">
                            <?php foreach ($recentArticles as $article): ?>
                            <article class="article-card">
                                <h4>
                                    <a style='text-decoration: none; color:black;' href='article.php?id=<?php echo $article['idTexts'] ?>&page=1'>
                                    <?php echo htmlspecialchars($article['textTitle']) ?>
                                    </a>
                                </h4>
                            
                                <div style="font-size: 0.9rem; color: #718096; margin-top: 0.5rem;">
                                    <?php echo htmlspecialchars($article['author']['username']) ?> 
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>