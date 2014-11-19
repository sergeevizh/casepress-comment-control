<?php
/*
Plugin Name: CasePress. ToDo
*/        

function add_todo_test_cp(){
ob_start();
?>

<ul class="list-group example-todo">
    <li class="list-group-item">
        <div class="item-content">Cras justo odio</div>
        <ul></ul>
    </li>
    <li class="list-group-item" data-id=234><div class="item-content">Dapibus ac facilisis in</div></li>
  <li class="list-group-item">Morbi leo risus</li>
  <li class="list-group-item">Porta ac consectetur ac</li>
  <li class="list-group-item">Vestibulum at eros</li>
</ul>
<input type="text" id="add-note" size="50">

<script>
    jQuery(document).ready(function($) {

                //вызов функции если нажали Enter
                $('#add-note').bind('keypress', function(e) 
                    {
                         if(e.keyCode==13)
                         {
                            add_note();
                         }
                    });

                //вызов функции если нажали ссылку добавления
                $("#add-note-action").click(function () {
                    add_note();
                });

                function add_note(){

                    note = $("#add-note").val();
                    timestamp = $.now();

                    tmpl_item = '<li class="list-group-item" data-id="'+ timestamp +'">';
                    //tmpl_item += '<div class="dd-handle">Drag</div>';
                    tmpl_item += '<div class="item-content">' + note + '</div>';
                    tmpl_item += '</li>';

                    $(".example-todo").append(tmpl_item);
                }
        })
                           
    jQuery(function($){
        $("ul.example-todo").sortable();
    })
        
</script>

<?php
return ob_get_clean();
}
add_shortcode('todo_comments', 'add_todo_test_cp');



add_action('wp_enqueue_scripts', 'load_ss');
function load_ss(){
    global $post;
    if( has_shortcode( $post->post_content, 'todo_test') or is_singular('cases') ) {
        wp_enqueue_style( 'todo', plugin_dir_url(__FILE__).'/todo.css' );

        wp_enqueue_script( 'jquery-sortable', plugin_dir_url(__FILE__).'/jquery-sortable-min.js' );

    }
}

