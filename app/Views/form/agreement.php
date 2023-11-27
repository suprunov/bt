<?php
/**
 * @var string $agreementId
 */
?>
<!--<div class="agreement-wrap">
    <label class="form-checkbox-label">
        <input type="checkbox" name="agreement" class="form-checkbox" value="1" checked onchange="const b=$('[data-agreement_bond=<?php /*= $agreementId */?>]'); $(this).prop('checked') ? b.removeClass('disabled').prop('disabled', false) : b.addClass('disabled').prop('disabled', true)">
        <span class="form-checkbox-box"></span>
        <span class="form-checkbox-label-text">
            Согласен(на) на обработку персональных данных в соответствии с Федеральным законом "О персональных данных" от 27.07.2006 N 152-ФЗ." <a class="radio-link" target="_blank" href="/pokupatelyu/politika-konfidentsialnosti/">Подробнее…</a>
        </span>
    </label>
</div>-->

<div class="form-check agreement-wrap">
    <input class="form-check-input" type="checkbox" checked id="agreement-<?= $agreementId ?>" name="agreement" onchange="const b=$('[data-agreement_bond=<?= $agreementId ?>]'); $(this).prop('checked') ? b.removeClass('disabled').prop('disabled', false) : b.addClass('disabled').prop('disabled', true)">
    <label class="form-check-label" for="agreement-<?= $agreementId ?>">
        Даю согласие на обработку <a class="radio-link" target="_blank" href="/pokupatelyu/politika-konfidentsialnosti/">персональных данных</a>
    </label>
</div>
