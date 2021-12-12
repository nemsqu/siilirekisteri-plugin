<?php
/**
 * Plugin Name: Siilirekisteri toiminnot
 * Description: For Siilirekisteri. To be used with WP Data Access or similar.
 * Version: 1.0
 * Author: Nelli Kemi
 */

 defined( 'ABSPATH' ) || exit;

 define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__)); 
 require_once MY_PLUGIN_PATH . 'familytree/siilitfamilytree.php' ;

 register_activation_hook( __FILE__, 'siilit_init_plugin' );
 add_action('admin_menu', 'siilit_setup_menu');
 add_action('admin_enqueue_scripts', 'siilit_set_scripts');
 add_action( 'wp_ajax_ajax_siilit', ['siilitfamilytree', 'siilit_find_family'] );


 function siilit_set_scripts($hook){
    if('toplevel_page_siilit-actions' === $hook){
        wp_register_style('siilit', plugins_url('/styles/siilit.css', __FILE__));
        wp_enqueue_style('siilit');
    }
 }

 function siilit_setup_menu(){
     add_menu_page('siilit-actions', 'Siilirekisteri toiminnot', 'manage_options', 'siilit-actions', 'siilit_init');
     add_submenu_page('siilit-actions', 'Lisää siili', 'Lisää siili', 'manage_options', 'siilit-actions', 'siilit_init');
     add_submenu_page('siilit-actions', 'Sukupuu', 'Sukupuu', 'manage_options', 'siilit-familytree', 'siilit_familytree_init');
 }

 function siilit_familytree_init(){
     $familytree = new Siilitfamilytree();
     $familytree->siilit_familytree_init();
 }

 function siilit_init($name = null, $breeder = null, $bday = null, $gender = null, $father = null, $mother = null, $dead = null, $ilnesses = null){
    global $wpdb;
    $breeders = $wpdb->get_col("SELECT Nimi FROM {$wpdb->prefix}siilit_kasvattajat");
    $hedgehogs = $wpdb->get_results("SELECT Nimi, Siilinro FROM {$wpdb->prefix}siilit");
    
?>
    <h1>Lisää uusi siili</h1>
    <div id='inputcontainer'>
        <form method='post' action = "<?php admin_url( 'admin-post.php')?>" >
            <?php wp_nonce_field('add_siili','siili-was-added'); ?>
            <input name='action' value='add_siili' type='hidden'>
            Nimi: <input type='text' id='name' name='name' required><br>
            Kasvattaja: <input type='text' id='select_breeder' list='breeder-list' name='breeder'><br>
            <datalist id='breeder-list'>
                <?php foreach($breeders as &$value){
                    echo "<option value='$value'>$value</option>";
                };
                unset($value);?>
            </datalist>
            Omistaja: <input type='text' id='owner' name='owner'><br>
            Syntymäpäivä: <input type='date' id='bday' name='bday' required><br>
            Sukupuoli: <select id='gender' name='gender'>
                <option value='male'>Uros</option>
                <option value='female'>Naaras</option>
            </select><br>
            Isä: <input type='text' id='father_id' placeholder='Isän ID' name='father' list='hedgehog-list'><br>
            <datalist id='hedgehog-list'>
                <?php foreach($hedgehogs as &$value){
                    echo "<option value='$value->Siilinro'>$value->Nimi</option>";
                };
                unset($value);?>
            </datalist>
            Emo: <input type='text' id='mother_id' placeholder='Emon ID' name='mother' list='hedgehog-list'><br>
            <input type='submit' id='siili-submit'>
        </form>
    </div>
    <script>
        const submitButton = document.getElementById("siili-submit");

        submitButton.addEventListener("click", function (){
            let warnings = document.getElementsByTagName("h3");
            while(warnings[0]){
                warnings[0].parentNode.removeChild(warnings[0]);
            }
            if(document.getElementById("result")){
                document.getElementById("result").parentNode.removeChild(document.getElementById("result"));
            }
            warnings = document.getElementsByClassName("warning");
            while(warnings[0]){
                warnings[0].parentNode.removeChild(warnings[0]);
            }
        })
    </script>
<?php

    //Handling the form
    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        siilit_add_new();  
    }
}

function siilit_add_new() {
    //verifying source
    if(!isset($_POST['siili-was-added']) && wp_verify_nonce($_POST['siili-was-added'], 'add_siili')){
        echo "Varmennus epäonnistui. Yritä uudestaan.";
        return;
    }
    $name = $breeder = $owner =  $bday = $gender = $father = $mother = "";

    $name = siilit_check_input($_POST['name']);
    $breeder = siilit_check_input($_POST['breeder']);
    $owner = siilit_check_input($_POST['owner']);
    $bday = $_POST['bday'];
    $gender = siilit_get_gender($_POST['gender']);
    $father = siilit_check_input($_POST['father']);
    $mother = siilit_check_input($_POST['mother']);

    $year = $bdayarray = $id = $month = $dbbreeder = "";
    $bdayarray = explode('-', $bday);
    $year = $bdayarray[0];

    global $wpdb;

    //Checking mother hedgehog's rest period
    if($mother){
        siilit_check_rest_period($mother, $bday);    
    }
    
    //create hedgehog's ID
    $amount = $wpdb->get_var("SELECT Siilit FROM {$wpdb->prefix}siilit_vuosittain WHERE Vuosi = $year");
    
    if($amount == 0){
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}siilit_vuosittain (Vuosi, Siilit)
                VALUES (%d, %d)", array($year, 1)
            )
        );
    } else {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}siilit_vuosittain SET Siilit = %d WHERE Vuosi = %d", $amount + 1, $year
            )
        );
    }
    
    $month = $bdayarray[1];
    $year = substr($year, -2);
    $id = 'SY' . $year . $month . '-' . sprintf("%03d", $amount+1) . $gender;


    //Add new breeders to database
    $dbbreeder = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}siilit_kasvattajat WHERE Nimi = '$breeder'");

    if($dbbreeder == NULL){
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}siilit_kasvattajat (ID, Nimi)
                VALUES (NULL, %s)", $breeder
            )
        );
    }

    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}siilit (Siilinro, Nimi, Kasvattaja, Isa, Emo, Syntyma_aika, Kuollut, Kuolinsyy, Omistaja)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %d, %s)", $id, $name, $breeder, $father, $mother, $bday, 0, '', $owner
        )
    );

    $successCheck = $wpdb->get_var("SELECT Nimi FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$id}'");
    if(!$successCheck){
        siilit_handle_failure();
        return;
    }

    //checking ilnesses and other possible added info
    $familyIlnesses = siilit_check_family_ilnesses($id);
    if($familyIlnesses){
        ?>
        <div>
            <h3 class='warning'> Suvusta löytyy merkintöjä:</h3>
            <br>
                <?php 
                $i = 0;
                foreach($familyIlnesses as &$value){
                    if ($i % 2 == 0){
                        echo "<p class='warning'>" . $value;
                    } else {
                        echo ": " . $value . "</p>";
                    }
                    $i++;
                };
                unset($value); ?> </h3>
        </div>
         <?php
    }

    //check history for close relatives
    siilit_check_family_history($mother, $father);

    siilit_handle_success($id);
 }

 function siilit_check_rest_period($mother, $newestBday){
     global $wpdb;

     $lastLitter = $wpdb->get_var($wpdb->prepare(
        "SELECT PentueSynt FROM {$wpdb->prefix}siilit_emot WHERE Siilinro = '%s'", $mother)
     );

     if($lastLitter != null){
        $lastLitter = strtotime($lastLitter);

        if($lastLitter < strtotime($newestBday)){
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}siilit_emot SET PentueSynt = %s WHERE Siilinro = %s", $newestBday, $mother
                )
            );

            $restPeriodStart = strtotime("+8 weeks", $lastLitter);

            //get actual rest period in days
            $restPeriod = (strtotime($newestBday) - $restPeriodStart)/60/60/24;
            if($restPeriod < 60){
                echo "<h3 class='warning'>Siilin " . $mother . " lepoaika oli " . $restPeriod . " päivää liian lyhyt.</h3><br>";
            }
        }
    } else {
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}siilit_emot (Siilinro, PentueSynt)
                VALUES (%s, %s)", array($mother, $newestBday)
            )
        );
    }
 }

 function siilit_check_family_history($mother, $father){

    global $wpdb;

    $dadsdad = $wpdb->get_results($wpdb->prepare("SELECT Isa, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $father));
    $mumsdad = $wpdb->get_results($wpdb->prepare("SELECT Isa, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $mother));
    $dadsmum = $wpdb->get_results($wpdb->prepare("SELECT Emo, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $father));
    $mumsmum = $wpdb->get_results($wpdb->prepare("SELECT Emo, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $mother));

    $dadsdadsdad = $wpdb->get_results("SELECT Isa, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsdad[0]->Siilinro}'");
    $dadsdadsmum = $wpdb->get_results("SELECT Emo, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsdad[0]->Siilinro}'");
    $dadsmumsdad = $wpdb->get_results("SELECT Isa, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsmum[0]->Siilinro}'");
    $dadsmumsmum = $wpdb->get_results("SELECT Emo, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$dadsmum[0]->Siilinro}'");
    $mumsdadsdad = $wpdb->get_results("SELECT Isa, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsdad[0]->Siilinro}'");
    $mumsdadsmum = $wpdb->get_results("SELECT Emo, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsdad[0]->Siilinro}'");
    $mumsmumsdad = $wpdb->get_results("SELECT Isa, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsmum[0]->Siilinro}'");
    $mumsmumsmum = $wpdb->get_results("SELECT Emo, Siilinro FROM {$wpdb->prefix}siilit WHERE Siilinro = '{$mumsmum[0]->Siilinro}'");


    $siblings = $fatherdaughter = $motherson = $familyWarning = false;

    //parents: father-daughter ?
    if(strcmp($mumsdad[0]->Isa, $father) == 0 && $mumsdad[0]->Isa && $father){
        $fatherdaughter = true;
    }

    //parents: mother-son ?
    if(strcmp($dadsmum[0]->Emo, $mother) == 0 && $dadsmum[0]->Emo && $mother){
        $motherson = true;
    }

    //parents have one or two same parent(s)
    if(strcmp($dadsdad[0]->Isa, $mumsdad[0]->Isa) == 0 && $dadsdad[0]->Isa && $mumsdad[0]->Isa){
        $siblings = true;
    }else if(strcmp($dadsmum[0]->Emo, $mumsmum[0]->Emo) == 0 && $dadsmum[0]->Emo && $mumsmum[0]->Emo){
        $siblings = true;
    }

    //parents' parents and their parents' parents
    if(strcmp($dadsdadsdad[0]->Isa, $mumsdad[0]->Isa) == 0 && $dadsdadsdad[0]->Isa && $mumsdad[0]->Isa){
        $familyWarning = true;
    } else if(strcmp($dadsdadsmum[0]->Emo, $mumsmum[0]->Emo) == 0 && $dadsdadsmum[0]->Emo && $mumsmum[0]->Emo){
        $familyWarning = true;
    } else if(strcmp($dadsmumsdad[0]->Isa, $mumsdad[0]->Isa) == 0 && $dadsmumsdad[0]->Isa && $mumsdad[0]->Isa){
        $familyWarning = true;
    } else if(strcmp($dadsmumsmum[0]->Emo, $mumsmum[0]->Emo) == 0 && $dadsmumsmum[0]->Emo && $mumsmum[0]->Emo){
        $familyWarning = true;
    } else if(strcmp($mumsdadsdad[0]->Isa, $dadsdad[0]->Isa) == 0 && $mumsdadsdad[0]->Isa && $dadsdad[0]->Isa){
        $familyWarning = true;
    } else if(strcmp($mumsdadsmum[0]->Emo, $dadsmum[0]->Emo) == 0 && $mumsdadsmum[0]->Emo && $dadsmum[0]->Emo){
        $familyWarning = true;
    } else if(strcmp($mumsmumsdad[0]->Isa, $dadsdad[0]->Isa) == 0 && $mumsmumsdad[0]->Isa && $dadsdad[0]->Isa){
        $familyWarning = true;
    } else if(strcmp($mumsmumsmum[0]->Emo, $dadsmum[0]->Emo) == 0 && $mumsmumsmum[0]->Emo && $dadsmum[0]->Emo){
        $familyWarning = true;
    }

    if($fatherdaughter){
        ?>
        <div>
            <h3 class='warning'> Vanhemmat ovat isä ja tytär. </h3>
        </div>
         <?php
    }
    if($motherson){
        ?>
        <div>
            <h3 class='warning'> Vanhemmat ovat emo ja poika. </h3>
        </div>
         <?php
    }
    if($siblings){
        ?>
        <div>
            <h3 class='warning'> Vanhemmilla on sama emo ja/tai isä. </h3>
        </div>
         <?php
    } 
    if($familyWarning){
        ?>
        <div>
            <h3 class='warning'> Toisen vanhemman vanhempi on toisen vanhemman isovanhempi. </h3>
        </div>
         <?php
    }


 }

 function siilit_check_family_ilnesses($id){

    global $wpdb;
    $wpdb->show_errors();

    //First generation of parents
    $parentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $id));

    $parents = [];
    $parents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $parentsids[0]->Emo, $parentsids[0]->Isa));

    //Second generation of parents
    $dadsparents = $mumsparents = $dadsparentsids = $mumsparentsids = [];
    foreach($parents as &$parent){
        if(substr($parent->Siilinro, -1) == 'U'){
            $dadsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        } else {
            $mumsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        }
    }
    unset($parent);

    $dadsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $dadsparentsids[0]->Emo, $dadsparentsids[0]->Isa));
    $mumsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $mumsparentsids[0]->Emo, $mumsparentsids[0]->Isa));

    //Third generation of parents
    $dadsdadsparents = $dadsmumsparents = $mumsdadsparents = $mumsmumsparents = $dadsdadsparentsids = $mumsmumsparentsids = $mumsdadsparentsids = $dadsmumsparentsids = [];
    foreach($dadsparents as &$parent){
        if(substr($parent->Siilinro, -1) == 'U'){
            $dadsdadsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        } else {
            $dadsmumsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        }
    }
    unset($parent);
    foreach($mumsparents as &$parent){
        if(substr($parent->Siilinro, -1) == 'U'){
            $mumsdadsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        } else {
            $mumsmumsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        }
    }
    unset($parent);
    $dadsdadsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $dadsdadsparentsids[0]->Emo, $dadsdadsparentsids[0]->Isa));
    $dadsmumsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $dadsmumsparentsids[0]->Emo, $dadsmumsparentsids[0]->Isa));
    $mumsdadsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $mumsdadsparentsids[0]->Emo, $mumsdadsparentsids[0]->Isa));
    $mumsmumsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $mumsmumsparentsids[0]->Emo, $mumsmumsparentsids[0]->Isa));

    //Fourth generation of parents
    $dadsdadsdadsparents = $dadsdadsmumsparents = $dadsmumsdadsparents = $dadsmumsmumsparents = $mumsdadsdadsparents = $mumsdadsmumsparents = $mumsmumsdadsparents = $mumsmumsmumsparents = []; 
    $dadsdadsdadsparentsids = $dadsdadsmumsparentsids = $dadsmumsdadsparentsids = $dadsmumsmumsparentsids = $mumsdadsmumsparentsids = $mumsdadsdadsparentsids = $mumsmumsmumsparentsids = $mumsmumsdadsparentsids = [];

     foreach($dadsdadsparents as &$parent){
        if(substr($parent->Siilinro, -1) == 'U'){
            $dadsdadsdadsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        } else {
            $dadsdadsmumsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        }
    }
    unset($parent);
    foreach($dadsmumsparents as &$parent){
        if(substr($parent->Siilinro, -1) == 'U'){
            $dadsmumsdadsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        } else {
            $dadsmumsmumsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        }
    }
    unset($parent);
    foreach($mumsdadsparents as &$parent){
        if(substr($parent->Siilinro, -1) == 'U'){
            $mumsdadsdadsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        } else {
            $mumsdadsmumsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        }
    }
    unset($parent);
    foreach($mumsmumsparents as &$parent){
        if(substr($parent->Siilinro, -1) == 'U'){
            $mumsmumsdadsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        } else {
            $mumsmumsmumsparentsids = $wpdb->get_results($wpdb->prepare("SELECT Isa, Emo FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s'", $parent->Siilinro));
        }
    }
    unset($parent);
    $dadsdadsdadsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $dadsdadsdadsparentsids[0]->Emo, $dadsdadsdadsparentsids[0]->Isa));
    $dadsdadsmumsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $dadsmumsmumsparentsids[0]->Emo, $dadsmumsmumsparentsids[0]->Isa));
    $dadsmumsdadsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $dadsmumsdadsparentsids[0]->Emo, $dadsmumsdadsparentsids[0]->Isa));
    $dadsmumsmumsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $dadsmumsmumsparentsids[0]->Emo, $dadsmumsmumsparentsids[0]->Isa));
    $mumsdadsdadsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $mumsdadsdadsparentsids[0]->Emo, $mumsdadsdadsparentsids[0]->Isa));
    $mumsdadsmumsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $mumsdadsmumsparentsids[0]->Emo, $mumsdadsmumsparentsids[0]->Isa));
    $mumsmumsdadsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $mumsmumsdadsparentsids[0]->Emo, $mumsmumsdadsparentsids[0]->Isa));
    $mumsmumsmumsparents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}siilit WHERE Siilinro = '%s' OR Siilinro = '%s'", $mumsmumsmumsparentsids[0]->Emo, $mumsmumsmumsparentsids[0]->Isa));

    $family = array('parents'=> $parents, 'dadsparents' => $dadsparents, 'mumsparents' => $mumsparents, 'dadsdadsparents' => $dadsdadsparents, 'dadsmumsparents' => $dadsmumsparents, 'mumsdadsparents' => $mumsdadsparents, 'mumsmumsparents' => $mumsmumsparents, 'dadsdadsdadsparents' => $dadsdadsdadsparents, 'dadsdadsmumsparents' => $dadsdadsmumsparents, 'dadsmumsdadsparents' => $dadsmumsdadsparents, 'dadsmumsmumsparents' => $dadsmumsmumsparents, 'mumsdadsdadsparents' => $mumsdadsdadsparents, 'mumsdadsmumsparents' => $mumsdadsmumsparents, 'mumsmumsdadsparents' => $mumsmumsdadsparents, 'mumsmumsmumsparents' => $mumsmumsmumsparents);

    $familyIlnesses = [];
    foreach($family as &$parents){

        if(!empty($parents)){
            if(! empty($parents[0]->Kuolinsyy)){
                $familyIlnesses = array_merge($familyIlnesses, array($parents[0]->Nimi, $parents[0]->Kuolinsyy));
            }
            if(! empty($parents[1]->Kuolinsyy)){
                $familyIlnesses = array_merge($familyIlnesses, array($parents[1]->Nimi, $parents[1]->Kuolinsyy));
            }
        }
    }
    return $familyIlnesses;
 }

 function siilit_handle_failure(){
    ?>
    <div>
        <h2 id="result"> Jokin meni pieleen. Yritä uudelleen. </h2>
    </div>
     <?php
 }

 function siilit_handle_success($id){
    ?>
    <div>
        <h2 id="result"> Siili lisätty rekisteriin. ID: <?php echo $id ?> </h2>
    </div>
     <?php
 }

 function siilit_check_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

  function siilit_get_gender($data) {
      if($data == 'male'){
          return 'U';
      }else{
          return 'N';
      }
  }

  //create tables when the plugin is activated (if they don't already exist)
  function siilit_init_plugin(){
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $siilit_table = $wpdb->prefix . 'siilit';
    $siilit_breeders_table = $wpdb->prefix . 'siilit_kasvattajat';
    $siilit_mothers_table = $wpdb->prefix . 'siilit_emot';
    $siilit_per_year_table = $wpdb->prefix . 'siilit_vuosittain';

    $query = "CREATE TABLE $siilit_table (
                 `Siilinro` varchar(255) NOT NULL
                , `Nimi` varchar(255) DEFAULT NULL
                , `Kasvattaja` varchar(255) DEFAULT NULL
                , `Isa` varchar(255) DEFAULT NULL
                , `Emo` varchar(255) DEFAULT NULL
                , `Syntyma_aika` varchar(11) DEFAULT NULL
                , `Kuollut` bit(1) DEFAULT b'0'
                , `Kuolinsyy` varchar(255) DEFAULT NULL
                , `Omistaja` varchar(255) DEFAULT NULL
                , PRIMARY KEY (`Siilinro`)
    )";
    dbDelta($query);

    $query = "CREATE TABLE $siilit_breeders_table (
            `ID` int(11) NOT NULL AUTO_INCREMENT
            , `Nimi` varchar(255) NOT NULL
            , PRIMARY KEY (`ID`)
    )";
    dbDelta($query);

    $query = "CREATE TABLE $siilit_mothers_table (
            `Siilinro` varchar(255) NOT NULL
            , `PentueSynt` date NOT NULL
            , PRIMARY KEY (`Siilinro`)
    )";
    dbDelta($query);

    $query = "CREATE TABLE $siilit_per_year_table (
            `Vuosi` int(11) NOT NULL
            , `Siilit` int(11) NOT NULL
            , PRIMARY KEY (`Vuosi`)
    )";
    dbDelta($query);
}
 ?>