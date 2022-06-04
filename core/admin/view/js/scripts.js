document.querySelector('.sitemap-button').onclick = (e) => {
	e.preventDefault();
	createSitemap();
}

let links_counter = 0;

function createSitemap() {
	links_counter++;

	Ajax({ data: { ajax: 'sitemap', links_counter: links_counter } })
		.then((res) => {
			//console.log('успех Ajax - ' + res);
		})
		.catch((res) => {
			//console.log('ошибка Ajax - ' + res);
			createSitemap();
		});
}


createFile();

// метод создания файла  (добавление картинки (по одной и галереи))
function createFile() {

	// В св-ве: files будут находиться все файлы, которые мы добавили
	// в переменную: files сохраним результат работы метода: querySelectorAll, объекта: document и выберем сюда 
	// все: input[type=file] на странице форм
	// (Метод querySelectorAll() Document возвращает статический (не динамический) NodeList , содержащий все найденные
	// элементы документа, которые соответствуют указанному селектору Его свойство: length может быть равно нулю)

	let files = document.querySelectorAll('input[type=file]');

	let fileStore = [];

	// проверим еть ли что то в массиве: NodeListfiles
	if (files.length) {

		// запустим метод: forEach, который даёт нам доступ к переменной: item
		files.forEach(item => {

			// на каждый элемент: item повесим событие: onchange (через св-во)
			item.onchange = function () {

				// объявим флаг:
				let multiple = false;

				// переменная будет контейнером, в который будут добавляться изображения
				let parentContainer;

				// объявим переменную
				let container;

				// проверим имеет ли input (добавление картинок в галерею) множественное добавление (установлен ли атрибут: multiple)
				// если атрибут (поданный на вход) есть, то метод: hasAttribute() вернёт: true
				if (item.hasAttribute('multiple')) {

					multiple = true;

					// получим контейнер для элементов Сначала найдём родителя с классом: gallery_container
					parentContainer = this.closest('.gallery_container');

					if (!parentContainer) {
						return false;
					}

					//  в переменную: container сохраняем результат работы метода: querySelectorAll (ищем элементы с классом: 
					// empty_container) Это и есть пустые квадратики в галере админки для новых картинок
					container = parentContainer.querySelectorAll('.empty_container');

					// сделаем сравнение со свойством: files (это св-во указателя на item (this)) 
					// если меньше
					if (container.length < this.files.length) {

						// то нам необходимо создать ещё необходимое количество квадратиков
						for (let index = 0; index < this.files.length - container.length; index++) {

							// создадим элемент: div
							let el = document.createElement('div');
							// добавляем необходимые классы для квадратиков
							el.classList.add('vg-dotted-square', 'vg-center', 'empty_container');
							// вставим элемент, что бы они выстроились под добавление данных
							parentContainer.append(el);
						}

						// перезапишем св-во: container (чтобы метод увидел также все новые квадратики с классом: empty_container)
						container = parentContainer.querySelectorAll('.empty_container');
					}
				}

				// в переменную: fileName положим то что хранится в св-ве: name элемента (объекта): item (т.е. gallery_img как массив)
				let fileName = item.name;

				// нам будет нужно удалять элементы, которые уже загружены а админку, но ещё не добавлены в БД Для этого повесим на div (для пустых квадратиков) атрибуты, но сначала заменим символы [] (обозначение массива) в fileName на пустой символ
				let attributeName = fileName.replace(/[\[\]]/g, '');

				for (let i in this.files) {

					// проверим является ли св-во: files, свойством данного элемента (объекта): (this)
					if (this.files.hasOwnProperty(i)) {

						if (multiple) {

							// проверим есть ли ячейка массива: fileStore[fileName]
							// если нет
							if (typeof fileStore[fileName] === 'undefined') {
								// то создадим её
								fileStore[fileName] = [];
							}

							// в ячейку массива добавляем элементы (с помощью метода: push())
							// метод: push() после добавления возвращает новое количество выборки массива в который он добавил элементы
							// В переменную: elId  (наш элемент меньше чем длина массива на единицу)
							// (так мы получим порядковый номер элемента, который добавился в массиве: fileStore[fileName])
							let elId = fileStore[fileName].push(this.files[i]) - 1;
							// создадим атрибут у контейнера (его i-того элемента) для того чтобы можно было удалять добавленные 
							// элементы Метод: setAttribute() получает на вход: 1- название атрибута (с добавлением атрибута в виде переменной: ${attributeName}) в обратных кавычках, 2- значение атрибута: elId 
							container[i].setAttribute(`data-deleteFileId-${attributeName}`, elId);

							// добавив 3-им параметром ф-ию, получаем возможность сортировки картинок сразу после добавления
							showImage(this.files[i], container[i], function () {

								parentContainer.sortable({

									excludedElements: 'label .empty_container'
								});
							});

							// вызовем метод отвечающий за удаление новых файлов (картинок)
							// на вход: 1- значение атрибута, 2- элемент, который будем искать, 3-атрибут, 4- ячейку: container[i]
							deleteNewFiles(elId, fileName, attributeName, container[i]);

							// если нет атрибута множественного добавления
						} else {

							// в контейнер будем вставлять данные
							// в переменную положим результат работы метода поиска: closest Ищем класс родителя элемента: img_container Далее вызываем метод: querySelector и выбираем класс: img_show
							container = this.closest('.img_container').querySelector('.img_show');

							// вызовем функцию, которая будет осуществлять показ 
							// (на вход: 1- конкретный элемент массива: this.files, 2- контейнер)
							showImage(this.files[i], container);
						}
					}
				}

				//console.log(fileStore);
			}

			// Метод closest ищет ближайший родительский элемент, подходящий под указанный CSS селектор, при этом сам элемент // тоже включается в поиск
			let area = item.closest('.img_wrapper');

			if (area) {
				// взовем метод, для добавления файлов перетаскиванием (описана ниже)
				dragAndDrop(area, item);
			}
		});

		let form = document.querySelector('#main-form');

		if (form) {

			// Событие onsubmit возникает при отправке формы, это обычно происходит, когда пользователь нажимает специальную 
			// кнопку Submit
			form.onsubmit = function (e) {

				createJsSortable(form);

				// если массив не пуст
				if (!isEmpty(fileStore)) {

					e.preventDefault();

					// создадим объект FormData (элемент js-формы) Получим форму в которой мы находимся: form (т.е. this)
					let forData = new FormData(this);
					//console.log(forData);

					for (let i in fileStore) {

						// если i- это его собственное свойство 
						if (fileStore.hasOwnProperty(i)) {

							// почистим св-во в форме (что бы на сервер не прилетали не корректные данные)
							forData.delete(i);

							// получим чистое имя свойства (от квадратных скобок в конце)
							let rowName = i.replace(/[\[\]]/g, '');

							// пройдёмся в цикле по i-му элементу массива
							// (нам нужны: 1- переменная: item (сам элемент), 2-индекс этого элемента)
							fileStore[i].forEach((item, index) => {
								// обратимся к объекту и вызовем у него метод: append(), который добавляет в конец формы элементы
								// на вход: 1- ключ, который создастся (запишем в обратные кавычки, чтобы указывать переменные),
								// 2- значение, которое в него запишется
								forData.append(`${rowName}[${index}]`, item);
							})
						}
					}

					//console.log(forData.get('gallery_img[1]'));

					// добавим в объект ключ: ajax, со значением: editData
					forData.append('ajax', 'editData');

					// сформируем данные для вызова
					// обращаемся к объекту: Ajax и передадим ему св-ва, которые нам нужны (настройка объекта)
					Ajax({
						url: this.getAttribute('action'), // есть в нашей форме (в action)
						type: 'post',
						data: forData, // сформировали переменную: data, в которую отправим объект: forData
						processData: false,
						contentType: false
					}).then(res => { // пришлём результат
						try {
							res = JSON.parse(res);

							if (!res.success) {
								throw new Error();
							}

							// перезагрузка страницы
							location.reload();

						} catch (e) {
							alert('Произошла внутрення ошибка');
						}
					});
				}
			}
		}

		// метод отвечающий за удаление новых файлов (картинок)
		// на вход: 1- значение атрибута, 2- элемент, который будем искать, 3-атрибут, 4- ячейку: container
		function deleteNewFiles(elId, fileName, attributeName, container) {

			// на контейнер повесим событие: click
			container.addEventListener('click', function () {
				// метод: remove() удаляет элемент со всеми его обработчиками событий
				this.remove();
				// обращаемся к ячейке: fileStore[fileName][elId] и удаляем её с помощью инструкции: delete
				// (при этом элемента в массиве не будет, но длина массива не изменится)
				delete fileStore[fileName][elId];
				//console.log(fileStore);
			})
		}

		// метод, который будет осуществлять показ, при помощи объкта: FileReader
		// (на вход: 1- конкретный элемент массива, 2- контейнер)
		function showImage(item, container, calcback) {

			// сохраним в переменную объект: FileReader
			let reader = new FileReader();
			// очистим контейнер (на случай если добавили, а потои решили передобавить файл (изображение))
			container.innerHTML = '';
			// у объекта: reader вызовем метод: readAsDataURL, который прочитает файл, который пришёл в качестве base64-строки
			reader.readAsDataURL(item);

			// обратимся к св-ву: onload, объекта: reader
			// указатель на reader нам не нужен, поэтому это будет стрелочная функция (здесь будет объект e (событие, как
			// произойдёт загрузка))
			reader.onload = e => {
				// когда FileReader прочитает наш элемент необходимо:

				// вызовем св-во: innerHTML для контейнера и заполним тегом: img 
				container.innerHTML = '<img class="img_item" src="">';
				// дозваниваемся до тега: img и атрибут: src поставим в то значение, которе вернётся по onload
				// метод: setAttribute() принимает на вход: 1- название атрибута, 2- значение (то что возвращает объект событие в его св-ве: target и в его св-ве: result)
				container.querySelector('img').setAttribute('src', e.target.result);
				// уберём класс у контейнера (обращаемся к объекту: classList, его методу: remove() На вход он принимает 
				// строку с названием класса)
				container.classList.remove('empty_container');

				// проверка: если в calcback что то пришло, то вызовем ф-ию: calcback()
				calcback && calcback();
			}
		}

		// метод, для добавления файлов перетаскиванием
		function dragAndDrop(area, input) {

			// опишем функционал: dragAndDrop (4-е события) в массиве:
			// 1- dragenter- событие, которое возникает когда перетаскиваем элемент (файл) и он попадает в нужную облась (здесь- area)
			// 2- dragover- событие, которое возникает когда элемент (файл) двигается внутри этой области
			// 3- dragleave- событие, которое возникает когда элемент (файл) покидает выделенную область
			// 4- drop- событие, которое возникает когда элемент (файл) падает в выделенную область (отпускаем кнопку мыши)
			// Пройдёмся по этому массиву методом: forEach() Этот метод может дать три переменных (используем две):
			// 1- eventName (в каждый указанный момент времени сюда будет попадать определённое событие (элемент) из массива),
			// 2- индекс элемента массива
			['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName, index) => {
				// на каждой итерации на area будем вешать событие: eventName (т.е. одно из 4-х из массива)
				area.addEventListener(eventName, e => { // нам понадобится объект события (е)
					// на каждом событии блокируем действие по умолчанию
					// т.е. метод preventDefault() объекта Event сообщает, что если событие не обрабатывается явно, его
					// действие по умолчанию не должно выполняться так, как обычно. Событие продолжает распространяться как
					// обычно, до тех пор, пока один из его обработчиков не вызывает методы stopPropagation ()
					e.preventDefault();
					// на каждом событии блокируем всплытие этого события
					// т.е. метод stopPropagation() объекта Event прекращает дальнейшую передачу текущего события (предотвращает всплытие этого события)
					e.stopPropagation();

					// если индекс элемента (события) < 2 т.е. dragenter
					if (index < 2) {
						// изменим св-во: background блока (цвет фона станет: lightblue)
						area.style.background = 'lightblue';

						// если мы покидаем блок (dragleave) или отпускаем элемент (drop)
					} else {
						// то фон блока возвращаем на исходный (белый)
						area.style.background = 'white';

						if (index === 3) {
							// в параметр: input (подан на вход), его св-во: files кладём то что пришло в объекте события (е), в его объекте (dataTransfer), в его объект (files)
							input.files = e.dataTransfer.files;
							// программно вызовем это собыие
							// метод dispatchEvent()- отправляет событие в общую систему событий на вход: объект: new Event, на
							// вход которго подаём событие: change
							input.dispatchEvent(new Event('change'));
						}
					}
				});
			});
		}
	}
}


changeMenuPosition();

// метод асинхронного пересчета позиций вывода
function changeMenuPosition() {

	let form = document.querySelector('#main-form');

	if (form) {

		let selectParent = form.querySelector('select[name=parent_id]');
		let selectPosition = form.querySelector('select[name=menu_position]');

		if (selectParent && selectPosition) {

			// получим дефолтные (по умолчанию) значения переменных
			let defaultParent = selectParent.value;
			// символ + означает приведение значения к числу
			let defaultPosition = +selectPosition.value;

			// слушаем событие: change
			selectParent.addEventListener('change', function () {
				// объявим переменную- выбор по умолчанию и установим ей первоначальное значение
				let defaultChoose = false;

				if (this.value === defaultParent) {

					defaultChoose = true;
				}

				// После того как получили все базовые значения, необходимо отправлять данные на сервер и с сервера данные получать

				// вызываем метод: Ajax() На входе опишем объект (его св-ва)
				Ajax({
					data: {
						table: form.querySelector('input[name=table]').value, // поля, которые необходимо передать
						'parent_id': this.value,
						ajax: 'change_parent', // св-во, исходя из которого подключается метод в AjaxController
						// проверим есть ли в форме идентификатор tableId
						// если нет, то отправим на сервер в качестве значения iteration единицу, иначе отправим значение обратное от defaultChoose (приведённое к числу(симввол + впереди))
						// т.е. если let defaultChoose = false, то итерировать нужно, иначе - нет (т.к. это выбор по умолчанию)
						iteration: !form.querySelector('#tableId') ? 1 : +!defaultChoose
					}
				}).then(res => {
					//console.log(res);

					// приведём переменную к числу (а не строка)
					res = +res;

					if (!res) {
						return errorAlert();
					}

					// в переменной создадим элемент с тегом: select
					let newSelect = document.createElement('select');
					// установим ему атрибут с именем: menu_position
					newSelect.setAttribute('name', 'menu_position');
					// для корректноо отображения, зададим ему те классы, которые у select есть в форме
					newSelect.classList.add('vg-input', 'vg-text', 'vg-full', 'vg-firm-color1');

					for (let i = 1; i <= res; i++) {

						// если какое то значение было выбрано и оно лежит в defaultPosition, то при формировании нового select это надо учесть
						let selected = defaultChoose && i === defaultPosition ? 'selected' : '';

						// сделаем вставку в HTML
						newSelect.insertAdjacentHTML('beforeend', `<option ${selected} value="${i}">${i}</option>`);
					}

					// вставим newSelect перед selectPosition
					selectPosition.before(newSelect);
					// теперь можно удалить selectPosition
					selectPosition.remove();
					// что бы отрабатывали все проверки, в переменную: selectPosition надо сохранить новую переменную: newSelect
					selectPosition = newSelect;
				})
			});
		}
	}
}


blockParameters();

// метод реализующий аккордеон в блоках админки
function blockParameters() {

	// получим в переменную все контейнеры (для раскрывающихся списков)
	let wraps = document.querySelectorAll('.select_wrap');

	// проверяем на длину данного массива
	if (wraps.length) {

		let selectAllIndexes = [];

		// пройдёмся в цикле по всем найденным контейнерам и для элемента: item будем выполнять действия
		wraps.forEach(item => {

			// в переменную сохраним то, что лежит в св-ве: nextElementSibling (хранит первого следующего за ним 
			// дочернего элемента, который является элементом, и null в противном случае) для нашего элемента
			let next = item.nextElementSibling;

			// если переменная заполнена и содержит, нужный нам класс: option_wrap (раскрывающийся список)
			if (next && next.classList.contains('option_wrap')) {

				// слушаем событие: click
				item.addEventListener('click', e => {

					// если объект, на который распространяется событие не содержит класса: select_all
					if (!e.target.classList.contains('select_all')) {
						//console.dir(next);

						// будем реализовывать аккордион для блока
						next.slideToggle();

						//иначе
					} else {

						// получим индекс объекта, на который распространяется событие (e.target) относительно всей выборки: select_all
						// [...] означает деструктивное присваивание (преобразование в массив)
						// т.к. document.querySelectorAll() возвращает статический список нод (NodeList), в который входят все найденные в документе элементы, соответствующие указанным селекторам (не массив)
						let index = [...document.querySelectorAll('.select_all')].indexOf(e.target);
						//console.log(index);

						// если условие выполнится (т.е. мы нажимаем по элементу первый раз)
						if (typeof selectAllIndexes[index] === 'undefined') {
							// активируем ячейку и ставим в значение: false
							selectAllIndexes[index] = false;
						}

						// переставим значение в обратное
						selectAllIndexes[index] = !selectAllIndexes[index];

						// у элемента: next обратимся к методу: querySelectorAll, выберем все: input с type=checkbox
						// вызываем метод: forEach В нём будет некий элемент, у этого элемента есть св-во: checked, которое
						// отвечает за заполненность чек-бокса (стоит ли галочка или др.символ), поставим его в значение из
						// ячейки: selectAllIndexes[index]
						next.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = selectAllIndexes[index]);
					}
				})
			}
		})
	}
}


showHideMenuSearch();

// метод для показа меню и поиска при нажатии на соответствующие кнопки
function showHideMenuSearch() {

	// для кнопки меню:
	document.querySelector('#hideButton').addEventListener('click', () => {
		// находим главный блок с классом: vg-carcass и у его объекта: classList вызваем метод: toggle (добавляет и убирает 
		// класс поданный на вход при каждом клике)
		document.querySelector('.vg-carcass').classList.toggle('vg-hide');
	});

	// для кнопки поиска:
	let searchBtn = document.querySelector('#searchButton');
	let searchInput = searchBtn.querySelector('input[type=text]');

	searchBtn.addEventListener('click', () => {

		// что бы блок поиска появился, добавим класс: vg-search-reverse
		searchBtn.classList.add('vg-search-reverse');
		// поставим курсор на поле ввода
		searchInput.focus();
	});

	// организуем закрытие поиска при потере фокуса (щелчке на другом месте, переключении вкладок): вешаем событие: blur
	searchInput.addEventListener('blur', e => {
		if (e.relatedTarget && e.relatedTarget.tagName === 'A') {
			return
		}

		// удалим класс: vg-search-reverse (поле поиска закроется)
		searchBtn.classList.remove('vg-search-reverse');
	});
}

// в переменную сохраним самовызывающуюся функцию, внутри которой будет реализовано замыкание (для работы с появляющимися 
// подсказками при вводе строки в поле поиска)
// эта функция будет возвращать другую функцию, которую мы будем вызывать по обращению к имени: searchResultHover
let searchResultHover = (() => {

	// инициализируем ряд переменных, которые будут замкнуты в участке кода до: return () => {} т.е. вызова самовызывающейся функции Эти переменные выполнятся один раз (при первом обращении к переменной: searchResultHover)
	let searchRes = document.querySelector('.search_res');
	let searchInput = document.querySelector('#searchButton input[type=text]');
	// переменная- дефолтное значение Input поиска
	let defaultInputValue = null;

	// метод, который будет обрабатывать нажатие стрелочек (вниз-вверх) в подсказках при поиске
	// на вход: объект события
	function searchKeyDown(e) {

		// если элемент с id = searchButton не содержит класса: vg-search-reverse или нажата не кнопка: вверх и не кнопка: вниз
		if (!(document.querySelector('#searchButton').classList.contains('vg-search-reverse')) ||
			(e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) {
			// завершаем работу скрипта
			return;
		}

		// сделаем деструктивное присваивание (приведём к массиву) для содержимого из searchRes.children
		let children = [...searchRes.children];

		if (children.length) {

			// скинем действия по умолчанию 
			e.preventDefault();

			// получим активный элемент
			let activeItem = searchRes.querySelector('.search_act');
			// сформируем переменную по условию
			let activeIndex = activeItem ? children.indexOf(activeItem) : -1;

			// если нажата кнопка: стрелка вниз
			if (e.key === 'ArrowUp') {

				// сформируем переменную по условию
				// здесь (children.length - 1) означает последний элемент массива
				activeIndex = activeIndex <= 0 ? children.length - 1 : --activeIndex;
				// если не нажата
			} else {
				// сформируем переменную по другому условию
				activeIndex = activeIndex === children.length - 1 ? 0 : ++activeIndex;
			}

			// у всех элементов: children необходимо убрать класс: search_act (если он есть)
			children.forEach(item => item.classList.remove('search_act'));

			// обратимся к массиву в переменной: children (его ячейке: [activeIndex])  и добавим класс: search_act
			children[activeIndex].classList.add('search_act');

			// в элемент: searchInput (в его значение: value) занесём значение: innerText из children[activeIndex]
			searchInput.value = children[activeIndex].innerText.replace(/\(.+?\)\s*$/, '');
		}
	}

	// метод установки значения по умолчанию (в строке поиска)
	function setDefaultValue() {
		// в переменную: searchInput (в его переменную: value) положим значение по умолчанию (из переменной: defaultInputValue)
		searchInput.value = defaultInputValue;
	}

	// опишем слушатели событий:
	// переданные в качестве 2-го параметра функции, сработают только тогда, когда на элементе сработает обработчик событий

	// Событие: mouseleave срабатывает, когда курсор манипулятора (обычно мыши) перемещается за границы элемента
	searchRes.addEventListener('mouseleave', setDefaultValue);
	// Событие: keydown срабатывает, когда клавиша была нажата
	window.addEventListener('keydown', searchKeyDown);

	// вернется самовызывающая функция (будет вызываться в качестве результата при каждом обращении к 
	// переменной: searchResultHover)
	return () => {

		defaultInputValue = searchInput.value;

		// если подсказки (ссылки) существуют в переменной: searchRes (его св-ве: children, его св-ве: length)
		if (searchRes.children.length) {

			// используем деструктивное присваивание (преобразуем значение из searchRes.children в массив) и сохраним в переменной: children
			let children = [...searchRes.children];

			children.forEach(item => {
				// вешаем обработчик события на событие: mouseover (наведение указателя мыши)
				item.addEventListener('mouseover', () => {
					// уберём класс который подсвечивает подсказки (ссылки)
					children.forEach(el => el.classList.remove('search_act'));
					// для элемента: item добавим класс
					item.classList.add('search_act');
					// то что лежит в innerText (для элемента: item) положим в элемент: searchInput, в его св-во: value
					searchInput.value = item.innerText;
				});
			});
		}
	};
})();

searchResultHover();

// метод работы поиска в админке (вывод подсказок(ссылок))
function search() {

	let searchInput = document.querySelector('input[name=search]');

	//console.log(searchInput);

	if (searchInput) {

		searchInput.oninput = () => {

			// сделаем ограничение (подсказки (ссылки) появятся при вводе более одного символа в поисковой строке)
			if (searchInput.value.length > 1) {

				Ajax(
					{
						// в Ajax нам нужен объект: data
						data: {
							// в котором будет три поля (свойства)
							data: searchInput.value,
							table: document.querySelector('input[name="search_table"]').value,
							ajax: 'search' // управляющий флаг (для Ajax-контроллера)
						}
					}
				).then(res => {
					//console.log(res);
					try {
						res = JSON.parse(res);
						console.log('success');
						let resBlok = document.querySelector('.search_res');
						let counter = res.length > 20 ? 20 : res.length;

						if (resBlok) {
							resBlok.innerHTML = '';
							for (let i = 0; i < counter; i++) {
								resBlok.insertAdjacentHTML('beforeend', `<a href="${res[i]['alias']}">${res[i]['name']}</a>`);
							}

							searchResultHover();
						}
					} catch (e) {
						console.log('error');
						//alert('Ошибка в системе поиска в админ панели');
					}
				})
			} else {
				console.log(123)
			}

		}
	}
}

search();

let galleries = document.querySelectorAll('.gallery_container');

if (galleries.length) {

	galleries.forEach(item => {

		item.sortable({

			// добавим в исклюючения (щапретим перетаскивать): ячейку с крестиком и пустые ячейки
			excludedElements: 'label .empty_container',

			stop: function (dragEl) {
				//console.log(this)
			}
		});
	});
}

function createJsSortable(form) {

	if (form) {

		// получим все блоки, которые надо сортирвоать (т.е. input с [type=file] и с атрибутом: multiple)
		let sortable = form.querySelectorAll('input[type=file][multiple]');

		if (sortable.length) {

			sortable.forEach(item => {

				let container = item.closest('.gallery_container');

				let name = item.getAttribute('name');

				if (name && container) {

					// удалим все скобки
					name = name.replace(/\[\]/g, '');

					let inputSorting = form.querySelector('input[name="js-sorting[${name}]"]');

					if (!inputSorting) {
						// создадим элемент
						inputSorting = document.createElement('input');
						// установим его атрибут
						inputSorting.name = `js-sorting[${name}]`;
						// закинем созданный элемент в форму
						form.append(inputSorting);
					}

					let res = [];

					for (let i in container.children) {
						if (container.children.hasOwnProperty(i)) {
							// проверим на наличие элементов которые в сортировке не учавствуют: label и empty_container
							if (!container.children[i].matches('label') && !container.children[i].matches('.empty_container')) {
								// здесь: А- новодобавленный элемент
								if (container.children[i].tagName === 'A') {
									res.push(container.children[i].querySelector('img').getAttribute('src'));
								} else {
									res.push(container.children[i].getAttribute(`data-deletefileid-${name}`));
								}
							}
						}
					}
					//console.log(res);

					// stringify()- из массива или объекта сделает строку
					inputSorting.value = JSON.stringify(res);
				}
			})
		}
	}
}

document.addEventListener('DOMContentLoaded', () => {
	function hideMessages() {
		document.querySelectorAll('.success, .error').forEach(item => item.remove());

		document.removeEventListener('click', hideMessages)
		//console.log(111333);
	}

	document.addEventListener('click', hideMessages)
});

