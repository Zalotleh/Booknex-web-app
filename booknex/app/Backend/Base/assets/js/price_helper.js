(function ($) {
    const $document = $(document);

    booknetic.priceAndNumberFormatter = {
        numberFormats: {
            '2': {decimalPoint: '.', thousandsSeparator: ','},
            '3': {decimalPoint: ',', thousandsSeparator: ' '},
            '4': {decimalPoint: ',', thousandsSeparator: '.'},
            '5': {decimalPoint: '.', thousandsSeparator: '’'},
            default: {decimalPoint: '.', thousandsSeparator: ' '}
        },

        formatNumber: function (price) {
            // Get the format or fallback to the default
            const {
                decimalPoint,
                thousandsSeparator
            } = booknetic.priceAndNumberFormatter.numberFormats[price_settings.price_number_format] || booknetic.priceAndNumberFormatter.numberFormats.default;

            price = this.floor(price);

            return this.phpNumberFormat(price, price_settings.price_number_of_decimals, decimalPoint, thousandsSeparator);
        },

        formatPrice: function (price, currency) {
            price = this.formatNumber(price);

            if (currency === false) return price;
            if (!currency) currency = this.getCurrencySymbol();

            const formats = {
                '2': () => `${currency} ${price}`,
                '3': () => `${price}${currency}`,
                '4': () => `${price} ${currency}`,
                default: () => `${currency}${price}`
            };

            return (formats[price_settings.currency_format] || formats.default)();
        },

        getCurrencySymbol: function () {
            return price_settings.currency_symbol || price_settings.currencies?.[price_settings.currency] || '$';
        },

        floor: function (num = 0, scale = price_settings.price_number_of_decimals) {
            const multiplier = Math.pow(10, scale);
            const flooredValue = Math.floor(num * multiplier) / multiplier;

            return this.phpNumberFormat(flooredValue, scale, '.', '');
        },

        phpNumberFormat: function (number, decimals = 0, dec_point = '.', thousands_sep = ',') {
            const n = isFinite(+number) ? +number : 0;
            const prec = Math.max(0, Math.abs(decimals));

            // Helper function to fix decimal rounding
            const toFixedFix = (num, precision) => {
                const multiplier = Math.pow(10, precision);
                return (Math.round(num * multiplier) / multiplier).toFixed(precision);
            };

            // Format the number with the specified precision
            let [integerPart, decimalPart] = toFixedFix(n, prec).split('.');

            // Add thousands separator to the integer part
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);

            // Ensure the decimal part has the correct length
            decimalPart = (decimalPart || '').padEnd(prec, '0');

            // Combine integer and decimal parts with the decimal point
            return prec ? `${integerPart}${dec_point}${decimalPart}` : integerPart;
        },

        initializeInput: function () {
            $document.on("input blur", `input[data-type='currency'], input[data-type='price']`, function () {
                const inputType = $(this).data("type");
                booknetic.priceAndNumberFormatter.formatInput($(this), inputType);
            });

            $document.on("focus", `input[data-type='currency'], input[data-type='price']`, function () {
                const input = $(this);
                const currentValue = input.val();
                // Remove everything except numbers and decimal point
                const format = booknetic.priceAndNumberFormatter.numberFormats[price_settings.price_number_format] || booknetic.priceAndNumberFormatter.numberFormats.default;
                const decimalPoint = format.decimalPoint;

                let rawValue = currentValue.replace(new RegExp(`[^0-9${decimalPoint}]`, 'g'), '');

                // Check if the value has only zero decimal places
                const parts = rawValue.split(decimalPoint);
                if (parts.length === 2) {
                    const decimalPart = parts[1];
                    // If all decimal places are zeros, remove them while typing
                    if (decimalPart.replace(/0/g, '') === '') {
                        rawValue = parts[0];
                    }
                }

                input.val(rawValue);
            });
        },

        formatInput: function (input, inputType) {
            let cursorPosition = input[0].selectionStart;
            let oldValue = input.val();
            let newValue = '';

            const format = booknetic.priceAndNumberFormatter.numberFormats[price_settings.price_number_format] || booknetic.priceAndNumberFormatter.numberFormats.default;
            const decimalPoint = format.decimalPoint;

            newValue = oldValue.replace(new RegExp(`[^0-9${decimalPoint}]`, 'g'), '');

            // Handle multiple decimal points - keep only the first one
            const parts = newValue.split(decimalPoint);
            if (parts.length > 2) {
                newValue = parts[0] + decimalPoint + parts.slice(1).join('');
            }

            // Limit decimal places
            if (newValue.includes(decimalPoint)) {
                const decimalPart = newValue.split(decimalPoint)[1];
                if (decimalPart.length > price_settings.price_number_of_decimals) {
                    newValue = newValue.slice(0, -(decimalPart.length - price_settings.price_number_of_decimals));
                }
            }

            if (oldValue !== newValue) {
                input.val(newValue);

                const lengthDifference = newValue.length - oldValue.length;
                cursorPosition += lengthDifference;
                input[0].setSelectionRange(cursorPosition, cursorPosition);
            }

            if (input.is(':focus') === false) {
                let numericValue = newValue;
                if (format.decimalPoint === ',') {
                    numericValue = newValue.replace(',', '.');
                }
                numericValue = parseFloat(numericValue) || 0;
                if (inputType === "currency") {
                    input.val(this.formatPrice(numericValue));
                } else {
                    input.val(this.formatNumber(numericValue));
                }
                input.data('value', numericValue);
            }
        },
    }

    $document.ready(() => booknetic.priceAndNumberFormatter.initializeInput())

})(jQuery);