<?php
defined( 'ABSPATH' ) || exit;

class siilitfamilytree{

    public function siilit_familytree_init(){
        wp_register_style('siilit', plugins_url('../styles/siilit.css', __FILE__));
        wp_enqueue_style('siilit');
        wp_enqueue_script('ajax-siilit', plugins_url( '/siilitfamilytree.js', __FILE__ ), array( 'jquery' ));
        wp_localize_script('ajax-siilit', 'siilit_ajax_obj', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                '_ajax_nonce'    => wp_create_nonce( 'siilit_nonce' ),
            )
        );
        

        global $wpdb;
        $hedgehogs = $wpdb->get_results("SELECT Nimi, Siilinro FROM {$wpdb->prefix}siilit");
        
        ?>
        <h1>Sukupuu </h1>
        <div id="treeinputs">
            <input name="action" value="find_siili_familytree" type="hidden">
            Siili, jonka sukupuu haetaan:
            <br></br>
            <div id="warnings" class="card-panel"></div>
            <input type="search" id="siili-search" name="siili-search" placeholder="Siilin nimi" list="hedgehog-list" required>
            <datalist id='hedgehog-list'>
                <?php foreach($hedgehogs as &$value){
                    echo "<option value='$value->Nimi'>$value->Siilinro</option>";
                };
                unset($value);?>
            </datalist>
            <br>
            <input type="submit" id="find-tree" name="find-tree" value= "Näytä sukupuu">
            <br>
            <div id="treecontainer">
        </div>

    <?php
    }
    
    public function siilit_find_family() {
        check_ajax_referer('siilit_nonce');
        global $wpdb;

        $name = $_POST['name'];

        $wanted = $wpdb->get_results($wpdb->prepare("SELECT Kasvattaja, Nimi, Siilinro FROM {$wpdb->prefix}siilit WHERE Nimi = '%s'", $name));
        $id = $wanted[0]->Siilinro;

        //Dad's side's IDs
        $dadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$id}'");
        $dadsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsid}'");
        $dadsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsid}'");

        $dadsDadsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsid}'");
        $dadsDadsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsid}'");
        $dadsMumsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsid}'");
        $dadsMumsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsid}'");

        $dadsDadsDadsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsDadsid}'");
        $dadsDadsDadsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsDadsid}'");
        $dadsDadsMumsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsMumsid}'");
        $dadsDadsMumsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsMumsid}'");
        $dadsMumsDadsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsDadsid}'");
        $dadsMumsDadsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsDadsid}'");
        $dadsMumsMumsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsMumsid}'");
        $dadsMumsMumsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsMumsid}'");

        //Dad's side's values
        $dad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsid}'");
        $dadsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsid}'");
        $dadsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsid}'");

        $dadsDadsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsDadsid}'");
        $dadsDadsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsMumsid}'");
        $dadsMumsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsDadsid}'");
        $dadsMumsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsMumsid}'");

        $dadsDadsDadsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsDadsDadsid}'");
        $dadsDadsDadsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsDadsMumsid}'");
        $dadsDadsMumsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsMumsDadsid}'");
        $dadsDadsMumsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsDadsMumsMumsid}'");
        $dadsMumsDadsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsDadsDadsid}'");
        $dadsMumsDadsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsDadsMumsid}'");
        $dadsMumsMumsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsMumsDadsid}'");
        $dadsMumsMumsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsMumsMumsMumsid}'");
        
        //Mum's side's IDs
        $mumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$id}'");
        $mumsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsid}'");
        $mumsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsid}'");

        $mumsDadsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsid}'");
        $mumsDadsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsid}'");
        $mumsMumsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsid}'");
        $mumsMumsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsid}'");

        $mumsDadsDadsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsDadsid}'");
        $mumsDadsDadsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsDadsid}'");
        $mumsDadsMumsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsMumsid}'");
        $mumsDadsMumsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsMumsid}'");
        $mumsMumsDadsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsDadsid}'");
        $mumsMumsDadsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsDadsid}'");
        $mumsMumsMumsDadsid = $wpdb->get_var("SELECT Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsMumsid}'");
        $mumsMumsMumsMumsid = $wpdb->get_var("SELECT Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsMumsid}'");

        //Mum's side's values
        $mum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsid}'");
        $mumsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsid}'");
        $mumsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsid}'");

        $mumsDadsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsDadsid}'");
        $mumsDadsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsDadsMumsid}'");
        $mumsMumsDad = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsDadsid}'");
        $mumsMumsMum = $wpdb->get_results("SELECT Kasvattaja, Nimi, Siilinro, Kuolinsyy, Emo, Isa FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsMumsMumsid}'");

        //Check Dad's side's values, replace values that weren't found from the db
        if($dad[0]->Nimi == null){
            $dad = $dadsid;
        }
        if($dadsDad[0]->Nimi == null){
            $dadsDad = $dadsDadsid;
        }
        if($dadsMum[0]->Nimi == null){
            $dadsMum = $dadsMumsid;
        }
        if($dadsDadsDad[0]->Nimi == null){
            $dadsDadsDad = $dadsDadsDadsid;
        }
        if($dadsDadsMum[0]->Nimi == null){
            $dadsDadsMum = $dadsDadsMumsid;
        }
        if($dadsMumsDad[0]->Nimi == null){
            $dadsMumsDad = $dadsMumsDadsid;
        }
        if($dadsMumsMum[0]->Nimi == null){
            $dadsMumsMum = $dadsMumsMumsid;
        }
        

        //check Mum's side's values, replace values that weren't found from the db
        if($mum[0]->Nimi == null){
            $mum = $mumsid;
        }
        if($mumsDad[0]->Nimi == null){
            $mumsDad = $mumsDadsid;
        }
        if($mumsMum[0]->Nimi == null){
            $mumsMum = $mumsMumsid;
        }
        if($mumsDadsDad[0]->Nimi == null){
            $mumsDadsDad = $mumsDadsDadsid;
        }
        if($mumsDadsMum[0]->Nimi == null){
            $mumsDadsMum = $mumsDadsMumsid;
        }
        if($mumsMumsDad[0]->Nimi == null){
            $mumsMumsDad = $mumsMumsDadsid;
        }
        if($mumsMumsMum[0]->Nimi == null){
            $mumsMumsMum = $mumsMumsMumsid;
        }
        
        $results = array('wanted' => $wanted, 'dad' => $dad, 'dadsDad' => $dadsDad, 'dadsMum' => $dadsMum, 'dadsDadsDad' => $dadsDadsDad, 
            'dadsDadsMum' => $dadsDadsMum, 'dadsMumsDad' => $dadsMumsDad, 'dadsMumsMum' => $dadsMumsMum, 'mum' => $mum, 'mumsDad' => $mumsDad, 'mumsMum' => $mumsMum, 'mumsDadsDad' => $mumsDadsDad, 
            'mumsDadsMum' => $mumsDadsMum, 'mumsMumsDad' => $mumsMumsDad, 'mumsMumsMum' => $mumsMumsMum);
        
        wp_send_json_success($results);
        wp_die();
    }
}





?>