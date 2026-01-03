jQuery(document).ready(function($) {
    'use strict';
    
    // آبجکت تنظیمات
    var HeadrixAdmin = {
        init: function() {
            this.bindEvents();
            this.initColorPickers();
            this.syncRangeInputs();
            this.updatePreview();
            this.initSpacing();
        },
        
        bindEvents: function() {
            // ذخیره فرم با AJAX
            $('#headrix-settings-form').on('submit', this.handleSubmit);
            
            // ریست سکشن
            $('.headrix-reset-section').on('click', this.resetSection);
            
            // همگام سازی range و input عددی
            $('.headrix-range').on('input change', this.syncRangeValue);
            $('.headrix-range-input').on('input change', this.syncRangeFromInput);
            
            // تغییرات تنظیمات برای پیش‌نمایش
            $('.headrix-settings-form input, .headrix-settings-form select, .headrix-settings-form textarea').on('change input', this.updatePreview);
            
            // toggle switches
            $('.headrix-toggle-checkbox').on('change', this.updatePreview);
            
            // اکسپورت تنظیمات
            $('#headrix-export-settings').on('click', this.exportSettings);
            
            // ایمپورت تنظیمات
            $('#headrix-import-file').on('change', this.handleFileSelect);
            $('#headrix-import-settings').on('click', this.importSettings);
            
            // دکمه پاک کردن کش
            $('#headrix_clear_cache').on('click', this.clearCache);
            
            // اطلاعات دیباگ
            $('.headrix-debug-info').on('click', this.showDebugInfo);
        },
        
        initColorPickers: function() {
            if (typeof $.fn.wpColorPicker === 'function') {
                $('.headrix-color-picker').wpColorPicker();
            }
        },
        
        syncRangeInputs: function() {
            $('.headrix-range').each(function() {
                var $range = $(this);
                var $input = $range.siblings('.headrix-range-value').find('.headrix-range-input');
                $input.val($range.val());
            });
        },
        
        syncRangeValue: function() {
            var $range = $(this);
            var $input = $range.siblings('.headrix-range-value').find('.headrix-range-input');
            $input.val($range.val());
            HeadrixAdmin.updatePreview();
        },
        
        syncRangeFromInput: function() {
            var $input = $(this);
            var $range = $input.closest('.headrix-range-value').siblings('.headrix-range');
            $range.val($input.val());
            HeadrixAdmin.updatePreview();
        },
        
        initSpacing: function() {
            $('.headrix-spacing-value').on('input', function() {
                var $container = $(this).closest('.headrix-spacing-container');
                var values = [];
                $container.find('.headrix-spacing-value').each(function() {
                    values.push($(this).val() + 'px');
                });
                $container.find('.headrix-spacing-hidden').val(values.join(' '));
                HeadrixAdmin.updatePreview();
            });
        },
        
        handleSubmit: function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('#submit');
            var $status = $('.headrix-save-status');
            
            // نمایش وضعیت ذخیره
            $status.text(headrixAdmin.strings.saving);
            $submitBtn.prop('disabled', true).addClass('button-disabled');
            
            // ارسال AJAX برای ذخیره سریع
            $.ajax({
                url: headrixAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'headrix_save_settings',
                    nonce: headrixAdmin.nonce,
                    form_data: $form.serialize()
                },
                success: function(response) {
                    if (response.success) {
                        $status.text(headrixAdmin.strings.saved).css('color', '#46b450');
                        
                        // رفرش پیش‌نمایش
                        HeadrixAdmin.updatePreview();
                    } else {
                        $status.text(headrixAdmin.strings.error).css('color', '#dc3232');
                    }
                },
                error: function() {
                    $status.text(headrixAdmin.strings.error).css('color', '#dc3232');
                },
                complete: function() {
                    setTimeout(function() {
                        $status.text('');
                        $submitBtn.prop('disabled', false).removeClass('button-disabled');
                    }, 2000);
                }
            });
        },
        
        resetSection: function() {
            if (confirm(headrixAdmin.strings.confirm_reset)) {
                var currentTab = window.location.search.match(/tab=([^&]+)/);
                currentTab = currentTab ? currentTab[1] : 'general';
                
                $.ajax({
                    url: headrixAdmin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'headrix_reset_section',
                        nonce: headrixAdmin.nonce,
                        section: currentTab
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            }
        },
        
        updatePreview: function() {
            // در اینجا می‌توانید پیش‌نمایش زنده را بروزرسانی کنید
            // فعلاً یک نمونه ساده:
            var $preview = $('.headrix-preview-header');
            var bgColor = $('.headrix-color-picker[name="headrix_bg_color"]').val() || '#ffffff';
            $preview.css('background-color', bgColor);
        },
        
        exportSettings: function() {
            $.ajax({
                url: headrixAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'headrix_export_settings',
                    nonce: headrixAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // دانلود فایل JSON
                        var dataStr = JSON.stringify(response.data);
                        var dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
                        
                        var exportFileDefaultName = 'headrix-settings-' + new Date().toISOString().split('T')[0] + '.json';
                        
                        var linkElement = document.createElement('a');
                        linkElement.setAttribute('href', dataUri);
                        linkElement.setAttribute('download', exportFileDefaultName);
                        linkElement.click();
                    }
                }
            });
        },
        
        handleFileSelect: function(e) {
            var file = e.target.files[0];
            if (file && file.type === 'application/json') {
                $('#headrix-import-settings').prop('disabled', false);
            } else {
                $('#headrix-import-settings').prop('disabled', true);
                alert('لطفا یک فایل JSON انتخاب کنید.');
            }
        },
        
        importSettings: function() {
            var fileInput = $('#headrix-import-file')[0];
            var file = fileInput.files[0];
            
            if (!file) {
                alert('لطفا یک فایل انتخاب کنید.');
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);
                    
                    if (confirm('آیا مطمئن هستید که می‌خواهید تنظیمات فعلی با تنظیمات فایل جایگزین شوند؟')) {
                        $.ajax({
                            url: headrixAdmin.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'headrix_import_settings',
                                nonce: headrixAdmin.nonce,
                                settings: settings
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('تنظیمات با موفقیت وارد شدند.');
                                    location.reload();
                                } else {
                                    alert('خطا در وارد کردن تنظیمات: ' + (response.data || 'خطای ناشناخته'));
                                }
                            },
                            error: function() {
                                alert('خطا در ارتباط با سرور.');
                            }
                        });
                    }
                } catch (error) {
                    alert('فایل نامعتبر است: ' + error.message);
                }
            };
            reader.readAsText(file);
        },
        
        clearCache: function() {
            $.ajax({
                url: headrixAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'headrix_clear_cache',
                    nonce: headrixAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('کش با موفقیت پاک شد.');
                    }
                }
            });
        },
        
        showDebugInfo: function(e) {
            e.preventDefault();
            $.ajax({
                url: headrixAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'headrix_get_debug_info',
                    nonce: headrixAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var debugInfo = JSON.stringify(response.data, null, 2);
                        var $modal = $('#headrix-debug-modal');
                        $modal.find('pre').text(debugInfo);
                        
                        // نمایش مدال
                        $modal.dialog({
                            title: 'Debug Information',
                            modal: true,
                            width: 600,
                            height: 400,
                            buttons: {
                                Close: function() {
                                    $(this).dialog('close');
                                }
                            }
                        });
                    }
                }
            });
        }
    };
    
    // راه‌اندازی
    HeadrixAdmin.init();
});