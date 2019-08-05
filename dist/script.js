'use strict'

document.addEventListener('DOMContentLoaded', () => {
	const reqURL = 'request.php'

	const table = document.querySelector('.books table tbody')

	const modalActions = document.querySelector('.modal__book-actions')

	const modalActionsForm = modalActions.querySelector('form')

	const alertBox = document.querySelector('.alert-box')

	let newAlert = ({ text, type = 'success' }) => {
		let alert = document.createElement('div')

		Array.from([
			`uk-alert-${type}`,
			'uk-animation-slide-bottom',
			'uk-padding-small',
			'uk-margin-small',
		]).forEach(item => alert.classList.add(item))

		alert.setAttribute('uk-alert', '')

		let alertText = document.createElement('p')

		alertText.classList.add('uk-text-center')

		alertText.textContent = text

		alert.appendChild(alertText)

		UIkit.alert(alert)

		setTimeout(() => {
			UIkit.alert(alert).close()
		}, 7000)

		alertBox.appendChild(alert)
	}

	let setYear = year => {
		year = Number(year)

		return year >= 0
			? year
			: `${year * -1} до н. э.`
	}

	let addTableRow = book => {
		let tableRow = document.createElement('tr')

		tableRow.classList.add('uk-animation-slide-top-small')

		tableRow.dataset.id = book.id

		let tableDataTitle = document.createElement('td')
		tableDataTitle.textContent = book.title
		tableRow.appendChild(tableDataTitle)

		let tableDataAuthor = document.createElement('td')
		tableDataAuthor.textContent = book.author
		tableRow.appendChild(tableDataAuthor)

		let tableDataGenre = document.createElement('td')
		tableDataGenre.textContent = book.genre
		tableRow.appendChild(tableDataGenre)

		let tableDataYear = document.createElement('td')
		tableDataYear.textContent = setYear(book.year)
		tableRow.appendChild(tableDataYear)

		let tableDataButtons = document.createElement('td')

		let editBtn = document.createElement('button')

		editBtn.dataset.id = book.id
		editBtn.dataset.title = book.title
		editBtn.dataset.author = book.author
		editBtn.dataset.genre = book.genre
		editBtn.dataset.year = book.year

		editBtn.classList.add('edit-book-btn')
		editBtn.classList.add('uk-icon-link')

		editBtn.setAttribute('uk-icon', 'file-edit')
		editBtn.setAttribute('title', 'Отредактировать книгу')

		editBtn.onclick = () => {
			modalActionsForm.dataset.action = 'edit'

			modalActions.querySelector('.uk-modal-title').textContent = 'Отредактировать книгу'
			modalActions.querySelector('.submit-btn').textContent = 'Отредактировать'

			modalActionsForm['book_id'].value = editBtn.dataset.id

			modalActionsForm['title'].value = editBtn.dataset.title
			modalActionsForm['author'].value = editBtn.dataset.author
			modalActionsForm['year'].value = editBtn.dataset.year

			modalActionsForm['genre'].value = 'custom'
			modalActionsForm['genre'].dispatchEvent(new Event('input'))
			modalActionsForm['genre_custom'].value = editBtn.dataset.genre

			UIkit.modal(modalActions).show()
		}

		tableDataButtons.appendChild(editBtn)

		let deleteBtn = document.createElement('button')

		deleteBtn.dataset.id = book.id
		deleteBtn.dataset.title = book.title

		deleteBtn.classList.add('delete-book-btn')
		deleteBtn.classList.add('uk-icon-link')
		deleteBtn.classList.add('uk-margin-small-left')

		deleteBtn.setAttribute('uk-icon', 'trash')
		deleteBtn.setAttribute('title', 'Удалить книгу')

		deleteBtn.onclick = () => {
			UIkit.modal.confirm(`Вы действительно хотите удалить книгу "${deleteBtn.dataset.title}"?`, {
				labels: {
					ok: 'Да, удалить',
					cancel: 'Отмена'
				}
			}).then(() => {
				deleteBook({ id: deleteBtn.dataset.id, title: deleteBtn.dataset.title })
			})
		}

		tableDataButtons.appendChild(deleteBtn)

		tableRow.appendChild(tableDataButtons)

		table.insertBefore(tableRow, table.firstChild)
	}

	modalActionsForm['genre'].addEventListener('input', e => {
		let cgh = modalActionsForm.querySelector('.cgh')

		if (e.target.value === 'custom') {
			cgh.removeAttribute('hidden')
		} else {
			cgh.setAttribute('hidden', '')
		}
	})

	modalActionsForm['year'].setAttribute('max', new Date().getFullYear())

	let addBook = ({ title, author, genre, year }) => {
		let formData = new FormData()

		let newBook = {
			title: title,
			author: author,
			genre: genre,
			year: year
		}

		Array.from([
			['action', 'add'],
			['title', newBook.title],
			['genre', newBook.author],
			['author', newBook.genre],
			['year', newBook.year],
		]).forEach(item => formData.append(item[0], item[1]))

		fetch(reqURL, {
			method: 'POST',
			cache: 'no-store',
			body: formData
		})
			.then(response => response.json())
			.then(data => {
				if (data.code === 1) {
					newBook.id = data.data.id
					addTableRow(newBook)

					UIkit.modal(modalActions).hide()

					newAlert({ text: data.msg })
				} else if (data.code === 0) {
					newAlert({ text: data.msg, type: 'warning' })
				} else if (data.code === 2) {
					newAlert({ text: data.msg, type: 'danger' })
				}
			})
	}

	let editBook = ({ id, title, author, genre, year }) => {
		let formData = new FormData()

		let editedBook = {
			id: id,
			title: title,
			author: author,
			genre: genre,
			year: year
		}

		Array.from([
			['action', 'edit'],
			['book_id', editedBook.id],
			['edited_params', JSON.stringify(editedBook)],
		]).forEach(item => formData.append(item[0], item[1]))

		fetch(reqURL, {
			method: 'POST',
			cache: 'no-store',
			body: formData
		})
			.then(response => response.json())
			.then(data => {
				if (data.code === 1) {
					let bookRow = table.querySelector(`tr[data-id='${editedBook.id}']`)

					bookRow.querySelector('td:nth-child(1)').textContent = editedBook.title
					bookRow.querySelector('td:nth-child(2)').textContent = editedBook.author
					bookRow.querySelector('td:nth-child(3)').textContent = editedBook.genre
					bookRow.querySelector('td:nth-child(4)').textContent = setYear(editedBook.year)

					let editBtn = bookRow.querySelector('.edit-book-btn')

					editBtn.dataset.title = editedBook.title
					editBtn.dataset.author = editedBook.author
					editBtn.dataset.genre = editedBook.genre
					editBtn.dataset.year = editedBook.year

					bookRow.querySelector('.delete-book-btn').title = editedBook.title

					UIkit.modal(modalActions).hide()

					newAlert({ text: data.msg })
				} else if (data.code === 0) {
					newAlert({ text: data.msg, type: 'warning' })
				} else if (data.code === 2) {
					newAlert({ text: data.msg, type: 'danger' })
				}
			})
	}

	let deleteBook = ({ id, title }) => {
		let formData = new FormData()

		Array.from([
			['action', 'rm'],
			['book_id', id],
		]).forEach(item => formData.append(item[0], item[1]))

		fetch(reqURL, {
			method: 'POST',
			cache: 'no-store',
			body: formData
		})
			.then(response => response.json())
			.then(data => {
				if (data.code === 1) {
					let bookRow = table.querySelector(`tr[data-id='${id}']`)

					bookRow.remove()

					newAlert({ text: `Книга "${title}" удалена` })
				} else if (data.code === 0) {
					newAlert({ text: data.msg, type: 'warning' })
				} else if (data.code === 2) {
					newAlert({ text: data.msg, type: 'danger' })
				}
			})
	}

	let addBookBtn = document.querySelector('.add-book-btn')

	addBookBtn.onclick = () => {
		modalActionsForm.dataset.action = 'add'

		modalActions.querySelector('.uk-modal-title').textContent = 'Добавить книгу'
		modalActions.querySelector('.submit-btn').textContent = 'Добавить'
	}

	modalActionsForm.addEventListener('submit', e => {
		e.preventDefault()

		let form = e.target

		let data = {
			title: form['title'].value,
			author: form['author'].value,
			year: form['year'].value
		}

		if (form['book_id'].value !== '') {
			data['id'] = form['book_id'].value
		}

		if (form['genre'].value === 'custom') {
			data['genre'] = (form['genre_custom'].value !== '')
				? form['genre_custom'].value
				: 'Без жанра'
		} else {
			data['genre'] = form['genre'].value
		}

		switch (form.dataset.action) {
			case 'add':
				addBook(data); break

			case 'edit':
				editBook(data); break
		}
	})

	fetch(reqURL + '?get', { cache: 'no-store' })
		.then(request => request.json())
		.then(data => {
			if (data.code === 1) {
				data.data.forEach(book => addTableRow(book))
			}
		})

	modalActions.addEventListener('hidden', () => {
		modalActionsForm.reset()
		modalActionsForm.querySelector('.cgh').setAttribute('hidden', '')
	})
})
