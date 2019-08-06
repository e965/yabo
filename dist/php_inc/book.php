<?php
	class Book {
		public function __construct($DB, $user) {
			$this->DB = $DB;
			$this->user = $user;
		}

		private function prepareString($string) {
			return addslashes(trim(preg_replace('/\s+/', ' ', $string)));
		}

		private function result($code, $msg, $data) {
			if (array_key_exists('result', $GLOBALS)) {
				$GLOBALS['result'] = [
					'code' => $code, 'msg' => $msg
				];

				if ($data) {
					$GLOBALS['result']['data'] = $data;
				}
			}
		}

		public function getAll() {
			$query = $this->DB->prepare("SELECT id, title, author, genre, year FROM books WHERE user = ?");

			$query->bind_param("s", $this->user);

			$query->execute();

			$queryResult = $query->get_result();

			if ($queryResult->num_rows > 0) {
				$data = [];

				while ($row = $queryResult->fetch_assoc()) {
					array_push($data, $row);
				}

				$this->result(1, 'Список книг получен', $data);
			} else {
				$this->result(0, 'Список книг пуст');
			}

			$query->close();
			$query->free_result();
		}

		public function add($title, $author, $genre, $year) {
			$title = $this->prepareString($title);
			$author = $this->prepareString($author);
			$genre = $this->prepareString($genre);
			$year = intval($year);

			if ($genre === '') {
				$genre = 'Без жанра';
			}

			if (
				$title === '' ||
				$author === '' ||
				$year === ''
			) {
				$this->result(2, 'Ошибка добавления книги: отправлены пустые данные');
				return;
			}

			if ($year > date('Y')) {
				$this->result(2, 'Ошибка добавления книги: год больше текущего');
				return;
			}

			$book = [
				'title' => $title, 'author' => $author,
				'genre' => $genre, 'year' => $year
			];

			$query = $this->DB->prepare("INSERT INTO books (
				title,
				author,
				genre,
				year,
				user
			) VALUES (?, ?, ?, ?, ?)");

			$query->bind_param("sssis",
				$book['title'],
				$book['author'],
				$book['genre'],
				$book['year'],
				$this->user
			);

			$queryState = $query->execute()
				? $this->result(1, 'Книга "' . $book['title'] . '" добавлена', [ 'id' => $this->DB->insert_id ])
				: $this->result(2, 'Ошибка добавления книги: ' . $this->DB->error);

			$query->close();
			$query->free_result();
		}

		public function edit($book_id, $editedParams) {
			$book_id = intval($book_id);
			$editedParams = json_decode($editedParams);

			$query = $this->DB->prepare("SELECT title, author, genre, year, user FROM books WHERE id = ?");

			$query->bind_param("i", $book_id);

			$query->execute();

			$query->bind_result($title, $author, $genre, $year, $user);
			$query->fetch();

			$query->close();

			if ($user === '') {
				$this->result(2, 'Книги с ID ' . $book_id . ' не существует');
				return;
			}

			if ($user !== $this->user) {
				$this->result(2, 'Ошибка доступа');
				return;
			}

			$bookInfo = [
				'title' => $title,
				'author' => $author,
				'genre' => $genre,
				'year' => $year
			];

			/*
			 * Доступ к значениям у $editedParams: $editedParams->$key
			 * Доступ к значениям у $bookInfo: $bookInfo[$key]
			 */

			foreach ($editedParams as $key => $value) {
				if (array_key_exists($key, $bookInfo)) {
					$value = $key !== 'year'
						? $this->prepareString($value)
						: intval($value);

					if ($key !== 'year') {
						$value = $this->prepareString($value);
					} else {
						$value = intval($value);

						if ($value > date('Y')) {
							continue;
						}
					}

					$bookInfo[$key] = $value;
				} else {
					unset($editedParams->$key);
				}
			}

			$query->free_result();

			$isChanged = !empty($editedParams);

			if ($isChanged) {
				$query = $this->DB->prepare("UPDATE books SET title = ?, author = ?, genre = ?, year = ? WHERE id = ?");

				$query->bind_param("sssii",
					$bookInfo['title'],
					$bookInfo['author'],
					$bookInfo['genre'],
					$bookInfo['year'],
					$book_id
				);

				$queryState = $query->execute()
					? $this->result(1, 'Книга "' . $title . '" успешно обновлена')
					: $this->result(2, 'Ошибка обновления книги: ' . $this->DB->error);

				$query->close();
				$query->free_result();
			} else {
				$this->result(0, 'Изменений в книге "' . $title . '" нет');
			}
		}

		public function rm($book_id) {
			$book_id = intval($book_id);

			$query = $this->DB->prepare("SELECT title, user FROM books WHERE id = ?");

			$query->bind_param("i", $book_id);

			$query->execute();

			$query->bind_result($title, $user);
			$query->fetch();

			$query->close();

			if ($user !== $this->user) {
				$this->result(2, 'Ошибка доступа');
				return;
			}

			$query->free_result();

			$query = $this->DB->prepare("DELETE FROM books WHERE id = ?");

			$query->bind_param("i", $book_id);

			$queryState = $query->execute()
				? $this->result(1, 'Книга "' . $title . '" удалена')
				: $this->result(2, 'Ошибка удаления книги: ' . $this->DB->error);

			$query->close();
			$query->free_result();
		}
	}
