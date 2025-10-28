<?php
session_start();
require_once 'functions.php';

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>Ошибка подключения к базе данных</h1>";
    echo "<p><a href='index.php'>← Назад к главной</a></p>";
    echo "</body></html>";
    exit;
}

if (isset($_POST['create'])) {
// берём данные из формы базы данных и запрашиваем id
        $articleData = [
            'textTitle' => sanitizeString($_POST['title']),
            'userText' => sanitizeHTML($_POST['content']),
            'userIdText' => '1'
        ];
        
        $errors = validateArticleData($articleData);
        if (empty($errors)) {
            $newId = createArticle($articleData);
            if ($newId) {
                $message = "Книга успешно создана с ID: $newId";
            } else {
                $error = 'Ошибка при создании книги';
            }
        } else {
            $error = implode(', ', $errors);
        }
        $articleId = $newId;
        //Если создается новая статья, нет олдпейдж 
        $oldPage = 0;
    }

elseif(isset($_POST['wordStatusIgnore'])){
    $userWord = [
            'word' => sanitizeString($_POST['word']),
            'translation' => sanitizeHTML($_POST['translationWord']),
            'usersForeign' => sanitizeHTML($_POST['usersForeign']),
            'descriptionStatus' => 2,
    ];
    saveWord($userWord);
    $articleId = sanitizeHTML($_POST['articleId']);
    $oldPage = sanitizeHTML($_POST['page']);
} elseif(isset($_POST['wordStatusStudying'])){
    $userWord = [
            'word' => sanitizeString($_POST['word']),
            'translation' => sanitizeHTML($_POST['translationWord']),
            'usersForeign' => sanitizeHTML($_POST['usersForeign']),
            'descriptionStatus' => 1,
    ];
    saveWord($userWord);
    $articleId = sanitizeHTML($_POST['articleId']);
    $oldPage = sanitizeHTML($_POST['page']);
} elseif(isset($_POST['wordStatusKnown'])){
    $userWord = [
            'word' => sanitizeString($_POST['word']),
            'translation' => sanitizeHTML($_POST['translationWord']),
            'usersForeign' => sanitizeHTML($_POST['usersForeign']),
            'descriptionStatus' => 3,
    ];
    saveWord($userWord);
    $articleId = sanitizeHTML($_POST['articleId']);
    $oldPage = sanitizeHTML($_POST['page']);
} else {
// Получаем ID книги
    $articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

// Получаем книгу
$article = getArticle($articleId);

//Получаем слово
$word = getWord(1);

// Получаем дополнительные данные
$stats = getWordsStats();
$author = getAuthor(1);


// Если книга не найдена
if (!$article) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>Книга не найдена $articleId</title></head><body>";
    echo "<div style='text-align: center; padding: 3rem;'>";
    echo "<h1> Книга не найдена</h1>";
    echo "<p>Возможно, книга была удалена или вы перешли по неверной ссылке.</p>";
    echo "<a href='index.php' style='display: inline-block; background: #000000ff; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 6px; margin-top: 1rem;'>← Вернуться в библиотеку</a>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Обработка действий
$message = '';
$error = '';

// Получаем номер страницы
// $oldPage = 1;
$page = max(1, (int)($_GET['page'] ?? $oldPage));
$perPage = 120; // Слов на странице

// Получаем слова с пагинацией
//Разбиваем текст на слова
$article['userText'] = trim(preg_replace('/\s\s+/', ' ', $article['userText']));
$articleAllWords = $article['userText'];
$titleWords = explode(' ', $article['textTitle']);
$allWords = explode(' ', $articleAllWords);


// Подсчитываем общее количество слов в статье
$totalWords = count($allWords);

// Вычисляем OFFSET
$offset = ($page - 1) * $perPage;

//Нормализация слова
function normalizeWord($word){
    return strtolower(preg_replace("/[^a-zA-Z 0-9]+/", "", $word));
}

for ($i=$offset; $i<$offset+$perPage && $i<$totalWords; $i++){ 
    $normalizedWord = normalizeWord($allWords[$i]);
    $pageWords[$normalizedWord] = [
                'status' => 0,
                'translation' => NULL
    ];
}

foreach ($titleWords as $titleWord){
    $normalizedWord = normalizeWord($titleWord);
    $pageWords[$normalizedWord] = [
                'status' => 0,
                'translation' => NULL
    ];
}


foreach (getWords(array_keys($pageWords)) as $wordData){
    if(array_key_exists($wordData['word'], $pageWords)){
        $pageWords[$wordData['word']]['status']= $wordData['descriptionStatus'];
        $pageWords[$wordData['word']]['translation'] = $wordData['translation'];
    }
}

$pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalWords / $perPage),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($totalWords / $perPage),
                'prev_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < ceil($totalWords / $perPage) ? $page + 1 : null,
                'article_Id' => $articleId
            ];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['textTitle']) ?> | LaineQ</title>
    <link rel="stylesheet" href="style_new.css">
    <link rel="icon" href="images/fav.png">
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
        
        <!-- Содержимое статьи -->
        <main class="article-content">
            <div class="article-text">

<!-- статья -->
        <section class="articles" id ="latest">
            <div class="article-row">
                <div class="article-element-1">
                        <?php if($pagination['current_page'] == 1){ ?>
                        <img width="40px" src="/images/arrow-white-left.png" class="hiddenArrow">
                        <?php } else { ?>
                        <a href='<?php echo previousPagination($pagination, 'article.php'); ?>'>
                        <img width="40px" src="/images/arrow-white-left.png"></a> 
                       <?php } 
                       ?>
                </div>

                <div class="article-element-2">
                    
                    <?php if($page == 1){ ?>
                        <b>
                        <?php foreach ($titleWords as $titleWord){ 
                        $normalizedWord = normalizeWord($titleWord); ?>
                        <span class = "word-status-<?php echo $pageWords[$normalizedWord]['status']?>"  onclick='changeText(event, <?php echo "\"" .  $normalizedWord . "\"" ?>)'>
                        <?php echo $titleWord?></span>
                        <?php }
                         
                        ?>
                        </b> <br><br>
                        <?php } ?>
            


                        <?php for ($i=$offset; $i<$offset+$perPage && $i<$totalWords; $i++){ 
                        $normalizedWord = normalizeWord($allWords[$i]); ?>
                        <span class = "word-status-<?php echo $pageWords[$normalizedWord]['status']?>"  onclick='changeText(event, <?php echo "\"" .  $normalizedWord . "\"" ?>)'>
                        <?php echo $allWords[$i]?></span>
                        <?php  } ?>
                </div>
                
                <div class="article-element-3">
                        <?php if($pagination['current_page'] == $pagination['total_pages']){ ?>
                        <img width="40px" src="/images/arrow-white-right.png" class="hiddenArrow">
                         
                        <?php } else { ?>
                        <a href='<?php echo nextPagination($pagination, 'article.php'); ?>'>
                        <img width="40px" src="/images/arrow-white-right.png"></a>
                       <?php } ?>
                </div>
            </div>
            
        </section>
                <!-- Пагинация -->
                 <div class="article-element-4">
                <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-wrapper">
                    <?php echo renderWordPagination($pagination, 'article.php'); ?>
                </div>
                <?php endif; ?>
                </div>
                <!-- Правое меню -->
             </div>
                <div class="right-menu-dictionary">
                    <div class="dictionary-structure">
                    <form method="POST" id="formStatus">
                        <input type="hidden" name ="articleId" value="<?php echo $articleId?>">
                        <input type="hidden" name ="page" value ="<?php echo $page?>">
                        <input type="hidden" id = "rightFieldWord" name ="word">
                        <input type="hidden" name ="usersForeign" value = "1">
                        <div class="dictionary-word"><b><p id ="slovo">Click on word</p></b></div>
                        <div class="input-dictionary-word">
                        <textarea class="word-input" id="translationWord" name="translationWord" placeholder="Ваш перевод здесь"></textarea>
                        </div>
                        <div class="dictionary-buttons">
                            <button class="word-status red" name="wordStatusIgnore"></button>
                            <button class="word-status yellow" name="wordStatusStudying"></button>
                            <button class="word-status green" name="wordStatusKnown"></button>
                        </div>
                    </div>
                    </form>
            </div>

        </main>
        <script>
        // Предзагрузка следующей страницы (опционально)
        <?php if ($pagination['has_next']): ?>
        const nextPageLink = 'article.php?id=<?php echo $pagination['article_Id'] ?>&page=<?php echo $pagination['next_page'] ?>';
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
                window.location.href = 'article.php?id=<?php echo $pagination['article_Id'] ?>&page=<?php echo $pagination['prev_page'] ?? 1 ?>';
            } else if (e.key === 'ArrowRight' && <?php echo $pagination['has_next'] ? 'true' : 'false' ?>) {
                window.location.href = 'article.php?id=<?php echo $pagination['article_Id'] ?>&page=<?php echo $pagination['next_page'] ?? 1 ?>';
            }
        });
        
        // 
    function changeText(event, normalizedWord) {
        // let normalWord = event.target.innerHTML.replace(/\W|_/g, '');
        // normalWord = normalWord.toLowerCase();
        console.log(normalizedWord);
        document.getElementById("slovo").innerHTML = normalizedWord;
        document.getElementById("rightFieldWord").value = normalizedWord;
        const translationTextArea = document.getElementById("translationWord");
        translationTextArea.value = pageWords.get(normalizedWord).translation;
        translationTextArea.style.height = "auto";
        translationTextArea.style.height = translationTextArea.scrollHeight + "px";
        translationTextArea.style.overflowY = "hidden";
    }

//достать эррей
    let pageWordsObject = <?php echo json_encode($pageWords); ?>;
    const pageWords = new Map(Object.entries(pageWordsObject));

        </script>
</body>
</html>