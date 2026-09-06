####  laravel12_wtg_spain
####  Currency Commission Calculation
##### About The Project 
* test task for WTG_Spain
* ![изображение](https://github.com/vadimlvov71/currency_commission/assets/57807117/43caa3ae-5475-45ee-aed0-e6aa26c45cda)


##### Prerequisites
* php 8.2
* Laravel 12
* queue by MySQL
* Mysql

##### Installation

1. Clone the repo
   ```sh
   git clone git@github.com:vadimlvov71/laravel12_wtg_spain.git
2. run  "php app.php input.php"
3. run functional tests:
* ```sh
      vendor/bin/phpunit tests



##### run command:
php artisan queue:work --verbose

## Механизм защиты від подвійного бронювання | Double Booking Prevention Mechanism

### 🔒 Проблема | Problem

При одночасних запитах від двох клієнтів на останню доступну одиницю пропозиції, існує ризик, що обидва замовлення будуть прийняті, хоча доступна лише одна одиниця.

**Приклад:**
- Offer має 1 доступну одиницю
- Клієнт A та Клієнт B одночасно роблять запит на бронювання
- Обидва мають отримати помилку, але один з них повинен успішно забронювати

---

When two clients make simultaneous requests for the last available unit of an offer, there is a risk that both orders will be accepted, even though only one unit is available.

**Example:**
- Offer has 1 available unit
- Client A and Client B make booking requests simultaneously
- Both should get an error, but one of them should successfully book

---
### ✅ Рішення | Solution

#### 1. **Database-Level Locking (FOR UPDATE)**

Система використовує блокування на рівні бази даних для гарантування атомарності операції.

```php
DB::transaction(function () use ($offer, $data) {
    // Блокуємо рядок у таблиці offers (FOR UPDATE)
    $lockedOffer = Offer::lockForUpdate()->find($offer->id);
    
    // Процес 2 чекає, поки процес 1 завершить транзакцію
    // Максимальний час очікування залежить від БД (MySQL: ~50s)
    
    // Перевіряємо доступність одиниць
    if ($activeReservations >= $lockedOffer->available_units) {
        throw new Exception('No available units');
    }
    
    // Створюємо бронювання
    $reservation = Reservation::create([...]);
    
}); // Блокировка автоматично знімається