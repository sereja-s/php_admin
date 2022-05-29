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

			let area = item.closest('.img_wrapper');

			if (area) {
				dragAndDrop(area, item);
			}
		});

		let form = document.querySelector('#main-form');

		if (form) {
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

				calcback && calcback();
			}
		}

		function dragAndDrop(area, input) {
			['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName, index) => {
				area.addEventListener(eventName, e => {
					e.preventDefault();
					e.stopPropagation();

					if (index < 2) {
						area.style.background = 'lightblue';
					} else {
						area.style.background = 'white';

						if (index === 3) {
							input.files = e.dataTransfer.files;
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

// Метод реализующий аккордеон в блоках админки
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

function showHideMenuSearch() {
	document.querySelector('#hideButton').addEventListener('click', () => {
		document.querySelector('.vg-carcass').classList.toggle('vg-hide');
	});

	let searchBtn = document.querySelector('#searchButton');
	let searchInput = searchBtn.querySelector('input[type=text]');

	searchBtn.addEventListener('click', () => {
		searchBtn.classList.add('vg-search-reverse');
		searchInput.focus();
	});

	searchInput.addEventListener('blur', e => {
		if (e.relatedTarget && e.relatedTarget.tagName === 'A') {
			return
		}

		searchBtn.classList.remove('vg-search-reverse');
	});
}

let searchResultHover = (() => {
	let searchRes = document.querySelector('.search_res');
	let searchInput = document.querySelector('#searchButton input[type=text]');
	let defaultInputValue = null;

	function searchKeyDown(e) {
		if (!(document.querySelector('#searchButton').classList.contains('vg-search-reverse')) ||
			(e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) {
			return;
		}

		let children = [...searchRes.children];

		if (children.length) {
			e.preventDefault();

			let activeItem = searchRes.querySelector('.search_act');
			let activeIndex = activeItem ? children.indexOf(activeItem) : -1;

			if (e.key === 'ArrowUp') {
				activeIndex = activeIndex <= 0 ? children.length - 1 : --activeIndex;
			} else {
				activeIndex = activeIndex === children.length - 1 ? 0 : ++activeIndex;
			}

			children.forEach(item => item.classList.remove('search_act'));
			children[activeIndex].classList.add('search_act');

			searchInput.value = children[activeIndex].innerText.replace(/\(.+?\)\s*$/, '');
		}
	}

	function setDefaultValue() {
		searchInput.value = defaultInputValue;
	}

	searchRes.addEventListener('mouseleave', setDefaultValue);
	window.addEventListener('keydown', searchKeyDown);

	return () => {
		defaultInputValue = searchInput.value;

		if (searchRes.children.length) {
			let children = [...searchRes.children];

			children.forEach(item => {
				item.addEventListener('mouseover', () => {
					children.forEach(el => el.classList.remove('search_act'));
					item.classList.add('search_act');
					searchInput.value = item.innerText;
				});
			});
		}
	};
})();

searchResultHover();

function search() {
	let searchInput = document.querySelector('input[name=search]');
	console.log(searchInput);

	if (searchInput) {
		searchInput.oninput = () => {
			if (searchInput.value.length > 1) {
				Ajax(
					{
						data: {
							data: searchInput.value,
							table: document.querySelector('input[name="search_table"]').value,
							ajax: 'search'
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
			excludedElements: 'label .empty_container',
			stop: function (dragEl) {
				//console.log(this)
			}
		});
	});
}

function createJsSortable(form) {
	if (form) {
		let sortable = form.querySelectorAll('input[type=file][multiple]');

		if (sortable.length) {
			sortable.forEach(item => {
				let container = item.closest('.gallery_container');
				let name = item.getAttribute('name');

				if (name && container) {
					name = name.replace(/\[\]/g, '');

					let inputSorting = form.querySelector('input[name="js-sorting[${name}]"]');

					if (!inputSorting) {
						inputSorting = document.createElement('input');
						inputSorting.name = `js-sorting[${name}]`;

						form.append(inputSorting);
					}

					let res = [];

					for (let i in container.children) {
						if (container.children.hasOwnProperty(i)) {
							if (!container.children[i].matches('label') && !container.children[i].matches('.empty_container')) {
								if (container.children[i].tagName === 'A') {
									res.push(container.children[i].querySelector('img').getAttribute('src'));
								} else {
									res.push(container.children[i].getAttribute(`data-deletefileid-${name}`));
								}
							}
						}
					}
					//console.log(res);

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

