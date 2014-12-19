<?php
/*
Plugin Name: CasePress. ToDo
*/
define('WP_DEBUG', true);

function add_todo_test_cp(){
    ob_start();

    $args = array(
        'order' => 'ASC',
        'meta_key' => 'cp_control',
        'meta_value' => 'yes',
    );


    if( $comments = get_comments( $args ) ){
        echo '<ul class="list-group" id="todo-comments">';
        foreach($comments as $comment){
           //echo '<pre>';print_r($comment);echo'</pre><br><hr>';
            $cp_control_done = get_comment_meta($comment->comment_ID , "cp_control_done", true);
            //$cp_control_done = ($cp_control_done == "") ? 0 : $cp_control_done;?>

               <li class="list-group-item" data-comment_id="<?php echo $comment->comment_ID?>" id="control_comment_id_<?php echo $comment->comment_ID?>">
                   <input type="checkbox" data-comment_id="<?php echo $comment->comment_ID?>" class="lock_comment" name="lock" <?php if ($cp_control_done == 'lock') echo 'checked';?>>
                   <?php echo $comment->comment_content;/* print_r($cp_control_done );*/?>

                   <br><input type="button" data-comment_id="<?php echo $comment->comment_ID?>" class="delete_li_item" value="Удалить">

               </li>
       <?php
        }
    }

    ?>
    </ul>

<div class="row well">
    <div class="col-md-1">1

            <input type="checkbox">

    </div>
    <div class="col-md-9">
    </div>
    <div class="col-md-2">

    <div class="btn-group">
  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    Action <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" role="menu">
    <li><a href="#">Action</a></li>
    <li><a href="#">Another action</a></li>
    <li><a href="#">Something else here</a></li>
    <li class="divider"></li>
    <li><a href="#">Separated link</a></li>
  </ul>
</div>

    </div>
</div>

<div class="input-group">
  <span class="input-group-addon">$</span>
  <div class="well form-control">...</div>

  <span class="input-group-addon">.00</span>
</div>


    <div class="input-group">
       <span class="input-group-addon">
        <input type="checkbox">
      </span>
      <div class="form-control">... some data </div>
      <div class="input-group-btn">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Action <span class="caret"></span></button>
        <ul class="dropdown-menu dropdown-menu-right" role="menu">
          <li><a href="#">Action</a></li>
          <li><a href="#">Another action</a></li>
          <li><a href="#">Something else here</a></li>
          <li class="divider"></li>
          <li><a href="#">Separated link</a></li>
        </ul>
      </div><!-- /btn-group -->
    </div><!-- /input-group -->


    <script>
        jQuery(function($){
            $("#todo-comments").sortable();
        })
    </script>
    <?php
    return ob_get_clean();
}


add_shortcode('todo_comments', 'add_todo_test_cp');
//подключения сортировки
add_action('wp_enqueue_scripts', 'load_ss');
function load_ss()
{
    global $post;
    if (has_shortcode($post->post_content, 'todo_comments') or is_single()) {
        wp_enqueue_style('todo', plugin_dir_url(__FILE__) . '/todo.css');
        wp_enqueue_script('jquery-sortable', plugin_dir_url(__FILE__) . '/jquery-sortable-min.js', array('jquery'));
    }
}

//добавляет чекбокс к форме комментария
add_action('comment_form', 'cp_control_checkbox');
function cp_control_checkbox() {
    echo '<p>На контроль
<input type="hidden" name="check" value="no">
<input type="checkbox" name="check" value="yes"></p>';
}

//добавляет мета поле, используемое для вывода коментов в шорткоде
add_action('comment_post','cp_control_check');
function cp_control_check($comment_id){
       if ($_POST['check']=='yes'){
        add_comment_meta($comment_id,'cp_control','yes');
    }
}
// показывает если стоит галочка
add_filter( 'get_comment_text', 'display_if_cp_control_yes' );
function display_if_cp_control_yes ($comment_id){
    $cp_control = get_comment_meta( get_comment_ID(), 'cp_control', true );
    if ($cp_control == 'yes'){
        return $comment_id . '<br>cp_control = yes';
    }
    else {
        return $comment_id;
    }
}

//обработчик ajax запроса
add_action("wp_ajax_my_user_vote", "my_cp_change");
add_action("wp_ajax_nopriv_my_user_vote", "my_cp_change");
function my_cp_change()
{

    /*
    // проверка nonce
    * if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
    exit("No naughty business please");
    }*/

    $cp_control_done = get_comment_meta($_REQUEST["comment_id"], "cp_control_done", true);
//если мета cp_control_done не объявлена создать не заблокированную
      if($cp_control_done === 'unlock') {
    $cp_control_done = 'lock';
    }
    elseif($cp_control_done === 'lock'){
        $cp_control_done = 'unlock';
    }
    else{
        $cp_control_done = 'lock';
    }
    $vote = update_comment_meta($_REQUEST["comment_id"], "cp_control_done", $cp_control_done);
  // $vote = update_metadata ('comment',$_REQUEST["post_id"],"cp_control_done", $cp_control_done);
    if ($vote === false) {
        $result['type'] = "error";
        $result['comment_id'] = $_REQUEST["comment_id"];
        $result['$cp_control_done_in_cicle'] = $cp_control_done;
        $result['cp_control_done'] = get_comment_meta($_REQUEST["comment_id"], "cp_control_done", true);

    } else {
        $result['type'] = "success";
        $result['comment_id'] = $_REQUEST["comment_id"];
        $result['$cp_control_done_in_cicle'] = $cp_control_done;
        $result['cp_control_done'] = get_comment_meta($_REQUEST["comment_id"], "cp_control_done", true);
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $result = json_encode($result);
        echo $result;
    } else {
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    }
    die();
}
//обработчик для неавторизированных юзеров
function my_must_login()
{
    echo "You must log in to vote";
    die();
}

//обработчик удаления cp_control
add_action("wp_ajax_cp_delete", "my_cp_delete");
add_action("wp_ajax_nopriv_cp_delete", "my_cp_delete");

function my_cp_delete(){
    $res = update_comment_meta($_REQUEST["comment_id"], "cp_control", 'no'); //меняем статус на no, сам комент остается
    if ($res === false) {
        $result['type'] = "error";
    } else {
        $result['type'] = "success";
        $result['comment_id'] = $_REQUEST["comment_id"];
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $result = json_encode($result);
        echo $result;
    } else {
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    }
    die();

}



//подключение скрипта ajax и my_check.js, обрабатывающего ajax
add_action( 'init', 'my_script_enqueuer' );
function my_script_enqueuer() {
    wp_register_script( "my_check", plugin_dir_url(__FILE__) . '/my_check.js', array('jquery') );
    wp_localize_script( 'my_check', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'my_check' );
}
?>
