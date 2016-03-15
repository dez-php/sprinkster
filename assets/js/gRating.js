$.fn.gRating = function(el, valueHolder) {
    el = el || 'a';
    valueHolder = valueHolder || '#store_rating';
    var activeElement;
    var holder = this;
    var elements = this.find(el);
    var hovering = false;

    this.find(el).click(function(){
        hovering = false;
        elements.removeClass('active');
        $(this).addClass('active');
        var val = $(this).data('rate');
        $(valueHolder).val(val);
    });
    this.find(el).hover(function() {
        hovering = true;
        activeElement = $(holder).find('a.active');
        activeElement.removeClass('active');
    }, function() {
        if(hovering) {
            activeElement.addClass('active');
        }
    });
}