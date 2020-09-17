//== Class definition
var Testpage = function() {

    function nouislider() {
        var sliders = [];
        var sliderValues = [];
        var sliderId;
        var inputId;
        var inputDefaultValue;
        var inputDefaultMax;
        var inputDefaultMin;
        var inputDefaultStep;
        $('.nouislider_item').each(function(i) {
            sliderId = $(this).attr('id');
            sliders[i] = document.getElementById(sliderId);
            inputId = $(this).closest('.align-items-center').find('.nouislider_input').attr('id');
            sliderValues[i] = document.getElementById(inputId);
            // Get default value - set it to 0 if not set
            inputDefaultValue = $(sliderValues[i]).val();
            inputDefaultValue = inputDefaultValue !== "" ? inputDefaultValue : 0;
            // Get default Max value - set it to 100 if not set
            inputDefaultMax = parseFloat($(sliderValues[i]).attr('max')); // nouislider getting value bug fix with parseFloat()
            inputDefaultMax = !isNaN(inputDefaultMax) ? inputDefaultMax : 100;
            // Get default Min value - set it to 0 if not set
            inputDefaultMin = parseFloat($(sliderValues[i]).attr('min')); // nouislider getting value bug fix with parseFloat()
            inputDefaultMin = !isNaN(inputDefaultMin) ? inputDefaultMin : 0;
            // Get default Step value - set it to 1 if not set
            inputDefaultStep = parseFloat($(sliderValues[i]).attr('step')); // nouislider getting value bug fix with parseFloat()
            inputDefaultStep = !isNaN(inputDefaultStep) ? inputDefaultStep : 1;

            noUiSlider.create(sliders[i], {
                start: [inputDefaultValue],
                step: inputDefaultStep,
                range: {
                    min: [inputDefaultMin],
                    max: [inputDefaultMax]
                },
                format: wNumb({
                    decimals: 0
                }),
                pips: {
                    mode: 'values',
                    values: Array.apply(null, {length: inputDefaultMax+inputDefaultStep}).map(Function.call, Number),
                    density: 100*inputDefaultStep/((inputDefaultMax-inputDefaultMin))
                  }
            });
            sliders[i].noUiSlider.on('update', function(e, t) {
                sliderValues[i].value = e[t];
            });
            sliderValues[i].addEventListener('change', function(e) {
                sliders[i].noUiSlider.set(this.value);
            })
        });
    }

    function portletSort() {
        // Sort by dragging
        $(".ui-sortable").sortable();

        // Sort up / down buttons logic
        var sortUpBtn = $('.js-sort-up'),
            sortDownBtn = $('.js-sort-down');

        sortUpBtn.on('click', function() {
            var portlet = $(this).closest('.m-portlet--sortable');
            portlet.prev().insertAfter(portlet);
        });

        sortDownBtn.on('click', function() {
            var portlet = $(this).closest('.m-portlet--sortable');
            portlet.next().insertBefore(portlet);
        });

        //checkbox logic
        var sortableCheck = $('.ui-unsortable input[type="checkbox"], .ui-sortable input[type="checkbox"]');
        // Initial check
        sortableCheck.each(function(i) {
            var portlet = $(this).closest('.m-portlet');
            var sortable = $(this).closest('.m-form__group').find('.ui-sortable');
            if ($(this).is(':checked')) {
                // Checked - make sortable
                portlet.appendTo(sortable);
            }
        });
        sortableCheck.on('change', function() {
            var portlet = $(this).closest('.m-portlet');
            var sortable = $(this).closest('.m-form__group').find('.ui-sortable');
            var unsortable = $(this).closest('.m-form__group').find('.ui-unsortable');
            if ($(this).is(':checked')) {
                // Checked - make sortable
                portlet.appendTo(sortable);
            } else {
                // Unchecked - make unsortable
                portlet.appendTo(unsortable);
            }
        });
    }

    function uniqueSelects() {
        $('.c-pair-row select').on('change', function () {
            var selectedValue = $(this).val();
            var pair = $(this).closest('.c-pair-row').find('select').not(this).find('option');
            pair.removeAttr('disabled');
            pair.each(function() {
                if ( $(this).val() === selectedValue ) {
                    $(this).attr('disabled', true);
                }
            });
        });
    }


    return {
        //== Init demos
        init: function() {
            // init scripts
            nouislider();
            portletSort();
            uniqueSelects();
        }
    };
}();

//== Class initialization on page load
jQuery(document).ready(function() {
    Testpage.init();
});
