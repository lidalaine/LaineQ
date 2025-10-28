<?php
// index.php - Обновленная главная страница с пагинацией
require_once 'functions.php';

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>Ошибка подключения к базе данных</h1>";
    echo "<p>Проверьте настройки в config/database.php </a></p>";
    echo "</body></html>";
    exit;
}

// Обработка действий
$message = '';
$error = '';


// Удаление статьи
if (isset($_POST['delete'])) {

        $id = (int)$_POST['text_id'];
        if (deleteArticle($id)) {
            $message = "Книга $id успешно удалена";
        } else {
            $error = "Ошибка при удалении книги";
        }
}


// Получаем номер страницы
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 3; // Статей на странице

// Получаем статьи с пагинацией
$result = getArticlesWithPagination($page, $perPage);
$allArticles = $result['texts'];
$pagination = $result['pagination'];

// Получаем дополнительные данные
$stats = getWordsStats();
$author = getAuthor(1);





?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English<?php echo $page > 1 ? ' | Страница ' . $page : '' ?></title>
    <link rel="stylesheet" href="style_new.css">
    <link rel="icon" href="images/fav.png">
    <meta name="description" content="Английский">
    <meta name="keywords" content="English, английский">
</head>
<body>
    <header class="header">
        <div>
            <div class="container_header">
                <div class="header__top">
                    <div class="arrow">
                    <a href="index.php"><img width="40" src="/images/arrow-left.png"></a>
                    </div>
                    <nav class="menu__left">
                    <ul class="menu__list">
                    <li class="menu__item">
                        <a href="./index.php" target="_self" class="menu__link">Библиотека</a>
                    </li>
                    <li class="menu__item">
                        <a href="./importText.php" target="_self" class="menu__link">Добавить текст</a>
                    </li>
                    </ul>
                    </nav>
                        <nav class="menu">
                            <ul class="menu__list">
                                <li class="menu__item">
                                    <a href="#" target="_self" class="menu__link"><?php echo $author['username']; ?></a>
                                </li>
                                <li class="menu__item">
                                    <a href="#" target="_self" class="menu__link"><?php echo $stats['knownWords']; ?></a>
                                </li>
                                <li class="menu__item">
                                    <img src="./images/fav.png" width="30px" alt="настройки">
                                </li>
                            </ul>
                        </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="container">

            <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error) ?></div>
            <?php endif; ?>
            


        <div class="container">
            <h1>Библиотека</h1>
        </div>
         <!-- Поиск -->
        <section class="search-section">
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Поиск книг..." class="search-input" 
                       value="<?php echo htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="submit" class="search-btn">Найти</button>
            </form>
        </section> 


        <!-- Все статьи -->
        <section class="articles" id ="latest">
            <?php if (empty($allArticles)): ?>
                <div class="no-articles">
                    <h3> Книг пока нет</h3>
                    <p>Создайте первую книгу <a href="./importText.php">здесь</a></p>
                </div>
            <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($allArticles as $article): ?>
                    <article class="article-card">
                        <div class="article-header">
                            <h3 class="article-title">
                                <a href='article.php?id=<?php echo $article['idTexts'] ?>&page=1'>
                                    <?php echo htmlspecialchars($article['textTitle']) ?>
                                </a>
                            </h3>
                        </div>
                        
                        <div class="article-meta">
                            <span> <?php echo htmlspecialchars($article['author']['username']) ?></span>
                        </div>
                        
                        
                        <div class="article-actions">
                            <a href='article.php?id=<?php echo $article['idTexts'] ?>&page=1' class="btn btn-primary">
                                Читать далее →
                            </a>

                            <form method="POST" style="display: inline;" 
                            onsubmit="return confirm('Вы уверены, что хотите удалить книгу \'<?php echo htmlspecialchars($article['textTitle']) ?>\'? Это действие нельзя отменить.');">
                                <input type="hidden" name="text_id" value="<?php echo $article['idTexts'] ?>">
                                <button type="submit" name="delete" class="btn btn-danger" title="Удалить">Удалить</button>
                            </form>
                        </div>
                            
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Пагинация -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-wrapper">
                    <?php echo renderPagination($pagination, 'index.php'); ?>
                    
                    <!-- Информация о пагинации -->
                    <div class="pagination-info">
                        <?php 
                        $start = ($pagination['current_page'] - 1) * $pagination['per_page'] + 1;
                        $end = min($pagination['current_page'] * $pagination['per_page'], $pagination['total_texts']);
                        ?>
                        Показано <strong><?php echo "$start-$end" ?></strong> 
                        из <strong><?php echo $pagination['total_texts'] ?></strong>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
    
    <!-- Кнопка "Наверх" -->
    <button id="scrollToTop" class="scroll-to-top" title="Наверх">↑</button>

    <script>
        // Плавная прокрутка для якорных ссылок
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Кнопка "Наверх"
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });
        
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Анимация появления карточек при прокрутке
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Применяем анимацию к карточкам статей
        document.querySelectorAll('.article-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
        
        // Предзагрузка следующей страницы (опционально)
        <?php if ($pagination['has_next']): ?>
        const nextPageLink = 'index.php?page=<?php echo $pagination['next_page'] ?>';
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = nextPageLink;
        document.head.appendChild(link);
        <?php endif; ?>
        
        // Клавиатурная навигация по страницам
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return; // Не обрабатываем, если фокус в поле ввода
            }
            
            if (e.key === 'ArrowLeft' && <?php echo $pagination['has_prev'] ? 'true' : 'false' ?>) {
                window.location.href = 'index.php?page=<?php echo $pagination['prev_page'] ?? 1 ?>';
            } else if (e.key === 'ArrowRight' && <?php echo $pagination['has_next'] ? 'true' : 'false' ?>) {
                window.location.href = 'index.php?page=<?php echo $pagination['next_page'] ?? 1 ?>';
            }
        });

    </script>
</body>
</html>