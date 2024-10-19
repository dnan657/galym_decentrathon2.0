<?

/*

CREATE DATABASE askbot;
USE askbot;

CREATE TABLE `users` (
	`id` int NOT NULL AUTO_INCREMENT,
	`email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`pass_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`create_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `email` (`email`),
	KEY `create_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci


CREATE TABLE `translations` (
	`id` int NOT NULL AUTO_INCREMENT,
	`title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`description` varchar(500) COLLATE utf8mb4_unicode_ci,
	`data` text COLLATE utf8mb4_unicode_ci,
	`data_roasting` text COLLATE utf8mb4_unicode_ci,
	`transcript` text COLLATE utf8mb4_unicode_ci,
	`transcript_date` timestamp DEFAULT NULL,
	`transcript_duration` int DEFAULT 0,
	`status` tinyint(1) DEFAULT 0,
	`user_id` int DEFAULT NULL,
	`public_date` datetime DEFAULT NULL,
	`create_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `user_id` (`user_id`),
	KEY `author` (`author`),
	KEY `title` (`title`),
	KEY `status` (`status`),
	KEY `public_date` (`public_date`),
	KEY `create_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `asks` (
	`id` int NOT NULL AUTO_INCREMENT,
	`translation_id` int DEFAULT NULL,
	`user_id` int DEFAULT NULL,
	`context` text COLLATE utf8mb4_unicode_ci,
	`question` text COLLATE utf8mb4_unicode_ci,
	`answer` text COLLATE utf8mb4_unicode_ci,
	`create_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `translation_id` (`translation_id`),
	KEY `user_id` (`user_id`),
	KEY `create_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

*/

error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_WARNING );

function cookie_set($name, $value){
	setcookie($name, $value, strtotime('+1 month'), '/', $_SERVER['SERVER_NAME'], isset($_SERVER['HTTPS']), true);
}
function cookie_get($name){
	return $_COOKIE[$name] ?? false;
}
function cookie_delete($name){
	setcookie($name, '', time() - 3600, '/', $_SERVER['SERVER_NAME'], isset($_SERVER['HTTPS']), true);
}


function mb_trim($string, $trim_chars = '\s'){
    return preg_replace('/^['.$trim_chars.']*(?U)(.*)['.$trim_chars.']*$/u', '\\1',$string);
}


function api_response($data_json=[]){
	header("Content-type: application/json; charset=utf-8");
	exit( json_encode($data_json, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT) );
}


function redirect($uri='/') {
   // Используем заголовок для перенаправления
	header('Location: ' . $uri);
	exit;
}

function valid_date($date, $format = 'Y-m-d'){
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}


function db_value($value){
	return addslashes($value);
}

function db_column($name){
	return preg_replace("/[^a-zA-Z0-9_]/", "", $name);
}


function db_query($query) {
    // Создаем соединение с базой данных
    $link = mysqli_connect(
		'localhost', // адрес сервера
		'askbot', // имя пользователя
		"(4crp6qdW*7(MW32", // пароль
		'askbot' // имя базы данных
	);

    // Проверяем соединение
    if (!$link) {
		api_response(['error'=>'No connection to the database']);
    }

    // Выполняем запрос
    $result = mysqli_query($link, $query);
	
	// Проверяем наличие ошибки
	if (!$result) {
		// Выводим сообщение об ошибке
		//$error = mysqli_error($link);
		// Закрываем соединение
		mysqli_close($link);
		api_response(['error'=>'Invalid DB query']);
		return null;
	}

    // Определяем, является ли запрос SELECT используя функцию mysqli_field_count
    if (mysqli_field_count($link) > 0) {
        // Возвращаем все данные в виде ассоциативного массива
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Закрываем соединение
    mysqli_close($link);

    // Для не-SELECT запросов возвращаем null
    return null;
}


function row_id($table, $id){
	$id = intval($id);
	$table = db_column($table);
	$item = db_query("SELECT * FROM {$table} WHERE id = {$id} LIMIT 1")[0];
	return $item ?? false;
}

function user_id($id){
	$id = intval($id);
	$user = db_query("SELECT * FROM users WHERE id = {$id} LIMIT 1")[0];
	return $user ?? false;
}

function user_get(){
	$cookie_user = cookie_get('user') ?: false;
	
	if( $cookie_user == false ){
		return false;
	}
	
	$arr_user = explode('-', $cookie_user);
	$id = intval($arr_user[0]);
	$hash_pass = $arr_user[1];
	
	$user = user_id($id);
	
	if( !$user ){
		cookie_delete('user');
		return false;
	}
	
	cookie_set('user', $cookie_user);
	
	return $user;
}

?>