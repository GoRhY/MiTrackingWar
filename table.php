<style>
table{
    width:100%;
    height:800px;
    display: table;
    table-layout: fixed;
    overflow-wrap: break-word;
	background-color: white;
}

.highlight-container, .highlight {
  position: relative;
}

.highlight-container {
  display: inline-block;
}
.highlight-container:before, .highlight-container:after {
  content: " ";
  display: block;
  height: 90%;
  width: 100%;
  margin-left: -3px;
  margin-right: -3px;
  position: absolute;
}
.highlight-container:before {
  background: rgba(255, 0, 0, 0.9);
  transform: rotate(2deg);
  top: -1px;
  left: -1px;
}
.highlight-container:after {
  background: rgba(255, 0, 0, 0.6);
  top: 3px;
  right: -2px;
}
.highlight-container .highlight {
  color: #333;
  z-index: 4;
}
</style>
<?php
date_default_timezone_set('Europe/Madrid');
include("names.php");
if (file_exists("names.json")){ 
    $array_names = json_decode(file_get_contents("names.json"),true);
}else{
    $array_names = $names;
}
$array_names = call_user_func_array('array_merge', $array_names);
foreach ($array_names as $new_name=>$new_twitter){
    $new_names[] = $new_name;
}
$division = ceil(count($names)/5);
echo '<table width="100%" align="center"><tr>';
$i = 0;
foreach ($names as $name){
    if (in_array(key($name),$new_names)){
        echo "<td>".key($name)."</td>"; 
    }else{
        echo "<td><span class=highlight-container><span class=highlight>".key($name)."</span></span></td>"; 
    }
    $i++; 
    if ($i % 5 == 0) { echo "</tr><tr>"; } 
} 
echo '</tr></table>';
?>