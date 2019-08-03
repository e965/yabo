<?php
	class Book {
		public function __construct($DB, $user) {
			$this->DB = $DB;
			$this->user = $user;
		}

		private function prepareString($string) {
			return addslashes(trim(preg_replace('/\s+/', ' ', $string)));
		}

		private function result($code, $msg) {
			if (array_key_exists('result', $GLOBALS)) {
				$GLOBALS['result'] = [
					'code' => $code, 'msg' => $msg
				];
			}
		}

		public function add($title, $author, $genre, $year) {
			$title = $this->prepareString($title);
			$author = $this->prepareString($author);
			$genre = $this->prepareString($genre);
			$year = intval($year);

			if (
				$title === '' ||
				$author === '' ||
				$genre === '' ||
				$year === ''
			) {
				$this->result(2, 'Ошибка добавления книги: отправлены пустые данные');
				return;
			}

			if ($year >= date('Y')) {
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
				? $this->result(1, 'Книга "' . $book['title'] . '" добавлена')
				: $this->result(2, 'Ошибка добавления книги: ' . $this->DB->error);

			$query->close();
		}

		public function edit($book_id, $editedParams) {
			$book_id = intval($book_id);
			$editedParams = json_decode($editedParams);

			$query = $this->DB->prepare("SELECT title, author, genre, year FROM books WHERE id = ?");

			$query->bind_param("i", $book_id);

			$query->execute();

			$query->bind_result($title, $author, $genre, $year);
			$query->fetch();

			$query->close();

			$isChanged = !empty($editedParams);

			foreach ($editedParams as $key => $value) {
				$value = $key !== 'year'
					? $this->prepareString($value)
					: intval($value);

				switch ($key) {
					case 'title':
						if ($title !== $value) {
							echo $title;
							$title = $value;
						} else {
							$isChanged = false;
						} break;

					case 'author':
						if ($author !== $value) {
							$author = $value;
						} else {
							$isChanged = false;
						} break;

					case 'genre':
						if ($genre !== $value) {
							$genre = $value;
						} else {
							$isChanged = false;
						} break;

					case 'year':
						if ($year !== $value) {
							$year = $value;
						} else {
							$isChanged = false;
						} break;

					default:
						$isChanged = false;
				}
			}

			if ($isChanged) {
				$query = $this->DB->prepare("UPDATE books SET title = ?, author = ?, genre = ?, year = ? WHERE id = ?");

				$query->bind_param("sssii",
					$title,
					$author,
					$genre,
					$year,
					$book_id
				);

				$queryState = $query->execute()
					? $this->result(1, 'Книга "' . $title . '" обновлена')
					: $this->result(2, 'Ошибка обновления книги: ' . $this->DB->error);

				$query->close();
			} else {
				$this->result(0, 'Изменений в книге "' . $title . '" нет');
			}
		}

		public function rm($book_id) {
			$book_id = intval($book_id);

			$query = $this->DB->prepare("DELETE FROM books WHERE id = ?");

			$query->bind_param("i", $book_id);

			print_r($query->execute());

			$queryState = $query->execute()
				? $this->result(1, 'Книга с ID ' . $book_id . ' удалена')
				: $this->result(2, 'Ошибка удаления книги: ' . $this->DB->error);

			$query->close();
		}
	}
