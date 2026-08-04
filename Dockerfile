FROM php:8.2-apache

# ลง library พื้นฐานที่ gd extension ต้องใช้ตอน compile (ไม่มีตัวนี้จะ build gd ไม่ผ่าน)
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && rm -rf /var/lib/apt/lists/*

# ตั้งค่า gd ให้รองรับ jpeg/freetype ก่อน install
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# ลง extension ที่ it_support ต้องใช้
# - pdo_mysql: สำหรับต่อ MySQL ผ่าน PDO (ที่ใช้อยู่ใน config/db.php)
# - gd: สำหรับ resize รูปตอน upload (GD Library ที่ทำไว้)
RUN docker-php-ext-install pdo_mysql gd

# เปิด mod_rewrite เผื่อใช้ .htaccess (redirect, clean URL)
RUN a2enmod rewrite

# ตั้ง working directory ให้ตรงกับที่ Apache จะ serve
WORKDIR /var/www/html