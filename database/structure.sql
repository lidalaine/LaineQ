
-- Создание базы данных (если не существует)
CREATE DATABASE IF NOT EXISTS LaineQ CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE LaineQ;





-- 1. Таблица слов
DROP TABLE IF EXISTS userWords;
CREATE TABLE userWords (
    idUserWords INT PRIMARY KEY AUTO_INCREMENT,
    word VARCHAR(45) NOT NULL UNIQUE,
    translation VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usersForeign INT NOT NULL,
    descriptionStatus INT NOT NULL,

-- Внешние ключи
    FOREIGN KEY (usersForeign) REFERENCES users(userId) ON DELETE CASCADE,
    FOREIGN KEY (descriptionStatus) REFERENCES statusDescription(statusCode) ON DELETE CASCADE
);

-- 2. Главная таблица текстов
DROP TABLE IF EXISTS texts;
CREATE TABLE texts (
    idTexts INT PRIMARY KEY AUTO_INCREMENT,
    textTitle VARCHAR(255) NOT NULL,
    wordCount INT,
    public VARCHAR(10),
    userIdText INT,
    userText LONGTEXT,
    published_at DATE,
    
    -- Внешние ключи
    FOREIGN KEY (userIdText) REFERENCES users(userId) ON DELETE CASCADE
    
);

-- 3. Таблица users
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    userId INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(32),
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    isAdmin TINYINT
);


-- 4. Таблица статуса слов
DROP TABLE IF EXISTS statusDescription;
CREATE TABLE statusDescription (
    statusCode INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    statusDescriptions VARCHAR(45)
);





-- Вставка тестовых данных

-- Авторы
INSERT INTO users (userId, username, email, password, isAdmin) VALUES
(1, 'Laine', 'laineq@laineq.ru', '123', 1),
(2, 'Oleg', 'cmertb@laineq.ru', '321', 0);

-- Категории
INSERT INTO texts (idTexts, textTitle, public, userIdText, userText) VALUES
(1, 'Harry Potter and the Goblet of Fire', 'public', 1, 
'The villagers of Little Hangleton still called it ‘the Riddle House’, even though it had been many years since the
Riddle family had lived there. It stood on a hill overlooking the village, some of its windows boarded, tiles missing
from its roof, and ivy spreading unchecked over its face. Once a fine-looking manor, and easily the largest and grandest
building for miles around, the Riddle House was now damp, derelict and unoccupied.
The Little Hangletons all agreed that the old house was ‘creepy’. Half a century ago, something strange and
horrible had happened there, something that the older inhabitants of the village still liked to discuss when topics for
gossip were scarce. The story had been picked over so many times, and had been embroidered in so many places, that
nobody was quite sure what the truth was any more. Every version of the tale, however, started in the same place: fifty
years before, at daybreak on a fine summer’s morning, when the Riddle House had still been well kept and impressive,
and a maid had entered the drawing room to find all three Riddles dead.
The maid had run screaming down the hill into the village, and roused as many people as she could.
‘Lying there with their eyes wide open! Cold as ice! Still in their dinner things!’
The police were summoned, and the whole of Little Hangleton had seethed with shocked curiosity and
ill-disguised excitement. Nobody wasted their breath pretending to feel very sad about the Riddles, for they had been
most unpopular. Elderly Mr and Mrs Riddle had been rich, snobbish and rude, and their grown-up son, Tom, had been
even more so. All the villagers cared about was the identity of their murderer – plainly, three apparently healthy people
did not all drop dead of natural causes on the same night.'),
(2, 'Harry Potter and the Chamber of Secrets', 'public', 2, 'Lorem ipsum'),
(3, 'Harry Potter and the Prisoner of Azkaban', 'public', 1, 'Lorem ipsum'),
(4, 'Mistborn', 'public', 2, 'Terra incognita');

INSERT INTO userWords (idUserWords,word,translation,descriptionStatus,usersForeign) VALUES
(1, 'house', 'дом', '1','1'),
(2, 'hangleton', '', '2','1'),
(3, 'many', 'много', '1','1'),
(4, 'creepy', 'жуткий', '3','1'),
(5, 'village', 'деревня', '1','1');

INSERT INTO statusDescription (statusCode,statusDescriptions) VALUES
(1, 'На повторении'),
(2, 'Исключено'),
(3, 'Изучено');


