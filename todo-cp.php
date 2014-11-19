<?php
/*
Plugin Name: CasePress. ToDo
*/        

function add_todo_test_cp(){
ob_start();
?>
<ol class='example'>
  <li>First<ol></ol></li>
  <li>Second
    <ol>
      <li>First</li>
      <li>Second</li>
      <li>Third</li>
    </ol>
  </li>
  <li>Third
      <ol>
      <li>First</li>
      <li>Second</li>
      <li>Third</li>        
      </ol>
   </li>
</ol>
<script>

    jQuery(function($){
  $("ol.example").sortable();
})
        
</script>

<?php
return ob_get_clean();
}
add_shortcode('todo_test', 'add_todo_test_cp');



add_action('wp_enqueue_scripts', 'load_ss');
function load_ss(){
    global $post;
    if( has_shortcode( $post->post_content, 'todo_test') or is_singular('cases') ) {
            wp_enqueue_style( 'todo', plugin_dir_url(__FILE__).'/todo.css' );
            wp_enqueue_script( 'nestable', plugin_dir_url(__FILE__).'/jquery.nestable.js' );
            wp_enqueue_script( 'jquery-sortable', plugin_dir_url(__FILE__).'/jquery-sortable-min.js' );

    }
}


//add_action('cp_entry_content_after', 'add_todo_to_case_page');

function add_todo_to_case_page(){
global $post;

if(! is_singular('cases')) return;
    
?>
    <section class="cases-todo">
        <h1>Заметки</h1>
        <?php todo_gen(); ?>
    </section>
<?php
}

add_action('wp_ajax_note_chekbox', 'note_chekbox');

function note_chekbox() {

    $post_id = $_REQUEST['post_id'];
    
    $post = get_post($post_id);
    
   if($post->post_content == "1")
   {
      $post->post_content = "0";
   }
   else
   {
      $post->post_content = "1"; 
   }

    wp_update_post( $post );
    echo $post->post_content;
    die;
}

add_action('wp_ajax_rem_note', 'rem_note');

function rem_note() {

    $post_id = $_REQUEST['post_id'];

    wp_delete_post( $post_id );
    die;
}
                            
add_action('wp_ajax_order_notes', 'order_notes');

function order_notes() {
	$array_notes = $_REQUEST['array_notes'];
    $user_id = $_REQUEST['user_id'];
    $post_id = $_REQUEST['post_id'];

    $parent = $post_id;
    
    update_notes($array_notes, $parent);
    
    //echo "Юзер " . $user_id;
    //echo json_encode(array('new'=>$new,'timestamp'=>$timestamp));
    die;
}
function update_notes($array_notes, $parent) {
    $i=0;
    error_log("массив2: ".print_r($array_notes, true));
    
    foreach ($array_notes as $item) {
        $i++;

        
        $id = $item['id'];
        
        $note = get_post($id);
        
        
        if ($note->post_parent != $parent) {
            $note->post_parent = $parent;
            //wp_update_post( $note );
        }
        
        //error_log("проверка1: ".print_r($item, true).", соответствующий MO = ".$note->menu_order.", соответствущий Й = ".$i);
        //error_log("i=".$i.', mu = '.$note->menu_order.'*** ');
        $note->menu_order = $i;
        wp_update_post( $note );
        //error_log("проверка2: ".print_r($item, true).", соответствующий MO = ".$note->menu_order.", соответствущий Й = ".$i);
        
        //рекурсивный вызов функции, если есть потомки
        if (isset($item['children'])){
            update_notes($item['children'], $id);
        }
        
    }
            
        //print_r($item['children']);
        //echo "<br>";
}


add_action('wp_ajax_save_note', 'save_note');

function save_note() {

	$id = $_REQUEST['id'];
    $text = $_REQUEST['text'];
    
    $note = get_post($id);
        
        
    $note->post_title = $text;

    return wp_update_post( $note );
}

add_action('wp_ajax_add_note', 'add_note');

function add_note() {
	$array_notes = $_REQUEST['array_notes'];
	$note = $_REQUEST['note'];
    $user_id = $_REQUEST['user_id'];
    $parent = $_REQUEST['post_id'];
    $timestamp = $_REQUEST['timestamp'];
    
    
    
    // Создаем массив  
    $new_post = array(  
        'post_title' => $note,
        'post_author' => $user_id,
        'post_parent' => $parent,
        //'menu_order' => count($array_notes)+1,
        'post_type' => 'note',
        'post_status' => 'publish'
      );

    // Вставляем данные в БД  
    $new = wp_insert_post( $new_post ); 
    
    //error_log("количество в массиве: ".count($array_notes).", новый? ".$new);
    //echo "Родительский ИД: " . $post_id . ", " . $user_id . "< юзер, " . $note . "</br>";
    //echo "Штамп " . $timestamp;
    //echo "Новый ИД " . $new;
    echo json_encode(array('new'=>$new,'timestamp'=>$timestamp));
    die;
}

function note_render($title, $id) {
?>
    <li class="dd-item" data-id="<?php echo $id ?>">
        <div class="dd-handle">Drag</div>
        <div class="dd-content">
            <input type="checkbox" class="dd_checkbox" <?php checked( get_post_field('post_content', $id, 'attribute'), 1 ); ?> />
            <div class="dd-text" data-key="<?php echo $id ?>" contenteditable style="display:inline;">
                <?php echo get_the_title($id) ?>
            </div>
        </div>
        <div class="note-control" style="display: none">
            <a href="#del_note" id="del_note">Удалить</a>
        </div>
    <?php
    $childrens = get_children( 'post_parent='.$id.'&post_type=note&post_status=publish&orderby=menu_order&order=ASC' );
    if($childrens) {
        //echo "sdf";
    ?>
        <ol class="dd-list">
            <?php
                foreach ( $childrens as $children) {  
                    note_render($children->post_title, $children->ID);
                }
            ?>
        </ol>
    </li>
<?php
    }
}

add_shortcode('todo', 'todo_gen'); 
function todo_gen() {
?>
    <div class="cf nestable-lists">
        <input type="text" id="add-note" size="50"><br>
        <a href="#add" id="add-note-action">Добавить заметку</a>
        <menu id="nestable-menu">
            <a href="#expand-all" data-action="expand-all">Развернуть</a>
            <a href="#collapse-all" data-action="collapse-all">Свернуть</a>
        </menu>
        <div class="dd" id="notes">
            <ol class="dd-list">

                <?php
                    $notes = new WP_Query( 'post_type=note&post_parent=' . get_the_ID().'&orderby=menu_order&order=ASC' );
                    //print_r($notes);
                    // The Loop
                    while ( $notes->have_posts() ) {
                        $notes->the_post();
                        note_render(get_the_title(), get_the_ID());
                    }

                    /* Restore original Post Data 
                     * NB: Because we are using new WP_Query we aren't stomping on the 
                     * original $wp_query and it does not need to be reset.
                    */
                    wp_reset_postdata();
                ?>
            </ol>
        </div>
    </div>
    <script>
        (function($) {
            $('.dd-text').on("focus", function(e) {

                $(this).parent().next('.note-control').show();
            });

            $(".note-control").hover(
              function () {
                $(this).show();
              },
              function () {
                $(this).hide();
              }
            );

            // Code below this line is to simply reset the element with some text after clicking/tabbing away.
            $('.dd-text').on("blur", function(e) {
                //$(e.target).text("Hello")
                //$(this).parent().next('.note-control').hide();
                //console.log(222);
            
            });
            
            $('a#del_note').click(function(e) {
                id_del = $(this).parent().parent().attr('data-id');
                console.log(id_del);
                $.ajax({
                    data: {
                        post_id : id_del,
                        action: 'rem_note'
                    },
                    url: ajaxurl,
                    //dataType: 'json',
                    success: function(data) {
                        //alert(data);

                    }
                 });                            
                $(this).parent().parent().remove();
            });

            $('input.dd_checkbox').click(function(e) {
                id_check = $(this).parent().parent().attr('data-id');
                console.log(id_check);
                $.ajax({
                    data: {
                        post_id : id_check,
                        action: 'note_chekbox'
                    },
                    url: ajaxurl,
                    success: function(data) {
                        console.log(data);
                        if(data == 1) {$(this).attr('checked', 'checked')}
                        if(data == 0) {$(this).attr("checked",false)}

                    }
                 });
            });

        })(jQuery);
    </script>

    
    
    <script>
    	jQuery(function(){
			
			// execute once on the container
			jQuery("#notes").contentEditable().change(function(e){
				// what to do when the data has changed
				//console.log(e);
				//jQuery(".output .action").html(e.action);
				for(i in e.changed){
                    id = i;
				}
                if (e.action=='save') {
                    id = jQuery.trim(id);
                    text = jQuery.trim(e.changed[id]);
                    console.log(id +" = "+text);
                    
                    jQuery.ajax({
                        data: {
                            id : id,
                            text : text,
                            action: 'save_note'
                        },
                        url: ajaxurl,
                        success: function(data) {
                            console.log(data);
                        }
                     }); 
                }
			});
			
		});
    </script>
    
    <script>
        jQuery(document).ready(function($) {
            // activate Nestable for list
            $('#notes').nestable();

            $('#notes').on('change', function(){
                //alert(JSON.stringify($('#notes').nestable('serialize')));

                $.ajax({
                    data: {
                        array_notes: $('#notes').nestable('serialize'),
                        user_id: <?php echo get_current_user_id() ?>,
                        post_id : <?php the_ID(); ?>,
                        action: 'order_notes'
                    },
                    url: ajaxurl,
                    //dataType: 'json',
                    success: function(data) {
                        //alert(data);

                    }
                 });            
            });
        });
    </script>

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

                tmpl_item = '<li class="dd-item" data-id="'+ timestamp +'">';
                //tmpl_item += '<div class="dd-handle">Drag</div>';
                tmpl_item += '<div class="dd-content">' + note + '</div>';
                tmpl_item += '</li>';

                $("#notes > .dd-list").append(tmpl_item);

                array_notes = $('#notes').nestable('serialize');

                $.ajax({
                    data: {
                        array_notes: array_notes,
                        user_id: <?php echo get_current_user_id() ?>,
                        note: note,
                        post_id : <?php the_ID(); ?>,
                        timestamp : timestamp,
                        action: 'add_note'
                    },
                    url: ajaxurl,
                    dataType: 'json',
                    success: function(data) {
                        ts = data['timestamp'];

                        $('[data-id = '+ ts +']').attr("data-id", data['new']);

                        //alert($("[data-id = '17']").html());

                        //alert(data['new']);
                        //alert(ts);
                        //alert(data);


                        //alert($('[data-id = '+ ts +']').html());
                        //'data-id="18"'
                    }
                 });
            }
        });
    </script>

    <script>
        jQuery(document).ready(function($) {
            //сворачиваем и разворачиваем все пункты
            $('#nestable-menu').on('click', function(e)
            {
                var target = $(e.target),
                    action = target.data('action');
                if (action === 'expand-all') {
                    $('.dd').nestable('expandAll');
                }
                if (action === 'collapse-all') {
                    $('.dd').nestable('collapseAll');
                }
            });
        });
    </script>
<?php
}


// Hook into the 'init' action
//add_action( 'init', 'create_notes_post_type', 0 );
// Register Custom Post Type
function create_notes_post_type() {

	$labels = array(
		'name'                => _x( 'Notes', 'Post Type General Name', 'casepress' ),
		'singular_name'       => _x( 'Note', 'Post Type Singular Name', 'casepress' ),
		'menu_name'           => __( 'Note', 'casepress' ),
		'parent_item_colon'   => __( 'Parent', 'casepress' ),
		'all_items'           => __( 'All', 'casepress' ),
		'view_item'           => __( 'View', 'casepress' ),
		'add_new_item'        => __( 'Add New', 'casepress' ),
		'add_new'             => __( 'New', 'casepress' ),
		'edit_item'           => __( 'Edit', 'casepress' ),
		'update_item'         => __( 'Update', 'casepress' ),
		'search_items'        => __( 'Search', 'casepress' ),
		'not_found'           => __( 'No found', 'casepress' ),
		'not_found_in_trash'  => __( 'No found in Trash', 'casepress' ),
	);
	$args = array(
		'label'               => __( 'note', 'casepress' ),
		'description'         => __( 'Notes', 'casepress' ),
		'labels'              => $labels,
		'supports'            => array('title','editor', 'page-attributes'),
		'hierarchical'        => true,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 50,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
	);
	register_post_type( 'note', $args );

}
?>