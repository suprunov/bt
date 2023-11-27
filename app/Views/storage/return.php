<?php
/**
 * @var array $storageNumber
 * @var array $pickups
 * @var array $contactPersons<\App\Entities\ContactPerson>
 */
helper('common'); // TODO update CI to 4.3 and move the common helper to the autoload config
?>
<div id="storage-return-wrap" class="popup-overlay" style="display:none">

    <div style="display:none;top:50%;left:50%;position:absolute;">
        <?= \App\Helpers\ImgHelper::tag("/media/images/ajax-loader.gif", ["style" => "width:125px;", "id" => "loader"]) ?>
    </div>

    <div id="storage-return-modal" class="popup cabinet-storage-order-popup" style="display:none;">

        <div class="popup-title">Отправить запрос на возврат имущества с хранения</div>

        <div class="popup-content">

            <div class="popup-result" id="thank_you" style="display:none;">
                Сообщение отправлено.<br>
                Наш менеджер свяжется с Вами в ближайшее время!
            </div>

            <div id="cabinet-storage-order-form" class="form popup-form">

                <div class="note mb-3">
                    Наш сотрудник свяжется с Вами в ближайшее время для подтверждения и уточнения деталей!
                </div>

                <div class="form-floating bt-form-floating mb-3 has-validation">
                    <input required disabled data-validate="123-" type="text" class="form-control" id="storage-number" placeholder="Номер договора" name="storage-number" value="<?= $storageNumber ?>">
                    <label for="storage-number">Номер договора</label>
                    <div class="invalid-feedback">
                        Обязательно для заполнения.
                    </div>
                </div>

                <div class="form-floating bt-form-floating mb-3 has-validation">
                    <input required="" data-validate="123dot" data-mask="date_dote" type="text" class="form-control" id="storage-return-date" placeholder="Желаемая дата возврата" value="" name="return-date">
                    <label for="storage-return-date">Желаемая дата возврата</label>
                    <div class="invalid-feedback">
                        Обязательно для заполнения.
                    </div>
                </div>

                <div class="form-floating mb-3 has-validation">
                    <select required data-validate="select" name="pickup" class="form-select" id="storage-pickup">
                        <option value="0">Выберите место возврата</option>
                        <? foreach ($pickups as $p): ?>
                            <? if (!$p->is_partner): ?>
                                <option value="<?= $p->id_pickup ?>"><?= $p->pickup_name ?></option> <? endif ?>
                        <? endforeach ?>
                    </select>
                    <label for="storage-pickup">Место возврата</label>
                    <div class="invalid-feedback">
                        Обязательно для заполнения.
                    </div>
                </div>

                <div class="form-floating mb-3 has-validation">
                    <select required data-validate="select" name="contact" class="form-select" id="storage-contact">
                        <? if (count($contactPersons) == 0): ?>
                            <option value="0">У вас отсутствуют контактные лица</option>
                        <? else: ?>
                            <? foreach ($contactPersons as $person): ?>
                                <option value="<?= $person->id ?>" data-contact-phone="<?= $person->phone ?>">
                                    <?= $person->getName() ?>
                                </option>
                            <? endforeach ?>
                        <? endif ?>
                    </select>
                    <label for="storage-contact">Получатель</label>
                    <div class="invalid-feedback">
                        Обязательно для заполнения.
                    </div>
                </div>

                <div class="form-floating mb-3 has-validation">
                    <input required disabled data-validate="phone_masked" data-mask="phone" inputmode="numeric" placeholder="phone" class="form-control" id="storage-phone" name="phone" value="<?= $contactPersons[0]->phone ?? '' ?>">
                    <label for="storage-phone">Номер телефона без +7</label>
                    <div class="invalid-feedback">
                        Обязательно для заполнения. Выберите получателя.
                    </div>
                </div>

                <div class="form-floating has-validation mb-3">
                    <textarea data-validate="any" class="form-control" placeholder="Комментарий" id="storage-comment" name="comment"></textarea>
                    <label for="order_comment">Комментарий</label>
                </div>

                <div class="mb-3">
                    <?= view('form/agreement', ['agreementId' => 'storage-agreement']) ?>
                </div>

                <div class="buttons">
                    <a href="#" id="storage-send" data-agreement_bond="storage-agreement" class="button big">Отправить</a>
                </div>

            </div>
        </div>
        <a href="#" class="popup-close i-close" onclick="return false;"></a>
    </div>
</div>

<script>
    const modalWrap = $('#storage-return-wrap'),
        modal = modalWrap.find('#storage-return-modal');

    function placePopup() {
        if ($BT.v.is_mobile) {
            let content = modal.find(".popup-content"),
                title = modal.find(".popup-title");
            content.height(modal.height() - title.outerHeight());
        } else {
            let mt = modal.css("marginTop").split('px').join(''),
                ml = modal.css("marginLeft").split('px').join(''),
                top = Math.round((modalWrap.innerHeight() - modal.outerHeight()) / 2) - mt,
                left = Math.round((modalWrap.innerWidth() - modal.outerWidth()) / 2) - ml;
            top = top < 0 ? 0 : top;
            left = left < 0 ? 0 : left;
            modal.css("top", top + "px");
            modal.css("left", left + "px");
        }
    }

    function showPopup() {
        modalWrap.show();
        modal.show();
        placePopup();
        $("body").css("overflow", "hidden");
        return false;
    }

    function hidePopup() {
        modalWrap.hide();
        modal.hide();
        $("body").css("overflow", "auto");
        return false;
    }

    jQuery(function ($) {
        modal.find("#storage-send").on("click", function (e) {
            e.preventDefault();

            if ($(this).hasClass('disabled')) {
                return false;
            }

            const storageNumber = $('#storage-number'),
                returnDate = $('#storage-return-date'),
                contactPersonId = $('#storage-contact'),
                comment = $('#storage-comment'),
                pickup = $('#storage-pickup');

            let validate = true;
            $('#cabinet-storage-order-form').find('[required]').each(function () {
                if (!processValidate($(this)))
                    validate = false;
            })
            if (!validate)
                return false;

            $.ajax({
                url: _GEOIP.city.url + "/seasonal_contract_off",
                method: "POST",
                data: {
                    "storageNumber": storageNumber.val(),
                    "returnDate": returnDate.val(),
                    "contactId": contactPersonId.val(),
                    "pickupId": pickup.val(),
                    "comment": comment.val()
                },
                dataType: "text",
                success: function (msg) {
                    if (msg == 1) {
                        modal.find(".popup-result").css({display: 'block'});
                        modal.find(".popup-form").hide();
                        modal.find(".error").removeClass("error");
                        placePopup();
                    } else
                        alert('Ошибка: попробуйте отправить заявку еще раз');
                },
                error: function (msg) {
                    alert('Ошибка: попробуйте отправить заявку еще раз');
                }
            });

            return false;
        });

        modal.find('#storage-contact').on('change', function (e) {
            modal.find('#storage-phone').val(modal.find('#storage-contact').find('option:selected').data('contact-phone'));
        });

        $(window).on("resize", function () {
            placePopup();
        });

        modal.find('.popup-close').on("click", function () {
            hidePopup();
        });
    });
</script>
