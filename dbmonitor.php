<?php
$conf = array(
"mysql_table_a" => array("table" => "table_a", "fun" => "select count(*) as num from table_a", "threshold" => 1000, "emails" => array(array("mail"=>"sohu@sohu.com", "phone"=>"13333333333"))),
"mysql_table_b" => array("table" => "table_b", "fun" => "select count(*) as num from table_b", "threshold" => 1000, "emails" => array(array("mail"=>"sohu@sohu.com", "phone"=>"13333333333"))),
);
/*confdbmonitor*/

/*
 * PHP Database Monitor
 *
 * PHP Database Monitor is distributed under GPL 2
 * Copyright (C) 2014 lovefcaaa <https://github.com/lovefcaaa>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or any later version.
 */

/*
 * cli
 */
if (PHP_SAPI == 'cli'){
    echo " @Copyright: Copyright (c) 2014\n @author: lovefcaaa\n";

    $opts = array(
        "p:", // project name
    );
    $p = 'all';
    $options = getopt("", $opts);
    if(!empty($options["p"]) && array_key_exists($p, $conf)){
    	$p = $options["p"];
    }
	if($p != 'all'){
		$conf_tmp = $conf[$p];
		$conf = $conf_tmp;
	}
	//begin
    $db = new pdo('mysql:host=127.0.0.1;port=3306;dbname=root;charset=gbk', 'database', 'pass');
    foreach($conf as $k => $v){
		$res = $db->query($v['fun'])->fetchAll();
   		if(empty($res) || !isset($res[0]['num'])){
			echo $v['table'].' is NULL';
		}
		if($res[0]['num'] > $v['threshold']){
			//Call the police 
			alarm($k, $v['fun'], $v['threshold'], $res[0]['num'], $v['emails']);
		}
		sleep(3);
    }
}

/*
 * web
 */
else{
	systemAuth();
	if(!empty($_POST)){
		$conf_num = count($_POST['key']);
		$conf_arr = array();
		for($i = 0; $i < $conf_num; $i++){
			eval( '$emails_='.html_entity_decode($_POST['emails'][$i][0], ENT_QUOTES, 'UTF-8').';');
			$fun_ = html_entity_decode($_POST['fun'][$i][0], ENT_QUOTES);
			$conf_arr[$_POST['key'][$i][0]] = array(
				"table" => $_POST['table'][$i][0], 
				"fun" => $fun_, 
				"threshold" => intval($_POST['threshold'][$i][0]), 
				"emails" => $emails_, 
			);
		}
		wconf($conf_arr);
	}
	echo <<<EOF
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>Monitoring configuration database </title>
	<FORM accept-charset="UTF-8" action="" method="post" >
	<DIV style="font-size: 40px;">Monitoring configuration database</DIV>
EOF;
	$i = 0;
	foreach($conf as $k => $v):
	echo <<<EOF
    <DIV>
     <FIELDSET id="conf{$i}">
      <LEGEND>{$k}</LEGEND>
          <DIV><LABEL for="port">Project name: </LABEL><INPUT maxLength="1024" size="150" name="key[$i][]" value="{$k}" /></DIV>
          <DIV><LABEL for="port">The data table name: </LABEL><INPUT maxLength="1024" size="150" name="table[$i][]" value="{$v['table']}" /></DIV>
          <DIV><LABEL for="port">Monitoring statements: </LABEL><INPUT maxLength="1024" size="150" name="fun[$i][]" value="{$v['fun']}" /></DIV>
          <DIV><LABEL for="port">Threshold limit: </LABEL><INPUT maxLength="1024" size="150" name="threshold[$i][]" value="{$v['threshold']}" /></DIV>
          <DIV><LABEL for="port">Notify the array: </LABEL><INPUT maxLength="1024" size="150" name="emails[$i][]" value="
EOF;
    	   echo trim(var_export($v['emails'], TRUE));
           echo <<<EOF
           " /></DIV>
     </FIELDSET>
     <button onClick="javascript:alert('TODO');return false;">Adding new monitoring </button>
    </DIV>
EOF;
	$i ++;
	endforeach;
    echo <<<EOF
    <hr />
    <INPUT type="submit" name="submit" value="Save the configuration " />
    </FORM>
EOF;
}

/*
 * Call the police --- Change
 */
function alarm($app, $fun, $threshold, $count, $emails = array(array("mail"=>"sohu@sohu.com", "phone"=>"13333333333"))){
    ini_set("memory_limit", -1);
    $from = array("mail" => "sohu@sohu.com");
    $title = "[ $app ]alarm[" . date("Y-m-d H:i:s") . "]";
    $msg = "Project name: $app<br /> monitoring statements: $fun <br/> threshold limit: $threshold <br /> the current value: $count <br /> " ;
    $phone = '';
    $mailto = '';

    foreach($emails as $t){
    	if(isset($t["mail"])){
            $mailto .= $t["mail"] . ",";
        }
        if(isset($t["phone"])){
            $phone .= $t["phone"] . ",";
        }
    }

    if(!empty($phone)){
        //TODO Send a text message 
    }
    if (!empty($mailto)){
        //TODO Send a email
    }
}

/*
 * Auth  ---  Change
 */
function systemAuth($now_url = "#"){
    define('ADMIN_USERNAME','admin1'); 	// Admin Username
	define('ADMIN_PASSWORD','admin1');  // Admin Password
    if(!isset($_GET['u']) || !isset($_GET['p']) || $_GET['u'] != ADMIN_USERNAME || $_GET['p'] != ADMIN_PASSWORD) {
    	die("After login, please use this feature.<a href=$now_url>login</a>");
    }
}

/*
 * Write files 
 */
function wconf($conf = array()) {

    if($fp = fopen(__FILE__, 'r')) {
  		$buffer = "<?php\n";
  		$buffer .= "\$conf = " . var_export($conf, TRUE) . ";\n";
  		$begin = false;
        while(!feof($fp)) {
        	$line = fgets($fp);
        	if(!$begin && strpos($line, '/*confdbmonitor*/') !== false){
        		$begin = true;
        	}
        	if($begin){
        		$buffer .= $line;
        	}
    	}
    	fclose($fp); 
        if(!file_put_contents(__FILE__, $buffer, LOCK_EX)) {
            echo '<script type="text/javascript">alert("Modify the failure! [ write failure ] will return to the previous page! ");history.go(-1);</script>';
        }else {
            echo '<script type="text/javascript">alert("Modify the success! Will return to the previous page! ");history.go(-1);</script>';
        }
    }else {
        echo '<script type="text/javascript">alert("Modify the failure! [ file permissions problems ]will return to the previous page! ");history.go(-1);</script>';
    }
    error_log("dbmonitor");
}
