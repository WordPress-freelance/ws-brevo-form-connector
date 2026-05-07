(function ($) {
  'use strict';

  $(document).ready(function () {

    // ─── Tabs ──────────────────────────────────────
    $('.wsbfc-tab').on('click', function () {
      var target = $(this).data('tab');
      $('.wsbfc-tab').removeClass('active');
      $('.wsbfc-panel').removeClass('active');
      $(this).addClass('active');
      $('#' + target).addClass('active');
    });

    // ─── Test API ──────────────────────────────────
    $('#wsbfc-test-api').on('click', function () {
      var $btn = $(this);
      var $result = $('#wsbfc-test-result');
      $btn.prop('disabled', true).text('Test en cours…');
      $result.removeClass('ok fail').hide();

      $.post(wsBrevoFC.ajaxurl, {
        action: 'ws_brevo_fc_test_api',
        nonce:  wsBrevoFC.nonce
      }, function (res) {
        $btn.prop('disabled', false).text('Tester la connexion');
        if (res.success) {
          $result
            .addClass('ok')
            .html('✓ Connexion OK — compte : <strong>' + res.data.email + '</strong>'
              + (res.data.company ? ' (' + res.data.company + ')' : ''))
            .show();
        } else {
          $result
            .addClass('fail')
            .text('✗ ' + res.data)
            .show();
        }
      }).fail(function () {
        $btn.prop('disabled', false).text('Tester la connexion');
        $result.addClass('fail').text('✗ Erreur réseau.').show();
      });
    });

    // ─── Toggle API key visibility ─────────────────
    $('#wsbfc-toggle-key').on('click', function () {
      var $input = $('#ws_brevo_fc_api_key');
      var type = $input.attr('type') === 'password' ? 'text' : 'password';
      $input.attr('type', type);
      $(this).text(type === 'password' ? 'Afficher' : 'Masquer');
    });

    // ─── Ajouter une règle par formulaire ──────────
    var ruleIndex = $('.wsbfc-rule-row').length;

    $('#wsbfc-add-rule').on('click', function () {
      var row = '<tr class="wsbfc-rule-row">'
        + '<td><input type="text" name="form_rule_id[]" placeholder="ex: 42" /></td>'
        + '<td><input type="number" name="form_rule_list_id[]" placeholder="ex: 3" min="1" /></td>'
        + '<td style="text-align:center;">'
        +   '<label class="wsbfc-toggle">'
        +     '<input type="checkbox" name="form_rule_active[]" value="1" checked />'
        +     '<span class="wsbfc-toggle-slider"></span>'
        +   '</label>'
        + '</td>'
        + '<td><button type="button" class="wsbfc-remove-rule" title="Supprimer">×</button></td>'
        + '</tr>';
      $('#wsbfc-rules-tbody').append(row);
      ruleIndex++;
    });

    $(document).on('click', '.wsbfc-remove-rule', function () {
      $(this).closest('tr').remove();
    });

    // ─── Vider le journal ──────────────────────────
    $('#wsbfc-clear-log').on('click', function () {
      if (!confirm('Vider le journal de synchronisation ?')) return;
      var $btn = $(this);
      $btn.prop('disabled', true);
      $.post(wsBrevoFC.ajaxurl, {
        action: 'ws_brevo_fc_clear_log',
        nonce:  wsBrevoFC.nonce
      }, function () {
        location.reload();
      });
    });

  });

})(jQuery);
