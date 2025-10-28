<?php
require_once './functions.php';

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>Ошибка подключения к базе данных</h1>";
    echo "<p>Проверьте настройки в config/database.php или запустите <a href='database/migration.php'>миграцию данных</a></p>";
    echo "<p><a href='../index.php'>← Назад к главной</a></p>";
    echo "</body></html>";
    exit;
}

// Обработка действий
$message = '';
$error = '';

// Получаем дополнительные данные
$stats = getWordsStats();
$author = getAuthor(1);

// Создание новой статьи
if (isset($_POST['create'])) {
    
        $articleData = [
            'textTitle' => sanitizeString($_POST['textTitle']),
            'userText' => sanitizeHTML($_POST['userText'])
        ];
        
        $errors = validateArticleData($articleData);
        if (empty($errors)) {
            $newId = createArticle($articleData);
            if ($newId) {
                $message = "Статья успешно создана с ID: $newId";
            } else {
                $error = 'Ошибка при создании статьи';
            }
        } else {
            $error = implode(', ', $errors);
        }
}



// Обновление статьи
if (isset($_POST['update'])) {

        $id = (int)$_POST['idTexts'];
        $articleData = [
            'textTitle' => sanitizeString($_POST['textTitle']),
            'userText' => sanitizeHTML($_POST['userText']),
        ];
        
        $errors = validateArticleData($articleData);
        if (empty($errors)) {
            if (updateArticle($id, $articleData)) {
                $message = "Статья ID $id успешно обновлена";
            } else {
                $error = 'Ошибка при обновлении статьи';
            }
        } else {
            $error = implode(', ', $errors);
        }
    
}

// Удаление статьи
if (isset($_POST['delete'])) {
  
        $id = (int)$_POST['idTexts'];
        if (deleteArticle($id)) {
            $message = "Статья ID $id успешно удалена";
        } else {
            $error = 'Ошибка при удалении статьи';
        }

}

// Получение данных
$allArticles = getAllArticles();
$authors = getAuthors();
$editingArticle = null;

// Если редактируем статью
if (isset($_GET['edit'])) {
    $editingArticle = getArticle((int)$_GET['edit']);
    if (!$editingArticle) {
        $error = 'Статья для редактирования не найдена';
    }
}

?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaineQ</title>
    <link rel="stylesheet" href="./style_new.css">
    <link rel="icon" href="images/fav.png">
    <meta name="description" content="Чтение на английском">
    <meta name="keywords" content="English, английский, чтение">
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

        <h1>Вставьте текст</h1>


            

            <form method="POST" class="placeTextForm" action = "./article.php">

            <?php if ($message): ?>
            <div class="message"> <?php echo htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error"> <?php echo htmlspecialchars($error) ?></div>
            <?php endif; ?>
                    

                <section class="placeText">
                <input type="text" name="title" id="title" placeholder="Добавьте название" class="t-input" required maxlength="255">

                <textarea name="content" id="content" class="texts-input" placeholder="Вставьте текст" required></textarea><br />
                    <button type="submit" class="menu__btn" 
                    name="create">Отправить</button>
            </form>
        </section>
    </main>

</body>
</html>