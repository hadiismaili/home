<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پنل مدیریت - جعبه لایتنر</title><link rel="stylesheet" href="/css/style.css">
<style>
body { font-family: 'Tahoma', sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 0; }
header.admin-header { background-color: #2c3e50; color: white; padding: 1rem; text-align: center; }
header.admin-header h1 { margin:0; font-size: 1.8em;}
nav.admin-nav { background-color: #34495e; padding: 0.5rem 0;}
nav.admin-nav ul { list-style-type: none; padding: 0; text-align: center; margin:0;}
nav.admin-nav ul li { display: inline-block; }
nav.admin-nav ul li a { display: block; color: white; padding: 0.8rem 1.2rem; text-decoration: none; font-size: 0.95em;}
nav.admin-nav ul li a:hover { background-color: #4a627a; border-radius: 4px;}
main.admin-main { max-width: 1200px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);}
footer.admin-footer { text-align: center; padding: 1.5rem; margin-top: 30px; background: #2c3e50; color: #aaa; font-size: 0.9em;}
h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top:0;}
</style>
</head><body>
<header class="admin-header"><h1>پنل مدیریت جعبه لایتنر</h1></header>
<nav class="admin-nav"><ul>
<li><a href="/admin/dashboard">داشبورد</a></li>
<li><a href="/admin/users">مدیریت کاربران</a></li>
<li><a href="/admin/global-words">بانک جهانی کلمات</a></li>
<li><a href="/admin/learning-sets">مجموعه‌های آموزشی</a></li>
<li><a href="/" target="_blank">مشاهده سایت</a></li>
<li><a href="/logout">خروج (<?php echo htmlspecialchars($_SESSION['admin_username'] ?? $_SESSION['username'] ?? 'کاربر'); ?>)</a></li>
</ul></nav>
<main class="admin-main">
