jQuery(document).ready( function() {
        jQuery(".lock_comment").change( function() {
            comment_id = jQuery(this).attr("data-comment_id")
       // nonce = jQuery(this).attr("data-nonce")
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : myAjax.ajaxurl,
            data : {action: "my_user_vote", comment_id : comment_id/*, nonce: nonce*/},
            success: function(response) {
                if(response.type == "success") {
                   // alert("все прошло хоррошо")
                    //jQuery("#vote_counter").html(response.vote_count)
                }
                else {
                    alert("Ошибка сохранения результата")
                }
            }
        })
    })
})




jQuery(document).ready( function() {
    jQuery(".delete_li_item").click( function() {
        comment_id = jQuery(this).attr("data-comment_id")
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : myAjax.ajaxurl,
            data : {action: "cp_delete", comment_id : comment_id},
            success: function(response) {
                if(response.type == "success") {
                    jQuery("#control_comment_id_"+comment_id).remove()
                }
                else {
                    alert("неудалось удалить")
                }
            }
        })
    })
})