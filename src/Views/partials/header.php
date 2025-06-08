<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جعبه لایتنر آلمانی</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <h1>جعبه لایتنر آلمانی</h1>
        <nav>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/leitner/dashboard">داشبورد</a></li>
                    <li><a href="/leitner/vocabulary">مدیریت واژگان</a></li>
                    <li><a href="/logout">خروج (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="/login">ورود</a></li>
                    <li><a href="/register">ثبت نام</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
