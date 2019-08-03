<?php
	include_once 'php_inc/book.php';

	$config = json_decode(file_get_contents('config.json'), true);

	$result = [
		// 0 - пустые запросы, 1 - успех, 2 - ошибка в скриптах, 3 - ошибка сервера
		'code' => 0, 'msg' => 'Пустой запрос'
	];

	header('Content-Type: application/json');

	include_once 'php_inc/db-init.php';

	$user = !empty($_SERVER['PHP_AUTH_USER'])
		? $_SERVER['PHP_AUTH_USER']
		: 'lmao';

	$book = new Book($DB, 'piser');

	switch($_POST["action"]) {
		case 'add':
			$book->add($_POST["title"], $_POST["author"], $_POST["genre"], $_POST["year"]); break;

		case 'edit':
			$book->edit($_POST["book_id"], $_POST["edited_params"]); break;

		case 'rm':
			$book->rm($_POST["book_id"]); break;
	}

	$DB->close();

	echo json_encode($result, JSON_UNESCAPED_UNICODE);
