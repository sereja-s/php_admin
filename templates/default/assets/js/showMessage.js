/* Выпуск №148 | Пользовательская часть | показ уведомлений пользователю */

document.addEventListener('DOMContentLoaded', () => {

	let messageWrap = document.querySelector('.wq-message__wrap');

	if (messageWrap) {

		// опишем объект, внутри которого 3-и объекта(стили класса: wq-message__wrap для всплывающего окна сообщений)
		let styles = {

			position: 'fixed',
			top: '10%',
			left: '50%',
			transform: 'translateX(-50%)',
			display: 'block',
			zIndex: '9999'
		};

		let successStyles = {

			backgroundColor: '#4c8a3c',
			color: 'white',
			marginBottom: '10px',
			padding: '25px 30px',
			borderRadius: '20px'
		};

		let errorStyles = {
			backgroundColor: '#d34343',
			color: 'white',
			marginBottom: '10px',
			padding: '25px 30px',
			borderRadius: '20px'
		};

		// если в переменую что то прилетело
		if (messageWrap.innerHTML.trim()) {

			// зададим описанные выше стили
			for (let i in styles) {

				messageWrap.style[i] = styles[i];

			}

			// если не пришёл тег div (т.е. если дочерних элементов у класса нет, то children.length вернёт ноль)
			if (!messageWrap.children.length) {

				// оборачиваем в div
				messageWrap.innerHTML = `<div>${messageWrap.innerHTML}</div>`

			}

			// в цикле пробежимся по дочерним элементам класса  
			for (let i in messageWrap.children) {

				if (messageWrap.children.hasOwnProperty(i)) {

					// определим тип стилей

					// если находим поле: success, обратимся к value, в котором хранится строка с классами, то в переменную 
					// положим то что хранится в successStyles, иначе errorStyles
					let typeStyles = /success/i.test(messageWrap.children[i].classList.value) ? successStyles : errorStyles;

					// применяем соответствующие стили
					for (let j in typeStyles) {

						messageWrap.children[i].style[j] = typeStyles[j];

					}

				}

			}

			// полключим слушатели для двух событий
			['click', 'scroll'].forEach(event => document.addEventListener(event, hideMessages));

		}

	}
	/**
	 * функция убирает всплывающие сообщения
	 */
	function hideMessages() {

		let messageWrap = document.querySelector('.wq-message__wrap');


		if (messageWrap) {

			messageWrap.remove()

		}

		// отключим слушатели для двух событий
		['click', 'scroll'].forEach(event => document.removeEventListener(event, hideMessages));
	}
});