(function($) {
    $(document).ready(function() {
       
       var  myOptions = '';

        user_def_cats.forEach(function(val,i,user_def_cats){
                myOptions += '<option value="'+ i  +'">' + val + '</option>';
        });

        $('<tr class="form-field form-required"></tr>').append(
            $('<th scope="row">Site Category</th>')
        ).append(
            $('<td></td>').append(
                $('<select name="blog[blog_cats]" >' + myOptions + '</select>')
            ).append(
                $('<p>Blog Category (Allows creation of different blog listings.)</p>')
            )
        ).insertAfter('#wpbody-content table tr:eq(2)');
    });
})(jQuery);