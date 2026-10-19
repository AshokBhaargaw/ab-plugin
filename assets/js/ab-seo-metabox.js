/**
 * AB SEO Meta Box JavaScript
 *
 * Handles:
 * - Real-time SEO analysis for multiple focus keyphrases (up to 5)
 * - Flesch Reading Ease & content readability analysis
 * - Live Google Snippet preview & device toggles (Desktop/Mobile)
 * - Character & pixel length progress meters
 * - Facebook & Twitter live card simulations
 * - Dynamic FAQ Schema builder
 * - Live internal linking suggestions & 1-click clipboard copy
 * - AI / Smart Generate Title & Meta Description
 */

(function($) {
  'use strict';

  var currentKwIndex = 0;

  $(document).ready(function() {
    initTabs();
    initKeywordTabs();
    initSnippetPreview();
    initMediaUploaders();
    initFAQBuilder();
    initInternalLinking();
    initSmartAIGenerator();

    // Run initial content & SEO evaluation
    runRealtimeSEOAnalysis();

    // Listen to changes in WordPress post title & content editor (Gutenberg & Classic)
    bindContentListeners();
  });

  /**
   * Main Tab navigation inside the meta box.
   */
  function initTabs() {
    $('.ab-seo-tab-btn').on('click', function() {
      var tabId = $(this).data('tab');
      $('.ab-seo-tab-btn').removeClass('active');
      $('.ab-seo-tab-content').removeClass('active');

      $(this).addClass('active');
      $('#' + tabId).addClass('active');

      if (tabId === 'tab-internal-links') {
        fetchInternalLinkingSuggestions();
      }
    });

    // Device toggle in Google snippet preview
    $('.ab-device-btn').on('click', function() {
      var device = $(this).data('device');
      $('.ab-device-btn').removeClass('active');
      $(this).addClass('active');

      if (device === 'desktop') {
        $('#ab-google-preview').removeClass('mobile-view').addClass('desktop-view');
      } else {
        $('#ab-google-preview').removeClass('desktop-view').addClass('mobile-view');
      }
    });
  }

  /**
   * Focus Keyword Tabs (Up to 5 Keywords - Yoast Premium Feature).
   */
  function initKeywordTabs() {
    $('.ab-seo-kw-tab-btn').on('click', function() {
      var index = $(this).data('kw-index');
      currentKwIndex = index;

      $('.ab-seo-kw-tab-btn').removeClass('active');
      $('.ab-seo-kw-panel').removeClass('active');

      $(this).addClass('active');
      $('[data-kw-panel="' + index + '"]').addClass('active');

      runRealtimeSEOAnalysis();
    });

    // Update tab label and status when keyword input changes
    $('.ab-seo-kw-input').on('input', function() {
      var index = $(this).closest('.ab-seo-kw-panel').data('kw-panel');
      var val = $(this).val().trim();
      var $tabBtn = $('[data-kw-index="' + index + '"]');

      if (val.length > 0) {
        $tabBtn.find('.ab-seo-kw-label').text(val);
        $tabBtn.addClass('has-kw');
      } else {
        var defaultLabel = index === 0 ? 'Primary Keyphrase' : '+ Add Keyphrase ' + (parseInt(index, 10) + 1);
        $tabBtn.find('.ab-seo-kw-label').text(defaultLabel);
        $tabBtn.removeClass('has-kw');
      }

      runRealtimeSEOAnalysis();
    });

    $('.ab-seo-synonyms-input').on('input', function() {
      runRealtimeSEOAnalysis();
    });
  }

  /**
   * Live Google Snippet & Character Meters.
   */
  function initSnippetPreview() {
    $('#ab_seo_title').on('input', function() {
      updateTitleMeter();
      updateGooglePreview();
      runRealtimeSEOAnalysis();
    });

    $('#ab_seo_meta_desc').on('input', function() {
      updateDescMeter();
      updateGooglePreview();
      runRealtimeSEOAnalysis();
    });

    // Tag chips click to insert
    $('.ab-tag-chip').on('click', function() {
      var tag = $(this).data('tag');
      var $input = $('#ab_seo_title');
      var pos = $input[0].selectionStart || $input.val().length;
      var cur = $input.val();
      $input.val(cur.slice(0, pos) + ' ' + tag + ' ' + cur.slice(pos)).trigger('input').focus();
    });

    updateTitleMeter();
    updateDescMeter();
  }

  function updateTitleMeter() {
    var title = $('#ab_seo_title').val();
    var len = title.length;
    $('#ab-title-meter-label').text(len + ' / 60');

    var pct = Math.min(100, Math.round((len / 60) * 100));
    var $bar = $('#ab-title-meter-bar');
    $bar.css('width', pct + '%');

    if (len >= 40 && len <= 60) {
      $bar.removeClass('ok bad').addClass('good');
    } else if (len > 60) {
      $bar.removeClass('good ok').addClass('bad');
    } else {
      $bar.removeClass('good bad').addClass('ok');
    }
  }

  function updateDescMeter() {
    var desc = $('#ab_seo_meta_desc').val();
    var len = desc.length;
    $('#ab-desc-meter-label').text(len + ' / 160');

    var pct = Math.min(100, Math.round((len / 160) * 100));
    var $bar = $('#ab-desc-meter-bar');
    $bar.css('width', pct + '%');

    if (len >= 120 && len <= 160) {
      $bar.removeClass('ok bad').addClass('good');
    } else if (len > 160) {
      $bar.removeClass('good ok').addClass('bad');
    } else {
      $bar.removeClass('good bad').addClass('ok');
    }
  }

  function updateGooglePreview() {
    var title = $('#ab_seo_title').val().trim();
    if (!title) {
      title = getPostTitle() || 'Untitled Post';
    }
    // Replace snippet tags in preview
    title = title.replace(/%%sitename%%/g, ab_seo_metabox_data.site_name || '')
                 .replace(/%%title%%/g, getPostTitle())
                 .replace(/%%sep%%/g, ab_seo_metabox_data.default_sep || '—');

    $('#ab-google-preview-title').text(title);

    var desc = $('#ab_seo_meta_desc').val().trim();
    if (!desc) {
      desc = getContentText().slice(0, 150) || 'Please provide a meta description by editing the snippet below.';
    }
    $('#ab-google-preview-desc').text(desc);

    // Social preview sync
    if (!$('#ab_og_title').val()) {
      $('#ab-fb-preview-title').text(title);
    }
    if (!$('#ab_og_desc').val()) {
      $('#ab-fb-preview-desc').text(desc);
    }
    if (!$('#ab_tw_title').val()) {
      $('#ab-tw-preview-title').text(title);
    }
    if (!$('#ab_tw_desc').val()) {
      $('#ab-tw-preview-desc').text(desc);
    }
  }

  /**
   * Media Uploader for Facebook and Twitter Images.
   */
  function initMediaUploaders() {
    // Custom inputs sync with social preview
    $('#ab_og_title').on('input', function() {
      $('#ab-fb-preview-title').text($(this).val() || $('#ab-google-preview-title').text());
    });
    $('#ab_og_desc').on('input', function() {
      $('#ab-fb-preview-desc').text($(this).val() || $('#ab-google-preview-desc').text());
    });
    $('#ab_tw_title').on('input', function() {
      $('#ab-tw-preview-title').text($(this).val() || $('#ab-fb-preview-title').text());
    });
    $('#ab_tw_desc').on('input', function() {
      $('#ab-tw-preview-desc').text($(this).val() || $('#ab-fb-preview-desc').text());
    });

    $(document).on('click', '.ab-upload-media-btn', function(e) {
      e.preventDefault();
      var targetInput = $(this).data('target');
      var previewBox = $(this).data('preview');

      var customUploader = wp.media({
        title: 'Select Social Share Image',
        button: { text: 'Use this Image' },
        multiple: false
      }).on('select', function() {
        var attachment = customUploader.state().get('selection').first().toJSON();
        $(targetInput).val(attachment.url);
        if (previewBox) {
          $(previewBox).css('background-image', 'url(' + attachment.url + ')').find('.dashicons').hide();
        }
      }).open();
    });

    $(document).on('click', '.ab-remove-media-btn', function(e) {
      e.preventDefault();
      var targetInput = $(this).data('target');
      var previewBox = $(this).data('preview');

      $(targetInput).val('');
      if (previewBox) {
        $(previewBox).css('background-image', 'none').find('.dashicons').show();
      }
    });
  }

  /**
   * Dynamic FAQ Schema Builder (Google Rich Snippet).
   */
  function initFAQBuilder() {
    $('#ab-add-faq-btn').on('click', function() {
      var count = $('#ab-faq-items-wrap .ab-faq-item-row').length;
      var html = '<div class="ab-faq-item-row">' +
        '<div class="ab-faq-head">' +
          '<strong>FAQ Question #' + (count + 1) + '</strong>' +
          '<button type="button" class="button button-link-delete ab-remove-faq-btn">Remove</button>' +
        '</div>' +
        '<input type="text" name="ab_seo[faq_items][' + count + '][q]" class="large-text" placeholder="Question (e.g. What are the temple timings?)" />' +
        '<textarea name="ab_seo[faq_items][' + count + '][a]" rows="2" class="large-text" placeholder="Answer..."></textarea>' +
      '</div>';

      $('#ab-faq-items-wrap').append(html);
    });

    $(document).on('click', '.ab-remove-faq-btn', function() {
      $(this).closest('.ab-faq-item-row').remove();
    });
  }

  /**
   * Internal Linking Suggestions.
   */
  function initInternalLinking() {
    $('#ab-refresh-suggestions-btn').on('click', function() {
      fetchInternalLinkingSuggestions();
    });

    $('#ab-linking-search-input').on('keyup', function(e) {
      if (e.key === 'Enter') {
        fetchInternalLinkingSuggestions();
      }
    });

    $(document).on('click', '.ab-copy-link-btn', function() {
      var url = $(this).data('url');
      var $btn = $(this);

      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
          var original = $btn.html();
          $btn.html('<span class="dashicons dashicons-yes"></span> ' + (ab_seo_metabox_data.i18n.copied || 'Copied!'));
          setTimeout(function() { $btn.html(original); }, 2000);
        });
      }
    });
  }

  function fetchInternalLinkingSuggestions() {
    var keyword = getActiveKeyword();
    var title = getPostTitle();
    var customSearch = $('#ab-linking-search-input').val().trim();
    var postId = $('#post_ID').val() || 0;

    $('#ab-internal-linking-results').html('<p class="description">Scanning site for relevant articles...</p>');

    $.ajax({
      url: ab_seo_metabox_data.ajax_url,
      type: 'POST',
      data: {
        action: 'ab_seo_get_linking_suggestions',
        nonce: ab_seo_metabox_data.nonce,
        post_id: postId,
        keyword: customSearch || keyword,
        title: title
      },
      success: function(res) {
        if (res.success && res.data.suggestions && res.data.suggestions.length > 0) {
          var html = '';
          $.each(res.data.suggestions, function(i, item) {
            html += '<div class="ab-linking-card">' +
              '<div>' +
                '<div class="ab-linking-card-title">' + escapeHtml(item.title) + (item.is_cornerstone ? ' <span class="ab-seo-cornerstone-star" title="Cornerstone Article">★</span>' : '') + '</div>' +
                '<div style="font-size:11px; color:#64748b;">Inbound links: <strong>' + item.inbound_links + '</strong></div>' +
              '</div>' +
              '<div class="ab-linking-meta">' +
                '<span>' + item.date + '</span>' +
                '<button type="button" class="button ab-copy-link-btn" data-url="' + item.url + '">' +
                  '<span class="dashicons dashicons-admin-links"></span> Copy Link' +
                '</button>' +
              '</div>' +
            '</div>';
          });
          $('#ab-internal-linking-results').html(html);
        } else {
          $('#ab-internal-linking-results').html('<p class="description">No matching suggestions found. Try searching for a different keyword above.</p>');
        }
      },
      error: function() {
        $('#ab-internal-linking-results').html('<p class="description">Unable to load suggestions.</p>');
      }
    });
  }

  /**
   * Smart AI / Template Generator.
   */
  function initSmartAIGenerator() {
    $('#ab-seo-smart-gen-btn').on('click', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var $spinner = $('.ab-seo-spinner');

      $spinner.addClass('is-active');
      $btn.prop('disabled', true);

      $.ajax({
        url: ab_seo_metabox_data.ajax_url,
        type: 'POST',
        data: {
          action: 'ab_seo_generate_smart_meta',
          nonce: ab_seo_metabox_data.nonce,
          post_id: $('#post_ID').val() || 0,
          title: getPostTitle(),
          content: getContentText(),
          keyword: getActiveKeyword()
        },
        success: function(res) {
          $spinner.removeClass('is-active');
          $btn.prop('disabled', false);

          if (res.success && res.data) {
            if (res.data.title) {
              $('#ab_seo_title').val(res.data.title).trigger('input');
            }
            if (res.data.description) {
              $('#ab_seo_meta_desc').val(res.data.description).trigger('input');
            }
          }
        },
        error: function() {
          $spinner.removeClass('is-active');
          $btn.prop('disabled', false);
        }
      });
    });
  }

  /**
   * Real-time Content & SEO Evaluation (Checks all 10+ SEO parameters + Readability).
   */
  function runRealtimeSEOAnalysis() {
    var kw = getActiveKeyword();
    var content = getContentText();
    var title = $('#ab_seo_title').val() || getPostTitle();
    var desc = $('#ab_seo_meta_desc').val();
    var words = countWords(content);
    var isCornerstone = $('input[name="ab_seo[is_cornerstone]"]').is(':checked');

    $('#ab-active-kw-display').text(kw ? 'Analyzing: "' + kw + '"' : 'Please enter a focus keyphrase');

    var seoAudits = [];
    var passCount = 0;

    // 1. Keyphrase provided
    if (!kw) {
      seoAudits.push({ status: 'bad', text: 'No focus keyphrase set for this post. Add a focus keyphrase above.' });
    } else {
      // 2. Keyphrase Length
      var kwWords = kw.split(/\s+/).length;
      if (kwWords > 4) {
        seoAudits.push({ status: 'ok', text: 'Keyphrase length: The keyphrase has ' + kwWords + ' words. Shorter keyphrases (1-4 words) are typically more targeted.' });
      } else {
        seoAudits.push({ status: 'good', text: 'Keyphrase length: Optimal length (' + kwWords + ' words).' });
        passCount++;
      }

      // 3. Keyphrase in SEO Title
      if (title.toLowerCase().indexOf(kw.toLowerCase()) !== -1) {
        // Check if at the beginning
        var titleIndex = title.toLowerCase().indexOf(kw.toLowerCase());
        if (titleIndex === 0) {
          seoAudits.push({ status: 'good', text: 'Keyphrase in SEO Title: Appears at the beginning of the title. Excellent!' });
          passCount++;
        } else {
          seoAudits.push({ status: 'good', text: 'Keyphrase in SEO Title: Found in the title.' });
          passCount++;
        }
      } else {
        seoAudits.push({ status: 'bad', text: 'Keyphrase in SEO Title: The focus keyphrase does not appear in the SEO title.' });
      }

      // 4. Keyphrase in Meta Description
      if (desc && desc.toLowerCase().indexOf(kw.toLowerCase()) !== -1) {
        seoAudits.push({ status: 'good', text: 'Keyphrase in Meta Description: Included in the snippet description.' });
        passCount++;
      } else {
        seoAudits.push({ status: 'bad', text: 'Keyphrase in Meta Description: The keyphrase was not found in the meta description.' });
      }

      // 5. Keyphrase in Introduction (first 100 words)
      var intro = content.split(/\s+/).slice(0, 100).join(' ').toLowerCase();
      if (intro.indexOf(kw.toLowerCase()) !== -1) {
        seoAudits.push({ status: 'good', text: 'Keyphrase in Introduction: Your keyphrase appears in the first paragraph.' });
        passCount++;
      } else {
        seoAudits.push({ status: 'bad', text: 'Keyphrase in Introduction: Your keyphrase does not appear in the first paragraph.' });
      }

      // 6. Keyphrase Density
      if (words > 0) {
        var matches = (content.toLowerCase().match(new RegExp(escapeRegex(kw.toLowerCase()), 'g')) || []).length;
        var density = ((matches * kwWords) / words) * 100;
        var densityRound = density.toFixed(2);

        if (matches === 0) {
          seoAudits.push({ status: 'bad', text: 'Keyphrase density: The focus keyphrase was found 0 times in the content.' });
        } else if (density >= 0.5 && density <= 2.5) {
          seoAudits.push({ status: 'good', text: 'Keyphrase density: ' + densityRound + '% (' + matches + ' times). This is within the optimal range (0.5% - 2.5%).' });
          passCount++;
        } else if (density > 2.5) {
          seoAudits.push({ status: 'bad', text: 'Keyphrase density: ' + densityRound + '% (' + matches + ' times). This is too high (keyword stuffing). Aim for under 2.5%.' });
        } else {
          seoAudits.push({ status: 'ok', text: 'Keyphrase density: ' + densityRound + '% (' + matches + ' times). A bit low; consider using it 1 or 2 more times naturally.' });
        }
      }

      // 7. Content Length
      var minWords = isCornerstone ? 900 : 300;
      if (words >= minWords) {
        seoAudits.push({ status: 'good', text: 'Text length: ' + words + ' words. Meets the recommended minimum of ' + minWords + ' words.' });
        passCount++;
      } else {
        seoAudits.push({ status: 'bad', text: 'Text length: ' + words + ' words. This is below the recommended minimum of ' + minWords + ' words' + (isCornerstone ? ' for Cornerstone content.' : '.') });
      }
    }

    // Render SEO checklist
    var seoHtml = '';
    $.each(seoAudits, function(i, a) {
      seoHtml += '<div class="ab-seo-audit-item"><span class="ab-dot ' + a.status + '"></span> <span class="ab-text">' + a.text + '</span></div>';
    });
    $('#ab-seo-audit-checklist').html(seoHtml);

    // Update keyword dot indicator
    var $activeKwTab = $('[data-kw-index="' + currentKwIndex + '"]');
    if (kw) {
      var kwStatus = (passCount >= 4) ? 'good' : (passCount >= 2 ? 'ok' : 'bad');
      $activeKwTab.find('.ab-seo-kw-status-dot').css('background', kwStatus === 'good' ? '#10b981' : (kwStatus === 'ok' ? '#f59e0b' : '#ef4444'));
    }

    // ---------------- READABILITY CHECKS ----------------
    var readAudits = [];
    var readScore = 'ok';

    // Flesch Reading Ease
    var flesch = calculateFleschScore(content);
    if (flesch >= 60) {
      readAudits.push({ status: 'good', text: 'Flesch Reading Ease: Score ' + flesch + '/100. Easy and pleasant to read for most visitors.' });
      readScore = 'good';
    } else if (flesch >= 45) {
      readAudits.push({ status: 'ok', text: 'Flesch Reading Ease: Score ' + flesch + '/100. Fairly readable, slightly complex phrasing.' });
    } else if (words > 50) {
      readAudits.push({ status: 'bad', text: 'Flesch Reading Ease: Score ' + flesch + '/100. The copy is difficult to read. Try using shorter sentences and simpler vocabulary.' });
      readScore = 'bad';
    } else {
      readAudits.push({ status: 'ok', text: 'Add more content to calculate reading ease.' });
    }

    // Paragraph Length Check (>150 words)
    var paras = content.split(/\n+/);
    var longParas = 0;
    $.each(paras, function(i, p) {
      if (countWords(p) > 150) { longParas++; }
    });
    if (longParas === 0) {
      readAudits.push({ status: 'good', text: 'Paragraph length: None of the paragraphs are too long. Great scannability.' });
    } else {
      readAudits.push({ status: 'ok', text: 'Paragraph length: ' + longParas + ' paragraph(s) contain more than 150 words. Consider splitting them.' });
    }

    var readHtml = '';
    $.each(readAudits, function(i, a) {
      readHtml += '<div class="ab-seo-audit-item"><span class="ab-dot ' + a.status + '"></span> <span class="ab-text">' + a.text + '</span></div>';
    });
    $('#ab-readability-checklist').html(readHtml);

    // Update Overall header badges
    var overallSeo = (passCount >= 4) ? 'good' : (passCount >= 2 ? 'ok' : 'bad');
    $('#ab-seo-overall-pill').removeClass('ab-seo-pill-good ab-seo-pill-ok ab-seo-pill-bad')
      .addClass('ab-seo-pill-' + overallSeo)
      .text(overallSeo === 'good' ? 'Good' : (overallSeo === 'ok' ? 'OK' : 'Needs Improvement'));

    $('#ab-seo-read-pill').removeClass('ab-seo-pill-good ab-seo-pill-ok ab-seo-pill-bad')
      .addClass('ab-seo-pill-' + readScore)
      .text(readScore === 'good' ? 'Good' : (readScore === 'ok' ? 'OK' : 'Needs Improvement'));
  }

  /**
   * Helper: Get current active keyword.
   */
  function getActiveKeyword() {
    return $('[data-kw-panel="' + currentKwIndex + '"] .ab-seo-kw-input').val().trim();
  }

  /**
   * Helper: Get Post Title from Gutenberg or Classic editor.
   */
  function getPostTitle() {
    if (window.wp && wp.data && wp.data.select('core/editor')) {
      var t = wp.data.select('core/editor').getEditedPostAttribute('title');
      if (t) { return t; }
    }
    return $('#title').val() || '';
  }

  /**
   * Helper: Get plain content text from Gutenberg or Classic editor.
   */
  function getContentText() {
    if (window.wp && wp.data && wp.data.select('core/editor')) {
      var c = wp.data.select('core/editor').getEditedPostContent();
      if (c) {
        return $('<div>').html(c).text();
      }
    }
    if (window.tinyMCE && tinyMCE.get('content')) {
      return tinyMCE.get('content').getContent({ format: 'text' });
    }
    return $('#content').val() || '';
  }

  /**
   * Helper: Count words in text.
   */
  function countWords(str) {
    if (!str) return 0;
    var matches = str.trim().match(/\S+/g);
    return matches ? matches.length : 0;
  }

  /**
   * Helper: Flesch Reading Ease algorithm.
   */
  function calculateFleschScore(text) {
    var words = countWords(text);
    if (words < 10) return 70;

    var sentences = (text.match(/[\.\?!]+/g) || []).length || 1;
    var syllables = 0;
    var cleanWords = text.toLowerCase().match(/\b[a-z]+\b/g) || [];

    $.each(cleanWords, function(i, word) {
      syllables += countSyllables(word);
    });

    var score = 206.835 - (1.015 * (words / sentences)) - (84.6 * (syllables / words));
    return Math.max(0, Math.min(100, Math.round(score)));
  }

  function countSyllables(word) {
    word = word.toLowerCase();
    if (word.length <= 3) return 1;
    word = word.replace(/(?:[^laeiouy]|ed|es|e)$/, '');
    word = word.replace(/^y/, '');
    var matches = word.match(/[aeiouy]{1,2}/g);
    return matches ? matches.length : 1;
  }

  function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function escapeHtml(string) {
    return $('<div>').text(string).html();
  }

  function bindContentListeners() {
    $('#title').on('input', function() {
      updateGooglePreview();
      runRealtimeSEOAnalysis();
    });

    $('#content').on('input', function() {
      runRealtimeSEOAnalysis();
    });

    if (window.wp && wp.data && wp.data.subscribe) {
      wp.data.subscribe(function() {
        updateGooglePreview();
        runRealtimeSEOAnalysis();
      });
    }
  }

})(jQuery);
