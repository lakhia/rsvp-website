/* Common services */
app.service('sizeService', function() {
    // Map database size values to display values
    this.sizeToDisplay = function(size) {
        const sizeMap = {
            'XL': 'XL',
            'L': 'LG',
            'M': 'MD',
            'S': 'SM',
            'XS': 'XS'
        };
        return sizeMap[size] || size;
    };

    // Map display values back to database values
    this.displayToSize = function(display) {
        const displayMap = {
            'XL': 'XL',
            'LG': 'L',
            'MD': 'M',
            'SM': 'S',
            'XS': 'XS'
        };
        return displayMap[display] || display;
    };
});
