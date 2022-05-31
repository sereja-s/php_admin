// Объект: Ajax, котрый осуществляет отправку данных на сервер
const Ajax = (set) => {
	if (typeof set === 'undefined') {
		set = {};
	}

	if (typeof set.url === 'undefined' || !set.url) {
		set.url = typeof PATH !== 'undefined' ? PATH : '/';
	}

	if (typeof set.ajax === 'undefined') {
		set.ajax = true;
	}

	if (typeof set.type === 'undefined' || !set.type) {
		set.type = 'GET';
	}

	set.type = set.type.toUpperCase();

	let body = '';

	if (typeof set.data !== 'undefined' && set.data) {

		if (typeof set.processData !== 'undefined' && !set.processData) {

			body = set.data;

		} else {

			for (let i in set.data) {

				if (set.data.hasOwnProperty(i)) {

					body += '&' + i + '=' + set.data[i];
				}
			}

			body = body.substr(1);

			if (typeof ADMIN_MODE !== 'undefined') {
				body += body ? '&' : '';
				body += 'ADMIN_MODE=' + ADMIN_MODE;
			}
		}
	}

	if (set.type === 'GET') {
		set.url += '?' + body;
		body = null;
	}


	return new Promise((resolve, reject) => {
		let xhr = new XMLHttpRequest();
		xhr.open(set.type, set.url, true);
		let contentType = false;

		if (typeof set.headers !== 'undefined' && set.headers) {
			for (let i in set.headers) {
				if (set.headers.hasOwnProperty(i)) {
					xhr.setRequestHeader(i, set.headers[i]);
					if (i.toLowerCase() === 'content-type') {
						contentType = true;
					}
				}
			}
		}

		// проверим условие
		if (!contentType && (typeof set.contentType === 'undefined' || set.contentType)) {
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		}

		if (set.ajax) {
			xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		}

		xhr.onload = function () {
			if (this.status >= 200 && this.status < 300) {
				if (/fatal\s+?error/ui.test(this.response)) {
					reject(this.response);
				}

				resolve(this.response);
			}

			reject(this.response);
		}

		xhr.onerror = function () {
			reject(this.response);
		}

		xhr.send(body);
	});
}

function isEmpty(arr) {
	// если цикл начнёт выполняться, значит массив не пуст
	for (let i in arr) {
		// то вернём:
		return false;
	}
	// иначе:
	return true;
}

function errorAlert() {
	alert('Произошла внутренняя ошибка');

	return false;
}

// на объекте: Element опишем метод: slideToggle для реализации аккордиона (что бы иметь возможность применять его к 
// любому элементу)

// обращаемся к св-ву: prototype, объекта: Element и у него описываем св-во: slideToggle в котором будет храниться функция
// на вход: 1- время анимации, 2- параметр: callback
Element.prototype.slideToggle = function (time, callback) {

	let _time = typeof time === 'number' ? time : 400;
	callback = typeof time === 'function' ? time : callback;

	// Функция getComputedStyle (у объекта: window) позволяет получить значение любого CSS свойства элемента, даже из CSS файла
	if (getComputedStyle(this)['display'] === 'none') {

		// элемент надо открыть
		this.style.transition = null;
		this.style.overflow = 'hidden';
		this.style.maxHeight = 0;

		this.style.display = 'block';

		//console.dir(this);

		this.style.transition = _time + 'ms';
		this.style.maxHeight = this.scrollHeight + 'px';

		setTimeout(() => {
			callback && callback();
		}, _time);

		// иначе элемент закроем
	} else {
		this.style.transition = _time + 'ms';
		this.style.maxHeight = 0;

		setTimeout(() => {
			this.style.transition = null;
			this.style.display = 'none';
			callback && callback();
		}, _time);
	}
}

// опишем самовызывающуюся функцию сортировки
Element.prototype.sortable = (function () {
	// инициализируем переменные для элемента, который перемещается и элемента, который стоит за ним по умолчанию
	let dragEl, nextEl;


	function _unDraggable(elements) {

		if (elements && elements.length) {

			for (let i = 0; i < elements.length; i++) {

				if (!elements[i].hasAttribute('draggable')) {

					elements[i].draggable = false;

					// рекурсивно запускаем функцию
					_unDraggable(elements[i].children);
				}
			}
		}
	}


	function _onDragStart(e) {

		// блокируем всплытие событий
		e.stopPropagation();

		this.tempTarget = null;

		// в переменную: dragEl положим элемент который будем (начинаем) тащить
		dragEl = e.target;

		//в переменную положим св-во: nextSibling (из dragEl)
		// Доступное только для чтения свойство nextSibling интерфейса Node возвращает узел, следующий сразу за указанным в
		// childNodes их родителя , или возвращает значение null , если указанный узел является последним дочерним элементом // в родительском элементе
		nextEl = dragEl.nextSibling;

		// установим св-во в значение: перемещать
		e.dataTransfer.dropEffect = 'move';

		// добавим два слушателя событий
		// т.к. мы работаем на прототипе элемента (Element.prototype), то сам элемент, к которому будем обращаться, находится в св-ве: this
		this.addEventListener('dragover', _onDragOver, false);
		this.addEventListener('dragend', _onDragEnd, false);
	}


	function _onDragOver(e) {
		// скинем действия по умолчанию 
		e.preventDefault();
		// блокируем всплытие событий
		e.stopPropagation();
		// установим св-во в значение: перемещать
		e.dataTransfer.dropEffect = 'move';

		let target;

		if (e.target !== this.tempTarget) {

			// здесь в e.target приходит элемент над которым мы тащим
			this.tempTarget = e.target;

			// в переменую сохраним: e.target с атрибутом: draggable = true
			target = e.target.closest('[draggable=true]');
		}

		if (target && target !== dragEl && target.parentElement === this) {

			let rect = target.getBoundingClientRect();

			// в переменную положим результат расчёта координат
			let next = (e.clientY - rect.top) / (rect.bottom - rect.top) > .5;

			// обращаемся к this, вызываем у него метод: insertBefore()
			// на вход: 1- указыаваем что вставляем: dragEl, 2- куда вствляем (если next, то вставим после: target.nextSibling
			// иначе вставим после: target)
			this.insertBefore(dragEl, next && target.nextSibling || target);
		}
	}

	function _onDragEnd(e) { // на вход методу подаём: объект событие: e

		// отменим действие по умолчанию
		e.preventDefault();

		// скинем два слушателя событий
		this.removeEventListener('dragover', _onDragOver, false);
		this.removeEventListener('dragend', _onDragEnd, false);

		if (nextEl !== dragEl.nextSibling) {

			this.onUpdate && this.onUpdate(dragEl);
		}
	}


	// реализуем замыкание
	return function (options) {
		// в переменную положим: options (если туда что то пришло иначе- пустой объект)
		options = options || {};

		this.onUpdate = options.stop || null;

		// в переменную сохраним элементы, которые необходмо исключить из процесса: сортировки (перетаскивания)
		// Метод split() разбивает объект String на массив строк, путём разделения строки указанной подстрокой
		let excludedElements = options.excludedElements && options.excludedElements.split(/,*\s+/) || null;

		[...this.children].forEach(item => {

			let draggable = 'true';

			if (excludedElements) {

				for (let i in excludedElements) {

					// метод: hasOwnProperty() проверяет, является ли св-во поданное на вход, собственным свойством элемента
					// метод: matches() проверяет элемент на соответствие заданному селектору 
					if (excludedElements.hasOwnProperty(i) && item.matches(excludedElements[i])) {

						draggable = false;

						break;
					}
				}
			}

			item.draggable = draggable;

			// вызываем метод
			_unDraggable(item.children);
		});

		// сбосим слушатель события: dragstart, 2-ым параметром передаётся метод: _onDragStart, 3-ий параметр: false (т.е с теми же опциями)
		this.removeEventListener('dragstart', _onDragStart, false);

		this.addEventListener('dragstart', _onDragStart, false);
	}
})();
