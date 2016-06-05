(function($) {
    $(document).ready(function() {
        $('<tr class="form-field form-required"></tr>').append(
            $('<th scope="row">Site Category</th>')
        ).append(
            $('<td></td>').append(
                $('<select name="blog[new_blog_cat]" ><option value="0">Please Select<?php echo "ddd"; ?></option>
<?php
$user_def_cats = array('Subject', 'Person', 'Client');

foreach ($user_def_cats as $key => $val) {
	echo "<option value=\"" . $key + 1 . "\" >" . $cat . "</option>";
}
?></select>')
            ).append(
                $('<p>Blog Category (Allows creation of different blog listings.)</p>')
            )
        ).insertAfter('#wpbody-content table tr:eq(2)');
    });

})(jQuery);