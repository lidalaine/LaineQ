<?php
// config/database.php - Настройки подключения к базе данных

// Настройки подключения к MySQL
define('DB_HOST', 'MySQL-8.4');
define('DB_NAME', 'LaineQ');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Получение подключения к базе данных PDO
 * @return PDO|false - объект PDO или false при ошибке
 */
function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return false;
        }
    }
    
    return $pdo;
}

/**
 * Проверка соединения с базой данных
 * @return bool
 */
function testDatabaseConnection() {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            echo "❌ Ошибка: Не удалось подключиться к базе данных<br>";
            return false;
        }
        
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        
        echo "✅ Соединение с базой данных успешно установлено!<br>";
        echo "📊 MySQL версия: " . $result['version'] . "<br>";
        echo "📋 База данных: " . DB_NAME . "<br>";
        echo "🏠 Хост: " . DB_HOST . "<br>";
        
        return true;
    } catch (PDOException $e) {
        echo "❌ Ошибка соединения с БД: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Автоматическая проверка соединения при прямом вызове файла
if (basename($_SERVER['PHP_SELF']) === 'database.php') {
    echo "<h2>🔧 Тестирование подключения к базе данных</h2>";
    testDatabaseConnection();
}
?>