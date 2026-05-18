jQuery(document).ready(function($) {
    // Initialize Select2
    if ($.fn.select2) {
        $('.csf-select2').not('.csf-mobile-code').select2({
            width: '100%',
            placeholder: 'Select an option',
            allowClear: false,
            containerCssClass: 'csf-select2-container csf-select2-root',
            dropdownCssClass: 'csf-select2-dropdown'
        });

        $('.csf-mobile-code').select2({
            width: '100%',
            placeholder: 'Select an option',
            allowClear: false,
            containerCssClass: 'csf-select2-container csf-select2-root csf-select2-mobile',
            dropdownCssClass: 'csf-select2-dropdown csf-mobile-dropdown'
        });
    }

    function csfGetFormStorageKey($form) {
        var id = $form.data('id');
        if (!id) {
            return null;
        }
        return 'csf_form_' + id + '_session';
    }

    function csfRestoreFormSessionData($form) {
        if (!$form.attr('data-keep-session')) {
            return;
        }
        var key = csfGetFormStorageKey($form);
        if (!key) {
            return;
        }
        var raw = sessionStorage.getItem(key);
        if (!raw) {
            return;
        }
        var payload;
        try {
            payload = JSON.parse(raw);
        } catch (e) {
            return;
        }
        if (!payload || typeof payload !== 'object' || !payload.data) {
            return;
        }

        var maxAgeMs = 24 * 60 * 60 * 1000;
        var storedAt = payload.storedAt || 0;
        if (!storedAt || (Date.now() - storedAt) > maxAgeMs) {
            sessionStorage.removeItem(key);
            return;
        }

        var data = payload.data;

        $form.find('.csf-repeater').each(function() {
            var $rep = $(this);
            var baseName = $rep.data('name');
            if (!baseName) {
                return;
            }
            var keyName = baseName + '[]';
            if (!Object.prototype.hasOwnProperty.call(data, keyName)) {
                return;
            }
            var stored = data[keyName];
            var values = Array.isArray(stored) ? stored : [stored];
            var $rows = $rep.find('.csf-repeater-row');
            while ($rows.length < values.length) {
                var $lastRow = $rep.find('.csf-repeater-row').last();
                var $newRow = $lastRow.clone();
                $newRow.find('input').val('');
                $lastRow.after($newRow);
                $rows = $rep.find('.csf-repeater-row');
            }
        });

        Object.keys(data).forEach(function(name) {
            var value = data[name];
            var fields = $form.find('[name="' + name.replace(/"/g, '\\"') + '"]');
            if (!fields.length) {
                return;
            }
            fields.each(function(index) {
                var $field = $(this);
                if ($field.closest('.csf-honeypot').length) {
                    return;
                }
                var type = ($field.attr('type') || '').toLowerCase();
                if (type === 'checkbox' || type === 'radio') {
                    var values = Array.isArray(value) ? value : [value];
                    if (values.indexOf($field.val()) !== -1) {
                        $field.prop('checked', true);
                    } else if (type === 'checkbox') {
                        $field.prop('checked', false);
                    }
                } else if ($field.is('select')) {
                    if ($field.prop('multiple') && Array.isArray(value)) {
                        $field.val(value);
                    } else {
                        $field.val(Array.isArray(value) ? value[0] : value);
                    }
                } else if (type !== 'file' && type !== 'password') {
                    if (Array.isArray(value)) {
                        var v = value[index] !== undefined ? value[index] : value[0];
                        $field.val(v);
                    } else {
                        $field.val(value);
                    }
                }
            });
        });
        $form.find('.csf-select2').each(function() {
            if ($(this).data('select2')) {
                $(this).trigger('change.select2');
            }
        });

        $form.find('.csf-checkbox-group').each(function() {
            var $group = jQuery(this);
            $group.find('.csf-checkbox-group-item').each(function() {
                var $item = jQuery(this);
                var isChecked = $item.find('input[type="checkbox"]').is(':checked');
                $item.toggleClass('csf-selected', isChecked);
            });
        });
    }

    function csfSaveFormSessionData($form) {
        if (!$form.attr('data-keep-session')) {
            return;
        }
        var key = csfGetFormStorageKey($form);
        if (!key) {
            return;
        }
        var formData = new FormData($form[0]);
        var data = {};
        formData.forEach(function(value, name) {
            var $fields = $form.find('[name="' + name.replace(/"/g, '\\"') + '"]');
            var skip = false;
            $fields.each(function() {
                if ($(this).closest('.csf-honeypot').length) {
                    skip = true;
                    return false;
                }
            });
            if (skip) {
                return;
            }
            if (data[name] !== undefined) {
                if (!Array.isArray(data[name])) {
                    data[name] = [data[name]];
                }
                data[name].push(value);
            } else {
                data[name] = value;
            }
        });
        try {
            var payload = {
                data: data,
                storedAt: Date.now()
            };
            sessionStorage.setItem(key, JSON.stringify(payload));
        } catch (e) {
        }
    }

    function csfClearFormSessionData($form) {
        var key = csfGetFormStorageKey($form);
        if (!key) {
            return;
        }
        sessionStorage.removeItem(key);
    }

    function csfGetFieldCurrentValue($form, fieldName) {
        var $fields = $form.find('[name="' + fieldName.replace(/"/g, '\\"') + '"]');
        if (!$fields.length) {
            return null;
        }
        var $first = $fields.first();
        var type = ($first.attr('type') || '').toLowerCase();

        if ($first.is('select')) {
            return $first.val();
        }

        if (type === 'radio') {
            var $checked = $fields.filter(':checked');
            return $checked.length ? $checked.val() : null;
        }

        if (type === 'checkbox') {
            var values = [];
            $fields.filter(':checked').each(function() {
                values.push($(this).val());
            });
            return values;
        }

        return $first.val();
    }

    function csfEvaluateConditionalField($wrapper, $form) {
        var fieldName = $wrapper.data('csf-cond-field');
        if (!fieldName) {
            return;
        }
        var operator = $wrapper.data('csf-cond-operator') || 'equals';
        var expected = $wrapper.data('csf-cond-value');
        var current = csfGetFieldCurrentValue($form, fieldName);

        var match = false;

        if (Array.isArray(current)) {
            match = current.indexOf(expected) !== -1;
        } else {
            match = current === expected;
        }

        var shouldShow = operator === 'equals' ? match : !match;

        if (shouldShow) {
            $wrapper.removeClass('csf-conditional-hidden');
            $wrapper.find('[data-csf-was-required="1"]').each(function() {
                $(this).prop('required', true);
            });
            $wrapper.find('[data-csf-was-required]').removeAttr('data-csf-was-required');
        } else {
            $wrapper.addClass('csf-conditional-hidden');
            $wrapper.find('input[required], select[required], textarea[required]').each(function() {
                $(this).attr('data-csf-was-required', '1');
                $(this).prop('required', false);
            });
        }
    }

    function csfInitConditionalLogic($form) {
        var $conditionalFields = $form.find('.csf-field[data-csf-conditional="1"]');
        if (!$conditionalFields.length) {
            return;
        }

        $conditionalFields.each(function() {
            csfEvaluateConditionalField($(this), $form);
        });

        var watched = {};
        $conditionalFields.each(function() {
            var fieldName = $(this).data('csf-cond-field');
            if (fieldName && !watched[fieldName]) {
                watched[fieldName] = true;
                $form.on('change', '[name="' + String(fieldName).replace(/"/g, '\\"') + '"]', function() {
                    $conditionalFields.each(function() {
                        var $wrapper = $(this);
                        if ($wrapper.data('csf-cond-field') === fieldName) {
                            csfEvaluateConditionalField($wrapper, $form);
                        }
                    });
                });
            }
        });
    }

    // Repeater field: add/remove rows
    $(document).on('click', '.csf-repeater-add', function() {
        var $wrapper = $(this).closest('.csf-repeater');
        var $lastRow = $wrapper.find('.csf-repeater-row').last();
        var $newRow = $lastRow.clone();
        $newRow.find('input').val('');
        $wrapper.find('.csf-repeater-row').last().after($newRow);
    });

    $(document).on('click', '.csf-repeater-remove', function() {
        var $wrapper = $(this).closest('.csf-repeater');
        var $rows = $wrapper.find('.csf-repeater-row');
        if ($rows.length > 1) {
            $(this).closest('.csf-repeater-row').remove();
        } else {
            $rows.find('input').val('');
        }
    });

    // Make Date Field Clickable (opens picker)
    $(document).on('click', '.csf-date-input', function() {
        this.showPicker();
    });

    $(document).on('click', '.csf-checkbox-group-item', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $item = $(this);
        var $checkbox = $item.find('input[type="checkbox"]').first();
        if (!$checkbox.length) {
            return;
        }
        var currentlyChecked = $checkbox.prop('checked');
        $checkbox.prop('checked', !currentlyChecked).trigger('change');
    });

    $(document).on('change', '.csf-checkbox-group input[type="checkbox"]', function() {
        var $checkbox = $(this);
        var $group = $checkbox.closest('.csf-checkbox-group');
        var multiple = $group.data('csf-multiple');

        if (multiple === 0 || multiple === '0' || multiple === false) {
            if ($checkbox.is(':checked')) {
                $group.find('input[type="checkbox"]').not($checkbox).prop('checked', false);
            }
        }

        $group.find('.csf-checkbox-group-item').each(function() {
            var $item = jQuery(this);
            var isChecked = $item.find('input[type="checkbox"]').is(':checked');
            $item.toggleClass('csf-selected', isChecked);
        });
    });

    function csfInitGoogleCityAutocomplete() {
        if (!window.csf_vars || !csf_vars.google_places_enabled) {
            return;
        }
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            return;
        }

        $('.csf-city-google-input').each(function() {
            var input = this;
            if ($(input).data('csf-city-bound')) {
                return;
            }
            $(input).data('csf-city-bound', true);

            var options = {
                types: ['(cities)'],
                componentRestrictions: { country: 'in' }
            };

            var autocomplete = new google.maps.places.Autocomplete(input, options);

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place || !place.address_components) {
                    return;
                }

                var city = '';

                place.address_components.forEach(function(component) {
                    if (component.types.indexOf('locality') !== -1) {
                        city = component.long_name;
                    }
                });

                if (!city && place.name) {
                    city = place.name;
                }

                if (city) {
                    $(input).val(city);
                }
            });
        });
    }

    function csfInitGoogleAddressAutocomplete() {
        if (!window.csf_vars || !csf_vars.google_places_enabled) {
            return;
        }
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            return;
        }

        $('.csf-google-address').each(function() {
            var $wrapper = $(this);
            if ($wrapper.data('csf-google-address-bound')) {
                return;
            }
            $wrapper.data('csf-google-address-bound', true);

            var $line1 = $wrapper.find('.csf-google-address-line1');
            if (!$line1.length) {
                return;
            }

            var base = '';
            var $hidden = $wrapper.find('.csf-address-combined');
            if ($hidden.length) {
                base = $hidden.data('address-base') || '';
            }

            var input = $line1.get(0);
            if (!input) {
                return;
            }

            var autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['geocode']
            });

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place || !place.address_components) {
                    return;
                }

                var streetNumber = '';
                var route = '';
                var city = '';
                var state = '';
                var postalCode = '';
                var countryLong = '';
                var countryShort = '';

                place.address_components.forEach(function(component) {
                    var types = component.types || [];
                    if (types.indexOf('street_number') !== -1) {
                        streetNumber = component.long_name;
                    }
                    if (types.indexOf('route') !== -1) {
                        route = component.long_name;
                    }
                    if (types.indexOf('locality') !== -1) {
                        city = component.long_name;
                    }
                    if (types.indexOf('administrative_area_level_1') !== -1) {
                        state = component.long_name;
                    }
                    if (types.indexOf('postal_code') !== -1) {
                        postalCode = component.long_name;
                    }
                    if (types.indexOf('country') !== -1) {
                        countryLong = component.long_name;
                        countryShort = component.short_name;
                    }
                });

                var line1Value = '';
                if (streetNumber && route) {
                    line1Value = streetNumber + ' ' + route;
                } else if (route) {
                    line1Value = route;
                } else if (place.name) {
                    line1Value = place.name;
                }

                if (line1Value) {
                    $line1.val(line1Value);
                }

                if (base) {
                    if (city) {
                        $wrapper.find('[name="' + base + '_city"]').val(city);
                    }
                    if (state) {
                        $wrapper.find('[name="' + base + '_state"]').val(state);
                    }
                    if (postalCode) {
                        $wrapper.find('[name="' + base + '_postcode"]').val(postalCode);
                    }
                    if (countryShort) {
                        var $countrySelect = $wrapper.find('[name="' + base + '_country"]');
                        if ($countrySelect.length) {
                            $countrySelect.val(countryShort).trigger('change');
                        }
                    }
                }
            });
        });
    }

    function csfInitGoogleStateAutocomplete() {
        if (!window.csf_vars || !csf_vars.google_places_enabled) {
            return;
        }
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            return;
        }

        $('.csf-state-google-input').each(function() {
            var input = this;
            if ($(input).data('csf-state-bound')) {
                return;
            }
            $(input).data('csf-state-bound', true);

            var options = {
                types: ['(regions)'],
                componentRestrictions: { country: 'in' }
            };

            var autocomplete = new google.maps.places.Autocomplete(input, options);

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place || !place.address_components) {
                    return;
                }

                var state = '';

                place.address_components.forEach(function(component) {
                    if (component.types.indexOf('administrative_area_level_1') !== -1) {
                        state = component.long_name;
                    }
                });

                if (!state && place.name) {
                    state = place.name;
                }

                if (state) {
                    $(input).val(state);
                }
            });
        });
    }

    // --- JS-Based Multi-Step Fallback (Revised) ---
    $('.csf-form').each(function() {
        var $form = $(this);
        csfRestoreFormSessionData($form);
        csfInitConditionalLogic($form);
        csfInitGoogleCityAutocomplete();
        csfInitGoogleAddressAutocomplete();
        csfInitGoogleStateAutocomplete();
        $form.on('input change', 'input, select, textarea', function() {
            csfSaveFormSessionData($form);
        });
        var $markers = $form.find('.csf-step-marker');

        // Only run if markers exist but no server-side steps structure
        if ($markers.length > 0 && $form.find('.csf-steps-container').length === 0) {

            // 1. Identify the container holding the content
            // We assume the first marker's parent holds all the form content
            var $container = $markers.first().parent();
            
            // 2. Prepare new structure
            var $stepsWrapper = $('<div class="csf-steps-container" style="width:100%;"></div>');
            var $progressHeader = $('<div class="csf-progress-header"></div>');
            var $progressCount = $('<div class="csf-progress-count"></div>');
            var $progressTitle = $('<div class="csf-progress-title"></div>');
            var $progressBar = $('<div class="csf-progress-bar"></div>');

            // 3. Get all children to process
            // We clone the list of children so we don't mess up the live node list while iterating
            var $children = $container.children(); 
            
            var stepIndex = 1;
            var $currentStepDiv = $('<div class="csf-step active" data-step="1"></div>');
            var totalSteps = $markers.length + 1;

            var firstHeadingText = $.trim($container.find('.csf-heading').first().text());
            if (firstHeadingText) {
                $currentStepDiv.attr('data-title', firstHeadingText);
            }

            // Initialize Header + count
            $progressCount
                .attr('data-total', totalSteps)
                .append('<span class="csf-progress-current">1</span>')
                .append('<span class="csf-progress-separator">/</span>')
                .append('<span class="csf-progress-total">' + totalSteps + '</span>');

            $progressHeader.append($progressCount).append($progressTitle).append($progressBar);

            // Initialize Progress Bar
            $progressBar.append('<div class="csf-progress-step active" data-step="1"></div>');
            
            // 4. Iterate and group
            $children.each(function() {
                var $el = $(this);

                // Skip if it's our new wrappers (just in case)
                if ($el.is('.csf-steps-container') || $el.is('.csf-progress-bar')) return;

                if ($el.hasClass('csf-step-marker')) {
                    // END OF CURRENT STEP
                    // 1. Add nav buttons to current step
                    addNavButtons($currentStepDiv, stepIndex, totalSteps);
                    // 2. Append current step to wrapper
                    $stepsWrapper.append($currentStepDiv);

                    // START NEW STEP
                    stepIndex++;
                    var markerTitle = $.trim($el.data('title') || '');
                    $currentStepDiv = $('<div class="csf-step" data-step="' + stepIndex + '"></div>');
                    if (markerTitle) {
                        $currentStepDiv.attr('data-title', markerTitle);
                    }
                    $progressBar.append('<div class="csf-progress-step" data-step="' + stepIndex + '"></div>');

                    // Remove marker from DOM (we don't need it anymore)
                    $el.remove(); // Actually we are moving everything else, so we can just ignore/remove it.
                } else {
                    // Add element to current step
                    // .append() moves the element from its old spot to the new spot
                    $currentStepDiv.append($el);
                }
            });
            
            // Finish last step
            addNavButtons($currentStepDiv, stepIndex, totalSteps);
            $stepsWrapper.append($currentStepDiv);
            
            // 5. Inject new structure
            // We prepend header (count + bullets) and append steps wrapper to the container.
            // Since we moved all original children into $stepsWrapper, the container should be empty now (except for the wrappers we are adding).
            $container.prepend($progressHeader);
            $container.append($stepsWrapper);
            csfUpdateStepTitle($form, 0);
            
            function addNavButtons($step, idx, total) {
                var $nav = $('<div class="csf-step-nav"></div>');
                var $left = $('<div class="csf-step-nav-left"></div>');
                var $right = $('<div class="csf-step-nav-right"></div>');

                var $prevBtn = idx > 1 ? $('<button type="button" class="csf-prev-step">Previous</button>') : null;
                var $nextBtn = idx < total ? $('<button type="button" class="csf-next-step">Next</button>') : null;

                if ($prevBtn) {
                    $left.append($prevBtn);
                }

                if ($nextBtn) {
                    $right.append($nextBtn);
                } else {
                    var $submitWrapper = $step.find('.csf-submit-wrapper');
                    if ($submitWrapper.length > 0) {
                        $right.append($submitWrapper);
                    }
                }

                $nav.append($left).append($right);
                $step.append($nav);
            }
        }
    });

    function csfGetPasswordStrengthLevel(password) {
        var score = 0;
        if (password.length >= 8) {
            score++;
        }
        if (/[A-Z]/.test(password)) {
            score++;
        }
        if (/[a-z]/.test(password)) {
            score++;
        }
        if (/[0-9]/.test(password)) {
            score++;
        }
        if (/[^A-Za-z0-9]/.test(password)) {
            score++;
        }
        if (score <= 2) {
            return 'weak';
        }
        if (score === 3 || score === 4) {
            return 'medium';
        }
        return 'strong';
    }

    function csfInitPasswordStrength($form) {
        var formType = $form.data('form-type') || 'normal';
        if (formType !== 'register') {
            return;
        }
        $form.find('input[type="password"]').each(function() {
            var $input = $(this);
            if ($input.data('csf-password-bound')) {
                return;
            }
            $input.data('csf-password-bound', true);
            var $meter = $('<div class="csf-password-strength"></div>');
            $meter.insertAfter($input);
            $input.on('input', function() {
                var val = $input.val();
                if (!val) {
                    $meter.text('').attr('data-strength', '');
                    return;
                }
                var level = csfGetPasswordStrengthLevel(val);
                var label = '';
                if (level === 'weak') {
                    label = 'Weak';
                } else if (level === 'medium') {
                    label = 'Medium';
                } else {
                    label = 'Strong';
                }
                $meter.text('Password strength: ' + label).attr('data-strength', level);
            });
        });
    }

    function csfValidateScope($form, $scope, $msg) {
        var valid = true;
        var mobileError = false;
        var emailError = false;
        var passwordError = false;
        var formType = $form.data('form-type') || 'normal';

        $scope.find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                valid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        $scope.find('.csf-mobile-input').each(function() {
            var val = $(this).val();
            if (val) {
                var digits = val.replace(/\D/g, '');
                if (digits.length !== 10) {
                    valid = false;
                    mobileError = true;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            }
        });

        $scope.find('input[type="email"]').each(function() {
            var val = $.trim($(this).val());
            if (val) {
                var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!pattern.test(val)) {
                    valid = false;
                    emailError = true;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            }
        });

        if (formType === 'register') {
            $scope.find('input[type="password"]').each(function() {
                var val = $(this).val();
                if (val) {
                    var level = csfGetPasswordStrengthLevel(val);
                    if (level === 'weak') {
                        valid = false;
                        passwordError = true;
                        $(this).addClass('error');
                    } else {
                        $(this).removeClass('error');
                    }
                }
            });
        }

        if (!valid && $msg && $msg.length) {
            if (passwordError) {
                $msg.text('Please choose a stronger password.').addClass('error');
            } else if (mobileError) {
                $msg.text('Please enter a valid 10-digit mobile number.').addClass('error');
            } else if (emailError) {
                $msg.text('Please enter a valid email address.').addClass('error');
            } else {
                $msg.text('Please fill all required fields.').addClass('error');
            }
        }

        return valid;
    }

    $('.csf-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $msg = $form.find('.csf-response-message');
        $msg.removeClass('error success').text('');

        $form.find('.csf-address-combined').each(function() {
            var $hidden = $(this);
            var base = $hidden.data('address-base');
            if (!base) {
                return;
            }
            var parts = [];
            var line1 = $form.find('[name="' + base + '_line1"]').val();
            var line2 = $form.find('[name="' + base + '_line2"]').val();
            var city = $form.find('[name="' + base + '_city"]').val();
            var state = $form.find('[name="' + base + '_state"]').val();
            var postcode = $form.find('[name="' + base + '_postcode"]').val();
            var $countryField = $form.find('[name="' + base + '_country"]');
            var country = $countryField.length ? $countryField.find('option:selected').text() : '';
            if (line1) { parts.push(line1); }
            if (line2) { parts.push(line2); }
            if (city) { parts.push(city); }
            if (state) { parts.push(state); }
            if (postcode) { parts.push(postcode); }
            if (country) { parts.push(country); }
            $hidden.val(parts.join(', '));
        });

        $form.find('.csf-daterange-combined').each(function() {
            var $hidden = $(this);
            var base = $hidden.data('daterange-base');
            if (!base) {
                return;
            }
            var fromVal = $form.find('[name="' + base + '_from"]').val();
            var toVal = $form.find('[name="' + base + '_to"]').val();
            var combined = '';
            if (fromVal && toVal) {
                combined = fromVal + ' - ' + toVal;
            } else if (fromVal) {
                combined = fromVal;
            } else if (toVal) {
                combined = toVal;
            }
            $hidden.val(combined);
        });
        
        var valid = csfValidateScope($form, $form, $msg);
        if (!valid) {
            return;
        }

        var formData = new FormData(this);
        formData.append('nonce', csf_vars.nonce);

        $.ajax({
            url: csf_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    var $wrapper = $form.closest('.csf-form-wrapper');
                    var isConversational = $wrapper.hasClass('csf-template-conversational');
                    var redirectUrl = response.data && response.data.redirect_url ? response.data.redirect_url : null;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                        return;
                    }
                    csfClearFormSessionData($form);
                    $form[0].reset();
                    if (isConversational) {
                        var heading = response.data && response.data.thankyou_heading ? response.data.thankyou_heading : response.data.message;
                        var text = response.data && response.data.thankyou_message ? response.data.thankyou_message : '';
                        var useReferrer = response.data && response.data.thankyou_use_referrer ? true : false;
                        var $stepsContainer = $form.find('.csf-steps-container');
                        var $progressHeader = $form.find('.csf-progress-header');
                        $stepsContainer.hide();
                        $progressHeader.hide();
                        $msg.hide();
                        var $thank = $('<div class="csf-thankyou-screen"></div>');
                        if (heading) {
                            $('<h2 class="csf-thankyou-heading"></h2>').text(heading).appendTo($thank);
                        }
                        if (text) {
                            $('<p class="csf-thankyou-message"></p>').text(text).appendTo($thank);
                        }
                        var $actions = $('<div class="csf-thankyou-actions"></div>');
                        if (useReferrer && document.referrer && document.referrer !== window.location.href) {
                            var $backBtn = $('<button type="button" class="csf-thankyou-back"></button>').text('Back to previous page');
                            $backBtn.on('click', function() {
                                window.location.href = document.referrer;
                            });
                            $actions.append($backBtn);
                        }
                        if ($actions.children().length) {
                            $thank.append($actions);
                        }
                        $form.append($thank);
                    } else {
                        $msg.text(response.data.message).addClass('success');
                    }
                } else {
                    $msg.text(response.data.message).addClass('error');
                }
            },
            error: function() {
                $msg.text('An error occurred. Please try again.').addClass('error');
            }
        });
    });

    function csfUpdateStepCount($form, stepIndexZeroBased) {
        var $currentSpan = $form.find('.csf-progress-current');
        if (!$currentSpan.length) {
            return;
        }
        $currentSpan.text(stepIndexZeroBased + 1);
        var total = parseInt($currentSpan.closest('.csf-progress-count').data('total') || '0', 10);
        if (total > 0) {
            var ratio = (stepIndexZeroBased + 1) / total;
            var percent = Math.max(0, Math.min(100, Math.round(ratio * 100)));
            $form.find('.csf-progress-line-fill').css('width', percent + '%');
        }
    }

    function csfUpdateStepTitle($form, stepIndexZeroBased) {
        var $titleEl = $form.find('.csf-progress-title');
        if (!$titleEl.length) {
            return;
        }
        var $steps = $form.find('.csf-steps-container .csf-step');
        if (!$steps.length) {
            return;
        }
        var $step = $steps.eq(stepIndexZeroBased);
        if (!$step.length) {
            return;
        }
        var title = $step.data('title') || '';
        $titleEl.text(title);
    }

    function csfSwitchStep($form, $current, $target, direction) {
        if (!$target.length) {
            return;
        }
        var $progressBar = $form.find('.csf-progress-bar');
        var currentIndex = $current.index();
        var targetIndex = $target.index();
        $current.removeClass('active').hide();
        $target.addClass('active').show();
        if ($progressBar.length) {
            if (direction === 'next') {
                $progressBar.find('.csf-progress-step').eq(targetIndex).addClass('active');
                $progressBar.find('.csf-progress-step').eq(targetIndex - 1).addClass('completed');
            } else if (direction === 'prev') {
                $progressBar.find('.csf-progress-step').eq(currentIndex).removeClass('active');
                $progressBar.find('.csf-progress-step').eq(currentIndex - 1).removeClass('completed');
            }
        }
        if (direction === 'next') {
            csfUpdateStepCount($form, targetIndex);
            csfUpdateStepTitle($form, targetIndex);
        } else if (direction === 'prev') {
            var prevIndex = targetIndex;
            if (prevIndex >= 0) {
                csfUpdateStepCount($form, prevIndex);
                csfUpdateStepTitle($form, prevIndex);
            }
        }
        $target.removeClass('csf-anim-next csf-anim-prev');
        if (direction === 'next') {
            $target.addClass('csf-anim-next');
        } else if (direction === 'prev') {
            $target.addClass('csf-anim-prev');
        }
        setTimeout(function() {
            $target.removeClass('csf-anim-next csf-anim-prev');
        }, 400);
    }

    $('.csf-next-step').on('click', function() {
        var $current = $(this).closest('.csf-step');
        var $next = $current.next('.csf-step');
        var $form = $(this).closest('.csf-form');
        var $msg = $form.find('.csf-response-message');
        $msg.removeClass('error success').text('');
        
        var valid = csfValidateScope($form, $current, $msg);
        if (valid && $next.length) {
            csfSwitchStep($form, $current, $next, 'next');
        }
    });

    $('.csf-prev-step').on('click', function() {
        var $current = $(this).closest('.csf-step');
        var $prev = $current.prev('.csf-step');
        var $form = $(this).closest('.csf-form');
        
        if ($prev.length) {
            csfSwitchStep($form, $current, $prev, 'prev');
        }
    });

    $('.csf-form').each(function() {
        var $form = $(this);
        csfInitPasswordStrength($form);
        if ($form.find('.csf-steps-container').length) {
            csfUpdateStepTitle($form, 0);
        }
        var $wrapper = $form.closest('.csf-form-wrapper');
        if ($wrapper.hasClass('csf-template-conversational')) {
            var $currentSpan = $form.find('.csf-progress-current');
            if ($currentSpan.length) {
                csfUpdateStepCount($form, 0);
            }
            $form.on('change', '.csf-checkbox-group input[type="checkbox"]', function() {
                var $input = $(this);
                var $group = $input.closest('.csf-checkbox-group');
                var allowMultiple = $group.data('csf-multiple') === 1 || $group.data('csf-multiple') === '1';
                if (!allowMultiple) {
                    var $current = $form.find('.csf-step.active');
                    $current.find('.csf-next-step').first().trigger('click');
                }
            });
            var $arrows = $('<div class="csf-fixed-step-arrows"></div>');
            var $upBtn = $('<button type="button" class="csf-fixed-step-arrow-btn csf-fixed-step-arrow-up" aria-label="Previous question"><i class="fa fa-chevron-up" aria-hidden="true"></i></button>');
            var $downBtn = $('<button type="button" class="csf-fixed-step-arrow-btn csf-fixed-step-arrow-down" aria-label="Next question"><i class="fa fa-chevron-down" aria-hidden="true"></i></button>');
            $arrows.append($upBtn).append($downBtn);
            $('body').append($arrows);
            $upBtn.on('click', function() {
                var $currentStep = $form.find('.csf-step.active');
                $currentStep.find('.csf-prev-step').first().trigger('click');
            });
            $downBtn.on('click', function() {
                var $currentStep = $form.find('.csf-step.active');
                $currentStep.find('.csf-next-step').first().trigger('click');
            });
        }
        $form.on('keydown', function(e) {
            if (e.key !== 'Enter') {
                return;
            }
            var target = e.target || e.srcElement;
            if (!target) {
                return;
            }
            var tag = target.tagName ? target.tagName.toLowerCase() : '';
            if (tag === 'textarea') {
                return;
            }
            if ($(target).is('button') || $(target).is('input[type="submit"]')) {
                return;
            }
            var $stepsContainer = $form.find('.csf-steps-container');
            if (!$stepsContainer.length) {
                return;
            }
            var $currentStep = $form.find('.csf-step.active');
            if (!$currentStep.length) {
                return;
            }
            var $nextStep = $currentStep.next('.csf-step');
            if ($nextStep.length) {
                e.preventDefault();
                $currentStep.find('.csf-next-step').first().trigger('click');
            }
        });
    });
});
jQuery(document).ready(function($) {
    // Wait for meta box to be available (important for block editor)
    function initAudioUploader() {
        var uploadButton = $('.gp-upload-audio-button');
        
        if (uploadButton.length > 0 && !uploadButton.data('initialized')) {
            uploadButton.data('initialized', true);
            
            // Audio file upload
            uploadButton.click(function(e) {
                e.preventDefault();
                
                var audioUploader = wp.media({
                    title: 'Select Audio File',
                    button: {
                        text: 'Use this audio'
                    },
                    multiple: false,
                    library: {
                        type: 'audio'
                    }
                });
                
                audioUploader.on('select', function() {
                    var attachment = audioUploader.state().get('selection').first().toJSON();
                    $('#audio_file_url').val(attachment.url);
                });
                
                audioUploader.open();
            });
            
            // Validate audio URL
            $('#audio_file_url').on('blur', function() {
                var url = $(this).val();
                if (url && !isValidAudioUrl(url)) {
                    alert('Please enter a valid audio file URL (MP3, WAV, OGG, M4A, AAC)');
                    $(this).focus();
                }
            });
            
            function isValidAudioUrl(url) {
                if (!url) return true;
                var audioExtensions = ['.mp3', '.wav', '.ogg', '.m4a', '.aac', '.flac', '.webm'];
                return audioExtensions.some(ext => url.toLowerCase().endsWith(ext));
            }
        }
    }
    
    // Initial initialization
    initAudioUploader();
    
    // Re-initialize when DOM changes (for block editor)
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    initAudioUploader();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});
/**
 * Audio Upload Script - Fixed version with proper element targeting
 */

var gpFeaturedAudio = {};
(function($) {
    gpFeaturedAudio = {
        container: '',
        frame: '',
        settings: window.gpAudioOptions || {},

        init: function() {
            // If wp.media isn't loaded (frontend), don't try to create the media frame.
            // Still initialize preview behavior if any initial data is present.
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                // Only init preview (safe on frontend)
                if (typeof gpFeaturedAudio.initAudioPreview === 'function') {
                    gpFeaturedAudio.initAudioPreview();
                }
                return;
            }

            var postId = gpFeaturedAudio.settings.post_id || '';

            // Store element IDs for this specific post
            gpFeaturedAudio.elements = {
                audioUrlInput: '#gp_audio_file_url_' + postId,
                attachmentIdInput: '#gp_audio_attachment_id_' + postId,
                uploadButton: '#upload_audio_button_' + postId,
                removeButton: '#remove_audio_button_' + postId,
                previewContainer: '#audio_preview_container_' + postId
            };

            gpFeaturedAudio.container = $(gpFeaturedAudio.elements.audioUrlInput).closest('.inside');
            gpFeaturedAudio.initFrame();

            // Bind events using the specific element IDs
            $(gpFeaturedAudio.elements.uploadButton).on('click', gpFeaturedAudio.openAudioFrame);
            $(gpFeaturedAudio.elements.removeButton).on('click', gpFeaturedAudio.removeAudio);

            // Handle manual URL input
            $(gpFeaturedAudio.elements.audioUrlInput).on('input change', function() {
                var url = $(this).val().trim();
                gpFeaturedAudio.updatePreview(url);
            });

            gpFeaturedAudio.initAudioPreview();
        },

        /**
         * Open the featured audio media modal.
         */
        openAudioFrame: function(event) {
            event.preventDefault();
            if (!gpFeaturedAudio.frame) {
                gpFeaturedAudio.initFrame();
            }
            gpFeaturedAudio.frame.open();
        },

        /**
         * Create a media modal select frame, and store it so the instance can be reused when needed.
         */
        initFrame: function() {
            // guard: wp.media must exist
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                gpFeaturedAudio.frame = null;
                return;
            }

            gpFeaturedAudio.frame = wp.media({
                title: gpFeaturedAudio.settings.l10n ? gpFeaturedAudio.settings.l10n.featuredAudio : 'Featured Audio',
                button: {
                    text: gpFeaturedAudio.settings.l10n ? gpFeaturedAudio.settings.l10n.select : 'Select Audio'
                },
                library: {
                    type: 'audio'
                },
                multiple: false
            });

            // When a file is selected, run a callback.
            gpFeaturedAudio.frame.on('select', gpFeaturedAudio.selectAudio);
        },

        /**
         * Callback handler for when an attachment is selected in the media modal.
         * Gets the selected attachment information, and sets it within the control.
         */
        selectAudio: function() {
            // Get the attachment from the modal frame.
            var attachment = gpFeaturedAudio.frame.state().get('selection').first().toJSON();
            
            // Set the URL in the input field using the specific element ID
            $(gpFeaturedAudio.elements.audioUrlInput).val(attachment.url);
            $(gpFeaturedAudio.elements.attachmentIdInput).val(attachment.id);
            
            // Update preview
            gpFeaturedAudio.updatePreview(attachment.url);
            
            // Show remove button
            $(gpFeaturedAudio.elements.removeButton).show();
            if (gpFeaturedAudio.settings.l10n && gpFeaturedAudio.settings.l10n.change) {
                $(gpFeaturedAudio.elements.uploadButton).text(gpFeaturedAudio.settings.l10n.change);
            } else {
                $(gpFeaturedAudio.elements.uploadButton).text('Change Audio');
            }
        },

        /**
         * Update audio preview
         */
        updatePreview: function(url) {
            var previewContainer = $(gpFeaturedAudio.elements.previewContainer);
            
            if (url && gpFeaturedAudio.isValidAudioUrl(url)) {
                var previewHtml = '<p><strong>Preview:</strong></p>' +
                                 '<audio controls style="width:100%;">' +
                                 '<source src="' + url + '">' +
                                 'Your browser does not support the audio element.' +
                                 '</audio>';
                
                previewContainer.html(previewHtml).show();
                
                // Show remove button if not already shown
                if ($(gpFeaturedAudio.elements.removeButton).is(':hidden')) {
                    $(gpFeaturedAudio.elements.removeButton).show();
                }
            } else {
                previewContainer.hide().html('');
                if (!url) {
                    $(gpFeaturedAudio.elements.removeButton).hide();
                    if (gpFeaturedAudio.settings.l10n && gpFeaturedAudio.settings.l10n.select) {
                        $(gpFeaturedAudio.elements.uploadButton).text(gpFeaturedAudio.settings.l10n.select);
                    } else {
                        $(gpFeaturedAudio.elements.uploadButton).text('Select Audio from Media Library');
                    }
                }
            }
        },

        /**
         * Validate audio URL
         */
        isValidAudioUrl: function(url) {
            if (!url) return true;
            var audioExtensions = [".mp3", ".wav", ".ogg", ".m4a", ".aac", ".flac", ".webm"];
            return audioExtensions.some(function(ext) {
                return url.toLowerCase().endsWith(ext);
            });
        },

        /**
         * Remove the selected audio.
         */
        removeAudio: function() {
            $(gpFeaturedAudio.elements.audioUrlInput).val('');
            $(gpFeaturedAudio.elements.attachmentIdInput).val('');
            $(gpFeaturedAudio.elements.previewContainer).hide().html('');
            $(gpFeaturedAudio.elements.removeButton).hide();
            if (gpFeaturedAudio.settings.l10n && gpFeaturedAudio.settings.l10n.select) {
                $(gpFeaturedAudio.elements.uploadButton).text(gpFeaturedAudio.settings.l10n.select);
            } else {
                $(gpFeaturedAudio.elements.uploadButton).text('Select Audio from Media Library');
            }
        },

        /**
         * Initialize featured audio preview.
         */
        initAudioPreview: function() {
            var initialAttachment = gpFeaturedAudio.settings.initialAudioAttachment;
            if (initialAttachment && initialAttachment.url) {
                if (gpFeaturedAudio.settings.l10n && gpFeaturedAudio.settings.l10n.change) {
                    $(gpFeaturedAudio.elements.uploadButton).text(gpFeaturedAudio.settings.l10n.change);
                } else {
                    $(gpFeaturedAudio.elements.uploadButton).text('Change Audio');
                }
                $(gpFeaturedAudio.elements.removeButton).show();
            }
        }
    };

    $(document).ready(function() {
        // Only call init if admin media is available; preview-only on frontend
        try {
            gpFeaturedAudio.init();
        } catch (e) {
            // fail silently; ensure frontend doesn't throw
            if (typeof gpFeaturedAudio.initAudioPreview === 'function') {
                gpFeaturedAudio.initAudioPreview();
            }
            // optional: console.warn(e);
        }
    });

})(jQuery);



