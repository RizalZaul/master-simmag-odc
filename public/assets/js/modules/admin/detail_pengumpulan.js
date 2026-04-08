$(document).ready(function () {
    var detailConfig = window.MTUGAS_PENGUMPULAN_DETAIL || {};
    var items = Array.isArray(detailConfig.itemsMeta) ? detailConfig.itemsMeta : [];

    function showDetailToast(icon, title) {
        if (!window.Swal) {
            return;
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        });
    }

    function showDetailAlert(message) {
        if (!window.Swal) {
            window.alert(message);
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: message,
            confirmButtonText: 'OK'
        });
    }

    function updateCsrfHash(newHash) {
        if (!newHash) {
            return;
        }

        detailConfig.csrfHash = newHash;
        $('meta[name="csrf-token-hash"]').attr('content', newHash);
        if (detailConfig.csrfName) {
            $('input[name="' + detailConfig.csrfName + '"]').val(newHash);
        }
    }

    function createHiddenInput(name, value) {
        return $('<input>', {
            type: 'hidden',
            name: name,
            value: value
        });
    }

    function appendCsrfFields($form) {
        if (detailConfig.csrfName && detailConfig.csrfHash) {
            $form.append(createHiddenInput(detailConfig.csrfName, detailConfig.csrfHash));
        }
    }

    function buildReviewUrl(itemId) {
        return (detailConfig.reviewUrlBase || '') + '/' + itemId + '/review';
    }

    function escapeHtmlWithBreaks(value) {
        return $('<div>').text(value == null ? '' : String(value)).html().replace(/\n/g, '<br>');
    }

    function buildApproveForm(item) {
        if (!item.id_item) {
            return null;
        }

        var $form = $('<form>', {
            method: 'post',
            action: buildReviewUrl(item.id_item),
            class: 'mtugas-inline-form',
            'data-item-id': item.id_item
        });

        appendCsrfFields($form);
        $form.append(createHiddenInput('review_status', 'diterima'));

        $form.append(
            $('<button>', {
                type: 'submit',
                class: 'btn-mpkl-submit mtugas-review-btn',
                html: '<i class="fas fa-check"></i> Setujui'
            })
        );

        return $form;
    }

    function buildRevisionToggle(item, reviewOpen) {
        if (!item.id_item) {
            return null;
        }

        return $('<button>', {
            type: 'button',
            class: 'btn-mpkl-filter mtugas-review-btn',
            'data-review-toggle': item.id_item,
            'aria-expanded': reviewOpen ? 'true' : 'false',
            html: '<i class="fas fa-rotate-left"></i> Revisi'
        });
    }

    function buildRevisionForm(item, reviewOpen) {
        if (!item.id_item) {
            return null;
        }

        var $form = $('<form>', {
            method: 'post',
            action: buildReviewUrl(item.id_item),
            class: 'mtugas-review-form' + (reviewOpen ? ' is-open' : ''),
            'data-review-form': item.id_item,
            'data-item-id': item.id_item
        });

        appendCsrfFields($form);
        $form.append(createHiddenInput('review_status', 'revisi'));
        $form.append(createHiddenInput('review_item_id', item.id_item));
        $form.append(
            $('<label>', {
                for: 'komentarRevisi' + item.id_item,
                class: 'mtugas-review-label',
                text: 'Keterangan Revisi'
            })
        );

        $form.append(
            $('<textarea>', {
                id: 'komentarRevisi' + item.id_item,
                name: 'komentar',
                class: 'mpkl-input mtugas-review-textarea',
                'data-sv-rule': 'multiline_text',
                'data-sv-label': 'Keterangan Revisi',
                rows: 3,
                placeholder: 'Tuliskan bagian yang perlu diperbaiki...'
            }).val(reviewOpen ? (detailConfig.oldKomentar || '') : '')
        );

        var $actions = $('<div>', { class: 'mtugas-review-form-actions' });
        $actions.append(
            $('<button>', {
                type: 'button',
                class: 'btn-mpkl-cancel mtugas-review-cancel',
                'data-review-cancel': item.id_item,
                text: 'Batal'
            })
        );
        $actions.append(
            $('<button>', {
                type: 'submit',
                class: 'btn-mpkl-submit mtugas-review-btn',
                html: '<i class="fas fa-paper-plane"></i> Kirim Revisi'
            })
        );

        $form.append($actions);

        return $form;
    }

    function buildActionLink(item) {
        if (!item.action_url) {
            return null;
        }

        var iconClass = item.action_label === 'Unduh' ? 'fa-download' : 'fa-eye';
        var $link = $('<a>', {
            href: item.action_url,
            class: 'mtugas-result-action-link',
            html: '<i class="fas ' + iconClass + '"></i> ' + (item.action_label || 'Lihat')
        });

        if (item.action_target) {
            $link.attr('target', item.action_target);
            $link.attr('rel', 'noopener noreferrer');
        }

        return $link;
    }

    function closeReviewForms(exceptId) {
        $('.mtugas-review-form').each(function () {
            var $form = $(this);
            var formId = parseInt($form.data('review-form'), 10);
            var isActive = exceptId && formId === exceptId;
            $form.toggleClass('is-open', !!isActive);
        });

        $('[data-review-toggle]').each(function () {
            var toggleId = parseInt($(this).attr('data-review-toggle'), 10);
            $(this).attr('aria-expanded', exceptId && toggleId === exceptId ? 'true' : 'false');
        });
    }

    function updateCommentMeta($card, komentar) {
        var $meta = $card.find('.mtugas-result-meta').first();
        if (!$meta.length) {
            return;
        }

        var $comment = $meta.find('.mtugas-result-comment').first();
        if (!$comment.length) {
            $comment = $meta.children('span').filter(function () {
                return $.trim($(this).text()).indexOf('Komentar:') === 0;
            }).first();

            if ($comment.length) {
                $comment.addClass('mtugas-result-comment');
            }
        }

        if (!komentar) {
            $comment.remove();
            return;
        }

        if (!$comment.length) {
            $comment = $('<span>', { class: 'mtugas-result-comment' });
            $meta.append($comment);
        }

        $comment.html('Komentar: ' + escapeHtmlWithBreaks(komentar));
    }

    function updateCardAfterReview($card, itemData) {
        var $badge = $card.find('.mtugas-result-side span[class*="badge-status"]').first();
        if ($badge.length) {
            $badge.attr('class', itemData.status_class || 'badge-status-menunggu');
            $badge.text(itemData.status_label || '-');
        }

        updateCommentMeta($card, itemData.komentar || '');
        $card.find('.mtugas-review-actions').remove();
        $card.find('.mtugas-review-form').remove();
    }

    function countPendingReviewCards() {
        return $('.mtugas-review-actions').length;
    }

    $('.mtugas-result-item').each(function (index) {
        var item = items[index] || {};

        var $card = $(this);
        var reviewOpen = item.id_item && parseInt(detailConfig.oldReviewItemId, 10) === parseInt(item.id_item, 10);
        var $strong = $card.find('.mtugas-result-head strong').first();

        $card.attr('data-item-id', item.id_item || '');

        if ($strong.length) {
            $strong.text(item.display_value || item.data_item || '-');
        }

        if (item.tipe_item === 'link') {
            $strong.find('a').each(function () {
                $(this).replaceWith(document.createTextNode($(this).text()));
            });
        }

        if (item.tipe_item === 'file' && $strong.length) {
            $strong.text(item.display_value || item.data_item || '-');
        }

        updateCommentMeta($card, item.komentar || '');

        var $top = $card.find('.mtugas-result-top').first();
        var $status = $top.children('span[class*="badge-status"]').first();
        if (!$status.length) {
            $status = $top.find('> span').first();
        }

        var $side = $top.find('.mtugas-result-side').first();
        if (!$side.length) {
            $side = $('<div>', { class: 'mtugas-result-side' });
            $top.append($side);
        }

        if ($status.length) {
            if (!$status.parent().is($side)) {
                $status.detach();
                $side.prepend($status);
            }

            var $actionLink = buildActionLink(item);
            if ($actionLink && !$side.find('.mtugas-result-action-link').length) {
                $side.append($actionLink);
            }
        } else {
            var $fallbackAction = buildActionLink(item);
            if ($fallbackAction && !$side.find('.mtugas-result-action-link').length) {
                $side.append($fallbackAction);
            }
        }

        if (item.status_raw === 'dikirim' && !$card.find('.mtugas-review-actions').length) {
            var $reviewActions = $('<div>', { class: 'mtugas-review-actions' });
            var $approveForm = buildApproveForm(item);
            var $revisionToggle = buildRevisionToggle(item, reviewOpen);

            if ($approveForm) {
                $reviewActions.append($approveForm);
            }

            if ($revisionToggle) {
                $reviewActions.append($revisionToggle);
            }

            if ($reviewActions.children().length) {
                $card.append($reviewActions);
            }
        }

        if (item.status_raw === 'dikirim' && !$card.find('.mtugas-review-form').length) {
            var $reviewForm = buildRevisionForm(item, reviewOpen);
            if ($reviewForm) {
                $card.append($reviewForm);
            }
        }
    });

    $(document).on('click', '[data-review-toggle]', function () {
        var itemId = parseInt($(this).attr('data-review-toggle'), 10);
        var $form = $('[data-review-form="' + itemId + '"]');
        var shouldOpen = !$form.hasClass('is-open');

        closeReviewForms(shouldOpen ? itemId : 0);

        if (shouldOpen) {
            $form.find('textarea[name="komentar"]').trigger('focus');
        }
    });

    $(document).on('click', '[data-review-cancel]', function () {
        closeReviewForms(0);
    });

    $(document).on('submit', '.mtugas-inline-form, .mtugas-review-form', function (event) {
        event.preventDefault();

        var $form = $(this);
        var itemId = parseInt($form.attr('data-item-id'), 10);
        var isRevisionForm = $form.hasClass('mtugas-review-form');
        var komentar = $form.find('textarea[name="komentar"]').val() || '';
        var normalizedKomentar = (window.SimmagValidation && window.SimmagValidation.normalizeMultilineValue)
            ? window.SimmagValidation.normalizeMultilineValue(komentar)
            : $.trim(komentar);

        if (isRevisionForm && normalizedKomentar === '') {
            showDetailAlert('Keterangan revisi wajib diisi.');
            return;
        }

        if (isRevisionForm && window.SimmagValidation && window.SimmagValidation.validateMultilinePatternField) {
            var commentError = window.SimmagValidation.validateMultilinePatternField(
                'Keterangan Revisi',
                komentar,
                10,
                255,
                /^[\p{L}\p{N}\s\p{P}\p{Sc}\p{Sk}]+$/u,
                'huruf, angka, spasi, tanda baca, dan baris baru'
            );
            if (commentError) {
                showDetailAlert(commentError);
                return;
            }
        }

        if (isRevisionForm) {
            $form.find('textarea[name="komentar"]').val(normalizedKomentar);
        }

        var $submitButtons = $form.find('button[type="submit"]');
        $submitButtons.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).done(function (response) {
            updateCsrfHash(response && response.csrfHash);

            var $card = $('.mtugas-result-item[data-item-id="' + itemId + '"]');
            if ($card.length && response && response.item) {
                updateCardAfterReview($card, response.item);
            }

            showDetailToast('success', response && response.message ? response.message : 'Review berhasil disimpan.');

            if (countPendingReviewCards() === 0) {
                window.setTimeout(function () {
                    window.location.reload();
                }, 900);
            }
        }).fail(function (xhr) {
            var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
            updateCsrfHash(response && response.csrfHash);
            showDetailAlert(response && response.message ? response.message : 'Gagal menyimpan review tugas.');
        }).always(function () {
            $submitButtons.prop('disabled', false);
        });
    });
});
