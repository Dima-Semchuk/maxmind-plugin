<?php
/*
Plugin Name: Maxmind-Location2
Description: Automatic website translation.
Version: 1.0
Author: DS
*/

require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

function get_current_mmdb_version() {
  // Используйте get_option() для получения сохраненной текущей версии базы данных из опции WordPress
  return get_option('current_mmdb_version', null);
}

// Функция для сохранения текущей версии базы данных GeoLite2-City
function set_current_mmdb_version($version) {
  // Используйте update_option() для сохранения текущей версии базы данных в опции WordPress
  update_option('current_mmdb_version', $version);
}

function get_public_ip_address() {
  // Запрос к внешнему сервису для получения текущего IP-адреса
  $ip = file_get_contents('https://api.ipify.org');
  return $ip;
}

function find_mmdb_file() {
  // Ищем файл GeoLite2-City.mmdb в папке /database и всех ее подкаталогах
  $files = glob(__DIR__ . '/database/**/GeoLite2-City.mmdb');
  
  // // Проверяем, что хотя бы один файл найден
  // if (!empty($files)) {
  //     // Возвращаем путь к первому найденному файлу
  //     return $files[0];
  // } else {
  //     // Если файл не найден, возвращаем null
  //     return null;
  // }

  return !empty($files) ? $files[0] : null;
}










function get_user_country() {
  $mmdb_file = find_mmdb_file();
  if ($mmdb_file !== null) {
    $reader = new Reader($mmdb_file);
    $ipAddress = get_public_ip_address();
    try {
      $record = $reader->city($ipAddress);
      return $record->country->isoCode; // Возвращает двухбуквенный код страны
    } catch (Exception $e) {
      return null;
    }
  }
  return null;
}

function get_location_message() {
  $countryCode = get_user_country();
  if ($countryCode === 'US') {
    if (is_admin()) {
      return "You are in the WordPress admin panel.";
    } else {
      return "You are in the WordPress theme.";
    }
  } elseif ($countryCode === 'UA') {
    if (is_admin()) {
      return "Ви знаходитесь в адмін панелі WordPress.";
    } else {
      return "Ви знаходитесь у темі WordPress.";
    }
  } else {
    if (is_admin()) {
      return "You are in the WordPress admin panel.";
    } else {
      return "You are in the WordPress theme.";
    }
  }
}

// Функция для вывода сообщения в админке
function display_location_message() {
  $message = get_location_message();
  echo '<h4 style="position: absolute; z-index: 10; right: 50%;" class="notice notice-info">' . esc_html($message) . '</h4>';
}

add_action('admin_notices', 'display_location_message');

// Функция для вывода сообщения в теме
function display_location_message_theme() {
  $message = get_location_message();
  echo '<h4 style="position: absolute; z-index: 10; right: 50%; top: 10%;" class="location-message">' . esc_html($message) . '</h4>';
}

add_action('wp_body_open', 'display_location_message_theme');









function set_language_and_currency($country_code) {
  global $site_language, $site_currency;

  $language = 'en';
  $currency = 'USD';
  
  switch ($country_code) {
      case 'FR':
          $language = 'fr';
          $currency = 'EUR';
          break;
      case 'DE':
          $language = 'de';
          $currency = 'EUR';
          break;
      case 'UA':
          $language = 'uk';
          $currency = 'UAH';
          break;
      // Добавьте другие страны и языки по мере необходимости
  }

  echo "Setting language: " . $language . " and currency: " . $currency; // Отладочное сообщение
}

function my_plugin_function() {
  // Получаем публичный IP-адрес текущего пользователя
  $user_ip = get_public_ip_address();
  // Находим путь к файлу GeoLite2-City.mmdb
  $mmdb_file = find_mmdb_file();
  if ($mmdb_file !== null) {
    // Создаем экземпляр Reader с найденным путем
    $reader = new Reader($mmdb_file); //инстанс класса
    // Получаем публичный IP-адрес текущего пользователя
    $user_ip = get_public_ip_address(); //Нужно проверить, возможно этот код  
    
    // Определяем местоположение пользователя
    $record = $reader->city($user_ip);

    $country_code = $record->country->isoCode;// Отладочное сообщение, можно удалить
    // Выводим информацию о местоположении пользователя
    echo "Страна: " . $record->country->name . "\n";
    echo "Город: " . $record->city->name . "\n";
    echo "Координаты (широта): " . $record->location->latitude . "\n";
    echo "Координаты (долгота): " . $record->location->longitude . "\n";

    set_language_and_currency($country_code);
    
    } else {
      // Сообщаем, что файл не найден
      echo "Файл GeoLite2-City.mmdb не найден.";
    }
}

// Выполняем функцию при загрузке WordPress
add_action('wp_loaded', 'my_plugin_function');



function update_maxmind_database() {
  // URL для загрузки новой версии базы данных MaxMind GeoIP2
  // $download_url = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=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&suffix=tar.gz';
  $download_url = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=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&suffix=tar.gz';
  // Получаем текущую версию базы данных
  $current_version = get_current_mmdb_version();

  // // Инициализируем переменную для новой версии базы данных
  // $new_version = '';

  // Получаем заголовки файла для извлечения версии (пример)
  $headers = get_headers($download_url, 1);
  if (isset($headers['Last-Modified'])) {
    // Извлекаем дату и время последнего изменения ресурса из заголовка
    $new_version = $headers['Last-Modified'];

    error_log("New version: $new_version"); //debug.log можно убрать
    error_log("current_version: $current_version"); //debug.log можно убрать
    

    if ($new_version !== $current_version) {
      // Путь, куда будет сохранена загруженная база данных
      $database_path = __DIR__ . '/GeoLite2-City.tar.gz';

      // Удаление архива GeoLite2-City.tar.gz
      if (file_exists($database_path)) {
      unlink($database_path);
      }

      // Удаление архива GeoLite2-City.tar
      $tar_file_path = __DIR__ . '/GeoLite2-City.tar';
      if (file_exists($tar_file_path)) {
      unlink($tar_file_path);
      }

      // Удаление папки GeoLite2-City, если она существует
      function removeDirectory($directory) {
        // Проверяем, что это директория и она существует
        if (!is_dir($directory)) {
          return;
        }

        // Получаем список всех файлов и подкаталогов в директории
        $files = scandir($directory);

        // Итерируемся по всем файлам и подкаталогам
        foreach ($files as $file) {
          if ($file === '.' || $file === '..') {
              // Пропускаем текущую и родительскую директории
              continue;
          }
          // Формируем полный путь к файлу или подкаталогу
          $filePath = $directory . '/' . $file;
          // Если это файл, удаляем его
          if (is_file($filePath)) {
              unlink($filePath);
          } elseif (is_dir($filePath)) {
              // Если это подкаталог, вызываем функцию рекурсивно
              removeDirectory($filePath);
          }
        }

        // Удаляем пустую директорию
        rmdir($directory);
      }

      // Используйте функцию для удаления папки '__DIR__/database' вместе со всем ее содержимым
      $database_folder = __DIR__ . '/database';
      removeDirectory($database_folder);


      // Загрузка базы данных
      $downloaded = file_put_contents($database_path, file_get_contents($download_url));

      if ($downloaded !== false) {
        // Распаковка архива
        $phar = new PharData($database_path);
        $phar->decompress();
        $extracted_files = $phar->extractTo(__DIR__ . '/database');

        // Проверка на успешную распаковку
        if ($extracted_files !== false) {
            // Удаление загруженного архива
            unlink($database_path);
            // Обновляем текущую версию базы данных
            set_current_mmdb_version($new_version);
            echo "База данных MaxMind успешно обновлена.";// erorlog в скобки строку
        } else {
            echo "Ошибка при распаковке архива.";
        }
      } else {
        echo "Ошибка при загрузке базы данных MaxMind.";
      }
      } else {
        // Если версия актуальна, ничего не делаем
        echo "База данных MaxMind актуальна.";
      }
    } else {
      echo "Не удалось получить заголовки HTTP.";
    }
}


// Добавление задачи крона при активации плагина
function schedule_maxmind_update() {
  // wp_next_scheduled Позволяет проверить есть ли в крон указанное задание. Возвращает метку времени/false
  if (!wp_next_scheduled('maxmind_update_event')) {
    // wp_schedule_event планирует повторяющие событие
      wp_schedule_event(time(), 'hourly', 'maxmind_update_event');
  }
}

//Эта функция прикрепляет указанную callback функцию к хуку activate_(plugin) и является оберткой для этого хука
register_activation_hook(__FILE__, 'schedule_maxmind_update');

// Удаление задачи крона при деактивации плагина
function unschedule_maxmind_update() {
  wp_clear_scheduled_hook('maxmind_update_event');
}
register_deactivation_hook(__FILE__, 'unschedule_maxmind_update');

// Выполнение обновления базы данных по расписанию
add_action('maxmind_update_event', 'update_maxmind_database');




// // Функция для определения текущего местоположения
// function get_location_message() {
//   if (is_admin()) {
//       return "Вы находитесь в админке WordPress.";
//   } else {
//       return "Вы находитесь в теме WordPress.";
//   }
// }

// // Функция для вывода сообщения
// function display_location_message() {
//   $message = get_location_message();
//   echo '<h4 style="position: absolute; z-index: 10; right: 50%;" class="notice notice-info">' . esc_html($message) . '</h4>';
// }

// // Хук для вывода сообщения в админке
// add_action('admin_notices', 'display_location_message');

// // Функция для вывода сообщения в теме
// function display_location_message_theme() {
// $message = get_location_message();
// echo '<h4 style="position: absolute; z-index: 10; right: 50%; top: 10%;" class="location-message">' . esc_html($message) . '</h4>';
// }

// // Хук для вывода сообщения в теме
// add_action('wp_body_open', 'display_location_message_theme');