<?php
/*
Plugin Name:           CasePress. ToDo Comments
Plugin URI:            http://casepress.org
Description:           Плагин позволяет при добавлении комментария отметить его галочкой "На контроль". Отмеченные комментарии выводятся в виде списка шорткодом cp_todo_comments, порядок комментариев можно менять перетаскиванием элементов списка (порядок сохраняется). У каждого комментария есть кнопки "Редактировать", "Удалить".
Author:                CasePress
Author URI:            http://casepress.org
Author email:          s@casepress.org
GitHub Plugin URI:     https://github.com/systemo-biz/todo-comments-cp
GitHub Branch:         master
Version:               1.0.1
*/

add_shortcode('cp_todo_comments', 'add_todo_cp');
function add_todo_cp(){
    global $post;
    ob_start();
    $args = array(
        'post_id' => $post->ID,
        'meta_query' => array(
            array(
                'key' => 'cp_control',
                'value' => 'yes',
            ),
        ),
        'meta_key' => 'cp_control_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    );
    $comments_query = new WP_Comment_Query;
    $comments       = $comments_query->query( $args );?>
    <ul class="todo-comments" id="todo-comments">
        <?php foreach($comments as $comment){
            $cp_control_done = get_comment_meta($comment->comment_ID , "cp_control_done", true);
            $com_ID = $comment->comment_ID;
            $link = get_comment_link( $comment->comment_ID );
            $anchor = preg_split('/#/', $link);
            $anchor = '#'.$anchor[1]; //anchor link to comment
            ?>

            <li class="todo-comments-li" data-comment_id="<?php echo $com_ID?>" id="controlcommentid_<?php echo $com_ID?>">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-1">
                                <input type="checkbox" data-comment_id="<?php echo $com_ID?>" class="lock_comment" name="lock" <?php if ($cp_control_done == 'lock') echo 'checked';?>>
                                <div class="icon-move">
                                    <span class="glyphicon glyphicon-sort"></span>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <?php echo $comment->comment_content;?>
                            </div>
                            <div class="col-md-2">
                                <div class="hide_hover">
                                    <div class="dropdown">
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
                                            Действие
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
                                            <li role="presentation"><a role="menuitem" tabindex="-1" href="#" data-comment_id="<?php echo $com_ID?>" class="delete_li_item">Удалить</a></li>
                                            <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $anchor;?>">Перейти к комментарию </a></li>
                                            <?php if( current_user_can('moderate_comments') ):?>
                                                <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo get_edit_comment_link( $comment->comment_ID );?>">Редактировать</a></li>
                                            <?php endif;?>
                                        </ul>
                                    </div>
                                </div>
                            </div><!--end col-md-2-->
                        </div><!--end row-->
                    </div><!--end panel-body-->
                </div><!--end panel panel-default-->
            </li>
        <?php
        }
        ?>
    </ul>
    <script>
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        jQuery(document).ready(function($){
            //отправка ajax`ом порядка вывода коментов
            var group = jQuery("ul#todo-comments").sortable({
                placeholder: '<li class="placeholder" />',
                handle: 'div.icon-move',                                //дергалка за которую перетаскивать
                onDrop: function (item, container, _super) {
                    var serialize_data = group.sortable("serialize").get();
                    var data = {
                        serialize_data: serialize_data,
                        action: 'cp_control_order_change'
                    };
                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.type == "success") {
                           // console.log (response)
                        } else {
                            console.log("Ошибка")
                        }
                    });
                    _super(item, container)
                }
            });
            //удаление меты cp_control, запрос ajax
            jQuery(".delete_li_item").click( function(e) {
                e.preventDefault();
                comment_id = jQuery(this).attr("data-comment_id");
                var data = {
                    comment_id: comment_id,
                    action: 'cp_delete'
                };
                jQuery.post(ajaxurl, data, function(response) {
                    if (response.type == "success") {
                        jQuery("#controlcommentid_"+comment_id).remove()
                    } else {
                        console.log("Ошибка удаления")
                    }
                });
            });
            //изменение меты cp_control_done, отправка данных ajax
            jQuery(".lock_comment").change(function () {
                comment_id = jQuery(this) . attr("data-comment_id");
                var data = {
                    comment_id: comment_id,
                    action: 'cp_change'
                };
                jQuery.post(ajaxurl, data, function(response) {
                    if (response.type == "success") {
                        // console.log("все прошло хоррошо")
                    } else {
                        console.log("Ошибка сохранения результата")
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

//подключения js-плагина сортировки и css
add_action('wp_enqueue_scripts', 'load_jquery_sortable');
function load_jquery_sortable()
{
    global $post;
    if (!isset ($post)) return;
    if (has_shortcode($post->post_content, 'cp_todo_comments') or is_single() ) {
        wp_enqueue_style('todo', plugin_dir_url(__FILE__) . '/todo.css');
        wp_enqueue_script('jquery-sortable-johny', plugin_dir_url(__FILE__) . '/jquery-sortable-min.js', array('jquery'));
    }
}

//отключает jquery ui sortable, у плагина jquery-sortable by johny конфликт с ним
add_action( 'wp_print_scripts', 'de_script', 100 );
function de_script() {
    global $post;
    if (!isset ($post)) return;
    if (has_shortcode($post->post_content, 'cp_todo_comments') or is_single() ){
        wp_dequeue_script( 'jquery-ui-sortable' );
        wp_deregister_script( 'jquery-ui-sortable' );
    }
}

//добавляет чекбокс к форме комментария
add_action('comment_form', 'cp_control_checkbox');
function cp_control_checkbox() {
    ?>
    <p>
        <input type="hidden" name="check" value="no">
        <label for="check_for"><input type="checkbox" name="check" value="yes" id="check_for"> На контроль</label>
    </p>
<?php
}

//добавляет мета поля к форме комментирования
add_action('comment_post','cp_control_check');
function cp_control_check($comment_id){
    add_comment_meta($comment_id, 'cp_control_order', $comment_id); // мета поле для сортировки вывода коментов
    if ($_POST['check'] == 'yes'){
        add_comment_meta ($comment_id, 'cp_control' ,'yes' ); // если стоит checkbox "На контроль" - мета поле, используемое для вывода коментов в шорткоде
    }
}

//обработчик ajax изменения порядка
add_action("wp_ajax_cp_control_order_change", "cp_control_order_change");
add_action("wp_ajax_nopriv_cp_control_order_change", "cp_control_order_change");
function cp_control_order_change(){
    $order = $_POST['serialize_data'];
    $i = 0;
    foreach ($order[0] as $val){ //такая струтура из-за входящего массива данных
        foreach ($val as $value){
            update_comment_meta($value, "cp_control_order", $i);
            $i++;
        }
    }
    $result['type'] = "success";
    wp_send_json($result);
}

//обработчик ajax запроса
add_action("wp_ajax_cp_change", "cp_control_change");
add_action("wp_ajax_nopriv_cp_change", "cp_control_change");
function cp_control_change(){
    /*
    // проверка nonce
    * if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
    exit("No naughty business please");
    }*/

    $cp_control_done = get_comment_meta($_REQUEST["comment_id"], "cp_control_done", true);
    //если мета cp_control_done не объявлена создать не заблокированную

    if ($cp_control_done === 'unlock') {
        $cp_control_done = 'lock'; // смена типа
    }
    elseif ($cp_control_done === 'lock'){
        $cp_control_done = 'unlock'; // смена типа
    }
    else{
        $cp_control_done = 'lock'; // в первый раз
    }

    $res = update_comment_meta($_REQUEST["comment_id"], "cp_control_done", $cp_control_done);

    if ($res === false) {
        $result['type'] = "error";
    } else {
        $result['type'] = "success";
    }

    wp_send_json($result);
}

//обработчик для неавторизированных юзеров
function my_must_login(){
    echo "You must log in to vote";
    die();
}

//обработчик удаления cp_control
add_action("wp_ajax_cp_delete", "my_cp_delete");
add_action("wp_ajax_nopriv_cp_delete", "my_cp_delete");
function my_cp_delete(){
    $res = delete_comment_meta($_REQUEST["comment_id"], "cp_control");
    if ($res === false) {
        $result['type'] = "error";
    } else {
        $result['type'] = "success";
        $result['comment_id'] = $_REQUEST["comment_id"];
    }
    wp_send_json($result);
}