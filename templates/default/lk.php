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
					<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $userData['name'] ?></td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Телефон:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $userData['phone'] ?></td>
				</tr>
				<tr>
					<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>E-mail:</td>
					<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $userData['email'] ?></td>
				</tr>
			</tbody>
		</table>

		<?php if (!empty($userData['orders'])) : ?>

			<?php foreach ($userData['orders'] as $value) : ?>

				<h2 style="margin-top: 55px;">Параметры заказа № <?= $value['id'] ?></h2>

				<table>

					<tbody>

						<tr>
							<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Номер заказа:</td>
							<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $value['id'] ?></td>
						</tr>
						<tr>
							<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Тип доставки:</td>
							<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $value['delivery_name'] ?></td>
						</tr>
						<tr>
							<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Тип оплаты:</td>
							<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $value['payments_name'] ?></td>
						</tr>
						<tr>
							<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Сумма заказа:</td>
							<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $value['total_sum'] ?>, ₽</td>
						</tr>
						<tr>
							<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Количество товаров:</td>
							<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $value['total_qty'] ?></td>
						</tr>
						<tr>
							<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Адрес:</td>
							<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $value['address'] ?></td>
						</tr>
						<tr>
							<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'>Дата и время:</td>
							<td style='padding: 5px; border: 1px solid #fe7f2d;'><?= $value['order_data'] ?></td>
						</tr>
					</tbody>

				</table>

				<h2>Заказ № <?= $value['id'] ?></h2>

				<table>
					<tbody>

						<tr>
							<th style='padding: 5px; border: 1px solid #fe7f2d;'>Название</th>
							<th style='padding: 5px; border: 1px solid #fe7f2d;'>Цена</th>
							<th style='padding: 5px; border: 1px solid #fe7f2d;'>Кол-во</th>
							<th style='padding: 5px; border: 1px solid #fe7f2d;'>Стоимость</th>
						</tr>

						<?php foreach ($value['goods'] as $good) : ?>

							<tr>
								<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'><?= $good['good_name'] ?></td>
								<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'><?= $good['good_price'] ?>, ₽</td>
								<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'><?= $good['good_qty'] ?></td>
								<td style='padding: 5px; border: 1px solid #fe7f2d; text-align: center;'><?= $good['good_price'] * $good['good_qty'] ?>, ₽</td>
							</tr>

						<?php endforeach; ?>

					</tbody>
				</table>

			<?php endforeach; ?>

		<?php endif; ?>

	</div>

</div>