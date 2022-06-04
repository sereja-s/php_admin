// Инициализируем графический редактор текста в админке
function MCEInit(element, height = 400) {

	// при инициализации задаём базовые настройки
	tinymce.init({
		language: 'ru',
		mode: 'exact', // режим работы редактора
		// инициаизируем редактор
		elements: element || tinyMceDefaultAreas,
		height: height, // базовая высота редактора
		gecko_spellcheck: true, // подключим браузерный словарь (покажет ошибки ввода)
		relative_urls: false, // для корректного формирования ссылок
		// укажем дополнительные возможности редактора текста
		plugins: [
			"advlist autolink lists link image charmap print preview hr anchor pagebreak",
			"searchreplace wordcount visualblocks visualchars code fullscreen",
			"insertdatetime media nonbreaking save table directionality",
			"emoticons template paste textpattern media imagetools"
		],
		toolbar: "insertfile undo redo | styleselect | bold italic | forecolor backcolor emoticons | " +
			"alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | " +
			"formatselect fontsizeselect | code media emoticons ",
		// включим дополнительные настройки 
		image_advtab: true,
		// добавление заголовков для изображений
		image_title: true,
		// автоматическая загрузка изображений
		automatic_uploads: true,
		// тип выбираемых файлов
		file_picker_types: 'image',
		// при загрузке изображения, присваивать корректное имя
		images_reuse_filename: true,
		// сократим кол-во инструментов в редакторе (оставили только два действия)
		imagetools_toolbar: 'editimage imageoptions',

		// обработчик загрузки изображений
		images_upload_handler: function (file, success, fail) {

			let formdata = new FormData;
			// добавляем в созданный объект: formdata необходиые данные
			// в функцию: append на вход: 1- ячейка, 2- то что пришло в переменную (из метода), 3- то что пришло в переменную (из метода) , т.е. заполняем массив: в file
			formdata.append('file', file.blob(), file.filename());
			// в функцию: append на вход: 1- флаг (показывает какой функционал будем выполнять) 2- название файла (для 
			// Ajax-контроллера)
			formdata.append('ajax', 'wyswyg_file');
			// добавим таблицу (чтобы корректно раскладывать данные по директориям)
			formdata.append('table', document.querySelector('input[name=table]').value);

			// вызываем метод отправки данныз на сервер:
			Ajax({
				url: document.querySelector('#main-form').getAttribute('action'),
				data: formdata,
				contentType: false,
				processData: false,
				type: 'post'
			}).then(res => {
				console.log(res);
				success(JSON.parse(res).location);
			});
		},

		// опишем свойство необходимое для появления кнопки
		file_picker_callback: function (callback, value, meta) {

			// Действия для активации кнопки добавления изображений, при щелчке на ней:

			let input = document.createElement('input');
			input.setAttribute('type', 'file');
			input.setAttribute('accept', 'image/*');
			input.click();

			// процесс добавления изображения в контентную часть
			input.onchange = function () {

				let reader = new FileReader();
				reader.readAsDataURL(this.files[0]);
				reader.onload = () => {

					let blobCache = tinymce.activeEditor.editorUpload.blobCache;
					let base64 = reader.result.split(',')[1];
					let blobInfo = blobCache.create(this.files[0].name, this.files[0], base64);

					blobCache.add(blobInfo);

					callback(blobInfo.blobUri(), { title: this.files[0].name });
				}
			};
		}
	})
}

MCEInit();

// иницализиируем кнопки, чтобы по кнопкам всё работало, а при необходимости редактор текста можно было отключать: 

let mceElements = document.querySelectorAll('input.tineMceInit');

if (mceElements.length) {

	mceElements.forEach(item => {

		item.onchange = () => {

			let blockContent = item.closest('.vg-content');
			let textArea = item.closest('.vg-element').querySelector('textarea');
			let textAreaName = textArea.getAttribute('name');

			if (textAreaName) {

				if (item.checked) {

					MCEInit(textAreaName, blockContent ? 400 : 300);
				} else {

					tinymce.remove(`[name="${textAreaName}"]`);

					if (!blockContent) {

						textArea.value = textArea.value.replace(/<\/?[^>]+(>|$)/g, '');
					}
				}
			}
		}
	})
}