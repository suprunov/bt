<?php
/**
 * @var \App\Entities\Product $product
 * @var array $pickups
 * @var string $baseUrl
 */
?>
<Ad>
    <Id><?= $product->id ?></Id>
    <Address>Москва, Ташкентская ул., д. 28, стр. 1<?// TODO= $pickups['ours']['tashkentskaya']->address ?></Address>
    <ContactMethod>По телефону</ContactMethod>
    <Category>Запчасти и аксессуары</Category>
    <ContactPhone>+7 (499) 302-48-30</ContactPhone>
    <ManagerName>Менеджер</ManagerName>
    <Description>
<![CDATA[
В продаже НОВАЯ <?= $product->getNameByTemplate('feed_full') ?><br>
👉 цена за одну шину — <?= $product->price->value ?> руб;<br>
👉 цена за комплект шин — <?= $product->price->value * $product->qty_set ?> руб;<br>
<br>
❗️ В наличии: <?= $product->qty > 12 ? '> 12' : $product->qty ?> шт.
<br>
✅ Бесплатный самовывоз.<br>
✅ Гарантия 5 лет.<br>
<? foreach ($product->promotions as $promotion): ?>
✅ <?= $promotion->name ?>.<br>
<? endforeach ?>
<br>
Оплата:<br>
— Наличными<br>
— Картой при получении<br>
— Картой на сайте<br>
— По QR-коду на сайте<br>
— Безналичным переводом через банк<br>
— В кредит через Юкасса<br>
<br>
Шинные центры BlackTyres:<br>
<br>
<? foreach ($pickups as $pickup): ?>
<? if ($pickup->type === 'ours'): //TODO ?>
<?= $pickup->name ?>;<br>
<? endif ?>
<? endforeach ?>
<br>
Партнерские пункты выдачи:<br>
<br>
<? foreach ($pickups as $pickup): ?>
<? if ($pickup->type === 'partners'): //TODO ?>
<?= $pickup->name ?>;<br>
<? endif ?>
<? endforeach ?>
<br>
Больше разных шин и дисков любого размера вы сможете найти на сайте BlackTyres.<br>
]]>
    </Description>
    <Price><?= $product->price->value ?></Price>
    <Images>
        <Image url="<?= $baseUrl . $product->pictures[0]->variations->product_large->path ?>"></Image>
    </Images>
    <TypeId>10-048</TypeId>
    <GoodsType>Шины, диски и колёса</GoodsType>
    <AdType>Товар приобретен на продажу</AdType>
    <ProductType>Легковые шины</ProductType>
    <Condition>Новое</Condition>
    <Brand><?= $product->features['brand']->value ?></Brand>
    <Model><?= $product->features['model']->value ?></Model>
    <TireSectionWidth><?= $product->features['width']->value ?></TireSectionWidth>
    <RimDiameter><?= $product->features['diameter']->value ?></RimDiameter>
    <TireAspectRatio><?= $product->features['profile']->value ?></TireAspectRatio>
    <TireType><?= $product->getTyreType() //TODO ?></TireType>
    <LoadIndex><?= $product->features['power']->value ?></LoadIndex>
    <SpeedIndex><?= $product->features['speed']->value ?></SpeedIndex>
    <RunFlat><?= $product->features['runflat']->value ? 'Да' : 'Нет' //TODO ?></RunFlat>
    <Quantity>за 1 шт.</Quantity>
</Ad>
