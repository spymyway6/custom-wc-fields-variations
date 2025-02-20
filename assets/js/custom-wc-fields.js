jQuery(document).on('click', function (e) {
    const $form = jQuery('.cwcin-floating-edit-form');
    const $button = jQuery('.cwcin-edit-btn');
    if (
        !$form.is(e.target) && $form.has(e.target).length === 0 && 
        !$button.is(e.target) && $button.has(e.target).length === 0
    ) {
        jQuery(`.cwcin-floating-edit-form`).addClass('cwcin-d-none');
    }
});

function cwcinExecuteItemNotesHTML() {
    const result = [];

    jQuery('.cwcin-cart-item').each(function () {
        const $this = jQuery(this);

        // Get all class names of the current element
        const classNames = $this.attr('class') || '';
        // Extract cartKey and cartItemID using regex
        const cartKeyMatch = classNames.match(/key-([a-z0-9]+)/);
        const cartItemIDMatch = classNames.match(/cart-item-id-(\d+)/);
        var cartNotes = '';

        if ($this.find('.wc-block-components-product-details__item-notes').length > 0) {
            cartNotes = $this.find('.wc-block-components-product-details__item-notes .wc-block-components-product-details__value').text();
        }
        
        // Push an object with the extracted values into the result array
        result.push({
            cartKey: cartKeyMatch ? cartKeyMatch[1] : null,
            cartItemID: cartItemIDMatch ? cartItemIDMatch[1] : null,
            cartNotes: cartNotes
        });
    });
    
    if(result.length > 0){
        result.forEach((item, index) => {
            jQuery(`.cwcin-cart-item.key-${item.cartKey}.cart-item-id-${item.cartItemID} .wc-block-components-product-details__item-notes .wc-block-components-product-details__name`).html(`
                <div class="cwcin-edit-notes-floating-wrapper">
                    <b>Item Notes:</b>
                    <form method="POST" class="cwcin-floating-edit-form cwcin-d-none" id="cwcin-form-${item.cartKey}">
                        <label class="cwcin-notes-label" for="edit_item_notes">Edit Item Notes</label>
                        <input type="hidden" name="cwcin_cart_key" value="${item.cartKey}">
                        <input type="hidden" name="cwcin_cart_id" value="${item.cartItemID}">
                        <input type="hidden" name="action" value="cwcin_update_cart_item_notes">
                        <textarea class="cwcin-item-notes" name="edit_item_notes" rows="3" placeholder="Enter addtional notes for this item">${item.cartNotes}</textarea>
                        <span id="error-msg-${item.cartKey}"></span>
                        <div class="cwcin-float-btns">
                            <button type="button" class="cwcin-Cancel" onclick="showHideEditForm('hide', '${item.cartKey}')">Cancel</button>
                            <button type="button" class="cwcin-Save" onclick="updateItemNotes(this, '${item.cartKey}', '${item.cartItemID}')">Save</button>
                        </div>
                    </form>
                    <a href="javascript:;" class="cwcin-edit-btn" onclick="showHideEditForm('show', '${item.cartKey}')"><i class="dashicons dashicons-edit"></i></a>
                </div>
            `);
        });
        
    }
}

function showHideEditForm(type, cartKey){
    jQuery(`#error-msg-${cartKey}`).html('');
    if(type==='show'){
        jQuery(`#cwcin-form-${cartKey}`).removeClass('cwcin-d-none');
    }else{
        jQuery(`#cwcin-form-${cartKey}`).addClass('cwcin-d-none');
    }
}

function updateItemNotes(e, cartKey, cartItemID){
    jQuery(e).html('Saving...').attr('disabled', true);
    jQuery(`#error-msg-${cartKey}`).html('');

    jQuery.ajax({
        type: 'POST',
        url: '/wp-admin/admin-ajax.php',
        data: jQuery(`#cwcin-form-${cartKey}`).serialize(),
        success:(res)=>{
            console.log(res);
            if(res.success === true){
                jQuery(`#error-msg-${cartKey}`).html('');
                showHideEditForm('hide', cartKey);
                jQuery(`.cwcin-cart-item.key-${cartKey}.cart-item-id-${cartItemID} .wc-block-components-product-details__item-notes span.wc-block-components-product-details__value`).html(jQuery(`#cwcin-form-${cartKey} .cwcin-item-notes`).val())
            }else{
                jQuery(`#error-msg-${cartKey}`).html(`<small class="cust-err-msg">${res.data.message}</small>`);
            }
            jQuery(e).html(`Save`).removeAttr('disabled');
        },
        error:(res)=>{
            console.log(res);
        }
    });
}

// Only runs on the Cart page and not on the sidebar cart
// if (window.wc && window.wc.blocksCheckout) {
if (window.location.pathname.includes('/cart') || window.location.pathname.includes('/checkout')) {
    const { registerCheckoutFilters } = window.wc.blocksCheckout;
    const modifyCartItemClass = ( defaultValue, extensions, args ) => {
        const isCartContext = args?.context === 'cart';
        if ( ! isCartContext ) {
            return defaultValue;
        }
    
        setTimeout(()=>{
            cwcinExecuteItemNotesHTML(args);
        }, 1000);
    
        return `cwcin-cart-item key-${args.cartItem.key} cart-item-id-${args.cartItem.id}`;
    };
    
    registerCheckoutFilters( 'example-extension', {
        cartItemClass: modifyCartItemClass,
    });
} else {
    console.warn('WooCommerce Blocks not initialized on this page.');
}