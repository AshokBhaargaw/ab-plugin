/**
 * AB SEO Admin Settings JavaScript
 *
 * Handles:
 * - Redirect Manager Modal (Add / Edit / Save / Delete)
 * - 404 Monitor actions (1-click 301 redirect conversion & clear logs)
 * - Internal Links Bulk Re-indexer
 * - IndexNow API key generation & test submission
 * - Media library uploaders on settings page
 */

(function($) {
  'use strict';

  $(document).ready(function() {
    initRedirectsModal();
    init404Actions();
    initInternalLinksReindexer();
    initIndexNow();
    initAdminMediaPickers();
  });

  /**
   * Redirects Modal & AJAX CRUD.
   */
  function initRedirectsModal() {
    // Open modal
    $('#ab-btn-open-redirect-modal').on('click', function(e) {
      e.preventDefault();
      $('#modal_red_source').val('');
      $('#modal_red_target').val('');
      $('#modal_red_type').val('301');
      $('#modal_red_regex').prop('checked', false);
      $('#modal_red_note').val('');
      $('#modal_red_target_group').show();
      $('#ab-redirect-modal').fadeIn(150);
    });

    // Close modal
    $('.ab-modal-close').on('click', function() {
      $('#ab-redirect-modal').fadeOut(150);
    });

    // Toggle target group when 410 or 451 is selected
    $('#modal_red_type').on('change', function() {
      var val = $(this).val();
      if (val === '410' || val === '451') {
        $('#modal_red_target_group').hide();
      } else {
        $('#modal_red_target_group').show();
      }
    });

    // Save redirect via AJAX
    $('#ab-save-redirect-btn').on('click', function() {
      var $btn = $(this);
      var source = $('#modal_red_source').val().trim();
      var target = $('#modal_red_target').val().trim();
      var type = $('#modal_red_type').val();
      var isRegex = $('#modal_red_regex').is(':checked') ? 1 : 0;
      var note = $('#modal_red_note').val().trim();

      if (!source) {
        alert('Please enter a source URL (e.g. /old-path)');
        return;
      }

      $btn.prop('disabled', true).text('Saving...');

      $.ajax({
        url: ab_seo_admin_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_save_redirect',
          nonce: ab_seo_admin_data.nonce,
          source_url: source,
          target_url: target,
          type: type,
          is_regex: isRegex,
          note: note
        },
        success: function(res) {
          $btn.prop('disabled', false).text('Save Redirect');
          if (res.success) {
            $('#ab-redirect-modal').fadeOut(150);
            window.location.reload();
          } else {
            alert(res.data.message || 'Error saving redirect');
          }
        },
        error: function() {
          $btn.prop('disabled', false).text('Save Redirect');
          alert('Request failed.');
        }
      });
    });

    // Delete redirect
    $(document).on('click', '.ab-del-redirect-btn', function() {
      var id = $(this).data('id');
      if (!confirm(ab_seo_admin_data.i18n.confirm_delete || 'Delete this redirect?')) {
        return;
      }

      var $row = $(this).closest('tr');

      $.ajax({
        url: ab_seo_admin_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_delete_redirect',
          nonce: ab_seo_admin_data.nonce,
          id: id
        },
        success: function(res) {
          if (res.success) {
            $row.fadeOut(200, function() { $(this).remove(); });
          }
        }
      });
    });
  }

  /**
   * 404 Monitor actions.
   */
  function init404Actions() {
    // 1-click Convert 404 to 301 Redirect
    $(document).on('click', '.ab-resolve-404-btn', function() {
      var url = $(this).data('url');
      var target = prompt('Enter the destination URL for this 404 broken link (leave / for Homepage):', '/');
      if (target === null) return;

      var $row = $(this).closest('tr');

      $.ajax({
        url: ab_seo_admin_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_convert_404',
          nonce: ab_seo_admin_data.nonce,
          url: url,
          target: target || '/'
        },
        success: function(res) {
          if (res.success) {
            alert(res.data.message);
            $row.fadeOut(200, function() { $(this).remove(); });
          } else {
            alert(res.data.message || 'Error converting 404.');
          }
        }
      });
    });

    // Clear all 404 logs
    $('#ab-clear-404-btn').on('click', function() {
      if (!confirm(ab_seo_admin_data.i18n.confirm_clear || 'Clear all 404 error logs?')) {
        return;
      }

      $.ajax({
        url: ab_seo_admin_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_clear_404_logs',
          nonce: ab_seo_admin_data.nonce
        },
        success: function(res) {
          if (res.success) {
            window.location.reload();
          }
        }
      });
    });
  }

  /**
   * Internal Links Re-indexer.
   */
  function initInternalLinksReindexer() {
    $('#ab-reindex-links-btn').on('click', function() {
      var $btn = $(this);
      $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Analyzing & Re-indexing Links...');

      $.ajax({
        url: ab_seo_admin_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_reindex_internal_links',
          nonce: ab_seo_admin_data.nonce
        },
        success: function(res) {
          $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Finished!');
          if (res.success) {
            alert(res.data.message);
            window.location.reload();
          }
        },
        error: function() {
          $btn.prop('disabled', false).html('Re-index Internal Links Now');
          alert('Process timed out or failed.');
        }
      });
    });
  }

  /**
   * IndexNow Protocol actions.
   */
  function initIndexNow() {
    // Regenerate Key
    $('#ab-gen-indexnow-key-btn').on('click', function() {
      var $btn = $(this);
      $btn.prop('disabled', true).text('Generating...');

      $.ajax({
        url: ab_seo_admin_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_generate_indexnow_key',
          nonce: ab_seo_admin_data.nonce
        },
        success: function(res) {
          $btn.prop('disabled', false).text('Regenerate Key');
          if (res.success) {
            $('#ab_indexnow_key').val(res.data.key);
            alert(res.data.message);
          }
        }
      });
    });

    // Manual test submission
    $('#ab-test-indexnow-btn').on('click', function() {
      var url = $('#ab-indexnow-test-url').val().trim();
      var $btn = $(this);
      var $msg = $('#ab-indexnow-status-msg');

      if (!url) {
        alert('Please enter a URL to submit.');
        return;
      }

      $btn.prop('disabled', true);
      $msg.text('Submitting to IndexNow...').css('color', '#6366f1');

      $.ajax({
        url: ab_seo_admin_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_submit_indexnow',
          nonce: ab_seo_admin_data.nonce,
          url: url
        },
        success: function(res) {
          $btn.prop('disabled', false);
          if (res.success) {
            $msg.text('✓ ' + res.data.message).css('color', '#10b981');
          } else {
            $msg.text('✕ ' + res.data.message).css('color', '#ef4444');
          }
        },
        error: function() {
          $btn.prop('disabled', false);
          $msg.text('✕ Request failed.').css('color', '#ef4444');
        }
      });
    });
  }

  /**
   * Media Pickers on settings page.
   */
  function initAdminMediaPickers() {
    $('.ab-upload-media-btn').on('click', function(e) {
      e.preventDefault();
      var targetInput = $(this).data('target');

      var customUploader = wp.media({
        title: 'Select Media',
        button: { text: 'Use this Image' },
        multiple: false
      }).on('select', function() {
        var attachment = customUploader.state().get('selection').first().toJSON();
        $(targetInput).val(attachment.url);
      }).open();
    });
  }

})(jQuery);
