$(function () {
	//------------------- Swipers -------------------//
	var mainSlider = new Swiper('.slider__container', {
		pagination: {
			el: '.slider__pagination',
			type: 'fraction',
		},
		navigation: {
			nextEl: '.slider__controls._next',
			prevEl: '.slider__controls._prev',
		},
	});

	var partnersSlider = new Swiper('.partners__container', {
		navigation: {
			nextEl: '.partners__controls._next',
			prevEl: '.partners__controls._prev',
		},
		slidesPerView: 1,
		spaceBetween: 20,
		loop: true,
		breakpoints: {
			1601: {
				slidesPerView: 8
			},
			1367: {
				slidesPerView: 7
			},
			1281: {
				slidesPerView: 6
			},
			1025: {
				slidesPerView: 5
			},
			769: {
				slidesPerView: 4
			},
			561: {
				slidesPerView: 3
			},
			421: {
				slidesPerView: 2
			},
		}
	});

	var indexOffersSlider = null;

	var indexOffersSliderOptions = {
		navigation: {
			nextEl: '.offers__controls._next',
			prevEl: '.offers__controls._prev',
		},
		slidesPerView: 1,
		spaceBetween: 20,
		wrapperClass: 'offers__tabs_wrapper',
		slideClass: 'offers__tabs_card',
		watchOverflow: true,
		breakpoints: {
			1251: {
				slidesPerView: 4,
			},
			769: {
				slidesPerView: 3
			},
			561: {
				slidesPerView: 2
			}
		}
	};
	indexOffersSlider = new Swiper('.offers__tabs_container', indexOffersSliderOptions);
	new Swiper('.active .offers__tabs_container', indexOffersSliderOptions);


	//------------------- Tabs Mainpage -------------------//
	$('ul.offers__tabs_header').on('click', 'li:not(.active)', function () {
		$(this)
			.addClass('active').siblings().removeClass('active')
			.closest('div.offers__tabs').find('div.offers__tabs_content').removeClass('active').eq($(this).index()).addClass('active');
		var index = $(this).index();
		indexOffersSlider[index].slideTo(0);
		indexOffersSlider[index].update();

	});
	//------------------- Burger Sidebar  -------------------//
	$('.burger-menu').on('click', function () {
		var burgerHidden = $('.header__menu').hasClass('_hidden');
		var callbackHidden = $('.header__callback').hasClass('_hidden');
		if (burgerHidden) {//бургер скрыт
			if (!callbackHidden) {//но открыта обратка
				$('.header__callback').addClass('_hidden');//скроем обратку
				if ($(window).width() <= 1024) {//на мобилке
					$('.overlay').removeClass('_visible');
					$('.header__sidebar').removeClass('_bg-opened');
				}
			}
			else {//обратка закрыта
				$('.overlay').addClass('_visible');//покажем оверлей тк его нет
				if ($(window).width() <= 1024) {
					$('.header__sidebar').addClass('_bg-opened')//на бургере повернем крестик
					$('.header__menu').removeClass('_hidden');
				}
			}
			if ($(window).width() > 1024) {
				$('.header__menu').removeClass('_hidden');//в любом случае вызовем меню на десктопе
			}
		}
		else {//бургер открыт
			$('.overlay').removeClass('_visible');//скроем оверлей
			if ($(window).width() <= 1024) {
				$('.header__sidebar').removeClass('_bg-opened')//свернем крестик в бургер
			}
			$('.header__menu').addClass('_hidden');//скроем меню
		}
	});
	$('.header__menu_close').on('click', function () {
		$('.overlay').removeClass('_visible');
		if ($(window).width() <= 1024) {
			$('.header__sidebar').removeClass('_bg-opened')
		}
		$('.header__menu').addClass('_hidden');
	});
	//------------------- Callback Popup  -------------------//
	$('.js-callback').on('click', function () {
		$('.overlay').addClass('_visible');
		if ($(window).width() <= 1024) {
			$('.header__sidebar').addClass('_bg-opened')
		}
		$('.header__callback').removeClass('_hidden');
	});
	$('.header__callback_close').on('click', function () {
		$('.overlay').removeClass('_visible');
		if ($(window).width() <= 1024) {
			$('.header__sidebar').removeClass('_bg-opened')
		}
		$('.header__callback').addClass('_hidden');
	});
	//------------------- Overlay Events  -------------------//
	$('.overlay').on('click', function () {
		var burgerHidden = $('.header__menu').hasClass('_hidden');
		var callbackHidden = $('.header__callback').hasClass('_hidden');

		if (!burgerHidden) {
			$('.header__menu').addClass('_hidden');
		}
		if (!callbackHidden) {
			$('.header__callback').addClass('_hidden');
		}

		$('.overlay').removeClass('_visible');
		if ($(window).width() <= 1024) {
			$('.header__sidebar').removeClass('_bg-opened')
		}
	});
	//------------------- Masked Inputs  -------------------//
	$('.js-mask-phone').mask("+7 (999) 999-99-99");

	//------------------- Horizontal Scroll -------------------//
	var controller = new ScrollMagic.Controller();

	if ($(window).width() > 1024) {
		var timeline = new TimelineMax();
		timeline
			.to($('.horizontal__wrapper'), 1, { xPercent: '-50' });

		var horizontalScroll = new ScrollMagic.Scene({
			triggerElement: '.horizontal',
			triggerHook: 'onEnter',
			offset: $('.horizontal__wrapper').height(),
			duration: '100%'
		})
			.setTween(timeline)
			.setPin(".horizontal__wrapper")
			.addTo(controller);
	}



	//------------------- Sticky Search -------------------//
	if ($(window).width() > 1024 && $('.news').length > 0) {
		var heightToFooter = $('.news').offset().top;

		var stickySearch = new ScrollMagic.Scene({
			triggerElement: '.footer',
			triggerHook: 'onEnter'
		})
			.setClassToggle('.search', '_unpin')
			.addTo(controller);
	}
	//------------------- Add Desktop Animate Classes -------------------//
	if ($(window).width() > 1024) {
		$('.search').addClass(['animated', 'bounceInLeft']);
		$('.header__sidebar').addClass(['animated', 'bounceInUp']);
	}


	$('.qty-items a').on('click', function (e) {

		e.preventDefault();

		let qty = +$(this).text()

		if (qty && !isNaN(qty)) {

			$(this).closest('.catalog-section-top-items__toggle').children('span').html(qty)

			$.ajax({

				url: '/',

				data: {

					qty: qty,

					ajax: 'catalog_quantities'

				}
			})

			setTimeout(() => {

				location.href = location.pathname

			}, 100)

		}
	})


});

/* =================================== кнопка ПОКАЗАТЬ ВСЁ ====================================================== */

document.addEventListener('DOMContentLoaded', () => {

	let moreBtn = document.querySelector('.card-main-info__description .more-button')

	if (moreBtn) {

		moreBtn.addEventListener('click', e => {

			e.preventDefault()

			// плавный скролл к блоку характеристик товара при щелчке на кнопке ПОКАЗАТЬ ВСЁ
			window.scrollTo({

				top: document.querySelector('.card-tabs').getBoundingClientRect().top + scrollY,
				behavior: 'smooth'
			})

			// блок характеристик товаров откроется при плавном скролле к нему
			document.querySelectorAll('.card-tabs__toggle.tabs__toggle')[1].dispatchEvent(new Event('click'))
		})
	}

	changeQty()

	addToCart()

	/* ====================================== order-popup при оформлении заказа ====================================== */

	document.querySelectorAll('[data-popup]').forEach(item => {

		if (item.getAttribute('data-popup')) {

			let popupElement = document.querySelector(`.${item.getAttribute('data-popup')}`);

			if (popupElement) {

				// Выпуск №154 | Пользовательская часть | регистрация пользователя
				item.addEventListener('click', e => {

					e.preventDefault()

					popupElement.classList.add('open')
				});

				popupElement.addEventListener('click', e => {

					if (e.target === popupElement) {

						popupElement.classList.remove('open')
					}

				});

			}
		}
	})

	// Выпуск №154 | Пользовательская часть | регистрация пользователя
	let loginVariants = document.querySelectorAll('.login-popup h2 span')
	let loginFormVariants = document.querySelectorAll('.login-popup form')

	loginVariants.forEach(item => {

		item.addEventListener('click', () => {

			// получим порядковый номер элемента
			let index = [...loginVariants].indexOf(item)

			loginFormVariants[index].style.display = 'block'
			loginFormVariants[+!index].style.display = 'none'
		})
	})

})

/* ================================================== Кнопки плюс и минус ========================================== */

function addToCart() {

	document.querySelectorAll('[data-addToCart]').forEach(item => {

		item.addEventListener('click', e => {

			e.preventDefault()

			let cart = {}

			cart.id = +item.getAttribute('data-addToCart')

			if (cart.id && !isNaN(cart.id)) {

				let productContainer = item.closest('[data-productContainer]') || document

				cart.qty = null

				let qtyBlock = productContainer.querySelector('[data-quantity]')

				if (qtyBlock) {

					cart.qty = +qtyBlock.innerHTML || 1
				}

				cart.ajax = 'add_to_cart'

				$.ajax({

					url: '/',
					data: cart,
					error: res => {

						console.error(res)
					},

					success: res => {

						console.log(res)

						try {

							res = JSON.parse(res)

							//console.log(res)

							if (typeof res.current === 'undefined') {

								throw new Error('')
							}

							item.setAttribute('data-toCartAdded', true);

							['data-totalQty', 'data-totalSum', 'data-totalOldSum'].forEach(attr => {

								let cartAttr = attr.replace(/data-/, '').replace(/([^A-Z])([A-Z])/g, '$1_$2').toLowerCase()

								//console.log(cartAttr)

								document.querySelectorAll(`[${attr}]`).forEach(el => {

									if (typeof res[cartAttr] !== 'undefined') {

										el.innerHTML = res[cartAttr] + (attr === 'data-totalQty' ? '' : ' руб.')

									}

								})
							})

						} catch (e) {

							alert('Ошибка добавления в корзину')
						}
					}
				})
			}

		})

	})
}

function changeQty() {

	let qtyButtons = document.querySelectorAll('[data-quantityPlus], [data-quantityMinus]')

	qtyButtons.forEach(item => {

		item.addEventListener('click', e => {

			e.preventDefault()

			let productContainer = item.closest('[data-productContainer]') || document

			//let inCart = false;

			let qtyEl = productContainer.querySelector('[data-quantity]')

			if (qtyEl) {

				let qty = +qtyEl.innerHTML || 1

				if (item.hasAttribute('data-quantityPlus')) {

					qty++
				} else {

					qty = qty <= 1 ? 1 : --qty
				}

				qtyEl.innerHTML = qty

				let addToCart = productContainer.querySelector('[data-addToCart]')

				if (addToCart) {

					if (addToCart && addToCart.hasAttribute('data-toCartAdded')) {

						addToCart.dispatchEvent(new Event('click'))
					}
				}
			}
		})
	})
}

//------------------------------------ валидатор номера телефона (Выпуск №146) ---------------------------------------//

document.querySelectorAll('input[type="tel"]').forEach(item => phoneValidate(item));

function phoneValidate(item) {

	let countriesOptions = {

		// +7(495)111-22-33
		'+7': {
			// укажем лимит символов в строке телефона в форме
			limit: 16,
			// укажем св-во, в котором сохраним первые цифры для которых будет осуществляться подмена
			firstDigits: '87',
			// св-во при помощи которого будет осуществляться форматирование телефона (здесь- это объект, в котором мы 
			// будем хранить порядковые номера символов, после которых будем что то вствлть(заменять и т.д.))
			formatChars: {

				2: '(',
				6: ')',
				10: '-',
				13: '-',
			}
		},

		// например добавим форатирование для другой страны:
		'+994': {

			// укажем лимит символов в строке телефона в форме
			limit: 18,
			// св-во при помощи которого будет осуществляться форматирование телефона (здесь- это объект, в котором мы 
			// будем хранить порядковые номера символов, после которых будем что то вствлть(заменять и т.д.))
			formatChars: {

				4: '(',
				8: ')',
				12: '-',
				15: '-',
			}
		}
	};

	item.addEventListener('input', e => {

		// если тип кнопки, которую нажимает пользователь: удалить символ(впереди или вконце)
		if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') {

			// не мешаем пользователю ничего удалить
			return false
		};

		// чистим от не цифр, всё что пользователь вводит в поле тел.номера формы
		item.value = item.value.replace(/\D/g, '');

		if (item.value) {
			// сформируем корректные ключи (по стране)
			for (let code in countriesOptions) {

				if (countriesOptions.hasOwnProperty(code) && countriesOptions[code].firstDigits) {

					// создадим объект регулярного выражения
					let regExp = new RegExp(`^[${countriesOptions[code].firstDigits}]`);

					if (regExp.test(item.value)) {

						// заменяем value на code(здесь- код страны: +7)
						item.value = item.value.replace(regExp, code);
						break;
					}
				}
			}

			// если символа: + нет, его надо подставить
			if (!/^\+/.test(item.value)) {

				item.value = '+' + item.value;
			}

			// снова запустим цикл:
			// сформируем корректные ключи (по стране)
			for (let code in countriesOptions) {

				if (countriesOptions.hasOwnProperty(code)/*  && countriesOptions[code].firstDigits */) {

					let regExp = new RegExp(code.replace(/\+/g, '\\+'), 'g');

					if (regExp.test(item.value)) {

						for (let i in countriesOptions[code].formatChars) {

							// приведём к числу строковую переменную i
							let j = +i;

							if (item.value[j] && item.value[j] !== countriesOptions[code].formatChars[i]) {

								item.value = item.value.substring(0, j) + countriesOptions[code].formatChars[i] + item.value.substring(j)
							}
						}

						// ограничим данные
						if (item.value[countriesOptions[code].limit]) {

							item.value = item.value.substring(0, countriesOptions[code].limit);
						}
					}
				}
			}
		}
	});

	// для корректной работы при перезагрузке страницы
	item.dispatchEvent(new Event('input'));

	// если после ввода телефонного номера пользователь попытается изменить формат записи на неправильный, номер 
	// автоматически будет исправлен на корректный
	item.addEventListener('change', () => phoneValidate(item));

}
