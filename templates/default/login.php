<div class="container">

	<h1>Личный кабинет</h1>

	<a style="display: inline-block;" title="Выход пользователя" href="<?= $this->alias('login') ?>logout/">
		<div>
			<img src="<?= PATH . ADMIN_TEMPLATE ?>img/out.png" alt="">
		</div>
	</a>

	<div style="display: flex; flex-direction: column; overflow: auto;" class="lk">
		<h2>Данные покупателя</h2>

		<table>
			<tbody>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Имя:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $this->userData['name'] ?></td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Телефон:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $this->userData['phone'] ?></td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>E-mail:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $this->userData['email'] ?></td>
				</tr>
			</tbody>
		</table>

		<h2>Параметры заказа</h2>

		<table>
			<tbody>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Номер заказа:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'>#id#</td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Тип доставки:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'>#delivery#</td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Тип оплаты:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'>#payments#</td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Сумма заказа:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'>#total_sum#, ₽</td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Количество товаров:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'>#total_qty#</td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Адрес:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'>#address#</td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Дополнительная информация:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'>#info#</td>
				</tr>
			</tbody>
		</table>

		<h2>Заказ</h2>

		<table>
			<tbody>
				<tr>
					<th style='padding: 5px; border: 1px solid #fe7f2d;'>Название</th>
					<th style='padding: 5px; border: 1px solid #fe7f2d;'>Цена</th>
					<th style='padding: 5px; border: 1px solid #fe7f2d;'>Кол-во</th>
					<th style='padding: 5px; border: 1px solid #fe7f2d;'>Стоимость</th>
				</tr>

				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>#name#</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>#price#, ₽</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>#qty#</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>#total_sum#, ₽</td>
				</tr>
			</tbody>
		</table>
	</div>

</div>